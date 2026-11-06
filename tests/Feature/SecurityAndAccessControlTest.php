<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Suite de Pruebas de Seguridad, Control de Acceso (RBAC) y Prevención IDOR/BOLA
 */
class SecurityAndAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $provider;
    protected User $dropshipperA;
    protected User $dropshipperB;
    protected User $carrierUser;
    protected Warehouse $warehouse;
    protected Carrier $carrier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);

        $this->tenant = Tenant::first();
        $this->admin = User::where('role', 'tenant_admin')->first();
        $this->provider = User::where('role', 'provider')->first();
        $this->dropshipperA = User::where('role', 'dropshipper')->first();
        $this->warehouse = Warehouse::first();
        $this->carrier = Carrier::first();
        $this->product = Product::first();

        // Crear un segundo dropshipper en el mismo tenant para pruebas de aislamiento de datos (IDOR)
        $this->dropshipperB = User::create([
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'Dropshipper Competidor B',
            'email'                 => 'dropshipper.b@dropana.bo',
            'password'              => bcrypt('password123'),
            'role'                  => 'dropshipper',
            'company_or_store_name' => 'Tienda Rival B',
        ]);

        $this->carrierUser = User::where('role', 'carrier')->first() ?? User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Chofer DEX',
            'email'     => 'chofer@dex.bo',
            'password'  => bcrypt('password123'),
            'role'      => 'carrier',
        ]);
    }

    /**
     * SEGURIDAD 1: Usuarios no autenticados no pueden acceder a endpoints protegidos (HTTP 401)
     */
    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/orders')->assertStatus(401);
        $this->getJson('/api/v1/wallet')->assertStatus(401);
        $this->postJson('/api/v1/wallet/withdraw', [])->assertStatus(401);
        $this->postJson('/api/v1/products', [])->assertStatus(401);
        $this->postJson('/api/v1/manifests', [])->assertStatus(401);
    }

    /**
     * SEGURIDAD 2: Dropshipper no puede acceder a las órdenes ni métricas de otro Dropshipper (Anti-IDOR)
     */
    public function test_dropshipper_cannot_view_another_dropshippers_order(): void
    {
        // Orden perteneciente al Dropshipper A
        $orderA = Order::create([
            'tenant_id'             => $this->tenant->id,
            'dropshipper_id'        => $this->dropshipperA->id,
            'warehouse_id'          => $this->warehouse->id,
            'carrier_id'            => $this->carrier->id,
            'order_number'          => 'ORD-PRIVATE-A-01',
            'customer_name'         => 'Cliente Privado A',
            'customer_phone'        => '71111111',
            'shipping_department'   => 'Santa Cruz',
            'shipping_city'         => 'Santa Cruz',
            'shipping_address'      => 'Dirección Secreta #1',
            'total_amount_bob'      => 100.00,
            'cost_amount_bob'       => 40.00,
            'shipping_fee_bob'      => 18.00,
            'platform_fee_bob'      => 5.00,
            'dropshipper_profit_bob'=> 37.00,
            'status'                => 'pending_confirmation',
        ]);

        // Dropshipper B intenta consultar la lista: solo debe ver sus propios pedidos
        Sanctum::actingAs($this->dropshipperB);

        $response = $this->getJson('/api/v1/orders');
        $response->assertStatus(200);

        $orderIds = collect($response->json('data.data'))->pluck('id');
        $this->assertFalse($orderIds->contains($orderA->id), 'Dropshipper B NO debe ver el pedido de Dropshipper A en su listado');

        // Dropshipper B intenta actualizar el estado del pedido de A (debe dar 403 Forbidden)
        $patchRes = $this->patchJson("/api/v1/orders/{$orderA->id}/status", [
            'status' => 'confirmed',
        ]);
        $patchRes->assertStatus(403);
    }

    /**
     * SEGURIDAD 3: Dropshipper solo puede retirar de su propia billetera
     */
    public function test_dropshipper_cannot_withdraw_from_other_users_wallet(): void
    {
        // Wallet de B con 0 fondos
        Wallet::updateOrCreate(
            ['user_id' => $this->dropshipperB->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        // Wallet de A con 500 fondos
        Wallet::updateOrCreate(
            ['user_id' => $this->dropshipperA->id, 'tenant_id' => $this->tenant->id, 'currency' => 'BOB'],
            ['balance_available' => 500.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        Sanctum::actingAs($this->dropshipperB);

        // El endpoint usa el usuario autenticado automáticamente
        $response = $this->postJson('/api/v1/wallet/withdraw', [
            'amount'          => 100.00,
            'payout_method'   => 'qr_simple',
            'account_details' => [
                'beneficiary_name' => 'Hacker B',
                'document_id'      => '1234567 LP',
            ],
        ]);

        // Debe rebotar porque la billetera de B no tiene saldo
        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'error']);
    }

    /**
     * SEGURIDAD 4: Roles y Permisos (RBAC) - Restricción de creación de productos y manifiestos
     */
    public function test_role_based_access_control_restrictions(): void
    {
        $category = Category::first();

        // 1. Dropshipper NO puede publicar productos
        Sanctum::actingAs($this->dropshipperA);
        $resProduct = $this->postJson('/api/v1/products', [
            'category_id'         => $category->id,
            'name'                => 'Producto Ilegal Dropshipper',
            'sku'                 => 'ILEGAL-SKU-01',
            'cost_price_bob'      => 50.00,
            'suggested_price_bob' => 100.00,
            'min_price_bob'       => 70.00,
        ]);
        $resProduct->assertStatus(403);

        // 2. Courier NO puede crear manifiestos de despacho (solo admin/provider)
        Sanctum::actingAs($this->carrierUser);
        $resManifest = $this->postJson('/api/v1/manifests', [
            'carrier_id' => $this->carrier->id,
            'order_ids'  => ['00000000-0000-0000-0000-000000000000'],
        ]);
        $resManifest->assertStatus(403);
    }

    /**
     * SEGURIDAD 5: Bloqueo de transiciones ilegales en la máquina de estados
     */
    public function test_illegal_order_state_transitions_are_blocked(): void
    {
        $order = Order::create([
            'tenant_id'             => $this->tenant->id,
            'dropshipper_id'        => $this->dropshipperA->id,
            'warehouse_id'          => $this->warehouse->id,
            'carrier_id'            => $this->carrier->id,
            'order_number'          => 'ORD-STATE-TEST-01',
            'customer_name'         => 'Cliente Transición',
            'customer_phone'        => '70000000',
            'shipping_department'   => 'Santa Cruz',
            'shipping_city'         => 'Santa Cruz',
            'shipping_address'      => 'Calle 1 #2',
            'total_amount_bob'      => 100.00,
            'cost_amount_bob'       => 40.00,
            'shipping_fee_bob'      => 18.00,
            'platform_fee_bob'      => 5.00,
            'dropshipper_profit_bob'=> 37.00,
            'status'                => 'pending_confirmation',
        ]);

        Sanctum::actingAs($this->admin);

        // Intento 1: Saltar directamente de pending_confirmation a delivered (no permitido)
        $res1 = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'delivered',
        ]);
        $res1->assertStatus(422);
        $this->assertEquals('pending_confirmation', $order->fresh()->status);

        // Intento 2: Transicionar una orden ya liquidada (settled)
        $order->update(['status' => 'settled']);
        $res2 = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'cancelled',
        ]);
        $res2->assertStatus(422);
        $this->assertEquals('settled', $order->fresh()->status);
    }
}

