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
    }
}

