<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Notifications\WhatsAppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppNotificationFlowTest extends TestCase
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
            'code'       => 'BOD-WHATSAPP-01',
            'name'       => 'Bodega Central WhatsApp',
            'department' => 'Santa Cruz',
            'city'       => 'Santa Cruz de la Sierra',
            'address'    => 'Parque Industrial',
            'is_active'  => true,
        ]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Hogar y Cocina',
            'slug'      => 'hogar-y-cocina',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'tenant_id'           => $this->tenant->id,
            'provider_id'         => $this->provider->id,
            'category_id'         => $category->id,
            'name'                => 'Mini Freidora de Aire',
            'slug'                => 'mini-freidora-aire',
            'sku'                 => 'FREI-01',
            'cost_price_bob'      => 120.00,
            'suggested_price_bob' => 220.00,
            'min_price_bob'       => 180.00,
            'status'              => 'published',
            'is_cod_available'    => true,
        ]);

        $this->order = Order::create([
            'tenant_id'              => $this->tenant->id,
            'dropshipper_id'         => $this->dropshipper->id,
            'warehouse_id'           => $this->warehouse->id,
            'order_number'           => 'ORD-WPP-TEST-777',
            'tracking_number'        => 'DEX-BOL-777888',
            'customer_name'          => 'Mariana Gutierrez',
            'customer_phone'         => '71234567',
            'shipping_department'    => 'Cochabamba',
            'shipping_city'          => 'Cochabamba',
            'shipping_address'       => 'Av. América y Pando',
            'total_amount_bob'       => 220.00,
            'cost_amount_bob'        => 120.00,
            'shipping_fee_bob'       => 30.00,
            'platform_fee_bob'       => 10.00,
            'dropshipper_profit_bob' => 60.00,
            'status'                 => 'in_transit',
            'payment_method'         => 'cash_on_delivery_bob',
        ]);

        OrderItem::create([
            'tenant_id'      => $this->tenant->id,
            'order_id'       => $this->order->id,
            'product_id'     => $this->product->id,
            'provider_id'    => $this->provider->id,
            'quantity'       => 1,
            'unit_cost_bob'  => 120.00,
            'unit_price_bob' => 220.00,
            'subtotal_bob'   => 220.00,
        ]);
    }

    public function test_whatsapp_notification_center_loads_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('notifications.whatsapp.index'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.whatsapp');
        $response->assertViewHasAll(['logs', 'templates']);
    }

    public function test_service_renders_template_with_order_variables(): void
    {
        $service = app(WhatsAppNotificationService::class);
        $message = $service->renderMessage($this->order, 'order_dispatched');

        $this->assertStringContainsString('Mariana Gutierrez', $message);
        $this->assertStringContainsString('#ORD-WPP-TEST-777', $message);
        $this->assertStringContainsString('220.00', $message);
        $this->assertStringContainsString('DEX-BOL-777888', $message);
    }

    public function test_service_generates_valid_wa_me_deep_link(): void
    {
        $service = app(WhatsAppNotificationService::class);
        $link = $service->buildWhatsAppLink($this->order, 'order_confirmed');

        $this->assertStringStartsWith('https://wa.me/59171234567?text=', $link);
        $this->assertStringContainsString('Mariana', $link);
    }

    public function test_send_order_notification_logs_in_database(): void
    {
        $service = app(WhatsAppNotificationService::class);
        $log = $service->sendOrderNotification($this->order, 'novedad_alert');

        $this->assertInstanceOf(NotificationLog::class, $log);
        $this->assertEquals('whatsapp', $log->channel);
        $this->assertEquals('+59171234567', $log->recipient);
        $this->assertEquals('sent', $log->status);

        $this->assertDatabaseHas('notification_logs', [
            'id'        => $log->id,
            'recipient' => '+59171234567',
            'channel'   => 'whatsapp',
        ]);
    }

    public function test_send_notification_via_http_controller(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('notifications.whatsapp.send', $this->order->id), [
                'template' => 'order_delivered',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('notification_logs', [
            'event_type' => 'order_delivered',
            'channel'    => 'whatsapp',
        ]);
    }

    public function test_get_link_via_api(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('notifications.whatsapp.link', [
                'orderId'  => $this->order->id,
                'template' => 'order_dispatched',
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'link', 'message']);
    }
}
