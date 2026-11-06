<?php

namespace Tests\Feature;

use App\Models\BankSettlementBatch;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Finance\BankSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankSettlementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Tenant compartido
        $this->tenant = Tenant::factory()->create();

        // Admin del tenant
        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'admin',
        ]);

        // Dropshipper en el mismo tenant con su wallet
        $dropshipper = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'dropshipper',
        ]);

        $this->wallet = Wallet::factory()->create([
            'tenant_id'         => $this->tenant->id,
            'user_id'           => $dropshipper->id,
            'currency'          => 'BOB',
            'balance_available' => 1000.00,
            'balance_pending'   => 0.00,
            'balance_withdrawn' => 0.00,
        ]);

        $this->actingAs($this->admin);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function createPendingWithdrawals(int $count = 3, float $amount = 200.00): void
    {
        WalletTransaction::factory()->count($count)->create([
            'tenant_id'               => $this->tenant->id,
            'wallet_id'               => $this->wallet->id,
            'transaction_type'        => 'withdrawal',
            'amount'                  => -$amount,
            'balance_after'           => 0,
            'bank_settlement_batch_id'=> null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Crear lote exitosamente
    // ─────────────────────────────────────────────────────────────────────────
    public function test_create_batch_groups_pending_withdrawals(): void
    {
        $this->createPendingWithdrawals(3, 300.00);

        $service = app(BankSettlementService::class);
        $batch   = $service->createBatch($this->tenant->id, 'BNB', $this->admin);

        $this->assertDatabaseHas('bank_settlement_batches', [
            'id'                      => $batch->id,
            'bank_name'               => 'BNB',
            'status'                  => BankSettlementBatch::STATUS_PENDING,
            'total_withdrawals_count' => 3,
            'total_amount_bob'        => 900.00,
        ]);

        // Verificar que las transacciones están vinculadas
        $this->assertEquals(3, WalletTransaction::where('bank_settlement_batch_id', $batch->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Error: sin retiros pendientes
    // ─────────────────────────────────────────────────────────────────────────
    public function test_create_batch_throws_when_no_pending_withdrawals(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/No hay retiros pendientes/');

        app(BankSettlementService::class)->createBatch($this->tenant->id, 'BNB', $this->admin);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Número de lote único y con formato correcto
    // ─────────────────────────────────────────────────────────────────────────
    public function test_batch_number_has_correct_format(): void
    {
        $this->createPendingWithdrawals(1);

        $service = app(BankSettlementService::class);
        $batch   = $service->createBatch($this->tenant->id, 'QR Simple', $this->admin);

        $this->assertMatchesRegularExpression('/^LOT-QRS-\d{8}-\d{3}$/', $batch->batch_number);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Marcar como en proceso
    // ─────────────────────────────────────────────────────────────────────────
    public function test_mark_batch_as_processing(): void
    {
        $this->createPendingWithdrawals(2);

        $service = app(BankSettlementService::class);
        $batch   = $service->createBatch($this->tenant->id, 'BCP', $this->admin);
        $updated = $service->markAsProcessing($batch);

        $this->assertEquals(BankSettlementBatch::STATUS_PROCESSING, $updated->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Completar lote con referencia bancaria
    // ─────────────────────────────────────────────────────────────────────────
    public function test_complete_batch_sets_reference_and_timestamp(): void
    {
        $this->createPendingWithdrawals(2);

        $service = app(BankSettlementService::class);
        $batch   = $service->createBatch($this->tenant->id, 'Banco Unión', $this->admin);
        $service->markAsProcessing($batch);
        $completed = $service->completeBatch($batch->fresh(), 'BUN-TRX-2026-99999');

        $this->assertEquals(BankSettlementBatch::STATUS_COMPLETED, $completed->status);
        $this->assertEquals('BUN-TRX-2026-99999', $completed->bank_reference_number);
        $this->assertNotNull($completed->completed_at);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Rechazar lote libera las transacciones
    // ─────────────────────────────────────────────────────────────────────────
    public function test_reject_batch_releases_transactions(): void
    {
        $this->createPendingWithdrawals(3);

        $service  = app(BankSettlementService::class);
        $batch    = $service->createBatch($this->tenant->id, 'BNB', $this->admin);
        $batchId  = $batch->id;

        // Todas vinculadas
        $this->assertEquals(3, WalletTransaction::where('bank_settlement_batch_id', $batchId)->count());

        $service->rejectBatch($batch, 'Error en cuenta bancaria');

        $this->assertEquals(BankSettlementBatch::STATUS_REJECTED, $batch->fresh()->status);
        // Liberadas
        $this->assertEquals(0, WalletTransaction::where('bank_settlement_batch_id', $batchId)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Summary retorna métricas correctas
    // ─────────────────────────────────────────────────────────────────────────
    public function test_summary_returns_correct_metrics(): void
    {
        $this->createPendingWithdrawals(2, 500.00);

        $service = app(BankSettlementService::class);
        $batch   = $service->createBatch($this->tenant->id, 'BNB', $this->admin);
        $service->markAsProcessing($batch);
        $service->completeBatch($batch->fresh(), 'REF-001');

        // Nuevo batch pendiente
        $this->createPendingWithdrawals(1, 200.00);
        $service->createBatch($this->tenant->id, 'QR Simple', $this->admin);

        $summary = $service->getSummary($this->tenant->id);

        $this->assertEquals(2, $summary['total_batches']);
        $this->assertEquals(1, $summary['completed_batches']);
        $this->assertEquals(1, $summary['pending_batches']);
        $this->assertEquals(1000.00, $summary['total_liquidated_bob']);
        $this->assertEquals(200.00, $summary['pending_amount_bob']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. Rutas web: índice accesible
    // ─────────────────────────────────────────────────────────────────────────
    public function test_settlements_index_route_returns_200(): void
    {
        $response = $this->get(route('settlements.index'));
        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. Crear lote vía HTTP POST
    // ─────────────────────────────────────────────────────────────────────────
    public function test_store_batch_via_http(): void
    {
        $this->createPendingWithdrawals(2);

        $response = $this->post(route('settlements.store'), [
            'bank_name' => 'BNB',
            'notes'     => 'Liquidación de prueba',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('bank_settlement_batches', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10. No se puede crear lote con banco inválido
    // ─────────────────────────────────────────────────────────────────────────
    public function test_store_batch_fails_with_invalid_bank(): void
    {
        $this->createPendingWithdrawals(1);

        $response = $this->post(route('settlements.store'), [
            'bank_name' => 'Banco Inexistente',
        ]);

        $response->assertSessionHasErrors('bank_name');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 11. Detalle del lote accesible y muestra transacciones
    // ─────────────────────────────────────────────────────────────────────────
    public function test_show_batch_displays_transactions(): void
    {
        $this->createPendingWithdrawals(2);

        $service = app(BankSettlementService::class);
        $batch   = $service->createBatch($this->tenant->id, 'BNB', $this->admin);

        $response = $this->get(route('settlements.show', $batch->id));
        $response->assertStatus(200);
        $response->assertSee($batch->batch_number);
    }
}
