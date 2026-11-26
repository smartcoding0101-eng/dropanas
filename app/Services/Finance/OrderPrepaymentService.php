<?php

namespace App\Services\Finance;

use App\Models\FinancialSetting;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderPrepaymentService
{
    /**
     * Valida y ejecuta el prepago obligatorio de un pedido por parte del vendedor dropshipper.
     * Retiene: Costo de Producto + Flete de Courier + Comisión Plataforma Vendedor (5 Bs configurable).
     *
     * @throws RuntimeException Si el saldo disponible es insuficiente.
     */
    public function processPrepayment(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $freshOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            if ($freshOrder->prepaid_at) {
                return $freshOrder; // Ya fue prepagado
            }

            $settings = FinancialSetting::forTenant($freshOrder->tenant_id);
            $sellerFee = (float) $settings->seller_commission_bob;
            $providerFee = (float) $settings->provider_commission_bob;

            $productCost = (float) $freshOrder->cost_amount_bob;
            $shippingFee = (float) $freshOrder->shipping_fee_bob;
            $requiredTotal = round($productCost + $shippingFee + $sellerFee, 2);

            $wallet = Wallet::where('user_id', $freshOrder->dropshipper_id)
                ->where('tenant_id', $freshOrder->tenant_id)
                ->where('currency', 'BOB')
                ->lockForUpdate()
                ->first() ?? Wallet::create([
                    'tenant_id' => $freshOrder->tenant_id,
                    'user_id' => $freshOrder->dropshipper_id,
                    'currency' => 'BOB',
                    'balance_available' => 0.00,
                    'balance_pending' => 0.00,
                    'balance_withdrawn' => 0.00,
                ]);

            if ((float) $wallet->balance_available < $requiredTotal) {
                $deficit = round($requiredTotal - (float) $wallet->balance_available, 2);
                throw new RuntimeException(
                    "Saldo insuficiente en tu billetera Dropana para activar este pedido. " .
                    "Requieres: Bs. {$requiredTotal} (Costo: {$productCost} + Flete: {$shippingFee} + Comisión: {$sellerFee}). " .
                    "Saldo actual: Bs. {$wallet->balance_available}. Te faltan Bs. {$deficit}. " .
                    "Por favor solicita una recarga de saldo mediante QR o transferencia al Administrador."
                );
            }

            // 1. Debitar saldo del Vendedor
            $wallet->balance_available -= $requiredTotal;
            $wallet->save();

            // 2. Registrar transacción en el Ledger de la billetera
            WalletTransaction::create([
                'tenant_id'        => $freshOrder->tenant_id,
                'wallet_id'        => $wallet->id,
                'transaction_type' => 'order_prepayment',
                'amount'           => -$requiredTotal,
                'balance_after'    => $wallet->balance_available,
                'reference_type'   => 'order',
                'reference_id'     => $freshOrder->id,
                'description'      => "Prepago obligatorio pedido #{$freshOrder->order_number} (Costo: Bs. {$productCost} + Flete: Bs. {$shippingFee} + Fee Dropana: Bs. {$sellerFee})",
                'notes'            => 'Fondos en custodia hasta entrega del repartidor',
            ]);

            // 3. Registrar comisiones duales en el pedido
            $freshOrder->platform_fee_seller_bob = $sellerFee;
            $freshOrder->platform_fee_provider_bob = $providerFee;
            $freshOrder->platform_fee_bob = round($sellerFee + $providerFee, 2);
            $freshOrder->prepaid_at = now();

            // Calcular ganancia neta estimada del vendedor
            $sellingPrice = (float) $freshOrder->total_amount_bob;
            $freshOrder->dropshipper_profit_bob = max(0.00, round($sellingPrice - $productCost - $shippingFee - $sellerFee, 2));

            $freshOrder->save();

            return $freshOrder;
        });
    }

    /**
     * Calcula los fondos necesarios para que el vendedor pueda emitir el pedido.
     */
    public function calculateRequiredPrepayment(string $tenantId, float $productCost, float $shippingFee): array
    {
        $settings = FinancialSetting::forTenant($tenantId);
        $sellerFee = (float) $settings->seller_commission_bob;
        $total = round($productCost + $shippingFee + $sellerFee, 2);

        return [
            'product_cost_bob'   => $productCost,
            'shipping_fee_bob'   => $shippingFee,
            'seller_fee_bob'     => $sellerFee,
            'provider_fee_bob'   => (float) $settings->provider_commission_bob,
            'total_prepayment_bob'=> $total,
        ];
    }
}
