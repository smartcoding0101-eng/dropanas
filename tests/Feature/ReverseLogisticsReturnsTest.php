<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\Logistics\ReverseLogisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReverseLogisticsReturnsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $dropshipper;
    protected User $provider;
    protected Warehouse $warehouse;
    protected Product $product;
    protected WarehouseInventory $inventory;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'admin',
        ]);

        $this->dropshipper = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'dropshipper',
        ]);

        $this->provider = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'proveedor',
        ]);

        $this->warehouse = Warehouse::create([
            'tenant_id'  => $this->tenant->id,
            'code'       => 'BOD-SCZ-01',
            'name'       => 'Bodega Central Equipetrol',
            'department' => 'Santa Cruz',
            'city'       => 'Santa Cruz de la Sierra',
            'address'    => '4to Anillo y San Martín',
            'is_active'  => true,
        ]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Electrónica',
            'slug'      => 'electronica',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'tenant_id'           => $this->tenant->id,
            'provider_id'         => $this->provider->id,
            'category_id'         => $category->id,
            'name'                => 'Auriculares Bluetooth Pro',
            'slug'                => 'auriculares-bluetooth-pro',
            'sku'                 => 'AUR-PRO-01',
            'cost_price_bob'      => 80.00,
            'suggested_price_bob' => 150.00,
            'min_price_bob'       => 100.00,
            'status'              => 'published',
            'is_cod_available'    => true,
        ]);

        $this->inventory = WarehouseInventory::create([
            'tenant_id'       => $this->tenant->id,
            'warehouse_id'    => $this->warehouse->id,
            'product_id'      => $this->product->id,
            'stock_available' => 10,
            'stock_reserved'  => 0,
        ]);

        // Crear una orden despachada en tránsito
        $this->order = Order::create([
            'tenant_id'              => $this->tenant->id,
            'dropshipper_id'         => $this->dropshipper->id,
            'warehouse_id'           => $this->warehouse->id,
            'order_number'           => 'ORD-NOV-TEST-001',
            'tracking_number'        => 'TRK-BOL-999',
            'customer_name'          => 'Carlos Mamani',
            'customer_phone'         => '70012345',
            'shipping_department'    => 'La Paz',
            'shipping_city'          => 'La Paz',
            'shipping_address'       => 'Av. 16 de Julio El Prado',
            'total_amount_bob'       => 150.00,
            'cost_amount_bob'        => 80.00,
            'shipping_fee_bob'       => 25.00,
            'platform_fee_bob'       => 5.00,
            'dropshipper_profit_bob' => 40.00,
            'status'                 => 'in_transit',
            'delivery_attempts'      => 0,
            'payment_method'         => 'cash_on_delivery_bob',
        ]);

        OrderItem::create([
            'tenant_id'      => $this->tenant->id,
            'order_id'       => $this->order->id,
            'product_id'     => $this->product->id,
            'provider_id'    => $this->provider->id,
            'quantity'       => 2,
            'unit_cost_bob'  => 80.00,
            'unit_price_bob' => 150.00,
            'subtotal_bob'   => 300.00,
        ]);
    }

    public function test_returns_index_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('returns.index'));

        $response->assertStatus(200);
        $response->assertViewIs('returns.index');
        $response->assertViewHas('orders');
    }

    public function test_record_novedad_increments_attempts(): void
    {
        $service = app(ReverseLogisticsService::class);
        $service->recordNovedad($this->order, 'cliente_ausente', 'Timbró 3 veces y nadie atendió');

        $this->order->refresh();
        $this->assertEquals(1, $this->order->delivery_attempts);
        $this->assertEquals('cliente_ausente', $this->order->novedad_reason);
        $this->assertEquals('Timbró 3 veces y nadie atendió', $this->order->novedad_notes);
    }

    public function test_third_failed_attempt_marks_order_as_failed_delivery(): void
    {
        $service = app(ReverseLogisticsService::class);
        $this->order->delivery_attempts = 2;
        $this->order->save();

        $service->recordNovedad($this->order, 'telefono_apagado', 'Llamada enviada al buzón');

        $this->order->refresh();
        $this->assertEquals(3, $this->order->delivery_attempts);
        $this->assertEquals('failed_delivery', $this->order->status);
    }

    public function test_reschedule_delivery_updates_status_and_date(): void
    {
        $service = app(ReverseLogisticsService::class);
        $service->rescheduleDelivery($this->order, '2026-10-30 14:00:00', 'Entregar en la tarde');

        $this->order->refresh();
        $this->assertEquals('in_transit', $this->order->status);
        $this->assertNotNull($this->order->rescheduled_delivery_at);
    }

    public function test_process_return_reintegrates_warehouse_stock_and_charges_freight(): void
    {
        $service = app(ReverseLogisticsService::class);
        
        // Stock inicial era 10, y el pedido tiene 2 unidades
        $service->processReturn($this->order, true, 'Rechazo definitivo por cliente');

        $this->order->refresh();
        $this->assertEquals('returned', $this->order->status);

        // Verificar que el inventario se restableció (10 + 2 = 12)
        $this->inventory->refresh();
        $this->assertEquals(12, $this->inventory->stock_available);

        // Verificar que a la billetera del dropshipper se le cobró el flete (Bs 25.00)
        $wallet = Wallet::where('user_id', $this->dropshipper->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(-25.00, $wallet->balance_available);
    }

    public function test_process_return_via_http_endpoint(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('returns.process', $this->order->id), [
                'deduct_shipping_fee' => true,
                'reason' => 'cliente_cancelo',
            ]);

        $response->assertRedirect();
        $this->order->refresh();
        $this->assertEquals('returned', $this->order->status);
    }
}
