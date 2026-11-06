<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoliviaApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_can_fetch_official_bcb_exchange_rate_publicly(): void
    {
        $response = $this->getJson('/api/v1/exchange-rate/current');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'currency_pair',
                    'buy_rate',
                    'sell_rate',
                    'official_source',
                    'last_updated_bolivia',
                ],
            ]);

        $this->assertIsNumeric($response->json('data.sell_rate'));
        $this->assertGreaterThan(0, $response->json('data.sell_rate'));
    }

    public function test_can_list_products_with_warehouse_inventory(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'sku',
                            'cost_price_bob',
                            'suggested_price_bob',
                            'min_price_bob',
                            'warehouse_inventories',
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_authenticate_and_create_cod_order_with_qr_and_waybill(): void
    {
        $dropshipper = User::where('email', 'dropshipper@dropana.bo')->first();
        $token = $dropshipper->createToken('test_token')->plainTextToken;

        $warehouse = Warehouse::first();
        $product = Product::first();
        $carrier = Carrier::where('code', 'dex_bolivia')->first();

        $payload = [
            'warehouse_id' => $warehouse->id,
            'carrier_code' => $carrier->code,
            'customer_name' => 'Alejandro Morales',
            'customer_phone' => '+591 76543210',
            'customer_document_id' => '1234567 LP',
            'shipping_department' => 'La Paz',
            'shipping_city' => 'La Paz',
            'shipping_address' => 'Zona Sopocachi, Calle Belisario Salinas #340',
            'shipping_reference' => 'Frente al Edificio Los Jazmines',
            'payment_method' => 'qr_interoperable',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'selling_price_bob' => 99.00, // Total = 198 BOB
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'order' => [
                        'id',
                        'order_number',
                        'total_amount_bob',
                        'dropshipper_profit_bob',
                        'platform_fee_bob',
                        'shipping_fee_bob',
                    ],
                    'waybill' => [
                        'carrier',
                        'waybill_number',
                        'cod_collection_amount_bob',
                    ],
                    'payment' => [
                        'provider',
                        'qr_identifier',
                        'qr_image_url',
                    ],
                ],
            ]);

        $this->assertEquals('qr_interoperable', $response->json('data.payment.provider'));
        $this->assertEquals(198.00, (float) $response->json('data.order.total_amount_bob'));
    }

    public function test_can_query_wallet_balance_and_transactions(): void
    {
        $dropshipper = User::where('email', 'dropshipper@dropana.bo')->first();
        $token = $dropshipper->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/wallet');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'currency' => 'BOB',
                    'symbol' => 'Bs',
                ],
            ]);
    }
}
