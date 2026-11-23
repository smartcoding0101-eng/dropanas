<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreationFlowEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_complete_order_creation_flow_with_sanctum_token(): void
    {
        // 1. Iniciar sesión vía API para obtener token
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email'    => 'dropshipper@bolidrop.bo',
            'password' => 'password123',
        ]);
        $loginRes->assertStatus(200);
        $token = $loginRes->json('data.token');
        $this->assertNotEmpty($token);

        // 2. Obtener producto y bodega con stock
        $product   = Product::where('status', 'published')->first();
        $warehouse = Warehouse::where('is_active', true)->first();
        $carrier   = Carrier::where('has_cod_service', true)->first();

        $payload = [
            'warehouse_id'        => $warehouse->id,
            'carrier_code'        => $carrier->code,
            'customer_name'       => 'Juan Perez Bolivia',
            'customer_phone'      => '+591 71234567',
            'customer_document_id'=> '4928172 SC',
            'shipping_department' => 'Santa Cruz',
            'shipping_city'       => 'Santa Cruz de la Sierra',
            'shipping_address'    => 'Av. Cristobal de Mendoza #200',
            'shipping_reference'  => 'Edificio Torre Duo, Piso 4',
            'payment_method'      => 'qr_interoperable',
            'items' => [
                [
                    'product_id'        => $product->id,
                    'quantity'          => 1,
                    'selling_price_bob' => (float) $product->suggested_price_bob,
                ],
            ],
        ];

        // 3. Crear pedido
        $orderRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/orders', $payload);

        $orderRes->assertStatus(201);
        $orderRes->assertJsonPath('status', 'success');
        $this->assertNotNull($orderRes->json('data.order.id'));
        $this->assertNotNull($orderRes->json('data.order.order_number'));
    }
}
