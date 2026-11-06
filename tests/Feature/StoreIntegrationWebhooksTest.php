<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\StoreIntegration;
use App\Models\User;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreIntegrationWebhooksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_dropshipper_can_connect_shopify_store(): void
    {
        $dropshipper = User::where('role', 'dropshipper')->first();
        $token = $dropshipper->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/store-integrations', [
                'platform'      => 'shopify',
                'store_name'    => 'Bolivia Trends Shop',
                'store_url'     => 'https://bolivia-trends.myshopify.com',
                'auto_dispatch' => true,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('store_integrations', [
            'dropshipper_id' => $dropshipper->id,
            'platform'       => 'shopify',
            'store_name'     => 'Bolivia Trends Shop',
            'auto_dispatch'  => true,
        ]);
    }

    public function test_shopify_webhook_creates_cod_order_in_dropana(): void
    {
        $dropshipper = User::where('role', 'dropshipper')->first();

        $integration = StoreIntegration::create([
            'tenant_id'      => $dropshipper->tenant_id,
            'dropshipper_id' => $dropshipper->id,
            'platform'       => 'shopify',
            'store_name'     => 'Mi Tienda Shopify',
            'store_url'      => 'https://mitienda.myshopify.com',
            'webhook_secret' => 'shopify_secret_12345',
            'auto_dispatch'  => false,
            'is_active'      => true,
        ]);

        $shopifyPayload = [
            'id'           => 100982392,
            'order_number' => 1024,
            'name'         => '#1024',
            'total_price'  => '299.00',
            'phone'        => '+591 78901234',
            'customer'     => [
                'first_name' => 'Carlos',
                'last_name'  => 'Mamani',
                'email'      => 'carlos@ejemplo.com',
            ],
            'shipping_address' => [
                'first_name' => 'Carlos',
                'last_name'  => 'Mamani',
                'phone'      => '+591 78901234',
                'province'   => 'Santa Cruz',
                'city'       => 'Santa Cruz de la Sierra',
                'address1'   => 'Av. Cristo Redentor 4to Anillo',
            ],
            'line_items' => [
                [
                    'id'       => 9991,
                    'title'    => 'Licuadora Portátil USB Recargable',
                    'quantity' => 1,
                    'price'    => '299.00',
                ]
            ],
        ];

        $response = $this->withHeader('X-Shopify-Topic', 'orders/create')
            ->postJson("/api/v1/webhooks/shopify/{$integration->webhook_secret}", $shopifyPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data'   => [
                'status' => 'processed',
            ],
        ]);

        // Verificar que el pedido COD boliviano se generó en base de datos
        $this->assertDatabaseHas('orders', [
            'dropshipper_id'      => $dropshipper->id,
            'customer_name'       => 'Carlos Mamani',
            'shipping_department' => 'Santa Cruz',
            'total_amount_bob'    => 299.00,
            'status'              => 'pending_confirmation',
        ]);

        // Verificar registro de log del webhook
        $this->assertDatabaseHas('store_webhook_logs', [
            'store_integration_id' => $integration->id,
            'platform'             => 'shopify',
            'status'               => 'processed',
        ]);
    }

    public function test_woocommerce_webhook_creates_cod_order_in_dropana(): void
    {
        $dropshipper = User::where('role', 'dropshipper')->first();

        $integration = StoreIntegration::create([
            'tenant_id'      => $dropshipper->tenant_id,
            'dropshipper_id' => $dropshipper->id,
            'platform'       => 'woocommerce',
            'store_name'     => 'Tienda WooCommerce Bolivia',
            'store_url'      => 'https://mitiendawoocod.bo',
            'webhook_secret' => 'wc_secret_67890',
            'auto_dispatch'  => false,
            'is_active'      => true,
        ]);

        $wcPayload = [
            'id'    => 5432,
            'total' => '250.00',
            'billing' => [
                'first_name' => 'Mariana',
                'last_name'  => 'Rojas',
                'phone'      => '+591 76543210',
                'city'       => 'Cochabamba',
                'state'      => 'Cochabamba',
                'address_1'  => 'Av. América Este #450',
            ],
            'shipping' => [
                'first_name' => 'Mariana',
                'last_name'  => 'Rojas',
                'city'       => 'Cochabamba',
                'state'      => 'Cochabamba',
                'address_1'  => 'Av. América Este #450',
            ],
            'line_items' => [
                [
                    'id'       => 12,
                    'name'     => 'Reloj Inteligente Smartwatch T900',
                    'quantity' => 1,
                    'total'    => '250.00',
                    'price'    => 250.00,
                ]
            ],
        ];

        $response = $this->withHeader('X-WC-Webhook-Topic', 'woocommerce_order_created')
            ->postJson("/api/v1/webhooks/woocommerce/{$integration->webhook_secret}", $wcPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data'   => [
                'status' => 'processed',
            ],
        ]);

        // Verificar que el pedido COD boliviano se generó en base de datos
        $this->assertDatabaseHas('orders', [
            'dropshipper_id'      => $dropshipper->id,
            'customer_name'       => 'Mariana Rojas',
            'shipping_department' => 'Cochabamba',
            'total_amount_bob'    => 250.00,
            'status'              => 'pending_confirmation',
        ]);
    }
}
