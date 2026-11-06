<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Customer;
use App\Models\InventoryAlert;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvancedModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    // ==========================================
    // MÓDULO 1: INVENTARIO AVANZADO
    // ==========================================

    /** Test 1: Transferencia exitosa de stock entre bodegas */
    public function test_can_transfer_stock_between_warehouses(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $product = Product::first();
        $warehouses = Warehouse::take(2)->get();
        $sourceWarehouse = $warehouses[0];
        $targetWarehouse = $warehouses[1];

        // Asegurar stock inicial en origen
        $inventory = WarehouseInventory::updateOrCreate(
            ['warehouse_id' => $sourceWarehouse->id, 'product_id' => $product->id],
            ['tenant_id' => $admin->tenant_id, 'stock_physical' => 50, 'stock_available' => 50, 'stock_reserved' => 0]
        );

        // Destino debe haber aumentado en 15
        $initialTargetStock = WarehouseInventory::where('warehouse_id', $targetWarehouse->id)
            ->where('product_id', $product->id)
            ->value('stock_available') ?? 0;

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/inventory/transfer', [
            'product_id'        => $product->id,
            'from_warehouse_id' => $sourceWarehouse->id,
            'to_warehouse_id'   => $targetWarehouse->id,
            'quantity'          => 15,
            'notes'             => 'Traslado Santa Cruz -> La Paz por alta demanda',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        // Origen debe tener 35
        $this->assertEquals(35, $inventory->fresh()->stock_available);

        // Destino debe haber sumado 15
        $targetInv = WarehouseInventory::where('warehouse_id', $targetWarehouse->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertEquals($initialTargetStock + 15, $targetInv->stock_available);

        // Debe existir el registro de transferencia
        $this->assertDatabaseHas('stock_transfers', [
            'product_id' => $product->id,
            'quantity'   => 15,
            'status'     => 'completed',
        ]);
    }

    /** Test 2: Falla transferencia si el stock disponible es insuficiente */
    public function test_cannot_transfer_stock_when_insufficient(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $product = Product::first();
        $warehouses = Warehouse::take(2)->get();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/inventory/transfer', [
            'product_id'        => $product->id,
            'from_warehouse_id' => $warehouses[0]->id,
            'to_warehouse_id'   => $warehouses[1]->id,
            'quantity'          => 99999, // Exceso deliberado
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    /** Test 3: Configurar y listar alertas de stock mínimo */
    public function test_can_configure_and_retrieve_inventory_alerts(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $product = Product::first();
        $warehouse = Warehouse::first();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/inventory/alerts', [
            'product_id'          => $product->id,
            'warehouse_id'        => $warehouse->id,
            'min_stock_threshold' => 10,
            'reorder_quantity'    => 100,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.min_stock_threshold', 10);

        // Consultar alertas activas
        $listResponse = $this->getJson('/api/v1/inventory/alerts');
        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ==========================================
    // MÓDULO 2: REPORTES Y ANALYTICS
    // ==========================================

    /** Test 4: Dashboard analítico con métricas en BOB y tasa de entrega */
    public function test_can_fetch_analytics_dashboard_metrics(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'summary' => [
                        'total_orders',
                        'delivered_orders',
                        'cancelled_orders',
                        'delivery_success_rate',
                        'gross_volume_bob',
                        'settled_volume_bob',
                        'platform_earnings_bob',
                        'dropshipper_payout_bob',
                    ],
                    'currency',
                    'symbol',
                ],
            ]);

        $this->assertEquals('BOB', $response->json('data.currency'));
        $this->assertEquals('Bs', $response->json('data.symbol'));
    }

    /** Test 5: Ranking de desempeño por dropshipper */
    public function test_can_fetch_dropshippers_performance(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/analytics/dropshippers');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'total_orders',
                        'delivered_orders',
                        'success_rate',
                        'profit_earned_bob',
                    ],
                ],
            ]);
    }

    // ==========================================
    // MÓDULO 3: NOTIFICACIONES
    // ==========================================

    /** Test 6: Envío y auditoría de notificación por WhatsApp Bolivia */
    public function test_can_dispatch_and_log_whatsapp_notification(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/notifications/send', [
            'channel'    => 'whatsapp',
            'event_type' => 'order_dispatched',
            'recipient'  => '+59176543210',
            'subject'    => 'Tu pedido va en camino por DEX Bolivia',
            'body'       => 'Hola Carlos, tu paquete con guía DEX-BO-001 ha salido a reparto en Santa Cruz.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.channel', 'whatsapp')
            ->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('notification_logs', [
            'channel'    => 'whatsapp',
            'event_type' => 'order_dispatched',
            'recipient'  => '+59176543210',
        ]);
    }

    // ==========================================
    // MÓDULO 4: GESTIÓN DE CLIENTES (CRM)
    // ==========================================

    /** Test 7: Sincronización automática de cliente desde pedido COD y segmentación */
    public function test_can_sync_and_segment_customer_from_order(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $dropshipper = User::where('role', 'dropshipper')->first();
        $warehouse = Warehouse::first();
        $carrier = Carrier::first();

        // Crear una orden COD entregada
        $order = Order::create([
            'tenant_id'            => $admin->tenant_id,
            'dropshipper_id'       => $dropshipper->id,
            'warehouse_id'         => $warehouse->id,
            'carrier_id'           => $carrier->id,
            'order_number'         => 'CRM-TEST-001',
            'customer_name'        => 'Juan Perez Bolivia',
            'customer_phone'       => '77001122',
            'customer_document_id' => '8899001 SCZ',
            'shipping_department'  => 'Santa Cruz',
            'shipping_city'        => 'Santa Cruz de la Sierra',
            'shipping_address'     => 'Av. Cristo Redentor #400',
            'total_amount_bob'     => 1200.00, // Alto valor -> Debe ser clasificado como VIP
            'cost_amount_bob'      => 600.00,
            'shipping_fee_bob'     => 25.00,
            'platform_fee_bob'     => 60.00,
            'dropshipper_profit_bob'=> 515.00,
            'status'               => 'settled',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/customers/sync', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.customer_segment', 'vip')
            ->assertJsonPath('data.phone', '77001122');

        $this->assertDatabaseHas('customers', [
            'phone'            => '77001122',
            'customer_segment' => 'vip',
            'completed_orders' => 1,
        ]);

        // Verificar búsqueda en listado del CRM
        $crmList = $this->getJson('/api/v1/customers?segment=vip');
        $crmList->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }
}
