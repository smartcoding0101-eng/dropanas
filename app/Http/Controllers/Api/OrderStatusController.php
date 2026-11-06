<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Finance\SettlementConciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderStatusController extends Controller
{
    /**
     * Máquina de estados válidos para un pedido COD en Bolivia.
     * Cada estado define los roles que pueden realizar la transición.
     */
    private const VALID_TRANSITIONS = [
        'pending_confirmation' => [
            'next'  => ['confirmed', 'cancelled', 'annulled'],
            'roles' => ['tenant_admin', 'provider'],
        ],
        'confirmed' => [
            'next'  => ['dispatched', 'cancelled', 'annulled'],
            'roles' => ['tenant_admin', 'provider'],
        ],
        'dispatched' => [
            'next'  => ['delivered', 'failed_delivery'],
            'roles' => ['tenant_admin', 'provider', 'carrier'],
        ],
        'delivered' => [
            'next'  => ['settled'],
            'roles' => ['tenant_admin'],
        ],
        'failed_delivery' => [
            'next'  => ['confirmed', 'dispatched', 'cancelled'],
            'roles' => ['tenant_admin', 'provider', 'carrier'],
        ],
        'settled'   => ['next' => [], 'roles' => []],
        'cancelled' => ['next' => [], 'roles' => []],
        'annulled'  => ['next' => [], 'roles' => []],
    ];

    /**
     * Actualiza el estado de un pedido con lógica de negocio COD boliviana.
     * Al pasar a "delivered" dispara la conciliación automática de billeteras.
     */
    public function update(
        Request $request,
        string $id,
        SettlementConciliationService $settlementService
    ): JsonResponse {
        $user  = $request->user();
        $order = Order::with(['items.product', 'carrier', 'warehouse', 'dropshipper'])->findOrFail($id);

        $validated = $request->validate([
            'status'                   => 'required|string',
            'tracking_number'          => 'nullable|string|max:100',
            'notes'                    => 'nullable|string|max:500',
            'novedad_reason'           => 'nullable|string|max:60',
            'novedad_notes'            => 'nullable|string|max:500',
            'rescheduled_delivery_at'  => 'nullable|date',
        ]);

        $newStatus   = $validated['status'];
        $currentStatus = $order->status;

        // Validar que la transición sea válida
        $allowedTransitions = self::VALID_TRANSITIONS[$currentStatus] ?? ['next' => [], 'roles' => []];

        if (!in_array($newStatus, $allowedTransitions['next'])) {
            return response()->json([
                'status'  => 'error',
                'message' => "No se puede transicionar el pedido de '{$currentStatus}' a '{$newStatus}'.",
                'current_status' => $currentStatus,
                'allowed_next'   => $allowedTransitions['next'],
            ], 422);
        }

        // Validar que el rol del usuario tenga permiso para esta transición
        $allowedRoles = $allowedTransitions['roles'];
        if (!empty($allowedRoles) && !in_array($user->role, $allowedRoles)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Tu rol '{$user->role}' no tiene permiso para realizar esta transición.",
            ], 403);
        }

        // Los dropshippers solo pueden ver sus propios pedidos
        if ($user->isDropshipper() && $order->dropshipper_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Acceso denegado.'], 403);
        }

        return DB::transaction(function () use ($order, $newStatus, $validated, $settlementService): JsonResponse {
            $order->status = $newStatus;

            // Timestamps por estado
            if ($newStatus === 'dispatched') {
                $order->dispatched_at = now();
                if (!empty($validated['tracking_number'])) {
                    $order->tracking_number = $validated['tracking_number'];
                }
            }

            if ($newStatus === 'delivered') {
                $order->delivered_at = now();
                $order->novedad_reason = null;

                // Descontar inventario físico y liberar reserva + Trazabilidad Kardex
                $kardexService = app(\App\Services\Inventory\KardexService::class);
                foreach ($order->items as $item) {
                    $inv = \App\Models\WarehouseInventory::where('warehouse_id', $order->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();
                    if ($inv) {
                        if ($inv->stock_reserved >= $item->quantity) {
                            $inv->stock_reserved -= $item->quantity;
                        }
                        if ($inv->stock_physical >= $item->quantity) {
                            $inv->stock_physical -= $item->quantity;
                        }
                        $inv->save();

                        if ($item->product) {
                            $kardexService->recordOrderDispatchDelivered(
                                $inv,
                                $item->product,
                                (int) $item->quantity,
                                $order->order_number,
                                $order->id,
                                auth()->id()
                            );
                        }
                    }
                }
            }

            if ($newStatus === 'cancelled') {
                // Liberar stock reservado a disponible + Trazabilidad Kardex
                $kardexService = app(\App\Services\Inventory\KardexService::class);
                foreach ($order->items as $item) {
                    $inv = \App\Models\WarehouseInventory::where('warehouse_id', $order->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();
                    if ($inv) {
                        if ($inv->stock_reserved >= $item->quantity) {
                            $inv->stock_reserved -= $item->quantity;
                            $inv->stock_available += $item->quantity;
                            $inv->save();

                            if ($item->product) {
                                $kardexService->recordOrderCancellation(
                                    $inv,
                                    $item->product,
                                    (int) $item->quantity,
                                    $order->order_number,
                                    $order->id,
                                    auth()->id(),
                                    $validated['notes'] ?? 'Cancelación de pedido'
                                );
                            }
                        }
                    }
                }
            }

            if ($newStatus === 'annulled') {
                // Anulación de pedido antes de despacho + Trazabilidad Kardex
                $kardexService = app(\App\Services\Inventory\KardexService::class);
                foreach ($order->items as $item) {
                    $inv = \App\Models\WarehouseInventory::where('warehouse_id', $order->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();
                    if ($inv) {
                        if ($inv->stock_reserved >= $item->quantity) {
                            $inv->stock_reserved -= $item->quantity;
                            $inv->stock_available += $item->quantity;
                            $inv->save();

                            if ($item->product) {
                                $kardexService->recordOrderAnnulment(
                                    $inv,
                                    $item->product,
                                    (int) $item->quantity,
                                    $order->order_number,
                                    $order->id,
                                    auth()->id(),
                                    $validated['notes'] ?? 'Anulación por Administrador'
                                );
                            }
                        }
                    }
                }
            }

            if ($newStatus === 'failed_delivery') {
                $order->delivery_attempts = ($order->delivery_attempts ?? 0) + 1;
                $order->novedad_reason = $validated['novedad_reason'] ?? 'cliente_ausente';
                $order->novedad_notes = $validated['novedad_notes'] ?? null;
                if (!empty($validated['rescheduled_delivery_at'])) {
                    $order->rescheduled_delivery_at = $validated['rescheduled_delivery_at'];
                }
            }

            $order->save();

            // Liquidación automática al marcar como entregado
            // El COD se recaudó, procesar pagos a billeteras
            if ($newStatus === 'delivered') {
                $order->load('items');
                $settlementService->settleDeliveredOrder($order);
                $order->refresh();
            }

            return response()->json([
                'status'  => 'success',
                'message' => "Pedido actualizado a '{$newStatus}' exitosamente.",
                'data'    => [
                    'order'          => $order->load(['items.product', 'carrier', 'warehouse']),
                    'previous_status' => $newStatus === 'settled' ? 'delivered' : $newStatus,
                    'auto_settled'   => $newStatus === 'delivered',
                ],
            ]);
        });
    }
}
