<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryNotePrintingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $dropshipper;
    protected User $provider;
    protected Warehouse $warehouse;
    protected Product $product;
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
            'code'       => 'BOD-NOTE-01',
            'name'       => 'Bodega Central Albaranes',
            'department' => 'Santa Cruz',
            'city'       => 'Santa Cruz de la Sierra',
            'address'    => 'Av. Cristo Redentor',
            'is_active'  => true,
        ]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Belleza',
            'slug'      => 'belleza',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'tenant_id'           => $this->tenant->id,
            'provider_id'         => $this->provider->id,
            'category_id'         => $category->id,
            'name'                => 'Serum Facial Antiedad',
            'slug'                => 'serum-facial-antiedad',
            'sku'                 => 'SERUM-01',
            'cost_price_bob'      => 45.00,
            'suggested_price_bob' => 110.00,
            'min_price_bob'       => 90.00,
            'status'              => 'published',
            'is_cod_available'    => true,
        ]);

        $this->order = Order::create([
            'tenant_id'              => $this->tenant->id,
            'dropshipper_id'         => $this->dropshipper->id,
            'warehouse_id'           => $this->warehouse->id,
            'order_number'           => 'ORD-NOTE-999',
            'tracking_number'        => 'DEX-NOTE-999',
            'customer_name'          => 'Luciana Peña',
            'customer_phone'         => '78912345',
            'shipping_department'    => 'Santa Cruz',
            'shipping_city'          => 'Santa Cruz de la Sierra',
            'shipping_address'       => 'Calle Sucre #240',
            'total_amount_bob'       => 110.00,
            'cost_amount_bob'        => 45.00,
            'shipping_fee_bob'       => 20.00,
            'platform_fee_bob'       => 5.00,
            'dropshipper_profit_bob' => 40.00,
            'status'                 => 'confirmed',
            'payment_method'         => 'cash_on_delivery_bob',
        ]);

        OrderItem::create([
            'tenant_id'      => $this->tenant->id,
            'order_id'       => $this->order->id,
            'product_id'     => $this->product->id,
            'provider_id'    => $this->provider->id,
            'quantity'       => 1,
            'unit_cost_bob'  => 45.00,
            'unit_price_bob' => 110.00,
            'subtotal_bob'   => 110.00,
        ]);
    }

    public function test_delivery_note_page_loads_successfully_with_correct_data(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('orders.delivery_note', $this->order->id));

        $response->assertStatus(200);
        $response->assertViewIs('labels.delivery_note');
        $response->assertSee('Nota de Entrega');
        $response->assertSee('#ORD-NOTE-999');
        $response->assertSee('Luciana Peña');
        $response->assertSee('Serum Facial Antiedad');
        $response->assertSee('Bs 110.00');
    }

    public function test_shipping_label_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('orders.label', $this->order->id));

        $response->assertStatus(200);
        $response->assertViewIs('labels.thermal');
    }
}
