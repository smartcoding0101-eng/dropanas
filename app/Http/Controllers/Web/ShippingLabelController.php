<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Shipping\BarcodeGeneratorService;
use Illuminate\Http\Request;

class ShippingLabelController extends Controller
{
    /**
     * Muestra la etiqueta térmica individual de un pedido para impresión (Zebra 4x6 / A6).
     */
    public function show(string $id, BarcodeGeneratorService $barcodeService)
    {
        $order = Order::with(['items.product', 'carrier', 'warehouse', 'dropshipper'])->findOrFail($id);

        $barcodeText = $order->tracking_number ?: $order->order_number;
        $barcodeSvg = $barcodeService->generateSvg($barcodeText, 2, 55);

        return view('labels.thermal', compact('order', 'barcodeSvg', 'barcodeText'));
    }

    /**
     * Impresión masiva de etiquetas térmicas filtradas por lote, bodega o estado.
     */
    public function batch(Request $request, BarcodeGeneratorService $barcodeService)
    {
        $ids = $request->input('ids');
        $query = Order::with(['items.product', 'carrier', 'warehouse', 'dropshipper']);

        if (!empty($ids)) {
            $idArray = is_array($ids) ? $ids : explode(',', $ids);
            $query->whereIn('id', $idArray);
        } else {
            // Por defecto los pedidos confirmados o despachados de las últimas 48h
            $query->whereIn('status', ['confirmed', 'dispatched'])
                  ->latest()
                  ->limit(30);
        }

        $orders = $query->get();

        $labels = $orders->map(function ($order) use ($barcodeService) {
            $barcodeText = $order->tracking_number ?: $order->order_number;
            return [
                'order'        => $order,
                'barcodeSvg'   => $barcodeService->generateSvg($barcodeText, 2, 55),
                'barcodeText'  => $barcodeText,
            ];
        });

        return view('labels.batch', compact('labels'));
    }

    /**
     * Muestra la Nota de Entrega / Albarán oficial para firma y comprobación de entrega COD.
     */
    public function deliveryNote(string $id, BarcodeGeneratorService $barcodeService)
    {
        $order = Order::with(['items.product', 'carrier', 'warehouse', 'dropshipper'])->findOrFail($id);

        $barcodeText = $order->tracking_number ?: $order->order_number;
        $barcodeSvg = $barcodeService->generateSvg($barcodeText, 2, 45);

        return view('labels.delivery_note', compact('order', 'barcodeSvg', 'barcodeText'));
    }
}
