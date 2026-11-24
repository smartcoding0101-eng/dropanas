<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StressSimulationCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_stress_test_command_executes_successfully(): void
    {
        $this->artisan('system:stress-test', [
            '--orders'       => 50,
            '--settle-ratio' => 0.40,
        ])
        ->expectsOutputToContain('[STRESS TEST PASSED]')
        ->assertSuccessful();

        $this->assertGreaterThanOrEqual(50, Order::count());
        $this->assertGreaterThanOrEqual(1, Order::where('status', 'settled')->count());

        $this->artisan('audit:reconcile-ledgers')
            ->expectsOutputToContain('Zero Drift')
            ->assertSuccessful();
    }
}
