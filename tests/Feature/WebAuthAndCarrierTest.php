<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebAuthAndCarrierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_login_page_loads_successfully(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Ingreso a la Plataforma');
        $response->assertSee('Correo Electrónico');
        $response->assertSee('Contraseña');
    }

    public function test_register_page_loads_successfully(): void
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertSee('Crear Nueva Cuenta');
        $response->assertSee('Dropshipper / Tienda');
        $response->assertSee('Proveedor Mayorista');
    }

    public function test_user_can_login_and_is_redirected_to_their_panel(): void
    {
        $tenant = Tenant::first();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email'     => 'test_dropshipper@dropana.bo',
            'password'  => Hash::make('password123'),
            'role'      => 'dropshipper',
            'is_active' => true,
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'test_dropshipper@dropana.bo',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('panel.dropshipper'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_register_new_account_and_wallet_is_created(): void
    {
        $response = $this->post(route('register'), [
            'name'                   => 'Nuevo Dropshipper Bolivia',
            'email'                  => 'nuevo.dropshipper@dropana.bo',
            'password'               => 'bolivia2026',
            'password_confirmation'  => 'bolivia2026',
            'role'                   => 'dropshipper',
            'phone'                  => '+591 71234567',
            'identity_document_type' => 'CI',
            'identity_document'      => '9988776 SC',
            'department'             => 'Santa Cruz',
            'city'                   => 'Santa Cruz de la Sierra',
        ]);

        $response->assertRedirect(route('panel.dropshipper'));
        $this->assertDatabaseHas('users', [
            'email'       => 'nuevo.dropshipper@dropana.bo',
            'role'        => 'dropshipper',
            'document_id' => '9988776 SC',
        ]);

        $newUser = User::where('email', 'nuevo.dropshipper@dropana.bo')->first();
        $this->assertNotNull($newUser);

        // Wallet en BOB debe haberse creado automáticamente
        $wallet = Wallet::where('user_id', $newUser->id)->where('currency', 'BOB')->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(0.00, (float)$wallet->balance_bob);
    }

    public function test_carrier_panel_loads_with_orders_in_route(): void
    {
        $response = $this->get(route('panel.carrier'));
        $response->assertStatus(200);
        $response->assertSee('DEX Bolivia Express — Ruta COD');
        $response->assertSee('Novedades Activas');
        $response->assertSee('Por Recaudar COD');
    }

    public function test_order_can_transition_to_failed_delivery_with_novedad_reason(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $dropshipper = User::where('role', 'dropshipper')->first();
        $warehouse = Warehouse::first();
        $carrier = Carrier::first();

        $order = Order::create([
            'tenant_id'              => $admin->tenant_id,
            'dropshipper_id'         => $dropshipper->id,
            'warehouse_id'           => $warehouse->id,
            'carrier_id'             => $carrier->id,
            'order_number'           => 'TEST-NOV-001',
            'customer_name'          => 'Cliente Novedad',
            'customer_phone'         => '76543210',
            'shipping_department'    => 'Santa Cruz',
            'shipping_city'          => 'Santa Cruz de la Sierra',
            'shipping_address'       => 'Equipetrol Norte Calle 7',
            'total_amount_bob'       => 280.00,
            'cost_amount_bob'        => 140.00,
            'shipping_fee_bob'       => 25.00,
            'platform_fee_bob'       => 10.00,
            'dropshipper_profit_bob' => 105.00,
            'status'                 => 'dispatched',
        ]);

        $token = $admin->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status'                  => 'failed_delivery',
                'novedad_reason'          => 'cliente_ausente',
                'novedad_notes'           => 'Se visitó el domicilio a las 11:30 pero el cliente estaba en el trabajo.',
                'rescheduled_delivery_at' => '2026-10-29 14:00:00',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $order->refresh();
        $this->assertEquals('failed_delivery', $order->status);
        $this->assertEquals('cliente_ausente', $order->novedad_reason);
        $this->assertEquals(1, $order->delivery_attempts);
        $this->assertStringContainsString('Se visitó el domicilio', $order->novedad_notes);
    }
}
