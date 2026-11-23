<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/reporte');
        $response->assertRedirect(route('login'));
    }

    public function test_dropshipper_can_access_commercial_report(): void
    {
        $dropshipper = User::where('role', 'dropshipper')->first();
        $response = $this->actingAs($dropshipper)->get('/reporte');

        $response->assertStatus(200);
        $response->assertSee('Reporte Comercial & Ventas');
        $response->assertSee('Total Ventas (BOB)');
        $response->assertSee('Pedidos Registrados');
    }

    public function test_provider_can_access_commercial_report(): void
    {
        $provider = User::where('role', 'provider')->first();
        $response = $this->actingAs($provider)->get('/reporte');

        $response->assertStatus(200);
        $response->assertSee('Reporte Comercial & Ventas');
        $response->assertSee('Mis Ingresos Proveedor');
    }

    public function test_admin_can_access_commercial_report_and_filter(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $response = $this->actingAs($admin)->get('/reporte');

        $response->assertStatus(200);
        $response->assertSee('Reporte Comercial & Ventas');
        $response->assertSee('Proveedor:');
    }

    public function test_commercial_report_json_endpoint_returns_data(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $response = $this->actingAs($admin)->getJson('/reporte/data');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'dateFrom',
                'dateTo',
                'kpis' => [
                    'total_sales_bob',
                    'orders_count',
                    'delivered_count',
                    'pending_count',
                    'provider_cost_bob',
                    'dropshipper_profit_bob',
                ],
                'paymentDistribution',
                'deptDistribution',
                'topProducts',
            ],
        ]);
    }

    public function test_commercial_report_csv_export_streams_file(): void
    {
        $admin = User::where('role', 'tenant_admin')->first();
        $response = $this->actingAs($admin)->get('/reporte/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }
}
