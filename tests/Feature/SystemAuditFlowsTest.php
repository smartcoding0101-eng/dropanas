<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Category;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ShippingManifest;
use App\Models\StockTransfer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\Finance\BankSettlementService;
use App\Services\Finance\CommissionEngineService;
use App\Services\Finance\SettlementConciliationService;
use App\Services\Logistics\Adapters\DexBoliviaAdapter;
use App\Services\Logistics\ReverseLogisticsService;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Auditoría Integral de Flujos del Sistema Dropana Bolivia
 *
 * Módulos auditados:
 * 1. Aprovisionamiento y Compras (Purchase & Warehouse Inbound)
 * 2. Transferencia Interdepartamental de Inventario (Kardex / Warehouses)
 * 3. Ciclo de Ventas COD (Pricing, Reglas de Comisión, Deducción de Stock)
 * 4. Operaciones de Courier (Manifiesto, Recaudación COD, Rótulos, Despacho)
 * 5. Logística Inversa (Novedades, Reprogramación, Devolución y Penalización de Flete)
 * 6. Auditoría del Libro Mayor y Saldos (Ledger Integrity: Wallet vs Transactions)
 * 7. Liquidación Bancaria Masiva (Bank Settlement Batches)
 */
class SystemAuditFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $provider;
    protected User $dropshipper;
    protected User $carrierUser;
    protected Warehouse $warehouseSCZ;
    protected Warehouse $warehouseLPZ;
    protected Carrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);

        $this->tenant = Tenant::first();
        $this->admin = User::where('role', 'tenant_admin')->first();
        $this->provider = User::where('role', 'provider')->first();
        $this->dropshipper = User::where('role', 'dropshipper')->first();
        $this->carrierUser = User::where('role', 'carrier')->first() ?? $this->admin;

        $this->warehouseSCZ = Warehouse::where('code', 'WH-SCZ-01')->first() ?? Warehouse::first();
        $this->warehouseLPZ = Warehouse::where('code', 'WH-LPZ-01')->first() ?? Warehouse::skip(1)->first();
        $this->carrier = Carrier::where('code', 'dex_bolivia')->first() ?? Carrier::first();
    }

    /**
     * AUDITORÍA 1: Flujo de Aprovisionamiento/Compras del Proveedor hacia Bodega
     */
    public function test_audit_inbound_purchase_order_and_warehouse_stocking(): void
    {
        Sanctum::actingAs($this->provider);

        // 1. Crear producto nuevo del proveedor
        $category = Category::first();
        $resProduct = $this->postJson('/api/v1/products', [
            'name'                 => 'Smartwatch Ultra AMOLED Z9',
            'sku'                  => 'SMART-Z9-AUDIT',
            'category_id'          => $category->id,
            'cost_price_bob'       => 120.00,
            'suggested_price_bob'  => 230.00,
            'min_price_bob'        => 160.00,
            'is_cod_available'     => true,
            'initial_warehouse_id' => $this->warehouseSCZ->id,
            'initial_stock'        => 50,
        ]);
        $resProduct->assertStatus(201);
        $productId = $resProduct->json('data.id');

        // Verificar existencia y saldos iniciales en bodega Santa Cruz
        $inventorySCZ = WarehouseInventory::where('warehouse_id', $this->warehouseSCZ->id)
            ->where('product_id', $productId)
            ->first();

        $this->assertNotNull($inventorySCZ, 'El inventario de bodega SCZ debe existir');
        $this->assertEquals(50, $inventorySCZ->stock_physical);
        $this->assertEquals(0, $inventorySCZ->stock_reserved);
        $this->assertEquals(50, $inventorySCZ->stock_available);

        // 2. Registrar Orden de Compra (Purchase Order) para reabastecimiento en La Paz
        $po = PurchaseOrder::create([
            'tenant_id'    => $this->tenant->id,
            'provider_id'  => $this->provider->id,
            'warehouse_id' => $this->warehouseLPZ->id,
            'po_number'    => 'PO-2026-AUDIT-001',
            'status'       => 'received',
            'received_at'  => now(),
            'notes'        => 'Lote de importación recibido en Aduana Interior La Paz',
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id'        => $productId,
            'quantity_ordered'  => 30,
            'quantity_received' => 30,
            'unit_cost_bob'     => 120.00,
        ]);

        // Ingresar stock de la OC a la bodega de La Paz
        $inventoryLPZ = WarehouseInventory::create([
            'tenant_id'       => $this->tenant->id,
            'warehouse_id'    => $this->warehouseLPZ->id,
            'product_id'      => $productId,
            'stock_physical'  => 30,
            'stock_reserved'  => 0,
            'stock_available' => 30,
        ]);

        $product = Product::find($productId);
        $this->assertEquals(80, $product->total_available_stock, 'El stock global disponible debe ser 50 (SCZ) + 30 (LPZ) = 80');
    }

    /**
     * AUDITORÍA 2: Transferencia Interdepartamental entre Bodegas con Auditoría Atómica
     */
    public function test_audit_inter_warehouse_stock_transfer(): void
    {
        $product = Product::first();

        // Asegurar stock base en origen (Santa Cruz)
        $invSCZ = WarehouseInventory::firstOrCreate(
            ['warehouse_id' => $this->warehouseSCZ->id, 'product_id' => $product->id],
            ['tenant_id' => $this->tenant->id, 'stock_physical' => 100, 'stock_reserved' => 0, 'stock_available' => 100]
        );
        $invSCZ->update(['stock_physical' => 100, 'stock_reserved' => 0, 'stock_available' => 100]);

        $invLPZ = WarehouseInventory::firstOrCreate(
            ['warehouse_id' => $this->warehouseLPZ->id, 'product_id' => $product->id],
            ['tenant_id' => $this->tenant->id, 'stock_physical' => 10, 'stock_reserved' => 0, 'stock_available' => 10]
        );
        $invLPZ->update(['stock_physical' => 10, 'stock_reserved' => 0, 'stock_available' => 10]);

        Sanctum::actingAs($this->admin);

        // Transferir 25 unidades de SCZ a LPZ
        $res = $this->postJson('/api/v1/inventory/transfer', [
            'product_id'        => $product->id,
            'from_warehouse_id' => $this->warehouseSCZ->id,
            'to_warehouse_id'   => $this->warehouseLPZ->id,
            'quantity'          => 25,
            'notes'             => 'Rebalanceo logístico de alta demanda en La Paz',
        ]);
        $res->assertStatus(201);

        // Verificar saldos pos-transferencia
        $invSCZ->refresh();
        $invLPZ->refresh();

        $this->assertEquals(75, $invSCZ->stock_available, 'Origen debe quedar con 100 - 25 = 75');
        $this->assertEquals(35, $invLPZ->stock_available, 'Destino debe quedar con 10 + 25 = 35');

        // Verificar registro de auditoría de transferencia
        $transfer = StockTransfer::where('product_id', $product->id)->latest()->first();
        $this->assertNotNull($transfer);
        $this->assertEquals(25, $transfer->quantity);
        $this->assertEquals('completed', $transfer->status);
    }

    /**
     * AUDITORÍA 3: Ciclo Completo de Venta COD, Desglose Financiero y Deducción de Stock
     */
    public function test_audit_sales_order_financial_breakdown_and_stock_reservation(): void
    {
        $product = Product::first();
        $inventory = WarehouseInventory::where('warehouse_id', $this->warehouseSCZ->id)
            ->where('product_id', $product->id)
            ->first();

        $initialAvailable = $inventory->stock_available;
        $initialReserved  = $inventory->stock_reserved;

        Sanctum::actingAs($this->dropshipper);

        $qty = 2;
        $sellingPrice = 150.00; // Total = 300.00 BOB
        $costPrice = (float) $product->cost_price_bob; // ej: 38 o 40

        $resOrder = $this->postJson('/api/v1/orders', [
            'warehouse_id'        => $this->warehouseSCZ->id,
            'carrier_code'        => $this->carrier->code,
            'customer_name'       => 'Valeria Montaño',
            'customer_phone'      => '+591 71239874',
            'customer_document_id'=> '9876543 SC',
            'shipping_department' => 'Santa Cruz',
            'shipping_city'       => 'Santa Cruz de la Sierra',
            'shipping_address'    => 'Equipetrol Calle 8 Este #12',
            'shipping_reference'  => 'Condominio Bella Vista',
            'payment_method'      => 'cash_on_delivery_bob',
            'items'               => [
                [
                    'product_id'        => $product->id,
                    'quantity'          => $qty,
                    'selling_price_bob' => $sellingPrice,
                ],
            ],
        ]);

        $resOrder->assertStatus(201);
        $orderData = $resOrder->json('data.order');
        $orderId = $orderData['id'];

        // 1. Verificar deducción de inventario (stock_available baja, stock_reserved sube)
        $inventory->refresh();
        $this->assertEquals($initialAvailable - $qty, $inventory->stock_available, 'Stock disponible debe descotarse al reservar');
        $this->assertEquals($initialReserved + $qty,  $inventory->stock_reserved,  'Stock reservado debe incrementarse');

        // 2. Verificar consistencia financiera del pedido
        $order = Order::with('items')->find($orderId);
        $totalAmount   = (float) $order->total_amount_bob;
        $costAmount    = (float) $order->cost_amount_bob;
        $shippingFee   = (float) $order->shipping_fee_bob;
        $platformFee   = (float) $order->platform_fee_bob;
        $dropshipperProfit = (float) $order->dropshipper_profit_bob;

        $this->assertEquals(300.00, $totalAmount);
        $this->assertEquals($costPrice * $qty, $costAmount);
        $this->assertGreaterThan(0, $shippingFee);
        $this->assertGreaterThan(0, $platformFee);

        // Regla contable: Total COD = Costo + Flete + Comisión Plataforma + Ganancia Dropshipper
        $expectedSum = round($costAmount + $shippingFee + $platformFee + $dropshipperProfit, 2);
        $this->assertEquals($totalAmount, $expectedSum, 'La suma de componentes debe igualar exactamente el monto COD');

        // 3. Verificar Guía de Courier generada
        $this->assertNotNull($resOrder->json('data.waybill'));
        $this->assertStringContainsString('DEX-BO-', $resOrder->json('data.waybill.waybill_number'));
    }

    /**
     * AUDITORÍA 4: Operaciones de Courier (Manifiesto, Despacho, Entrega y Liquidación)
     */
    public function test_audit_courier_manifest_delivery_and_settlement_ledger(): void
    {
        $product = Product::first();
        $dropshipperWallet = Wallet::firstOrCreate(
            ['user_id' => $this->dropshipper->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
        $dropshipperWallet->update(['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]);

        $providerWallet = Wallet::firstOrCreate(
            ['user_id' => $this->provider->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
        $providerWallet->update(['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]);

        // Crear orden confirmada
        $order = Order::create([
            'tenant_id'             => $this->tenant->id,
            'dropshipper_id'        => $this->dropshipper->id,
            'warehouse_id'          => $this->warehouseSCZ->id,
            'carrier_id'            => $this->carrier->id,
            'order_number'          => 'ORD-AUDIT-COURIER-01',
            'customer_name'         => 'Fernando Paz',
            'customer_phone'        => '70098765',
            'shipping_department'   => 'Santa Cruz',
            'shipping_city'         => 'Santa Cruz de la Sierra',
            'shipping_address'      => 'Barrio Sirari Calle 3 #10',
            'total_amount_bob'      => 200.00,
            'cost_amount_bob'       => 80.00,
            'shipping_fee_bob'      => 18.00,
            'platform_fee_bob'      => 10.00,
            'dropshipper_profit_bob'=> 92.00,
            'status'                => 'confirmed',
            'payment_method'        => 'cash_on_delivery_bob',
        ]);

        $order->items()->create([
            'tenant_id'     => $this->tenant->id,
            'product_id'    => $product->id,
            'provider_id'   => $this->provider->id,
            'quantity'      => 1,
            'unit_cost_bob' => 80.00,
            'unit_price_bob'=> 200.00,
            'subtotal_bob'  => 200.00,
        ]);

        // 1. Agrupar en Manifiesto de Despacho
        Sanctum::actingAs($this->admin);
        $resManifest = $this->postJson('/api/v1/manifests', [
            'carrier_id' => $this->carrier->id,
            'order_ids'  => [$order->id],
        ]);
        $resManifest->assertStatus(201);
        $this->assertEquals('dispatched', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->shipping_manifest_id);

        // 2. Courier entrega en campo y recauda el efectivo COD
        Sanctum::actingAs($this->carrierUser);
        $resDeliver = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'delivered',
            'notes'  => 'Entregado en mano propia del titular previa firma y recepción de Bs 200.',
        ]);
        $resDeliver->assertStatus(200);

        $order->refresh();
        $this->assertEquals('settled', $order->status, 'Al marcar como delivered debe auto-liquidarse a settled');
        $this->assertNotNull($order->delivered_at);
        $this->assertNotNull($order->settled_at);

        // 3. Auditoría de Saldos en Billeteras
        $dropshipperWallet->refresh();
        $providerWallet->refresh();

        $this->assertEquals(92.00, (float) $dropshipperWallet->balance_available, 'Billetera del dropshipper debe recibir 92.00 BOB');
        $this->assertEquals(80.00, (float) $providerWallet->balance_available, 'Billetera del proveedor debe recibir 80.00 BOB');

        // 4. Auditoría del Libro Mayor de Transacciones (Ledger)
        $dsTransaction = WalletTransaction::where('wallet_id', $dropshipperWallet->id)
            ->where('reference_id', $order->id)
            ->first();
        $this->assertNotNull($dsTransaction, 'Debe registrarse transacción de ganancia');
        $this->assertEquals(92.00, (float) $dsTransaction->amount);
        $this->assertEquals('order_profit', $dsTransaction->transaction_type);

        $provTransaction = WalletTransaction::where('wallet_id', $providerWallet->id)
            ->where('reference_id', $order->id)
            ->first();
        $this->assertNotNull($provTransaction, 'Debe registrarse transacción de costo');
        $this->assertEquals(80.00, (float) $provTransaction->amount);
        $this->assertEquals('order_cost_payout', $provTransaction->transaction_type);
    }

    /**
     * AUDITORÍA 5: Logística Inversa (Novedades de Entrega, Reprogramación, Devolución y Flete)
     */
    public function test_audit_reverse_logistics_novedades_and_return_freight_penalty(): void
    {
        $product = Product::first();
        $inventory = WarehouseInventory::where('warehouse_id', $this->warehouseSCZ->id)
            ->where('product_id', $product->id)
            ->first();

        $stockBefore = $inventory->stock_available;

        // Billetera dropshipper con saldo disponible de 100 BOB
        $dropshipperWallet = Wallet::updateOrCreate(
            ['user_id' => $this->dropshipper->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 100.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        $order = Order::create([
            'tenant_id'             => $this->tenant->id,
            'dropshipper_id'        => $this->dropshipper->id,
            'warehouse_id'          => $this->warehouseSCZ->id,
            'carrier_id'            => $this->carrier->id,
            'order_number'          => 'ORD-AUDIT-RETURN-01',
            'customer_name'         => 'Marcela Quiroga',
            'customer_phone'        => '78912345',
            'shipping_department'   => 'Santa Cruz',
            'shipping_city'         => 'Santa Cruz de la Sierra',
            'shipping_address'      => 'Radial 10 #500',
            'total_amount_bob'      => 150.00,
            'cost_amount_bob'       => 60.00,
            'shipping_fee_bob'      => 20.00, // Flete a deducir en retorno
            'platform_fee_bob'      => 8.00,
            'dropshipper_profit_bob'=> 62.00,
            'status'                => 'dispatched',
            'delivery_attempts'     => 0,
        ]);

        $order->items()->create([
            'tenant_id'     => $this->tenant->id,
            'product_id'    => $product->id,
            'provider_id'   => $this->provider->id,
            'quantity'      => 2,
            'unit_cost_bob' => 30.00,
            'unit_price_bob'=> 75.00,
            'subtotal_bob'  => 150.00,
        ]);

        $reverseService = app(ReverseLogisticsService::class);

        // 1. Primer intento fallido: cliente ausente
        $order = $reverseService->recordNovedad($order, 'cliente_ausente', 'Puerta cerrada, no atienden timbre');
        $this->assertEquals(1, $order->delivery_attempts);
        $this->assertEquals('cliente_ausente', $order->novedad_reason);

        // 2. Reprogramación de fecha solicitada por cliente
        $order = $reverseService->rescheduleDelivery($order, now()->addDay()->toDateTimeString(), 'Cliente solicitó entrega en la tarde');
        $this->assertEquals('in_transit', $order->status);

        // 3. Segundo intento fallido: teléfono apagado
        $order = $reverseService->recordNovedad($order, 'telefono_apagado', 'Llamadas enviadas a buzón de voz');
        $this->assertEquals(2, $order->delivery_attempts);

        // 4. Tercer intento fallido: límite alcanzado -> pasa a failed_delivery
        $order = $reverseService->recordNovedad($order, 'rechazo_sin_dinero', 'Cliente indica que no tiene el dinero');
        $this->assertEquals(3, $order->delivery_attempts);
        $this->assertEquals('failed_delivery', $order->status);

        // 5. Procesar Devolución Definitiva (Return) a Bodega
        $order = $reverseService->processReturn($order, deductShippingFeeFromDropshipper: true);
        $this->assertEquals('returned', $order->status);

        // Verificar que el inventario se reintegró a bodega (+2 unidades)
        $inventory->refresh();
        $this->assertEquals($stockBefore + 2, $inventory->stock_available, 'El stock devuelto debe reintegrarse al inventario disponible');

        // Verificar deducción de flete al Dropshipper (100.00 - 20.00 = 80.00 BOB)
        $dropshipperWallet->refresh();
        $this->assertEquals(80.00, (float) $dropshipperWallet->balance_available);

        // Verificar registro contable de penalización en el libro mayor
        $penaltyTx = WalletTransaction::where('wallet_id', $dropshipperWallet->id)
            ->where('transaction_type', 'penalty_fee')
            ->where('reference_id', $order->id)
            ->first();

        $this->assertNotNull($penaltyTx);
        $this->assertEquals(-20.00, (float) $penaltyTx->amount);
    }

    /**
     * AUDITORÍA 6: Retiro de Billetera y Ciclo de Lotes Bancarios (Bank Settlement Batches)
     */
    public function test_audit_wallet_withdrawal_and_bank_settlement_batch(): void
    {
        $wallet = Wallet::updateOrCreate(
            ['user_id' => $this->dropshipper->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 500.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        Sanctum::actingAs($this->dropshipper);

        // 1. Solicitar 2 retiros bancarios
        $resWithdraw1 = $this->postJson('/api/v1/wallet/withdraw', [
            'amount'          => 200.00,
            'payout_method'   => 'bank_transfer',
            'account_details' => [
                'beneficiary_name' => 'Antony Dropshipper',
                'document_id'      => '5432101 SC',
                'bank_name'        => 'Banco Nacional de Bolivia (BNB)',
                'account_number'   => '250-1234567-01',
            ],
        ]);
        $resWithdraw1->assertStatus(201);

        $resWithdraw2 = $this->postJson('/api/v1/wallet/withdraw', [
            'amount'          => 150.00,
            'payout_method'   => 'bank_transfer',
            'account_details' => [
                'beneficiary_name' => 'Antony Dropshipper',
                'document_id'      => '5432101 SC',
                'bank_name'        => 'Banco Nacional de Bolivia (BNB)',
                'account_number'   => '250-1234567-01',
            ],
        ]);
        $resWithdraw2->assertStatus(201);

        $wallet->refresh();
        $this->assertEquals(150.00, (float) $wallet->balance_available, 'Disponible debe ser 500 - 350 = 150');
        $this->assertEquals(350.00, (float) $wallet->balance_withdrawn, 'Retirado acumulado debe ser 350');

        // 2. Admin agrupa los retiros pendientes en un lote bancario
        $settlementService = app(BankSettlementService::class);
        $batch = $settlementService->createBatch($this->tenant->id, 'BNB', $this->admin, 'Liquidación semanal BNB');

        $this->assertEquals(2, $batch->total_withdrawals_count);
        $this->assertEquals(350.00, (float) $batch->total_amount_bob);
        $this->assertEquals('pending', $batch->status);

        // 3. Pasar a En Proceso
        $batch = $settlementService->markAsProcessing($batch);
        $this->assertEquals('processing', $batch->status);

        // 4. Completar lote con número de transferencia bancaria
        $batch = $settlementService->completeBatch($batch, 'TRX-BNB-20261104-9842');
        $this->assertEquals('completed', $batch->status);
        $this->assertEquals('TRX-BNB-20261104-9842', $batch->bank_reference_number);
        $this->assertNotNull($batch->completed_at);

        // 5. Verificar que las transacciones quedaron vinculadas al lote completado
        $linkedTxCount = WalletTransaction::where('bank_settlement_batch_id', $batch->id)->count();
        $this->assertEquals(2, $linkedTxCount);
    }
}

