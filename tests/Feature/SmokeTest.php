<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Suite de Smoke Tests (Pruebas de Humo de Validación Ultrarrápida)
 * Objetivo: Ejecutarse en < 5 segundos y verificar que los componentes críticos de UI y API están vivos.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_smoke_public_web_pages_load_successfully(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('/catalogo')->assertStatus(200);
        $this->get('/login')->assertStatus(200);
        $this->get('/registro')->assertStatus(200);
        $this->get('/checkout-demo')->assertStatus(200);
        $this->get('/soporte')->assertStatus(200);
        $this->get('/tracking')->assertStatus(200);
    }

    public function test_smoke_carrier_field_cobro_screen_loads(): void
    {
        $this->get('/carrier/cobro')->assertStatus(200);
        $this->get('/carrier/cobro/DRP-2026-001234')->assertStatus(200);
    }

    public function test_smoke_authenticated_panels_load(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $this->actingAs($admin)->get('/panel/admin')->assertStatus(200);
        $this->actingAs($admin)->get('/panel/dropshipper')->assertStatus(200);
        $this->actingAs($admin)->get('/panel/proveedor')->assertStatus(200);
        $this->actingAs($admin)->get('/panel/carrier')->assertStatus(200);
        $this->actingAs($admin)->get('/panel/fulfillment')->assertStatus(200);
    }

    public function test_smoke_public_api_endpoints_return_healthy_json(): void
    {
        $this->getJson('/api/v1/exchange-rate/current')->assertStatus(200);
        $this->getJson('/api/v1/products')->assertStatus(200);
    }
}
