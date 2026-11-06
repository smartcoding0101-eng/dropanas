<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Logistics\ReverseLogisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnsManagementController extends Controller
{
    public function __construct(
        protected ReverseLogisticsService $reverseLogistics
    ) {}

    /**
     * Listado de novedades y devoluciones en tránsito o bodega.
     */
    public function index(Request $request): View
    {
        $status = $request->get('status', 'novedades'); // novedades, returned, in_transit
        
        $query = Order::with(['dropshipper', 'carrier', 'warehouse', 'items.product'])
            ->latest('updated_at');

        if ($status === 'novedades') {
            $query->where(function ($q) {
                $q->where('status', 'failed_delivery')
                  ->orWhereNotNull('novedad_reason');
            })->where('status', '!=', 'returned')
              ->where('status', '!=', 'delivered')
              ->where('status', '!=', 'settled');
        } elseif ($status === 'returned') {
            $query->where('status', 'returned');
        }

        $orders = $query->paginate(15);
        $reasons = ReverseLogisticsService::REASONS;

        return view('returns.index', compact('orders', 'status', 'reasons'));
    }

    /**
     * Registrar novedad / intento fallido.
     */
    public function recordNovedad(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'reason'           => 'required|string',
            'notes'            => 'nullable|string|max:500',
            'rescheduled_date' => 'nullable|date',
        ]);

        $order = Order::findOrFail($id);
        $this->reverseLogistics->recordNovedad(
            $order,
            $request->input('reason'),
            $request->input('notes'),
            $request->input('rescheduled_date')
        );

        return back()->with('success', "Novedad registrada exitosamente para la orden #{$order->order_number}.");
    }

    /**
     * Reprogramar entrega.
     */
    public function reschedule(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'rescheduled_date' => 'required|date',
            'notes'            => 'nullable|string|max:500',
        ]);

        $order = Order::findOrFail($id);
        $this->reverseLogistics->rescheduleDelivery(
            $order,
            $request->input('rescheduled_date'),
            $request->input('notes')
        );

        return back()->with('success', "Pedido #{$order->order_number} reprogramado para nueva entrega.");
    }

    /**
     * Confirmar devolución física a bodega y restock.
     */
    public function processReturn(Request $request, string $id): RedirectResponse
    {
        $order = Order::findOrFail($id);
        $deductFreight = $request->boolean('deduct_shipping_fee', true);
        $reason = $request->input('reason', $order->novedad_reason ?? 'Devolución confirmada');
        $condition = $request->input('condition', 'optimal');

        $this->reverseLogistics->processReturn($order, $deductFreight, $reason, $condition);

        $msg = $condition === 'damaged' 
            ? "Devolución registrada con producto dañado/merma para pedido #{$order->order_number}. Se dio de baja en Kardex."
            : "Devolución confirmada para pedido #{$order->order_number}. Stock reintegrado a bodega en Kardex.";

        return back()->with('success', $msg);
    }
}
