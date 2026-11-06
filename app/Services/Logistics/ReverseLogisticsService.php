<?php

namespace App\Services\Logistics;

use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WarehouseInventory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ReverseLogisticsService
{
    /**
     * Motivos estándar de novedades COD en Bolivia.
     */
    public const REASONS = [
        'cliente_ausente'       => 'Cliente no se encontraba en el domicilio',
        'telefono_apagado'      => 'Teléfono apagado o no contesta WhatsApp/Llamadas',
        'direccion_incompleta'  => 'Dirección incompleta o zona sin cobertura/inaccesible',
        'rechazo_sin_dinero'    => 'Cliente no cuenta con efectivo/dinero suficiente',
        'cliente_cancelo'       => 'Cliente indica que canceló el pedido / ya no lo desea',
        'producto_danado'       => 'Empaque o producto dañado durante el transporte',
        'reprogramado'          => 'Cliente solicitó entrega en otra fecha',
    ];

    /**
     * Registra una novedad o intento fallido de entrega por parte del transportista.
     * Incrementa el contador de intentos y actualiza el estado.
     */
    public function recordNovedad(Order $order, string $reason, ?string $notes = null, ?string $rescheduledDate = null): Order
    {
        if (!array_key_exists($reason, self::REASONS)) {
            throw new InvalidArgumentException("Motivo de novedad inválido: {$reason}");
        }

        return DB::transaction(function () use ($order, $reason, $notes, $rescheduledDate) {
            $order->delivery_attempts += 1;
            $order->novedad_reason = $reason;
            $order->novedad_notes = $notes;
            
            if ($rescheduledDate) {
                $order->rescheduled_delivery_at = $rescheduledDate;
            }

            // Si supera el límite de intentos (3 en Bolivia), pasa automáticamente a alerta de devolución
            if ($order->delivery_attempts >= 3 && $reason !== 'reprogramado') {
                $order->status = 'failed_delivery';
            }

            $order->save();
            return $order;
        });
    }

    /**
     * Reprograma un nuevo intento de entrega para una orden con novedad.
     */
    public function rescheduleDelivery(Order $order, string $dateTime, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($order, $dateTime, $notes) {
            $order->status = 'in_transit';
            $order->rescheduled_delivery_at = $dateTime;
            if ($notes) {
                $order->novedad_notes = ($order->novedad_notes ? $order->novedad_notes . "\n" : '') . "[Reprogramado]: {$notes}";
            }
            $order->save();
            return $order;
        });
    }

    /**
     * Procesa la DEVOLUCIÓN DEFINITIVA (Return):
     * 1. Cambia estado a 'returned'.
     * 2. Reintegra stock o da de baja por daño con registro en Kardex Global.
     * 3. Aplica deducción de flete al Dropshipper (si corresponde la penalización por retorno COD).
     */
    public function processReturn(
        Order $order,
        bool $deductShippingFeeFromDropshipper = true,
        ?string $reason = null,
        string $condition = 'optimal'
    ): Order {
        if ($order->status === 'returned') {
            return $order;
        }

        return DB::transaction(function () use ($order, $deductShippingFeeFromDropshipper, $reason, $condition) {
            // 1. Reintegrar stock a la bodega con trazabilidad en Kardex
            $this->restockItemsToWarehouse($order, $condition, $reason);

            // 2. Cobrar flete logístico al dropshipper si está configurado
            if ($deductShippingFeeFromDropshipper && (float)$order->shipping_fee_bob > 0) {
                $this->chargeReturnFreightToDropshipper($order, (float)$order->shipping_fee_bob, $reason);
            }

            // 3. Actualizar estado de orden
            $order->status = 'returned';
            if ($reason) {
                $order->novedad_reason = $reason;
            }
            $order->save();

            return $order;
        });
    }

    /**
     * Reintegra los ítems del pedido al inventario de bodega registrando en Kardex.
     */
    protected function restockItemsToWarehouse(Order $order, string $condition = 'optimal', ?string $reason = null): void
    {
        $items = $order->items ?? $order->items()->get();
        $kardexService = app(\App\Services\Inventory\KardexService::class);

        foreach ($items as $item) {
            $inventory = WarehouseInventory::where('warehouse_id', $order->warehouse_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($inventory && $item->product) {
                $kardexService->recordOrderReturn(
                    $inventory,
                    $item->product,
                    (int) $item->quantity,
                    $order->order_number,
                    $order->id,
                    $condition,
                    auth()->id(),
                    $reason
                );
            }
        }
    }

    /**
     * Aplica débito por flete no recuperado a la billetera del dropshipper.
     */
    protected function chargeReturnFreightToDropshipper(Order $order, float $fee, ?string $reason): void
    {
        $wallet = Wallet::firstOrCreate(
            ['tenant_id' => $order->tenant_id, 'user_id' => $order->dropshipper_id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        $wallet->balance_available -= $fee;
        $wallet->save();

        WalletTransaction::create([
            'tenant_id'        => $order->tenant_id,
            'wallet_id'        => $wallet->id,
            'transaction_type' => 'penalty_fee',
            'amount'           => -$fee,
            'balance_after'    => $wallet->balance_available,
            'reference_type'   => 'order',
            'reference_id'     => $order->id,
            'description'      => "Flete de devolución (Reverse Logistics) por pedido #{$order->order_number}" . ($reason ? " ({$reason})" : ''),
        ]);
    }
}
