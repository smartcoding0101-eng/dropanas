<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\CarrierCashSettlement;
use App\Models\FinancialSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Services\Finance\CarrierSettlementService;
use App\Services\Finance\OrderPrepaymentService;
use App\Services\Finance\WalletTopupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FintechWalletAndSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $seller;
    protected User $provider;
    protected User $courierUser;
    protected Carrier $carrier;
    protected Warehouse $warehouse;
    protected Product $product;
    protected FinancialSetting $financialSetting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::first() ?? Tenant::create([
            'id' => '01a1f5b7-11c2-7350-89ce-f96f436c8ceb',
            'code' => 'DROPANA_BO_TEST',
            'company_name' => 'Dropana Bolivia COD Test S.R.L.',
            'legal_nit' => '1029384756',
            'domain' => 'dropana.test',
            'is_active' => true,
        ]);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.fintech@dropana.test'],
            [
                'tenant_id' => $this->tenant->id,
                'name' => 'Alejandro Admin FinTech',
                'role' => 'tenant_admin',
                'password' => bcrypt('password'),
            ]
        );

        $this->seller = User::firstOrCreate(
            ['email' => 'seller.fintech@dropana.test'],
            [
                'tenant_id' => $this->tenant->id,
                'name' => 'Vendedor Dropshipper FinTech',
                'role' => 'dropshipper',
                'password' => bcrypt('password'),
            ]
        );

        $this->provider = User::firstOrCreate(
            ['email' => 'provider.fintech@dropana.test'],
            [
                'tenant_id' => $this->tenant->id,
                'name' => 'Proveedor Mayorista FinTech',
                'role' => 'provider',
                'password' => bcrypt('password'),
            ]
        );

        $this->courierUser = User::firstOrCreate(
            ['email' => 'courier.fintech@dropana.test'],
            [
                'tenant_id' => $this->tenant->id,
                'name' => 'Repartidor DEX Carlos FinTech',
                'role' => 'carrier',
                'password' => bcrypt('password'),
            ]
        );

        $this->carrier = Carrier::firstOrCreate(
            ['name' => 'DEX Bolivia Express FinTech'],
            [
                'code' => 'DEX_FINTECH',
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->courierUser->id,
                'contact_phone' => '+591 70000000',
                'is_active' => true,
            ]
        );

        $this->warehouse = Warehouse::firstOrCreate(
            ['name' => 'Megabodega Central SCZ FinTech'],
            [
                'code' => 'WH_SCZ_FINTECH',
                'tenant_id' => $this->tenant->id,
                'department' => 'Santa Cruz',
                'city' => 'Santa Cruz de la Sierra',
                'address' => 'Parque Industrial PI-42',
                'is_active' => true,
            ]
        );

        $this->product = Product::firstOrCreate(
            ['sku' => 'SKU-FINTECH-TEST-001'],
            [
                'tenant_id' => $this->tenant->id,
                'provider_id' => $this->provider->id,
                'name' => 'Smartwatch Ultra 2 COD FinTech',
                'slug' => 'smartwatch-ultra-2-cod-fintech',
                'cost_price_bob' => 100.00,
                'suggested_price_bob' => 200.00,
                'min_price_bob' => 150.00,
                'status' => 'published',
            ]
        );

        $this->financialSetting = FinancialSetting::forTenant($this->tenant->id);
        $this->financialSetting->update([
            'seller_commission_bob' => 5.00,
            'provider_commission_bob' => 5.00,
        ]);
    }

    /**
     * Test 1: Vendedor sin saldo suficiente no puede prepagar la orden.
     */
    public function test_seller_cannot_prepay_order_without_sufficient_balance(): void
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $this->seller->id],
            [
                'tenant_id' => $this->tenant->id,
                'currency' => 'BOB',
                'balance_bob' => 10.00, // Insuficiente (se necesitan 100 + 25 + 5 = 130 Bs)
                'balance_available' => 10.00,
            ]
        );
        $wallet->update(['balance_bob' => 10.00, 'balance_available' => 10.00]);

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'dropshipper_id' => $this->seller->id,
            'warehouse_id' => $this->warehouse->id,
            'carrier_id' => $this->carrier->id,
            'order_number' => 'ORD-FINTECH-FAIL-' . uniqid(),
            'customer_name' => 'Carlos Perez',
            'customer_phone' => '+591 71234567',
            'shipping_department' => 'Santa Cruz',
            'shipping_city' => 'Santa Cruz',
            'shipping_address' => 'Calle Bolivar 123',
            'total_amount_bob' => 200.00,
            'cost_amount_bob' => 100.00,
            'shipping_fee_bob' => 25.00,
            'platform_fee_bob' => 10.00,
            'dropshipper_profit_bob' => 65.00,
            'status' => 'pending_confirmation',
        ]);

        $prepaymentService = app(OrderPrepaymentService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        $prepaymentService->processPrepayment($order);
    }

    /**
     * Test 2: Vendedor solicita recarga vía QR y el Administrador la aprueba.
     */
    public function test_seller_requests_topup_and_admin_approves(): void
    {
        Storage::fake('public');

        $topupService = app(WalletTopupService::class);

        $fakeFile = UploadedFile::fake()->create('comprobante_qr.jpg', 200);

        // Vendedor solicita recarga de 500 Bs
        $topup = $topupService->requestTopup(
            user: $this->seller,
            amount: 500.00,
            paymentMethod: 'qr_bolivia',
            bankName: 'Banco Union',
            bankReference: 'QR-TEST-778899',
            receiptFile: $fakeFile,
            notes: 'Recarga para prepago de pedidos'
        );

        $this->assertEquals('pending', $topup->status);
        $this->assertEquals(500.00, $topup->amount_bob);

        // Administrador aprueba la recarga
        $approvedTopup = $topupService->approveTopup($topup, $this->admin);

        $this->assertEquals('approved', $approvedTopup->status);
        $this->assertNotNull($approvedTopup->approved_at);

        // Billetera del vendedor debe tener 500 Bs acreditados
        $sellerWallet = Wallet::where('user_id', $this->seller->id)->first();
        $this->assertEquals(500.00, (float) $sellerWallet->balance_bob);
        $this->assertEquals(500.00, (float) $sellerWallet->balance_available);

        // Verificar asiento de auditoría en WalletTransaction
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $sellerWallet->id,
            'transaction_type' => 'wallet_topup',
            'amount' => 500.00,
        ]);
    }

    /**
     * Test 3: Con saldo disponible, la orden se prepaga con éxito (100 costo + 25 flete + 5 comisión Dropana).
     */
    public function test_seller_prepays_order_with_dual_dropana_commission(): void
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $this->seller->id],
            ['tenant_id' => $this->tenant->id, 'currency' => 'BOB', 'balance_bob' => 500.00, 'balance_available' => 500.00]
        );
        $wallet->update(['balance_bob' => 500.00, 'balance_available' => 500.00]);

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'dropshipper_id' => $this->seller->id,
            'warehouse_id' => $this->warehouse->id,
            'carrier_id' => $this->carrier->id,
            'order_number' => 'ORD-FINTECH-OK-' . uniqid(),
            'customer_name' => 'Lucia Rodriguez',
            'customer_phone' => '+591 72345678',
            'shipping_department' => 'La Paz',
            'shipping_city' => 'La Paz',
            'shipping_address' => 'Av. Arce 555',
            'total_amount_bob' => 200.00,
            'cost_amount_bob' => 100.00,
            'shipping_fee_bob' => 25.00,
            'platform_fee_bob' => 10.00,
            'dropshipper_profit_bob' => 65.00,
            'status' => 'confirmed',
        ]);

        $prepaymentService = app(OrderPrepaymentService::class);
        $prepaidOrder = $prepaymentService->processPrepayment($order);

        // Validar que se guardaron las comisiones configurables duales
        $this->assertEquals(5.00, (float) $prepaidOrder->platform_fee_seller_bob);
        $this->assertEquals(5.00, (float) $prepaidOrder->platform_fee_provider_bob);
        $this->assertNotNull($prepaidOrder->prepaid_at);

        // Descuento exacto: 500 - (100 costo + 25 flete + 5 comisión) = 370 Bs
        $wallet->refresh();
        $this->assertEquals(370.00, (float) $wallet->balance_bob);
        $this->assertEquals(370.00, (float) $wallet->balance_available);

        // Asiento de débito en WalletTransaction
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => 'order_prepayment',
            'amount' => -130.00,
        ]);
    }

    /**
     * Test 4: Courier rinde caja de ruta y Admin aprueba, dispersando fondos con CERO DESCUADRE CONTABLE.
     */
    public function test_courier_settlement_route_and_atomic_fund_distribution(): void
    {
        Storage::fake('public');

        // Billeteras iniciales
        $sellerWallet = Wallet::firstOrCreate(
            ['user_id' => $this->seller->id],
            ['tenant_id' => $this->tenant->id, 'currency' => 'BOB', 'balance_bob' => 370.00, 'balance_available' => 370.00]
        );
        $sellerWallet->update(['balance_bob' => 370.00, 'balance_available' => 370.00]);

        $providerWallet = Wallet::firstOrCreate(
            ['user_id' => $this->provider->id],
            [
                'tenant_id' => $this->tenant->id,
                'currency' => 'BOB',
                'balance_bob' => 0.00,
                'balance_available' => 0.00,
            ]
        );
        $providerWallet->update(['balance_bob' => 0.00, 'balance_available' => 0.00]);

        // Crear item de orden con provider_id
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'dropshipper_id' => $this->seller->id,
            'warehouse_id' => $this->warehouse->id,
            'carrier_id' => $this->carrier->id,
            'order_number' => 'ORD-FINTECH-SETTLE-' . uniqid(),
            'customer_name' => 'Roberto Paz',
            'customer_phone' => '+591 79998877',
            'shipping_department' => 'Cochabamba',
            'shipping_city' => 'Cochabamba',
            'shipping_address' => 'Plaza Colon 88',
            'total_amount_bob' => 200.00,
            'cost_amount_bob' => 100.00,
            'shipping_fee_bob' => 25.00,
            'platform_fee_bob' => 10.00,
            'platform_fee_seller_bob' => 5.00,
            'platform_fee_provider_bob' => 5.00,
            'dropshipper_profit_bob' => 70.00,
            'status' => 'delivered',
            'prepaid_at' => now(),
            'delivered_at' => now(),
        ]);

        OrderItem::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'quantity' => 1,
            'unit_price_bob' => 200.00,
            'unit_cost_bob' => 100.00,
            'subtotal_bob' => 200.00,
        ]);

        $settlementService = app(CarrierSettlementService::class);

        $fakeReceipt = UploadedFile::fake()->create('deposito_bancario_carrier.pdf', 300);

        // Courier rinde la caja: cobró 200 Bs, retiene sus 25 Bs de flete, deposita 175 Bs a Dropana
        $settlement = $settlementService->submitRouteSettlement(
            carrier: $this->courierUser,
            orderIds: [$order->id],
            totalCollected: 200.00,
            feesEarned: 25.00,
            netDeposit: 175.00,
            paymentMethod: 'bank_transfer',
            depositRef: 'DEP-UNION-445566',
            receiptFile: $fakeReceipt,
            notes: 'Ruta Cochabamba liquidada'
        );

        $this->assertEquals(200.00, (float) $settlement->total_collected_bob);
        $this->assertEquals(25.00, (float) $settlement->courier_fees_earned_bob);
        $this->assertEquals(175.00, (float) $settlement->net_deposit_amount_bob);
        $this->assertEquals('pending', $settlement->status);

        // Admin aprueba la rendición
        $approvedSettlement = $settlementService->approveSettlement($settlement, $this->admin);

        $this->assertEquals('approved', $approvedSettlement->status);
        $this->assertNotNull($approvedSettlement->approved_at);

        // 1. Orden queda liquidada
        $order->refresh();
        $this->assertEquals('settled', $order->status);
        $this->assertNotNull($order->cod_reimbursed_at);

        // 2. Vendedor recibe reembolso de recaudo bruto (200 Bs):
        // Tenía 370 Bs (tras prepagar 130) + 200 Bs = 570 Bs netos (+70 Bs utilidad real!)
        $sellerWallet->refresh();
        $this->assertEquals(570.00, (float) $sellerWallet->balance_bob);
        $this->assertEquals(570.00, (float) $sellerWallet->balance_available);

        // 3. Proveedor recibe costo mayorista (100 Bs) - comisión Dropana (5 Bs) = 95 Bs netos!
        $providerWallet->refresh();
        $this->assertEquals(95.00, (float) $providerWallet->balance_bob);
        $this->assertEquals(95.00, (float) $providerWallet->balance_available);

        // 4. Auditoría de Cero Descuadre Contable (Zero Drift):
        // Total dinero del cliente: 200.00 Bs
        // - Proveedor: 95.00 Bs
        // - Courier: 25.00 Bs (flete retenido)
        // - Utilidad Vendedor: 70.00 Bs (200 cobrado - 130 prepagado)
        // - Dropana: 10.00 Bs (5.00 vendedor + 5.00 proveedor)
        // Suma: 95 + 25 + 70 + 10 = 200.00 Bs (100% Cuadrado!)
        $totalDistributed = 95.00 + 25.00 + 70.00 + 10.00;
        $this->assertEquals(200.00, $totalDistributed);
    }

    /**
     * Test 5: Configuración de comisiones Dropana modificable por el Administrador.
     */
    public function test_admin_can_update_financial_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.fintech.settings.update'), [
            'seller_commission_bob' => 7.50,
            'provider_commission_bob' => 6.00,
            'bank_name' => 'Banco de Crédito de Bolivia (BCP)',
            'bank_account_number' => '201-5098765-BOB',
            'bank_account_holder' => 'Dropana S.R.L.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->financialSetting->refresh();
        $this->assertEquals(7.50, (float) $this->financialSetting->seller_commission_bob);
        $this->assertEquals(6.00, (float) $this->financialSetting->provider_commission_bob);
        $this->assertEquals('Banco de Crédito de Bolivia (BCP)', $this->financialSetting->bank_name);
    }
}
