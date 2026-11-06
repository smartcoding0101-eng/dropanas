<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Category;
use App\Models\InventoryKardex;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\Inventory\KardexService;
use App\Services\Logistics\ReverseLogisticsService;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryKardexTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $dropshipper;
    protected User $provider;
    protected User $carrierUser;
    protected Warehouse $warehouseSCZ;
    protected Warehouse $warehouseLPZ;
    protected Product $product;
    protected WarehouseInventory $inventorySCZ;
    protected KardexService $kardexService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);

        $this->tenant = Tenant::first();
        $this->admin = User::where('role', 'tenant_admin')->first();
        $this->dropshipper = User::where('role', 'dropshipper')->first();
        $this->provider = User::where('role', 'provider')->first();
        $this->carrierUser = User::where('role', 'carrier')->first();

        $this->warehouseSCZ = Warehouse::where('code', 'WH-SCZ-01')->first();
        $this->warehouseLPZ = Warehouse::where('code', 'WH-LPZ-01')->first();

        $this->product = Product::first();
        $this->inventorySCZ = WarehouseInventory::where('warehouse_id', $this->warehouseSCZ->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->kardexService = app(KardexService::class);
    }

    /**
     * Seguridad y RBAC: Solo tenant_admin puede ver y acceder al Kardex Global.
     */
    public function test_only_tenant_admin_can_access_kardex_panel_and_api(): void
    {
        // 1. Visitante sin autenticación redirige a login
        $this->get('/panel/admin/kardex')->assertRedirect('/login');

        // 2. Dropshipper bloqueado con 403
        $this->actingAs($this->dropshipper)
            ->get('/panel/admin/kardex')
            ->assertStatus(403);

        // 3. Proveedor bloqueado con 403
        $this->actingAs($this->provider)
            ->get('/panel/admin/kardex')
            ->assertStatus(403);

        // 4. Repartidor bloqueado con 403
        $this->actingAs($this->carrierUser)
            ->get('/panel/admin/kardex')
            ->assertStatus(403);

        // 5. API protegida para Dropshipper
        Sanctum::actingAs($this->dropshipper);
        $this->getJson('/api/v1/admin/kardex')
            ->assertStatus(403);

        // 6. Administrador autorizado (200 OK)
        $this->actingAs($this->admin)
            ->get('/panel/admin/kardex')
            ->assertStatus(200)
            ->assertSee('Kardex Global y Trazabilidad')
            ->assertSee('Saldo Inicial');

        // 7. API autorizada para Administrador
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/admin/kardex')
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['data']]);
    }

    /**
     * Flujo 1: Ingreso por Recepción de Orden de Compra (PO)
     */
    public function test_inbound_purchase_order_receipt_creates_kardex_entry(): void
    {
        $physBefore = (int) $this->inventorySCZ->stock_physical;
        $availBefore = (int) $this->inventorySCZ->stock_available;

        $kardex = $this->kardexService->recordPurchaseReceipt(
            $this->tenant->id,
            $this->warehouseSCZ->id,
            $this->product->id,
            25,
            (float) $this->product->cost_price_bob,
            'PO-TEST-9988',
            null,
            $this->admin->id,
            'Lote de prueba PO'
        );

        $this->inventorySCZ->refresh();

        $this->assertEquals($physBefore + 25, $this->inventorySCZ->stock_physical);
        $this->assertEquals($availBefore + 25, $this->inventorySCZ->stock_available);

        $this->assertEquals('inbound', $kardex->movement_category);
        $this->assertEquals('purchase_receipt', $kardex->movement_type);
        $this->assertEquals(25, $kardex->quantity);
        $this->assertEquals($physBefore, $kardex->stock_physical_before);
        $this->assertEquals($physBefore + 25, $kardex->stock_physical_after);
    }

    /**
     * Flujo 2 & 3: Creación de Pedido (Reserva) y Entrega Exitosa (Salida Despacho)
     */
    public function test_order_lifecycle_traceability_reservation_and_delivery(): void
    {
        Sanctum::actingAs($this->dropshipper);

        $availStart = (int) $this->inventorySCZ->stock_available;
        $resvStart = (int) $this->inventorySCZ->stock_reserved;
        $physStart = (int) $this->inventorySCZ->stock_physical;

        // 1. Crear Pedido COD
        $res = $this->postJson('/api/v1/orders', [
            'warehouse_id'        => $this->warehouseSCZ->id,
            'carrier_code'        => 'dex_bolivia',
            'customer_name'       => 'Juan Perez Cochabamba',
            'customer_phone'      => '+591 71234567',
            'shipping_department' => 'Santa Cruz',
            'shipping_city'       => 'Santa Cruz de la Sierra',
            'shipping_address'    => 'Calle Sucre #100',
            'payment_method'      => 'cash_on_delivery_bob',
            'items' => [
                [
                    'product_id'        => $this->product->id,
                    'quantity'          => 2,
                    'selling_price_bob' => (float) $this->product->suggested_price_bob,
                ]
            ]
        ]);
        $res->assertStatus(201);
        $orderId = $res->json('data.order.id');
        $orderNumber = $res->json('data.order.order_number');

        $this->inventorySCZ->refresh();
        $this->assertEquals($availStart - 2, $this->inventorySCZ->stock_available);
        $this->assertEquals($resvStart + 2, $this->inventorySCZ->stock_reserved);
        $this->assertEquals($physStart, $this->inventorySCZ->stock_physical);

        // Verificar que se registró en Kardex la Reserva
        $kardexResv = InventoryKardex::withoutGlobalScopes()
            ->where('reference_id', $orderId)
            ->where('movement_type', 'sale_reservation')
            ->first();

        $this->assertNotNull($kardexResv);
        $this->assertEquals(-2, $kardexResv->quantity);
        $this->assertEquals($availStart, $kardexResv->stock_available_before);
        $this->assertEquals($availStart - 2, $kardexResv->stock_available_after);

        // 2. Transición a confirmed -> dispatched -> delivered
        Sanctum::actingAs($this->admin);
        $this->putJson("/api/v1/orders/{$orderId}/status", ['status' => 'confirmed'])->assertStatus(200);
        $this->putJson("/api/v1/orders/{$orderId}/status", ['status' => 'dispatched', 'tracking_number' => 'DEX-TRK-001'])->assertStatus(200);
        $this->putJson("/api/v1/orders/{$orderId}/status", ['status' => 'delivered'])->assertStatus(200);

        $this->inventorySCZ->refresh();
        // Al entregarse, se libera la reserva (-2) y se descuenta el físico (-2)
        $this->assertEquals($physStart - 2, $this->inventorySCZ->stock_physical);
        $this->assertEquals($resvStart, $this->inventorySCZ->stock_reserved);
        $this->assertEquals($availStart - 2, $this->inventorySCZ->stock_available);

        // Verificar registro de Salida Definitiva en Kardex
        $kardexDispatch = InventoryKardex::withoutGlobalScopes()
            ->where('reference_id', $orderId)
            ->where('movement_type', 'sale_dispatch')
            ->first();

        $this->assertNotNull($kardexDispatch);
        $this->assertEquals(-2, $kardexDispatch->quantity);
        $this->assertEquals('outbound', $kardexDispatch->movement_category);
    }

    /**
     * Flujo 4: Cancelación y Anulación antes de Despacho (Liberación de Reserva)
     */
    public function test_order_cancellation_and_annulment_releases_stock_in_kardex(): void
    {
        Sanctum::actingAs($this->dropshipper);

        // Crear pedido para cancelar
        $res1 = $this->postJson('/api/v1/orders', [
            'warehouse_id'        => $this->warehouseSCZ->id,
            'carrier_code'        => 'dex_bolivia',
            'customer_name'       => 'Carlos Mesa LPZ',
            'customer_phone'      => '+591 79876543',
            'shipping_department' => 'Santa Cruz',
            'shipping_city'       => 'Santa Cruz de la Sierra',
            'shipping_address'    => 'Av. Cañoto #500',
            'payment_method'      => 'cash_on_delivery_bob',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 3, 'selling_price_bob' => (float) $this->product->suggested_price_bob]
            ]
        ]);
        $order1Id = $res1->json('data.order.id');

        // Cancelar pedido
        Sanctum::actingAs($this->admin);
        $this->putJson("/api/v1/orders/{$order1Id}/status", [
            'status' => 'cancelled',
            'notes'  => 'Cliente solicitó cancelar por teléfono antes de empaque'
        ])->assertStatus(200);

        $kardexCancel = InventoryKardex::withoutGlobalScopes()
            ->where('reference_id', $order1Id)
            ->where('movement_type', 'order_cancellation')
            ->first();

        $this->assertNotNull($kardexCancel);
        $this->assertEquals(3, $kardexCancel->quantity);
        $this->assertEquals('release', $kardexCancel->movement_category);

        // Crear pedido para anular
        Sanctum::actingAs($this->dropshipper);
        $res2 = $this->postJson('/api/v1/orders', [
            'warehouse_id'        => $this->warehouseSCZ->id,
            'carrier_code'        => 'dex_bolivia',
            'customer_name'       => 'Prueba Anulacion',
            'customer_phone'      => '+591 78901234',
            'shipping_department' => 'Santa Cruz',
            'shipping_city'       => 'Santa Cruz de la Sierra',
            'shipping_address'    => 'Barrio Sirari #22',
            'payment_method'      => 'cash_on_delivery_bob',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'selling_price_bob' => (float) $this->product->suggested_price_bob]
            ]
        ]);
        $order2Id = $res2->json('data.order.id');

        // Anular pedido por Administrador
        Sanctum::actingAs($this->admin);
        $this->putJson("/api/v1/orders/{$order2Id}/status", [
            'status' => 'annulled',
            'notes'  => 'Orden duplicada por error de conexión'
        ])->assertStatus(200);

        $kardexAnnul = InventoryKardex::withoutGlobalScopes()
            ->where('reference_id', $order2Id)
            ->where('movement_type', 'order_annulment')
            ->first();

        $this->assertNotNull($kardexAnnul);
        $this->assertEquals(1, $kardexAnnul->quantity);
    }

    /**
     * Flujo 5: Devolución Apta para Reventa vs Devolución con Producto Dañado (Merma)
     */
    public function test_return_optimal_vs_damaged_traceability(): void
    {
        Sanctum::actingAs($this->dropshipper);

        // 1. Pedido que retornará en estado Apto
        $resApto = $this->postJson('/api/v1/orders', [
            'warehouse_id'        => $this->warehouseSCZ->id,
            'carrier_code'        => 'dex_bolivia',
            'customer_name'       => 'Ana Maria Retorno Apto',
            'customer_phone'      => '+591 71112233',
            'shipping_department' => 'Santa Cruz',
            'shipping_city'       => 'Santa Cruz de la Sierra',
            'shipping_address'    => 'Calle España #44',
            'payment_method'      => 'cash_on_delivery_bob',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'selling_price_bob' => (float) $this->product->suggested_price_bob]
            ]
        ]);
        $orderApto = Order::find($resApto->json('data.order.id'));

        $this->inventorySCZ->refresh();
        $availBeforeApto = (int) $this->inventorySCZ->stock_available;

        // Procesar devolución como APTO (Reingreso a stock disponible)
        $reverseLogistics = app(ReverseLogisticsService::class);
        $reverseLogistics->processReturn($orderApto, true, 'Cliente ausente', 'optimal');

        $this->inventorySCZ->refresh();
        $this->assertEquals($availBeforeApto + 2, $this->inventorySCZ->stock_available);

        $kardexApto = InventoryKardex::withoutGlobalScopes()
            ->where('reference_id', $orderApto->id)
            ->where('movement_type', 'return_restock')
            ->first();

        $this->assertNotNull($kardexApto);
        $this->assertEquals('optimal', $kardexApto->condition);
        $this->assertEquals(2, $kardexApto->quantity);

        // 2. Pedido que retornará con producto DAÑADO (Merma / Baja)
        $resDanado = $this->postJson('/api/v1/orders', [
            'warehouse_id'        => $this->warehouseSCZ->id,
            'carrier_code'        => 'dex_bolivia',
            'customer_name'       => 'Pedro Dañado Transporte',
            'customer_phone'      => '+591 72223344',
            'shipping_department' => 'Santa Cruz',
            'shipping_city'       => 'Santa Cruz de la Sierra',
            'shipping_address'    => 'Av. Brasil #100',
            'payment_method'      => 'cash_on_delivery_bob',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'selling_price_bob' => (float) $this->product->suggested_price_bob]
            ]
        ]);
        $orderDanado = Order::find($resDanado->json('data.order.id'));

        $this->inventorySCZ->refresh();
        $availBeforeDanado = (int) $this->inventorySCZ->stock_available;
        $physBeforeDanado = (int) $this->inventorySCZ->stock_physical;

        // Procesar devolución como DAÑADO
        $reverseLogistics->processReturn($orderDanado, true, 'Empaque destruido por lluvia', 'damaged');

        $this->inventorySCZ->refresh();
        // NO debe aumentar el disponible, y el stock físico debe darse de baja (-1)
        $this->assertEquals($availBeforeDanado, $this->inventorySCZ->stock_available);
        $this->assertEquals($physBeforeDanado - 1, $this->inventorySCZ->stock_physical);

        $kardexDanado = InventoryKardex::withoutGlobalScopes()
            ->where('reference_id', $orderDanado->id)
            ->where('movement_type', 'return_damaged')
            ->first();

        $this->assertNotNull($kardexDanado);
        $this->assertEquals('damaged', $kardexDanado->condition);
        $this->assertEquals(-1, $kardexDanado->quantity);
        $this->assertEquals('outbound', $kardexDanado->movement_category);
    }

    /**
     * Flujo 6: Transferencia de Stock entre Bodegas (Doble registro en Kardex)
     */
    public function test_stock_transfer_creates_dual_kardex_records(): void
    {
        Sanctum::actingAs($this->admin);

        $res = $this->postJson('/api/v1/inventory/transfer', [
            'product_id'        => $this->product->id,
            'from_warehouse_id' => $this->warehouseSCZ->id,
            'to_warehouse_id'   => $this->warehouseLPZ->id,
            'quantity'          => 5,
            'notes'             => 'Envío de stock Santa Cruz a La Paz por alta demanda'
        ]);
        $res->assertStatus(201);
        $transferId = $res->json('data.id');

        // Verificar salida en Santa Cruz
        $kardexOut = InventoryKardex::withoutGlobalScopes()
            ->where('reference_id', $transferId)
            ->where('movement_type', 'transfer_out')
            ->first();

        $this->assertNotNull($kardexOut);
        $this->assertEquals($this->warehouseSCZ->id, $kardexOut->warehouse_id);
        $this->assertEquals(-5, $kardexOut->quantity);

        // Verificar entrada en La Paz
        $kardexIn = InventoryKardex::withoutGlobalScopes()
            ->where('reference_id', $transferId)
            ->where('movement_type', 'transfer_in')
            ->first();

        $this->assertNotNull($kardexIn);
        $this->assertEquals($this->warehouseLPZ->id, $kardexIn->warehouse_id);
        $this->assertEquals(5, $kardexIn->quantity);
    }

    /**
     * Flujo 7: Ajuste Manual de Auditoría y Exportación CSV
     */
    public function test_admin_manual_adjustment_and_csv_export(): void
    {
        // 1. Dropshipper no puede hacer ajuste manual
        $this->actingAs($this->dropshipper)
            ->post('/panel/admin/kardex/adjust', [
                'warehouse_id'   => $this->warehouseSCZ->id,
                'product_id'     => $this->product->id,
                'quantity_delta' => 10,
                'reason'         => 'Intento no autorizado',
                'condition'      => 'optimal',
            ])->assertStatus(403);

        // 2. Administrador realiza ajuste positivo (Sobrante por conteo físico)
        $physBefore = (int) $this->inventorySCZ->stock_physical;
        $this->actingAs($this->admin)
            ->post('/panel/admin/kardex/adjust', [
                'warehouse_id'   => $this->warehouseSCZ->id,
                'product_id'     => $this->product->id,
                'quantity_delta' => 4,
                'reason'         => 'Conteo físico anual pasillo 4B arroja 4 unidades sobrantes',
                'condition'      => 'optimal',
            ])->assertRedirect();

        $this->inventorySCZ->refresh();
        $this->assertEquals($physBefore + 4, $this->inventorySCZ->stock_physical);

        $kardexAdjust = InventoryKardex::withoutGlobalScopes()
            ->where('movement_type', 'audit_adjustment_in')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($kardexAdjust);
        $this->assertEquals(4, $kardexAdjust->quantity);
        $this->assertEquals('inbound', $kardexAdjust->movement_category);

        // 3. Exportación CSV por Administrador
        $resCsv = $this->actingAs($this->admin)
            ->get('/panel/admin/kardex/export');

        $resCsv->assertStatus(200);
        $resCsv->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}

