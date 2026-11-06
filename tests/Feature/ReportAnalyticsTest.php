<?php

namespace Tests\Feature;

use App\Models\BankSettlementBatch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
    }

    public function test_reports_index_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        $response->assertViewHasAll([
            'kpis', 'statusDistribution', 'byDepartment',
            'topDropshippers', 'topProducts', 'carrierPerformance',
            'walletSummary', 'settlementSummary', 'dailySeries',
        ]);
    }

    public function test_reports_daily_series_api_returns_json(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('reports.api.daily', ['days' => 7]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'date',
                    'orders_count',
                    'volume_bob',
                    'fees_bob',
                    'delivered_count',
                ]
            ]);
    }

    public function test_reports_kpis_api_returns_json(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('reports.api.kpis', ['period' => '30d']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'kpis' => [
                    'total_orders',
                    'delivered_orders',
                    'delivery_rate',
                    'total_volume_bob',
                ],
                'wallet',
                'settlements',
                'by_department',
                'carrier_performance',
            ]);
    }
}
