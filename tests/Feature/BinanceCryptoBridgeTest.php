<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Services\Currency\BcbExchangeRateService;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Suite de Pruebas del Puente Cripto (Binance Pay Gateway & Disbursement Bridge)
 */
class BinanceCryptoBridgeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $dropshipper;
    protected User $provider;
    protected Warehouse $warehouse;
    protected Carrier $carrier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);

        $this->tenant = Tenant::first();
        $this->dropshipper = User::where('role', 'dropshipper')->first();
        $this->provider = User::where('role', 'provider')->first();
        $this->warehouse = Warehouse::first();
        $this->carrier = Carrier::first();
        $this->product = Product::first();
    }

    /**
     * TEST 1: Creación de pedido con pago cripto Binance Pay y cálculo exacto a TCO BCB
     */
    public function test_create_order_with_binance_pay_calculates_correct_usdt(): void
    {
        Sanctum::actingAs($this->dropshipper);

        $bcbService = app(BcbExchangeRateService::class);
        $currentRate = $bcbService->getCurrentSellRate(); // 12.06 BOB

        $sellingPriceBob = 241.20; // Exactamente 20.00 USDT si la tasa es 12.06
        $expectedUsdt = round($sellingPriceBob / $currentRate, 2);

        $response = $this->postJson('/api/v1/orders', [
            'warehouse_id'        => $this->warehouse->id,
            'carrier_code'        => $this->carrier->code,
            'customer_name'       => 'Comprador Cripto Bolivia',
            'customer_phone'      => '+591 77788990',
            'customer_document_id'=> '6543210 LP',
            'shipping_department' => 'La Paz',
            'shipping_city'       => 'La Paz',
            'shipping_address'    => 'Calacoto Calle 15 #200',
            'payment_method'      => 'binance_pay',
            'items'               => [
                [
                    'product_id'        => $this->product->id,
                    'quantity'          => 1,
                    'selling_price_bob' => $sellingPriceBob,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $paymentData = $response->json('data.payment');

        $this->assertNotNull($paymentData);
        $this->assertEquals('binance_pay', $paymentData['provider']);
        $this->assertEquals('USDT', $paymentData['currency']);
        $this->assertEquals($expectedUsdt, $paymentData['order_amount_usdt']);
        $this->assertEquals($sellingPriceBob, $paymentData['base_amount_bob']);
        $this->assertStringContainsString('pay.binance.com', $paymentData['checkout_url']);
        $this->assertStringContainsString('binancepay%3A%2F%2Forder', $paymentData['qr_code_url']);
    }

    /**
     * TEST 2: Recepción de Webhook oficial de Binance Pay y confirmación instantánea del pedido
     */
    public function test_binance_pay_webhook_confirms_order_instantly(): void
    {
        // 1. Crear pedido pendiente
        $order = Order::create([
            'tenant_id'             => $this->tenant->id,
            'dropshipper_id'        => $this->dropshipper->id,
            'warehouse_id'          => $this->warehouse->id,
            'carrier_id'            => $this->carrier->id,
            'order_number'          => 'ORD-BINANCE-PAY-001',
            'customer_name'         => 'Cliente Webhook Binance',
            'customer_phone'        => '71234567',
            'shipping_department'   => 'Santa Cruz',
            'shipping_city'         => 'Santa Cruz',
            'shipping_address'      => 'Av. Cristo Redentor #100',
            'total_amount_bob'      => 361.80,
            'cost_amount_bob'       => 100.00,
            'shipping_fee_bob'      => 18.00,
            'platform_fee_bob'      => 10.00,
            'dropshipper_profit_bob'=> 233.80,
            'status'                => 'pending_confirmation',
            'payment_method'        => 'binance_pay',
        ]);

        // 2. Simular notificación webhook de Binance Pay
        $webhookPayload = [
            'bizType'   => 'PAY',
            'bizStatus' => 'PAY_SUCCESS',
            'data'      => json_encode([
                'merchantTradeNo' => 'ORD-BINANCE-PAY-001',
                'productType'     => 'B2C',
                'productName'     => 'Pedido Dropana #ORD-BINANCE-PAY-001',
                'totalFee'        => 30.00,
                'currency'        => 'USDT',
                'transactionId'   => 'BINANCE-TX-9988776655',
            ]),
        ];

        $response = $this->postJson('/api/v1/webhooks/binance-pay', $webhookPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'returnCode'    => 'SUCCESS',
            'returnMessage' => null,
        ]);

        // 3. Verificar que la orden pasó a confirmed
        $this->assertEquals('confirmed', $order->fresh()->status);
    }

    /**
     * TEST 3: Retiro de utilidades vía Binance Pay (Pay-out Puente Cripto)
     */
    public function test_withdrawal_to_binance_pay_uid(): void
    {
        $wallet = Wallet::updateOrCreate(
            ['user_id' => $this->dropshipper->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 603.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
        $wallet->update(['balance_available' => 603.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]);

        Sanctum::actingAs($this->dropshipper);

        // Solicitar retiro de 603 BOB (equivalente a 50 USDT a tasa 12.06) a Binance Pay ID
        $response = $this->postJson('/api/v1/wallet/withdraw', [
            'amount'          => 603.00,
            'payout_method'   => 'binance_pay',
            'account_details' => [
                'beneficiary_name' => 'Antony Trader',
                'document_id'      => '5432101 SC',
                'binance_pay_id'   => '849302194', // Binance Pay ID / UID
            ],
        ]);

        $response->assertStatus(201);

        $wallet->refresh();
        $this->assertEquals(0.00, (float) $wallet->balance_available);
        $this->assertEquals(603.00, (float) $wallet->balance_withdrawn);

        // Verificar registro de auditoría en el ledger
        $tx = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'withdrawal_request')
            ->latest()
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals(-603.00, (float) $tx->amount);
        $this->assertEquals('binance_pay', $tx->metadata['payout_method']);
        $this->assertEquals('849302194', $tx->metadata['details']['binance_pay_id']);
    }
}

