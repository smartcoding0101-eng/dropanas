<?php

namespace App\Services\Finance;

use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SettlementConciliationService
{
    /**
     * Concilia un pedido entregado y recaudo COD:
     * - Acredita costo al Proveedor
     * - Acredita ganancia neta al Dropshipper
     * - Retiene comisión de la plataforma y flete de transportadora
     */
    public function settleDeliveredOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $freshOrder = Order::where('id', $order->id)->lockForUpdate()->first();
            if (!$freshOrder || $freshOrder->status === 'settled') {
                return;
            }

            $dropshipperWallet = Wallet::where('user_id', $freshOrder->dropshipper_id)
                ->where('tenant_id', $freshOrder->tenant_id)
                ->where('currency', 'BOB')
                ->lockForUpdate()
                ->first() ?? $this->getOrCreateWallet($freshOrder->dropshipper_id, $freshOrder->tenant_id);

            // 1. Acreditar ganancia al Dropshipper
            $profit = (float) $freshOrder->dropshipper_profit_bob;
            if ($profit > 0) {
                $dropshipperWallet->balance_available += $profit;
                $dropshipperWallet->save();

                WalletTransaction::create([
                    'tenant_id' => $freshOrder->tenant_id,
                    'wallet_id' => $dropshipperWallet->id,
                    'transaction_type' => 'order_profit',
                    'amount' => $profit,
                    'balance_after' => $dropshipperWallet->balance_available,
                    'reference_type' => 'order',
                    'reference_id' => $freshOrder->id,
                    'description' => "Ganancia de dropshipping por pedido #{$freshOrder->order_number}",
                ]);
            }

            // 2. Acreditar a cada proveedor de los items
            foreach ($freshOrder->items as $item) {
                $providerWallet = Wallet::where('user_id', $item->provider_id)
                    ->where('tenant_id', $freshOrder->tenant_id)
                    ->where('currency', 'BOB')
                    ->lockForUpdate()
                    ->first() ?? $this->getOrCreateWallet($item->provider_id, $freshOrder->tenant_id);

                $costPayout = (float) $item->unit_cost_bob * $item->quantity;

                $providerWallet->balance_available += $costPayout;
                $providerWallet->save();

                WalletTransaction::create([
                    'tenant_id' => $freshOrder->tenant_id,
                    'wallet_id' => $providerWallet->id,
                    'transaction_type' => 'order_cost_payout',
                    'amount' => $costPayout,
                    'balance_after' => $providerWallet->balance_available,
                    'reference_type' => 'order',
                    'reference_id' => $freshOrder->id,
                    'description' => "Liquidación costo de producto por pedido #{$freshOrder->order_number}",
                ]);
            }

            // 3. Marcar pedido como liquidado
            $freshOrder->status = 'settled';
            $freshOrder->settled_at = now();
            $freshOrder->save();
        });
    }

    protected function getOrCreateWallet(string $userId, string $tenantId): Wallet
    {
        return Wallet::firstOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $userId, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
    }
}
