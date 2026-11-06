<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderLifecycleAndManifestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_order_status_transitions_and_auto_settlement(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $dropshipper = User::where('role', 'dropshipper')->first();
        $warehouse = Warehouse::first();
        $carrier = Carrier::first();
        $product = Product::first();

        // Crear una orden demo para transicionar
        $order = Order::create([
            'tenant_id' => $admin->tenant_id,
            'dropshipper_id' => $dropshipper->id,
            'warehouse_id' => $warehouse->id,
            'carrier_id' => $carrier->id,
            'order_number' => 'TEST-ORD-001',
            'customer_name' => 'Cliente Prueba',
            'customer_phone' => '71234567',
            'shipping_department' => 'Santa Cruz',
            'shipping_city' => 'Santa Cruz de la Sierra',
            'shipping_address' => 'Av. San Martín #123',
            'total_amount_bob' => 250.00,
            'cost_amount_bob' => 100.00,
            'shipping_fee_bob' => 25.00,
            'platform_fee_bob' => 12.50,
            'dropshipper_profit_bob' => 112.50,
            'status' => 'pending_confirmation',
        ]);

        $order->items()->create([
            'tenant_id' => $admin->tenant_id,
            'product_id' => $product->id,
            'provider_id' => $product->provider_id,
            'quantity' => 1,
            'unit_price_bob' => 250.00,
            'unit_cost_bob' => 100.00,
            'subtotal_bob' => 250.00,
        ]);

        Sanctum::actingAs($admin);

        // 1. Confirmar pedido
        $resConfirm = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'confirmed',
        ]);
        $resConfirm->assertStatus(200);
        $this->assertEquals('confirmed', $order->fresh()->status);

        // 2. Despachar pedido
        $resDispatch = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'dispatched',
            'tracking_number' => 'DEX-BO-TEST-ORD-001',
        ]);
        $resDispatch->assertStatus(200);
        $this->assertEquals('dispatched', $order->fresh()->status);

        // 3. Marcar como entregado -> dispara auto-liquidación a billetera
        $resDeliver = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'delivered',
        ]);
        $resDeliver->assertStatus(200);

        // La orden debe haber sido liquidada automáticamente
        $this->assertEquals('settled', $order->fresh()->status);

        // Verificar que la billetera del dropshipper recibió la ganancia
        $dropshipperWallet = Wallet::where('user_id', $dropshipper->id)->first();
        $this->assertGreaterThan(0, $dropshipperWallet->balance_available);
    }

    public function test_wallet_withdrawal_request(): void
    {
        $dropshipper = User::where('role', 'dropshipper')->first();
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $dropshipper->id, 'tenant_id' => $dropshipper->tenant_id, 'currency' => 'BOB'],
            ['balance_available' => 300.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );
        $wallet->balance_available = 300.00;
        $wallet->balance_withdrawn = 0.00;
        $wallet->save();

        Sanctum::actingAs($dropshipper);

        // Solicitud de retiro vía QR Simple de banco boliviano
        $response = $this->postJson('/api/v1/wallet/withdraw', [
            'amount' => 150.00,
            'payout_method' => 'qr_simple',
            'account_details' => [
                'beneficiary_name' => 'Antony Dropshipper',
                'document_id' => '12345678 SCZ',
                'bank_name' => 'Banco Nacional de Bolivia (BNB)',
                'account_number' => '150-123456-01',
            ],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(150.00, (float) $wallet->fresh()->balance_available);
        $this->assertEquals(150.00, (float) $wallet->fresh()->balance_withdrawn);
    }

    public function test_shipping_manifest_generation_and_csv(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $dropshipper = User::where('role', 'dropshipper')->first();
        $carrier = Carrier::first();
        $warehouse = Warehouse::first();

        // Crear una orden confirmada lista para manifiesto
        $order = Order::create([
            'tenant_id' => $admin->tenant_id,
            'dropshipper_id' => $dropshipper->id,
            'warehouse_id' => $warehouse->id,
            'carrier_id' => $carrier->id,
            'order_number' => 'TEST-ORD-MAN-01',
            'customer_name' => 'Cliente Manifiesto',
            'customer_phone' => '76543210',
            'shipping_department' => 'La Paz',
            'shipping_city' => 'El Alto',
            'shipping_address' => 'Av. 6 de Marzo #456',
            'total_amount_bob' => 180.00,
            'cost_amount_bob' => 80.00,
            'shipping_fee_bob' => 20.00,
            'platform_fee_bob' => 9.00,
            'dropshipper_profit_bob' => 71.00,
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($admin);

        // Crear manifiesto
        $response = $this->postJson('/api/v1/manifests', [
            'carrier_id' => $carrier->id,
            'order_ids' => [$order->id],
        ]);

        $response->assertStatus(201);
        $manifestId = $response->json('data.manifest.id');

        // La orden debe pasar a dispatched
        $this->assertEquals('dispatched', $order->fresh()->status);

        // Descargar CSV
        $csvResponse = $this->get("/api/v1/manifests/{$manifestId}/download");
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
