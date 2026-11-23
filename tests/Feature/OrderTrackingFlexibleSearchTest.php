<?php

namespace Tests\Feature;

use App\Models\Order;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingFlexibleSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_tracking_finds_order_by_various_formats(): void
    {
        $order = Order::first();
        $order->tracking_number = 'DEX-' . $order->order_number;
        $order->save();

        // 1. Búsqueda por número de tracking exacto
        $res1 = $this->getJson('/api/v1/tracking/' . $order->tracking_number);
        $res1->assertStatus(200);
        $res1->assertJsonPath('data.order_number', $order->order_number);

        // 2. Búsqueda por número de orden directo
        $res2 = $this->getJson('/api/v1/tracking/' . $order->order_number);
        $res2->assertStatus(200);
        $res2->assertJsonPath('data.order_number', $order->order_number);

        // 3. Búsqueda con prefijo doble o redundante (ej: DEX-BO-BO-...)
        $doublePrefixed = 'DEX-BO-' . $order->order_number;
        $res3 = $this->getJson('/api/v1/tracking/' . $doublePrefixed);
        $res3->assertStatus(200);
        $res3->assertJsonPath('data.order_number', $order->order_number);
    }
}
