<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /** Listado de clientes del CRM con segmentación y búsqueda */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->has('segment')) {
            $query->where('customer_segment', $request->segment);
        }

        if ($request->has('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('document_id', 'like', "%{$s}%");
            });
        }

        $customers = $query->orderByDesc('total_spent_bob')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $customers,
        ]);
    }

    /** Obtener detalle de un cliente */
    public function show(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $customer,
        ]);
    }

    /** Sincronizar cliente desde una orden COD completada */
    public function syncFromOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        $customer = Customer::firstOrCreate(
            [
                'tenant_id' => $order->tenant_id,
                'phone'     => $order->customer_phone,
            ],
            [
                'full_name'       => $order->customer_name,
                'document_id'     => $order->customer_document_id ?? null,
                'department'      => $order->shipping_department,
                'city'            => $order->shipping_city,
                'default_address' => $order->shipping_address,
            ]
        );

        $customer->total_orders += 1;
        if ($order->status === 'settled' || $order->status === 'delivered') {
            $customer->completed_orders += 1;
            $customer->total_spent_bob += (float) $order->total_amount_bob;
        } elseif ($order->status === 'cancelled') {
            $customer->cancelled_orders += 1;
        }
        $customer->last_order_at = now();
        $customer->recalculateSegment();

        return response()->json([
            'status'  => 'success',
            'message' => 'Perfil del cliente actualizado en el CRM exitosamente.',
            'data'    => $customer,
        ], 200);
    }
}
