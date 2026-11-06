<?php

namespace App\Services\Inventory;

use App\Models\InventoryKardex;
use App\Models\Order;
use App\Models\Product;
use App\Models\WarehouseInventory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class KardexService
{
    /**
     * Registra el saldo de apertura inicial para un producto en bodega.
     */
    public function recordOpeningBalance(WarehouseInventory $inventory, string|int|null $userId = null, ?string $notes = null): InventoryKardex
    {
        $product = $inventory->product ?? Product::find($inventory->product_id);
        $unitCost = (float) ($product ? $product->cost_price_bob : 0);
        $qty = (int) $inventory->stock_physical;

        return InventoryKardex::create([
            'tenant_id'             => $inventory->tenant_id,
            'warehouse_id'          => $inventory->warehouse_id,
            'product_id'            => $inventory->product_id,
            'user_id'               => $userId,
            'movement_category'     => 'inbound',
            'movement_type'         => 'opening_balance',
            'reference_type'        => 'audit',
            'reference_id'          => $inventory->id,
            'document_number'       => 'INICIAL-' . ($inventory->warehouse->code ?? 'BOD') . '-' . substr($inventory->id, 0, 8),
            'quantity'              => $qty,
            'unit_cost_bob'         => $unitCost,
            'total_cost_bob'        => round($qty * $unitCost, 2),
            'stock_physical_before' => 0,
            'stock_physical_after'  => $qty,
            'stock_reserved_before' => 0,
            'stock_reserved_after'  => (int) $inventory->stock_reserved,
            'stock_available_before'=> 0,
            'stock_available_after' => (int) $inventory->stock_available,
            'condition'             => 'optimal',
            'notes'                 => $notes ?? 'Saldo de apertura inicial de inventario en bodega',
        ]);
    }

    /**
     * Registra INGRESO por Orden de Compra (Recepción física en bodega).
     */
    public function recordPurchaseReceipt(
        string $tenantId,
        string $warehouseId,
        string $productId,
        int $quantity,
        float $unitCost,
        string $poNumber,
        ?string $poId = null,
        string|int|null $userId = null,
        ?string $notes = null
    ): InventoryKardex {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("La cantidad recibida debe ser mayor a cero.");
        }

        return DB::transaction(function () use ($tenantId, $warehouseId, $productId, $quantity, $unitCost, $poNumber, $poId, $userId, $notes) {
            $inventory = WarehouseInventory::firstOrCreate(
                ['tenant_id' => $tenantId, 'warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['stock_physical' => 0, 'stock_reserved' => 0, 'stock_available' => 0]
            );

            $physBefore = (int) $inventory->stock_physical;
            $resvBefore = (int) $inventory->stock_reserved;
            $availBefore = (int) $inventory->stock_available;

            $inventory->stock_physical += $quantity;
            $inventory->stock_available += $quantity;
            $inventory->save();

            return InventoryKardex::create([
                'tenant_id'             => $tenantId,
                'warehouse_id'          => $warehouseId,
                'product_id'            => $productId,
                'user_id'               => $userId,
                'movement_category'     => 'inbound',
                'movement_type'         => 'purchase_receipt',
                'reference_type'        => 'purchase_order',
                'reference_id'          => $poId,
                'document_number'       => $poNumber,
                'quantity'              => $quantity,
                'unit_cost_bob'         => $unitCost,
                'total_cost_bob'        => round($quantity * $unitCost, 2),
                'stock_physical_before' => $physBefore,
                'stock_physical_after'  => $inventory->stock_physical,
                'stock_reserved_before' => $resvBefore,
                'stock_reserved_after'  => $inventory->stock_reserved,
                'stock_available_before'=> $availBefore,
                'stock_available_after' => $inventory->stock_available,
                'condition'             => 'optimal',
                'notes'                 => $notes ?? "Ingreso por recepción de mercadería PO #{$poNumber}",
            ]);
        });
    }

    /**
     * Registra RESERVA por nuevo pedido COD (Hold: disponible -> reservado).
     */
    public function recordOrderReservation(
        WarehouseInventory $inventory,
        Product $product,
        int $quantity,
        string $orderNumber,
        string $orderId,
        string|int|null $userId = null
    ): InventoryKardex {
        $unitCost = (float) $product->cost_price_bob;

        $physBefore = (int) $inventory->stock_physical;
        $resvBefore = (int) $inventory->stock_reserved - $quantity;
        $availBefore = (int) $inventory->stock_available + $quantity;

        return InventoryKardex::create([
            'tenant_id'             => $inventory->tenant_id,
            'warehouse_id'          => $inventory->warehouse_id,
            'product_id'            => $inventory->product_id,
            'user_id'               => $userId,
            'movement_category'     => 'hold',
            'movement_type'         => 'sale_reservation',
            'reference_type'        => 'order',
            'reference_id'          => $orderId,
            'document_number'       => $orderNumber,
            'quantity'              => -$quantity,
            'unit_cost_bob'         => $unitCost,
            'total_cost_bob'        => round($quantity * $unitCost, 2),
            'stock_physical_before' => $physBefore,
            'stock_physical_after'  => (int) $inventory->stock_physical,
            'stock_reserved_before' => $resvBefore,
            'stock_reserved_after'  => (int) $inventory->stock_reserved,
            'stock_available_before'=> $availBefore,
            'stock_available_after' => (int) $inventory->stock_available,
            'condition'             => 'optimal',
            'notes'                 => "Reserva de inventario por venta de pedido COD #{$orderNumber}",
        ]);
    }

    /**
     * Registra CANCELACIÓN de pedido antes de despacho (Release: reservado -> disponible).
     */
    public function recordOrderCancellation(
        WarehouseInventory $inventory,
        Product $product,
        int $quantity,
        string $orderNumber,
        string $orderId,
        string|int|null $userId = null,
        ?string $reason = null
    ): InventoryKardex {
        $unitCost = (float) $product->cost_price_bob;

        $physBefore = (int) $inventory->stock_physical;
        $resvBefore = (int) $inventory->stock_reserved + $quantity;
        $availBefore = (int) $inventory->stock_available - $quantity;

        return InventoryKardex::create([
            'tenant_id'             => $inventory->tenant_id,
            'warehouse_id'          => $inventory->warehouse_id,
            'product_id'            => $inventory->product_id,
            'user_id'               => $userId,
            'movement_category'     => 'release',
            'movement_type'         => 'order_cancellation',
            'reference_type'        => 'order',
            'reference_id'          => $orderId,
            'document_number'       => $orderNumber,
            'quantity'              => $quantity,
            'unit_cost_bob'         => $unitCost,
            'total_cost_bob'        => round($quantity * $unitCost, 2),
            'stock_physical_before' => $physBefore,
            'stock_physical_after'  => (int) $inventory->stock_physical,
            'stock_reserved_before' => $resvBefore,
            'stock_reserved_after'  => (int) $inventory->stock_reserved,
            'stock_available_before'=> $availBefore,
            'stock_available_after' => (int) $inventory->stock_available,
            'condition'             => 'optimal',
            'notes'                 => "Liberación de reserva por cancelación de pedido #{$orderNumber}" . ($reason ? " (Motivo: {$reason})" : ''),
        ]);
    }

    /**
     * Registra ANULACIÓN de pedido (solo antes de despacho físico).
     */
    public function recordOrderAnnulment(
        WarehouseInventory $inventory,
        Product $product,
        int $quantity,
        string $orderNumber,
        string $orderId,
        string|int|null $userId = null,
        ?string $reason = null
    ): InventoryKardex {
        $unitCost = (float) $product->cost_price_bob;

        $physBefore = (int) $inventory->stock_physical;
        $resvBefore = (int) $inventory->stock_reserved + $quantity;
        $availBefore = (int) $inventory->stock_available - $quantity;

        return InventoryKardex::create([
            'tenant_id'             => $inventory->tenant_id,
            'warehouse_id'          => $inventory->warehouse_id,
            'product_id'            => $inventory->product_id,
            'user_id'               => $userId,
            'movement_category'     => 'release',
            'movement_type'         => 'order_annulment',
            'reference_type'        => 'order',
            'reference_id'          => $orderId,
            'document_number'       => $orderNumber,
            'quantity'              => $quantity,
            'unit_cost_bob'         => $unitCost,
            'total_cost_bob'        => round($quantity * $unitCost, 2),
            'stock_physical_before' => $physBefore,
            'stock_physical_after'  => (int) $inventory->stock_physical,
            'stock_reserved_before' => $resvBefore,
            'stock_reserved_after'  => (int) $inventory->stock_reserved,
            'stock_available_before'=> $availBefore,
            'stock_available_after' => (int) $inventory->stock_available,
            'condition'             => 'optimal',
            'notes'                 => "Anulación de pedido por Administrador #{$orderNumber}" . ($reason ? " (Causa: {$reason})" : ''),
        ]);
    }

    /**
     * Registra SALIDA DEFINITIVA por Entrega Exitosa (Outbound físico y liberación de reserva).
     */
    public function recordOrderDispatchDelivered(
        WarehouseInventory $inventory,
        Product $product,
        int $quantity,
        string $orderNumber,
        string $orderId,
        string|int|null $userId = null
    ): InventoryKardex {
        $unitCost = (float) $product->cost_price_bob;

        $physBefore = (int) $inventory->stock_physical + $quantity;
        $resvBefore = (int) $inventory->stock_reserved + $quantity;
        $availBefore = (int) $inventory->stock_available;

        return InventoryKardex::create([
            'tenant_id'             => $inventory->tenant_id,
            'warehouse_id'          => $inventory->warehouse_id,
            'product_id'            => $inventory->product_id,
            'user_id'               => $userId,
            'movement_category'     => 'outbound',
            'movement_type'         => 'sale_dispatch',
            'reference_type'        => 'order',
            'reference_id'          => $orderId,
            'document_number'       => $orderNumber,
            'quantity'              => -$quantity,
            'unit_cost_bob'         => $unitCost,
            'total_cost_bob'        => round($quantity * $unitCost, 2),
            'stock_physical_before' => $physBefore,
            'stock_physical_after'  => (int) $inventory->stock_physical,
            'stock_reserved_before' => $resvBefore,
            'stock_reserved_after'  => (int) $inventory->stock_reserved,
            'stock_available_before'=> $availBefore,
            'stock_available_after' => (int) $inventory->stock_available,
            'condition'             => 'optimal',
            'notes'                 => "Salida definitiva de bodega por entrega exitosa y cobro COD #{$orderNumber}",
        ]);
    }

    /**
     * Registra DEVOLUCIÓN FÍSICA A BODEGA (Logística Inversa):
     * - Si condición es 'optimal': Reingresa a físico y disponible.
     * - Si condición es 'damaged': Se da de baja a merma (no entra a disponible).
     */
    public function recordOrderReturn(
        WarehouseInventory $inventory,
        Product $product,
        int $quantity,
        string $orderNumber,
        string $orderId,
        string $condition = 'optimal',
        string|int|null $userId = null,
        ?string $reason = null
    ): InventoryKardex {
        $unitCost = (float) $product->cost_price_bob;

        $physBefore = (int) $inventory->stock_physical;
        $resvBefore = (int) $inventory->stock_reserved;
        $availBefore = (int) $inventory->stock_available;

        if ($condition === 'optimal') {
            // Reintegro completo a inventario disponible
            $inventory->stock_physical += $quantity;
            $inventory->stock_available += $quantity;
            if ($inventory->stock_reserved >= $quantity) {
                $inventory->stock_reserved -= $quantity;
            }
            $inventory->save();

            $movType = 'return_restock';
            $cat = 'inbound';
            $notes = "Devolución confirmada apta para reventa del pedido #{$orderNumber}" . ($reason ? " (Novedad: {$reason})" : '');
        } else {
            // Producto dañado: se da de baja
            if ($inventory->stock_reserved >= $quantity) {
                $inventory->stock_reserved -= $quantity;
            }
            if ($inventory->stock_physical >= $quantity) {
                $inventory->stock_physical -= $quantity;
            }
            $inventory->save();

            $movType = 'return_damaged';
            $cat = 'outbound';
            $notes = "Baja de inventario por producto devuelto dañado/defectuoso pedido #{$orderNumber}" . ($reason ? " (Daño: {$reason})" : '');
        }

        return InventoryKardex::create([
            'tenant_id'             => $inventory->tenant_id,
            'warehouse_id'          => $inventory->warehouse_id,
            'product_id'            => $inventory->product_id,
            'user_id'               => $userId,
            'movement_category'     => $cat,
            'movement_type'         => $movType,
            'reference_type'        => 'order',
            'reference_id'          => $orderId,
            'document_number'       => $orderNumber,
            'quantity'              => $condition === 'optimal' ? $quantity : -$quantity,
            'unit_cost_bob'         => $unitCost,
            'total_cost_bob'        => round($quantity * $unitCost, 2),
            'stock_physical_before' => $physBefore,
            'stock_physical_after'  => (int) $inventory->stock_physical,
            'stock_reserved_before' => $resvBefore,
            'stock_reserved_after'  => (int) $inventory->stock_reserved,
            'stock_available_before'=> $availBefore,
            'stock_available_after' => (int) $inventory->stock_available,
            'condition'             => $condition,
            'notes'                 => $notes,
        ]);
    }

    /**
     * Registra TRANSFERENCIA entre bodegas (Origen y Destino).
     */
    public function recordStockTransfer(
        WarehouseInventory $sourceInventory,
        WarehouseInventory $targetInventory,
        Product $product,
        int $quantity,
        string $transferId,
        string|int|null $userId = null,
        ?string $notes = null
    ): array {
        $unitCost = (float) $product->cost_price_bob;
        $doc = 'TR-' . substr($transferId, 0, 8);

        // Movimiento 1: Salida en Origen
        $kardexSource = InventoryKardex::create([
            'tenant_id'             => $sourceInventory->tenant_id,
            'warehouse_id'          => $sourceInventory->warehouse_id,
            'product_id'            => $sourceInventory->product_id,
            'user_id'               => $userId,
            'movement_category'     => 'outbound',
            'movement_type'         => 'transfer_out',
            'reference_type'        => 'stock_transfer',
            'reference_id'          => $transferId,
            'document_number'       => $doc,
            'quantity'              => -$quantity,
            'unit_cost_bob'         => $unitCost,
            'total_cost_bob'        => round($quantity * $unitCost, 2),
            'stock_physical_before' => (int) $sourceInventory->stock_physical + $quantity,
            'stock_physical_after'  => (int) $sourceInventory->stock_physical,
            'stock_reserved_before' => (int) $sourceInventory->stock_reserved,
            'stock_reserved_after'  => (int) $sourceInventory->stock_reserved,
            'stock_available_before'=> (int) $sourceInventory->stock_available + $quantity,
            'stock_available_after' => (int) $sourceInventory->stock_available,
            'condition'             => 'optimal',
            'notes'                 => "Salida por transferencia interdepartamental hacia bodega " . ($targetInventory->warehouse->city ?? 'destino') . ". {$notes}",
        ]);

        // Movimiento 2: Entrada en Destino
        $kardexTarget = InventoryKardex::create([
            'tenant_id'             => $targetInventory->tenant_id,
            'warehouse_id'          => $targetInventory->warehouse_id,
            'product_id'            => $targetInventory->product_id,
            'user_id'               => $userId,
            'movement_category'     => 'inbound',
            'movement_type'         => 'transfer_in',
            'reference_type'        => 'stock_transfer',
            'reference_id'          => $transferId,
            'document_number'       => $doc,
            'quantity'              => $quantity,
            'unit_cost_bob'         => $unitCost,
            'total_cost_bob'        => round($quantity * $unitCost, 2),
            'stock_physical_before' => (int) $targetInventory->stock_physical - $quantity,
            'stock_physical_after'  => (int) $targetInventory->stock_physical,
            'stock_reserved_before' => (int) $targetInventory->stock_reserved,
            'stock_reserved_after'  => (int) $targetInventory->stock_reserved,
            'stock_available_before'=> (int) $targetInventory->stock_available - $quantity,
            'stock_available_after' => (int) $targetInventory->stock_available,
            'condition'             => 'optimal',
            'notes'                 => "Entrada por transferencia interdepartamental desde bodega " . ($sourceInventory->warehouse->city ?? 'origen') . ". {$notes}",
        ]);

        return [$kardexSource, $kardexTarget];
    }

    /**
     * Registra AJUSTE MANUAL exclusivo del Administrador (Auditoría física, conteo cíclico, sobrante o merma).
     */
    public function recordManualAdjustment(
        string $tenantId,
        string $warehouseId,
        string $productId,
        int $quantityDelta, // Positivo para sobrante, negativo para faltante/merma
        string $reason,
        string|int|null $adminUserId = null,
        string $condition = 'optimal'
    ): InventoryKardex {
        if ($quantityDelta === 0) {
            throw new InvalidArgumentException("El ajuste debe ser una cantidad diferente de cero.");
        }

        return DB::transaction(function () use ($tenantId, $warehouseId, $productId, $quantityDelta, $reason, $adminUserId, $condition) {
            $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            $product = Product::findOrFail($productId);
            $unitCost = (float) $product->cost_price_bob;

            $physBefore = (int) $inventory->stock_physical;
            $resvBefore = (int) $inventory->stock_reserved;
            $availBefore = (int) $inventory->stock_available;

            if ($quantityDelta < 0 && $inventory->stock_available < abs($quantityDelta)) {
                throw new RuntimeException("No se puede realizar un ajuste negativo superior al stock disponible ({$inventory->stock_available}).");
            }

            $inventory->stock_physical += $quantityDelta;
            $inventory->stock_available += $quantityDelta;
            $inventory->save();

            $isPositive = $quantityDelta > 0;
            $movType = $isPositive ? 'audit_adjustment_in' : 'audit_adjustment_out';
            $category = $isPositive ? 'inbound' : 'outbound';

            return InventoryKardex::create([
                'tenant_id'             => $tenantId,
                'warehouse_id'          => $warehouseId,
                'product_id'            => $productId,
                'user_id'               => $adminUserId,
                'movement_category'     => $category,
                'movement_type'         => $movType,
                'reference_type'        => 'audit',
                'reference_id'          => null,
                'document_number'       => 'AJUSTE-AUDIT-' . date('YmdHis'),
                'quantity'              => $quantityDelta,
                'unit_cost_bob'         => $unitCost,
                'total_cost_bob'        => round(abs($quantityDelta) * $unitCost, 2),
                'stock_physical_before' => $physBefore,
                'stock_physical_after'  => (int) $inventory->stock_physical,
                'stock_reserved_before' => $resvBefore,
                'stock_reserved_after'  => (int) $inventory->stock_reserved,
                'stock_available_before'=> $availBefore,
                'stock_available_after' => (int) $inventory->stock_available,
                'condition'             => $condition,
                'notes'                 => "Ajuste manual de auditoría por Administrador: {$reason}",
            ]);
        });
    }
}

