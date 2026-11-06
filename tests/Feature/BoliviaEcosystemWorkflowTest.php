<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Finance\CommissionEngineService;
use App\Services\Finance\SettlementConciliationService;
use App\Services\Logistics\Adapters\DexBoliviaAdapter;
use App\Services\Payments\PaymentManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoliviaEcosystemWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_cod_order_workflow_in_bolivia(): void
    {
        // 1. Sembrar el ecosistema inicial
        $this->seed(\Database\Seeders\BoliviaEcosystemSeeder::class);

        $tenant = Tenant::first();
        $provider = User::where('role', 'provider')->first();
        $dropshipper = User::where('role', 'dropshipper')->first();
        $product = Product::first();
        $warehouse = Warehouse::first();
        $carrier = Carrier::where('code', 'dex_bolivia')->first();

        // Asegurar que las billeteras comiencen en balance cero para comprobar acreditación neta
        \App\Models\Wallet::updateOrCreate(
            ['user_id' => $dropshipper->id, 'tenant_id' => $tenant->id],
            ['currency' => 'BOB', 'balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
        \App\Models\Wallet::updateOrCreate(
            ['user_id' => $provider->id, 'tenant_id' => $tenant->id],
            ['currency' => 'BOB', 'balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        // 2. Crear Pedido COD en Bolivia
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'dropshipper_id' => $dropshipper->id,
            'warehouse_id' => $warehouse->id,
            'carrier_id' => $carrier->id,
            'order_number' => 'ORD-TEST-BO-001',
            'customer_name' => 'Carlos Mendoza',
            'customer_phone' => '+591 78945612',
            'customer_document_id' => '4567891 SC',
            'shipping_department' => 'Santa Cruz',
            'shipping_city' => 'Santa Cruz de la Sierra',
            'shipping_address' => 'Av. San Martín, Calle 7 Oeste #45',
            'total_amount_bob' => 99.00, // Precio venta al cliente final
            'cost_amount_bob' => 38.00,  // Costo mayorista proveedor
            'shipping_fee_bob' => 18.00, // Tarifa DEX Santa Cruz
            'platform_fee_bob' => 0.00,
            'dropshipper_profit_bob' => 0.00,
            'status' => 'pending_confirmation',
            'payment_method' => 'cash_on_delivery_bob',
        ]);

        $item = OrderItem::create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'provider_id' => $provider->id,
            'quantity' => 1,
            'unit_cost_bob' => 38.00,
            'unit_price_bob' => 99.00,
            'subtotal_bob' => 99.00,
        ]);

        // Cargar relación de items
        $order->load('items.product');

        // 3. Probar Motor de Comisiones Variables
        $commissionEngine = app(CommissionEngineService::class);
        $platformFee = $commissionEngine->calculatePlatformFee($order);

        // En seeder la categoría tecnología tiene 4% = 99 * 0.04 = 3.96 BOB
        $this->assertGreaterThan(0, $platformFee);
        $order->platform_fee_bob = $platformFee;

        // Calcular ganancia neta del Dropshipper
        // Ganancia = Total (99) - Costo (38) - Flete (18) - Comisión (3.96) = 39.04 BOB
        $order->dropshipper_profit_bob = $order->total_amount_bob - $order->cost_amount_bob - $order->shipping_fee_bob - $order->platform_fee_bob;
        $order->save();

        $this->assertEquals(39.04, $order->dropshipper_profit_bob);

        // 4. Probar Adaptador de Pasarela de Pagos (QR Simple Bolivia)
        $paymentManager = app(PaymentManagerService::class);
        $qrPayload = $paymentManager->initiatePayment($order, 'qr_interoperable');

        $this->assertEquals('qr_interoperable', $qrPayload['provider']);
        $this->assertStringContainsString('https://api.qrserver.com', $qrPayload['qr_image_url']);

        // 5. Probar Adaptador Logístico DEX Bolivia
        $dexAdapter = app(DexBoliviaAdapter::class);
        $waybill = $dexAdapter->generateWaybill($order);

        $this->assertEquals('DEX-BO-ORD-TEST-BO-001', $waybill['waybill_number']);
        $this->assertEquals('99.00', $waybill['cod_collection_amount_bob']);

        // 6. Probar Conciliación Automática de Liquidación y Acreditación en Billeteras
        $settlementService = app(SettlementConciliationService::class);
        $settlementService->settleDeliveredOrder($order);

        $order->refresh();
        $this->assertEquals('settled', $order->status);

        // Verificar Billetera del Dropshipper
        $dropshipperWallet = \App\Models\Wallet::where('user_id', $dropshipper->id)->first();
        $this->assertNotNull($dropshipperWallet);
        $this->assertEquals(39.04, (float) $dropshipperWallet->balance_available);

        // Verificar Billetera del Proveedor (Costo del producto: 38.00 BOB)
        $providerWallet = \App\Models\Wallet::where('user_id', $provider->id)->first();
        $this->assertNotNull($providerWallet);
        $this->assertEquals(38.00, (float) $providerWallet->balance_available);
    }
}
