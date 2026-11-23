<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    /** Invitados no autenticados son redirigidos a login */
    public function test_guests_cannot_access_reportes_hub(): void
    {
        $response = $this->get('/reportes');
        $response->assertRedirect('/login');
    }

    /** Proveedor puede acceder a la interfaz de /reportes */
    public function test_provider_can_access_reportes_hub(): void
    {
        $provider = User::where('role', 'provider')->first();

        $response = $this->actingAs($provider)->get('/reportes');
        $response->assertStatus(200);
        $response->assertSee('Centro de Reportes & Métricas');
        $response->assertSee('reportesHubPage');
    }

    /** Proveedor puede obtener metricas en formato JSON via /reportes/metricas */
    public function test_provider_can_fetch_reportes_metrics_json(): void
    {
        $provider = User::where('role', 'provider')->first();

        $response = $this->actingAs($provider)->getJson('/reportes/metricas');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'summary_kpis',
                'funnel',
                'valuation',
                'rotation',
                'geo',
                'top_sellers',
                'movements',
                'wallet',
            ],
        ]);
        $response->assertJson(['status' => 'success']);
    }

    /** Proveedor puede descargar CSV via /reportes/exportar */
    public function test_provider_can_export_reportes_csv(): void
    {
        $provider = User::where('role', 'provider')->first();

        $response = $this->actingAs($provider)->get('/reportes/exportar');
        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type') ?? '', 'text/csv'),
            'Content type should be text/csv'
        );
    }

    /** Dropshipper no puede acceder al modulo de reportes del proveedor */
    public function test_dropshipper_cannot_access_reportes_hub(): void
    {
        $dropshipper = User::where('role', 'dropshipper')->first();

        $response = $this->actingAs($dropshipper)->get('/reportes');
        $response->assertStatus(403);
    }
}
