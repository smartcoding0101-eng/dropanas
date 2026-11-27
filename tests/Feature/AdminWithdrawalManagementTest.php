<?php

namespace Tests\Feature;

use App\Mail\WalletWithdrawalRequestedMail;
use App\Models\FinancialSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletWithdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWithdrawalManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $dropshipper;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::first() ?? Tenant::create([
            'id'           => '01a1f5b7-11c2-7350-89ce-f96f436c8ceb',
            'code'         => 'DROPANA_BO_TEST',
            'company_name' => 'Dropana Bolivia COD Test S.R.L.',
            'legal_nit'    => '1029384756',
            'domain'       => 'dropana.test',
            'is_active'    => true,
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Finanzas Dropana',
            'email'     => 'admin.fintech@dropana.bo',
            'password'  => bcrypt('password123'),
            'role'      => 'tenant_admin',
            'is_active' => true,
        ]);

        $this->dropshipper = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Alejandro Soliz Dropshipper',
            'email'     => 'alejandro.dropshipper@bolidrop.bo',
            'password'  => bcrypt('password123'),
            'role'      => 'dropshipper',
            'is_active' => true,
        ]);

        $this->wallet = Wallet::create([
            'tenant_id'         => $this->tenant->id,
            'user_id'           => $this->dropshipper->id,
            'currency'          => 'BOB',
            'balance_available' => 1000.00,
            'balance_pending'   => 0.00,
            'balance_withdrawn' => 0.00,
        ]);

        FinancialSetting::create([
            'tenant_id'                => $this->tenant->id,
            'seller_commission_bob'    => 5.00,
            'provider_commission_bob'  => 5.00,
            'bank_account_name'        => 'Dropana Bolivia S.R.L.',
            'bank_name'                => 'Banco Nacional de Bolivia / BCP (QR Simple)',
            'bank_account_number'      => '1000-482910-BOB',
            'admin_notification_email' => 'finanzas.admin@dropana.bo',
            'recharge_instructions'    => 'Paga vía QR Simple oficial y sube comprobante.',
        ]);
    }

    public function test_dropshipper_can_submit_withdrawal_with_qr_freezes_balance_and_notifies_admin(): void
    {
        Mail::fake();
        Storage::fake('public');

        $qrImage = UploadedFile::fake()->image('mi_qr_cobro_bcp.png', 400, 400);

        $response = $this->actingAs($this->dropshipper)->post('/wallet/withdraw', [
            'amount'           => '350.00',
            'payout_method'    => 'qr_simple',
            'beneficiary_name' => 'Alejandro Soliz',
            'document_id'      => '7891234 SCZ',
            'bank_name'        => 'Banco Nacional de Bolivia (BNB)',
            'account_number'   => '150-098765-01',
            'qr_code'          => $qrImage,
        ]);

        $response->assertSessionHas('success');

        // 1. Registro en base de datos
        $this->assertDatabaseHas('wallet_withdrawals', [
            'tenant_id'        => $this->tenant->id,
            'user_id'          => $this->dropshipper->id,
            'amount_bob'       => 350.00,
            'payout_method'    => 'qr_simple',
            'beneficiary_name' => 'Alejandro Soliz',
            'bank_name'        => 'Banco Nacional de Bolivia (BNB)',
            'status'           => 'pending',
        ]);

        $withdrawal = WalletWithdrawal::where('user_id', $this->dropshipper->id)->first();
        $this->assertNotNull($withdrawal->qr_code_path);
        Storage::disk('public')->assertExists($withdrawal->qr_code_path);

        // 2. SEGURIDAD: Balance disponible debitado y congelado en balance_pending
        $this->wallet->refresh();
        $this->assertEquals(650.00, (float) $this->wallet->balance_available);
        $this->assertEquals(350.00, (float) $this->wallet->balance_pending);
        $this->assertEquals(0.00, (float) $this->wallet->balance_withdrawn);

        // 3. Ledger de transacción auditable
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id'        => $this->wallet->id,
            'transaction_type' => 'withdrawal',
            'amount'           => -350.00,
            'balance_after'    => 650.00,
            'reference_type'   => 'withdrawal_request',
        ]);

        // 4. Email enviado al correo oficial del administrador con el QR
        Mail::assertSent(WalletWithdrawalRequestedMail::class, function ($mail) {
            return $mail->hasTo('finanzas.admin@dropana.bo') &&
                   $mail->withdrawal->amount_bob == 350.00 &&
                   $mail->withdrawal->beneficiary_name === 'Alejandro Soliz';
        });
    }

    public function test_dropshipper_cannot_withdraw_more_than_available_balance(): void
    {
        $response = $this->actingAs($this->dropshipper)->post('/wallet/withdraw', [
            'amount'           => '1500.00', // Excede los 1000 disponibles
            'payout_method'    => 'bank_transfer',
            'beneficiary_name' => 'Alejandro Soliz',
            'bank_name'        => 'BCP',
            'account_number'   => '201-5049382',
        ]);

        $response->assertSessionHasErrors('error');

        $this->wallet->refresh();
        $this->assertEquals(1000.00, (float) $this->wallet->balance_available);
        $this->assertEquals(0.00, (float) $this->wallet->balance_pending);
    }

    public function test_admin_can_view_withdrawals_tab(): void
    {
        WalletWithdrawal::create([
            'tenant_id'         => $this->tenant->id,
            'user_id'           => $this->dropshipper->id,
            'wallet_id'         => $this->wallet->id,
            'withdrawal_number' => 'RET-20261127-TEST',
            'amount_bob'        => 400.00,
            'payout_method'     => 'qr_simple',
            'beneficiary_name'  => 'Alejandro Soliz',
            'document_id'       => '7891234 SCZ',
            'bank_name'         => 'Banco BCP',
            'account_number'    => '7891234',
            'status'            => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/recargas?tab=withdrawals');

        $response->assertStatus(200);
        $response->assertSee('Solicitudes de Retiro (Desembolsos)');
        $response->assertSee('RET-20261127-TEST');
        $response->assertSee('Alejandro Soliz');
        $response->assertSee('Bs. 400.00');
        $response->assertSee('Pendientes por Transferir');
    }

    public function test_admin_can_approve_withdrawal_and_balance_is_settled(): void
    {
        // 1. Simular retiro solicitado y saldo congelado
        $this->wallet->update([
            'balance_available' => 600.00,
            'balance_pending'   => 400.00,
        ]);

        $withdrawal = WalletWithdrawal::create([
            'tenant_id'         => $this->tenant->id,
            'user_id'           => $this->dropshipper->id,
            'wallet_id'         => $this->wallet->id,
            'withdrawal_number' => 'RET-20261127-0099',
            'amount_bob'        => 400.00,
            'payout_method'     => 'qr_simple',
            'beneficiary_name'  => 'Alejandro Soliz',
            'document_id'       => '7891234 SCZ',
            'bank_name'         => 'Banco BCP',
            'account_number'    => '7891234',
            'status'            => 'pending',
        ]);

        // 2. Admin confirma el desembolso
        $response = $this->actingAs($this->admin)->post("/admin/recargas/withdrawals/{$withdrawal->id}/approve", [
            'transfer_reference' => 'TRAN-BCP-8841239',
            'admin_notes'        => 'Transferido exitosamente por QR Simple.',
        ]);

        $response->assertSessionHas('success');

        // 3. Verificaciones de base de datos
        $withdrawal->refresh();
        $this->assertEquals(WalletWithdrawal::STATUS_COMPLETED, $withdrawal->status);
        $this->assertEquals('TRAN-BCP-8841239', $withdrawal->transfer_reference);
        $this->assertEquals($this->admin->id, $withdrawal->processed_by_id);
        $this->assertNotNull($withdrawal->processed_at);

        // 4. Saldo: el congelado pasa a efectivamente retirado
        $this->wallet->refresh();
        $this->assertEquals(600.00, (float) $this->wallet->balance_available);
        $this->assertEquals(0.00, (float) $this->wallet->balance_pending);
        $this->assertEquals(400.00, (float) $this->wallet->balance_withdrawn);

        // 5. Ledger
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id'      => $this->wallet->id,
            'reference_type' => 'withdrawal_completed',
            'reference_id'   => $withdrawal->id,
        ]);
    }

    public function test_admin_can_reject_withdrawal_and_balance_is_immediately_refunded(): void
    {
        // 1. Simular retiro solicitado y saldo congelado
        $this->wallet->update([
            'balance_available' => 750.00,
            'balance_pending'   => 250.00,
        ]);

        $withdrawal = WalletWithdrawal::create([
            'tenant_id'         => $this->tenant->id,
            'user_id'           => $this->dropshipper->id,
            'wallet_id'         => $this->wallet->id,
            'withdrawal_number' => 'RET-20261127-0100',
            'amount_bob'        => 250.00,
            'payout_method'     => 'qr_simple',
            'beneficiary_name'  => 'Alejandro Soliz',
            'document_id'       => '7891234 SCZ',
            'bank_name'         => 'Banco Bisa',
            'status'            => 'pending',
        ]);

        // 2. Admin rechaza la solicitud
        $response = $this->actingAs($this->admin)->post("/admin/recargas/withdrawals/{$withdrawal->id}/reject", [
            'reason' => 'El código QR proporcionado caducó. Por favor solicita de nuevo con un QR sin vencimiento.',
        ]);

        $response->assertSessionHas('status');

        // 3. Verificaciones de retiro
        $withdrawal->refresh();
        $this->assertEquals(WalletWithdrawal::STATUS_REJECTED, $withdrawal->status);
        $this->assertStringContainsString('código QR proporcionado caducó', $withdrawal->admin_notes);
        $this->assertNotNull($withdrawal->rejected_at);

        // 4. SEGURIDAD: Reembolso automático e inmediato al balance disponible
        $this->wallet->refresh();
        $this->assertEquals(1000.00, (float) $this->wallet->balance_available); // 750 + 250 = 1000!
        $this->assertEquals(0.00, (float) $this->wallet->balance_pending);

        // 5. Ledger registra reembolso
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id'        => $this->wallet->id,
            'transaction_type' => 'refund',
            'amount'           => 250.00,
            'balance_after'    => 1000.00,
            'reference_type'   => 'withdrawal_rejected',
            'reference_id'     => $withdrawal->id,
        ]);
    }
}
