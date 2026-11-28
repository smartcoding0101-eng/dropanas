<?php

namespace App\Services\Finance;

use App\Models\CarrierCashSettlement;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CarrierSettlementService
{
    /**
     * El courier motorizado crea una rendición de caja con el lote de pedidos entregados y el comprobante de depósito a Dropana.
     */
    public function submitRouteSettlement(
        User|Carrier $carrier,
        array $orderIds,
        float $totalCollected,
        float $feesEarned,
        float $netDeposit,
        string $paymentMethod = 'bank_transfer',
        ?string $depositRef = null,
        ?UploadedFile $receiptFile = null,
        ?string $notes = null
    ): CarrierCashSettlement {
        if (empty($orderIds)) {
            throw new RuntimeException('Debes seleccionar al menos un pedido entregado para rendir caja.');
        }

        return DB::transaction(function () use ($carrier, $orderIds, $totalCollected, $feesEarned, $netDeposit, $paymentMethod, $depositRef, $receiptFile, $notes) {
            $tenantId = $carrier->tenant_id;

            // Verificar pedidos del tenant que estén en estado entregado y pendientes de rendir
            $orders = Order::whereIn('id', $orderIds)
                ->where('tenant_id', $tenantId)
                ->whereNull('carrier_cash_settlement_id')
                ->where('status', 'delivered')
                ->get();

            if ($orders->count() !== count($orderIds)) {
                throw new RuntimeException('Uno o más pedidos seleccionados no están disponibles para rendición o ya fueron rendidos.');
            }

            $receiptPath = null;
            if ($receiptFile) {
                $receiptPath = $receiptFile->store("carrier_settlements/{$carrier->tenant_id}", 'public');
            }

            $settlement = CarrierCashSettlement::create([
                'tenant_id'               => $carrier->tenant_id,
                'carrier_id'              => $carrier->id,
                'settlement_number'       => CarrierCashSettlement::generateSettlementNumber(),
                'total_collected_bob'     => $totalCollected,
                'courier_fees_earned_bob' => $feesEarned,
                'net_deposit_bob'         => $netDeposit,
                'payment_method'          => $paymentMethod,
                'deposit_reference'       => $depositRef,
                'receipt_path'            => $receiptPath,
                'status'                  => CarrierCashSettlement::STATUS_PENDING,
                'notes'                   => $notes,
            ]);

            // Vincular pedidos a esta rendición
            Order::whereIn('id', $orders->pluck('id'))->update([
                'carrier_cash_settlement_id' => $settlement->id,
            ]);

            return $settlement;
        });
    }

    /**
     * El Administrador aprueba la rendición del courier tras verificar el ingreso bancario en la cuenta de Dropana.
     * En este acto, se liberan y dispersan todos los fondos a las partes (Cámara de Compensación).
     */
    public function approveSettlement(CarrierCashSettlement $settlement, User $admin): CarrierCashSettlement
    {
        return DB::transaction(function () use ($settlement, $admin) {
            $freshSettlement = CarrierCashSettlement::where('id', $settlement->id)
                ->with('orders.items')
                ->lockForUpdate()
                ->firstOrFail();

            if ($freshSettlement->status !== CarrierCashSettlement::STATUS_PENDING) {
                throw new RuntimeException('Esta rendición ya fue procesada anteriormente.');
            }

            $tenantId = $freshSettlement->tenant_id;
            $retainedNetInStreet = ((float) $freshSettlement->courier_fees_earned_bob > 0 && (float) $freshSettlement->net_deposit_bob < (float) $freshSettlement->total_collected_bob);

            foreach ($freshSettlement->orders as $order) {
                if ($order->status === 'settled') {
                    continue;
                }

                // ==============================================================
                // 1. REEMBOLSO + GANANCIA AL VENDEDOR (DROPSHIPPER)
                // ==============================================================
                $sellerWallet = Wallet::where('tenant_id', $tenantId)
                    ->where('user_id', $order->dropshipper_id)
                    ->where('currency', 'BOB')
                    ->lockForUpdate()
                    ->first() ?? Wallet::create([
                        'tenant_id' => $tenantId,
                        'user_id' => $order->dropshipper_id,
                        'currency' => 'BOB',
                        'balance_available' => 0.00,
                        'balance_pending' => 0.00,
                        'balance_withdrawn' => 0.00,
                    ]);

                // El vendedor recibe el monto total recaudado del cliente (Recupera su prepago + su ganancia)
                $orderGross = (float) $order->total_amount_bob;
                $sellerWallet->balance_available += $orderGross;
                $sellerWallet->save();

                WalletTransaction::create([
                    'tenant_id'        => $tenantId,
                    'wallet_id'        => $sellerWallet->id,
                    'transaction_type' => 'order_cod_reimbursement',
                    'amount'           => $orderGross,
                    'balance_after'    => $sellerWallet->balance_available,
                    'reference_type'   => 'order',
                    'reference_id'     => $order->id,
                    'description'      => "Recaudo COD liquidado por rendición courier (#{$freshSettlement->settlement_number}) — Pedido #{$order->order_number}",
                    'notes'            => "Retorno de capital adelantado más ganancia neta",
                ]);

                // ==============================================================
                // 2. LIQUIDACIÓN AL PROVEEDOR MAYORISTA
                // ==============================================================
                foreach ($order->items as $item) {
                    $providerWallet = Wallet::where('tenant_id', $tenantId)
                        ->where('user_id', $item->provider_id)
                        ->where('currency', 'BOB')
                        ->lockForUpdate()
                        ->first() ?? Wallet::create([
                            'tenant_id' => $tenantId,
                            'user_id' => $item->provider_id,
                            'currency' => 'BOB',
                            'balance_available' => 0.00,
                            'balance_pending' => 0.00,
                            'balance_withdrawn' => 0.00,
                        ]);

                    $costPayout = (float) $item->unit_cost_bob * $item->quantity;
                    $providerFee = (float) ($order->platform_fee_provider_bob ?? 5.00);
                    $netProviderPayout = max(0.00, round($costPayout - $providerFee, 2));

                    $providerWallet->balance_available += $netProviderPayout;
                    $providerWallet->save();

                    WalletTransaction::create([
                        'tenant_id'        => $tenantId,
                        'wallet_id'        => $providerWallet->id,
                        'transaction_type' => 'order_cost_payout',
                        'amount'           => $netProviderPayout,
                        'balance_after'    => $providerWallet->balance_available,
                        'reference_type'   => 'order',
                        'reference_id'     => $order->id,
                        'description'      => "Liquidación costo de venta pedido #{$order->order_number} (Costo: Bs. {$costPayout} - Fee Dropana: Bs. {$providerFee})",
                        'notes'            => "Acreditado a cuenta de proveedor",
                    ]);
                }

                // ==============================================================
                // 3. LIQUIDACIÓN AL COURIER MOTORIZADO
                // ==============================================================
                $carrierWallet = Wallet::where('tenant_id', $tenantId)
                    ->where('user_id', $freshSettlement->carrier_id)
                    ->where('currency', 'BOB')
                    ->lockForUpdate()
                    ->first() ?? Wallet::create([
                        'tenant_id' => $tenantId,
                        'user_id' => $freshSettlement->carrier_id,
                        'currency' => 'BOB',
                        'balance_available' => 0.00,
                        'balance_pending' => 0.00,
                        'balance_withdrawn' => 0.00,
                    ]);

                $shippingFee = (float) $order->shipping_fee_bob;

                if ($retainedNetInStreet) {
                    // Si el chofer ya retuvo el flete en mano descontándolo del depósito, registramos el asiento contable de compensación (Zero Drift)
                    WalletTransaction::create([
                        'tenant_id'        => $tenantId,
                        'wallet_id'        => $carrierWallet->id,
                        'transaction_type' => 'carrier_fee_payout',
                        'amount'           => $shippingFee,
                        'balance_after'    => $carrierWallet->balance_available,
                        'reference_type'   => 'order',
                        'reference_id'     => $order->id,
                        'description'      => "Flete ganado pedido #{$order->order_number} (Compensado en mano en rendición #{$freshSettlement->settlement_number})",
                        'notes'            => "Retenido directamente del efectivo cobrado en calle",
                    ]);
                } else {
                    // Si el chofer depositó el 100% íntegro a Dropana, se le abona a su saldo disponible
                    $carrierWallet->balance_available += $shippingFee;
                    $carrierWallet->save();

                    WalletTransaction::create([
                        'tenant_id'        => $tenantId,
                        'wallet_id'        => $carrierWallet->id,
                        'transaction_type' => 'carrier_fee_payout',
                        'amount'           => $shippingFee,
                        'balance_after'    => $carrierWallet->balance_available,
                        'reference_type'   => 'order',
                        'reference_id'     => $order->id,
                        'description'      => "Abono flete por entrega exitosa pedido #{$order->order_number}",
                        'notes'            => "Acreditado a billetera virtual de courier",
                    ]);
                }

                // ==============================================================
                // 4. ACTUALIZACIÓN DEL ESTADO DEL PEDIDO
                // ==============================================================
                $order->status = 'settled';
                $order->settled_at = now();
                $order->cod_reimbursed_at = now();
                $order->save();
            }

            // Marcar rendición como aprobada
            $freshSettlement->update([
                'status'         => CarrierCashSettlement::STATUS_APPROVED,
                'approved_by_id' => $admin->id,
                'approved_at'    => now(),
            ]);

            return $freshSettlement;
        });
    }

    /**
     * Rechaza una rendición de courier si el depósito bancario no coincide o hubo un problema.
     */
    public function rejectSettlement(CarrierCashSettlement $settlement, User $admin, string $reason): CarrierCashSettlement
    {
        return DB::transaction(function () use ($settlement, $admin, $reason) {
            $freshSettlement = CarrierCashSettlement::where('id', $settlement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($freshSettlement->status !== CarrierCashSettlement::STATUS_PENDING) {
                throw new RuntimeException('Esta rendición ya fue procesada anteriormente.');
            }

            // Desvincular pedidos para que puedan corregirse o rendirse de nuevo
            Order::where('carrier_cash_settlement_id', $freshSettlement->id)->update([
                'carrier_cash_settlement_id' => null,
            ]);

            $freshSettlement->update([
                'status'         => CarrierCashSettlement::STATUS_REJECTED,
                'approved_by_id' => $admin->id,
                'notes'          => $reason,
            ]);

            return $freshSettlement;
        });
    }
}
