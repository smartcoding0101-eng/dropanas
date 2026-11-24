<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarrierReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_carrier_can_view_carrier_reports_page(): void
    {
        $carrier = User::where('role', 'carrier')->first();
        $this->assertNotNull($carrier);

        $response = $this->actingAs($carrier)->get(route('carrier.reportes'));

        $response->assertStatus(200);
        $response->assertViewIs('carrier.reportes');
        $response->assertSee('DEX Bolivia Express');
        $response->assertSee('Arqueo de Caja / Cierre Diario');
        $response->assertSee('Consolidado por Proveedor');
    }

    public function test_carrier_can_fetch_reactive_metrics_json(): void
    {
        $carrier = User::where('role', 'carrier')->first();

        $response = $this->actingAs($carrier)->getJson(route('carrier.reportes.data'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'kpis',
                'arqueoCaja',
                'consolidadoProveedores',
                'consolidadoCiudades',
                'chart',
                'ordersList',
            ],
        ]);
    }

    public function test_carrier_can_export_csv_report(): void
    {
        $carrier = User::where('role', 'carrier')->first();

        $response = $this->actingAs($carrier)->get(route('carrier.reportes.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_carrier_accessing_global_reportes_hub_is_redirected_to_carrier_reports(): void
    {
        $carrier = User::where('role', 'carrier')->first();

        $response = $this->actingAs($carrier)->get('/reportes');

        $response->assertRedirect(route('carrier.reportes'));
    }

    public function test_unauthenticated_guest_cannot_access_carrier_reports(): void
    {
        $response = $this->get(route('carrier.reportes'));
        $response->assertRedirect(route('login'));
    }
}
