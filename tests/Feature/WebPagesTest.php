<?php

namespace Tests\Feature;

use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_homepage_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Bolidrop');
        $response->assertSee('Bolivia');
        $response->assertDontSee('Nuevo Pedido COD');
    }

    public function test_unauthenticated_guest_is_redirected_to_login_on_internal_pages(): void
    {
        $this->get('/catalogo')->assertRedirect(route('login'));
        $this->get('/checkout-demo')->assertRedirect(route('login'));
        $this->get('/panel/dropshipper')->assertRedirect(route('login'));
        $this->get('/panel/proveedor')->assertRedirect(route('login'));
        $this->get('/panel/admin')->assertRedirect(route('login'));
        $this->get('/panel/carrier')->assertRedirect(route('login'));
        $this->get('/panel/fulfillment')->assertRedirect(route('login'));
    }

    public function test_catalog_page_loads_with_products(): void
    {
        $user = \App\Models\User::where('role', 'dropshipper')->first();
        $response = $this->actingAs($user)->get('/catalogo');
        $response->assertStatus(200);
        $response->assertSee('Catálogo Mayorista');
        $response->assertSee('BOB');
    }

    public function test_checkout_demo_page_loads(): void
    {
        $user = \App\Models\User::where('role', 'dropshipper')->first();
        $response = $this->actingAs($user)->get('/checkout-demo');
        $response->assertStatus(200);
        $response->assertSee('Checkout Contra Entrega');
        $response->assertSee('COD');
    }

    public function test_dropshipper_panel_loads(): void
    {
        $user = \App\Models\User::where('role', 'dropshipper')->first();
        $response = $this->actingAs($user)->get('/panel/dropshipper');
        $response->assertStatus(200);
        $response->assertSee('Panel del Dropshipper');
        $response->assertSee('Nuevo Pedido COD');
    }

    public function test_proveedor_panel_loads(): void
    {
        $user = \App\Models\User::where('role', 'provider')->first();
        $response = $this->actingAs($user)->get('/panel/proveedor');
        $response->assertStatus(200);
        $response->assertSee('Panel del Proveedor');
        $response->assertSee('Megabodega');
    }

    public function test_admin_panel_loads(): void
    {
        $admin = \App\Models\User::where('role', 'tenant_admin')->first();
        $response = $this->actingAs($admin)->get('/panel/admin');
        $response->assertStatus(200);
        $response->assertSee('Panel de Control de la Plataforma');
        $response->assertSee('Tenant Admin');
        $response->assertSee('Control Global de Actores del Ecosistema');
    }

    public function test_admin_has_oversight_and_control_over_all_roles(): void
    {
        $admin = \App\Models\User::where('role', 'tenant_admin')->first();
        $provider = \App\Models\User::where('role', 'provider')->first();
        $dropshipper = \App\Models\User::where('role', 'dropshipper')->first();

        // 1. Admin supervisando proveedor (general y específico por provider_id)
        $respProv = $this->actingAs($admin)->get('/panel/proveedor');
        $respProv->assertStatus(200)->assertSee('Modo Supervisión y Control Administrativo');

        if ($provider) {
            $respProvId = $this->actingAs($admin)->get('/panel/proveedor?provider_id=' . $provider->id);
            $respProvId->assertStatus(200)->assertSee($provider->name);
        }

        // 2. Admin supervisando dropshipper (general y específico por dropshipper_id)
        $respDrop = $this->actingAs($admin)->get('/panel/dropshipper');
        $respDrop->assertStatus(200)->assertSee('Modo Supervisión y Control Administrativo');

        if ($dropshipper) {
            $respDropId = $this->actingAs($admin)->get('/panel/dropshipper?dropshipper_id=' . $dropshipper->id);
            $respDropId->assertStatus(200)->assertSee($dropshipper->name);
        }

        // 3. Admin supervisando Courier DEX y sus Reportes
        $respCarrier = $this->actingAs($admin)->get('/panel/carrier');
        $respCarrier->assertStatus(200)->assertSee('Modo Supervisión y Control Administrativo');

        $respCarrierRep = $this->actingAs($admin)->get('/panel/carrier/reportes');
        $respCarrierRep->assertStatus(200);

        // 4. Admin accediendo a Kardex e Informes
        $respKardex = $this->actingAs($admin)->get('/panel/admin/kardex');
        $respKardex->assertStatus(200);

        $respSettlements = $this->actingAs($admin)->get('/settlements');
        $respSettlements->assertStatus(200);
    }
}

