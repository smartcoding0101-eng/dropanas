<?php

namespace Tests\Feature;

use App\Mail\WalletTopupRequestedMail;
use App\Models\FinancialSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTopupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $dropshipper;

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
            'name'      => 'Admin Dropana',
            'email'     => 'admin.fintech@dropana.bo',
            'password'  => bcrypt('password123'),
            'role'      => 'tenant_admin',
            'is_active' => true,
        ]);

        $this->dropshipper = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Vendedor Top Bolivia',
            'email'     => 'vendedor.top@dropana.bo',
            'password'  => bcrypt('password123'),
            'role'      => 'dropshipper',
            'is_active' => true,
        ]);

        Wallet::create([
            'tenant_id'         => $this->tenant->id,
            'user_id'           => $this->dropshipper->id,
            'currency'          => 'BOB',
            'balance_available' => 100.00,
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
            'admin_notification_email' => 'finanzas.notificaciones@dropana.bo',
            'recharge_instructions'    => 'Paga vía QR Simple oficial y sube comprobante.',
        ]);
    }

    public function test_guest_cannot_access_admin_recargas(): void
    {
        $response = $this->get('/admin/recargas');
        $response->assertRedirect('/login');
    }

    public function test_dropshipper_cannot_access_admin_recargas(): void
    {
        $response = $this->actingAs($this->dropshipper)->get('/admin/recargas');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_recargas_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/recargas');

        $response->assertStatus(200);
        $response->assertSee('Historial de Solicitudes de Recarga');
        $response->assertSee('Configurar QR & Correo Admin', false);
        $response->assertSee('finanzas.notificaciones@dropana.bo');
    }

    public function test_dropshipper_can_submit_topup_with_pdf_receipt_and_admin_is_notified(): void
    {
        Mail::fake();
        Storage::fake('public');

        $pdfFile = UploadedFile::fake()->create('comprobante_bancario_oficial.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->dropshipper)->post('/wallet/topups', [
            'amount_bob'     => '750.50',
            'payment_method' => 'qr_simple',
            'bank_name'      => 'Banco BCP QR Simple',
            'bank_reference' => 'REF-BCP-987654321',
            'receipt'        => $pdfFile,
            'notes'          => 'Recarga capital de trabajo para pedidos COD',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('wallet_topups', [
            'tenant_id'      => $this->tenant->id,
            'user_id'        => $this->dropshipper->id,
            'amount_bob'     => 750.50,
            'payment_method' => 'qr_simple',
            'bank_reference' => 'REF-BCP-987654321',
            'status'         => 'pending',
        ]);

        // Verificar que se envió el correo al email configurable del administrador
        Mail::assertSent(WalletTopupRequestedMail::class, function ($mail) {
            return $mail->hasTo('finanzas.notificaciones@dropana.bo') &&
                   $mail->topup->amount_bob == 750.50 &&
                   $mail->topup->topup_number !== null;
        });

        // Verificar que se registró la auditoría en notification_logs
        $this->assertDatabaseHas('notification_logs', [
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->dropshipper->id,
            'channel'    => 'email',
            'event_type' => 'wallet_topup_requested',
            'recipient'  => 'finanzas.notificaciones@dropana.bo',
            'status'     => 'sent',
        ]);
    }

    public function test_admin_can_approve_topup_and_balance_is_credited_in_real_time(): void
    {
        $topup = WalletTopup::create([
            'tenant_id'      => $this->tenant->id,
            'user_id'        => $this->dropshipper->id,
            'wallet_id'      => Wallet::where('user_id', $this->dropshipper->id)->first()->id,
            'topup_number'   => 'REC-TEST-99999',
            'amount_bob'     => 500.00,
            'payment_method' => 'qr_simple',
            'bank_name'      => 'Banco Nacional de Bolivia',
            'bank_reference' => 'BNB-TRF-12345',
            'status'         => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/recargas/{$topup->id}/approve", [
            'notes' => 'Aprobado y verificado en extracto BNB',
        ]);

        $response->assertSessionHas('success');

        $topup->refresh();
        $this->assertEquals('approved', $topup->status);
        $this->assertNotNull($topup->approved_at);
        $this->assertEquals($this->admin->id, $topup->approved_by_id);

        // Saldo inicial 100 + 500 recarga = 600
        $wallet = Wallet::where('user_id', $this->dropshipper->id)->first();
        $this->assertEquals(600.00, (float) $wallet->balance_available);

        // Partida contable en WalletTransaction
        $this->assertDatabaseHas('wallet_transactions', [
            'tenant_id'        => $this->tenant->id,
            'wallet_id'        => $wallet->id,
            'transaction_type' => 'wallet_topup',
            'amount'           => 500.00,
            'balance_after'    => 600.00,
            'reference_id'     => $topup->id,
        ]);
    }

    public function test_admin_can_reject_topup_with_reason(): void
    {
        $topup = WalletTopup::create([
            'tenant_id'      => $this->tenant->id,
            'user_id'        => $this->dropshipper->id,
            'wallet_id'      => Wallet::where('user_id', $this->dropshipper->id)->first()->id,
            'topup_number'   => 'REC-TEST-REJECT',
            'amount_bob'     => 300.00,
            'payment_method' => 'bank_transfer',
            'bank_reference' => 'FAKE-REF-000',
            'status'         => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/recargas/{$topup->id}/reject", [
            'reason' => 'Referencia no encontrada en extracto bancario BNB',
        ]);

        $response->assertSessionHas('status');

        $topup->refresh();
        $this->assertEquals('rejected', $topup->status);
        $this->assertNotNull($topup->rejected_at);
        $this->assertEquals('Referencia no encontrada en extracto bancario BNB', $topup->admin_notes);

        // Saldo no debe haberse modificado
        $wallet = Wallet::where('user_id', $this->dropshipper->id)->first();
        $this->assertEquals(100.00, (float) $wallet->balance_available);
    }

    public function test_admin_can_update_accreditation_settings_and_qr_code(): void
    {
        Storage::fake('public');

        $qrImage = UploadedFile::fake()->image('mi_qr_simple_oficial.png', 400, 400);

        $response = $this->actingAs($this->admin)->post('/admin/recargas/settings', [
            'admin_notification_email' => 'nuevo.admin.finanzas@dropana.bo',
            'bank_name'                => 'Banco Bisa S.A.',
            'bank_account_number'      => '555-998877-BOB',
            'bank_account_name'        => 'Dropana FinTech Logística Bolivia S.R.L.',
            'recharge_instructions'    => 'Pagar mediante QR Simple Bisa.',
            'qr_image'                 => $qrImage,
        ]);

        $response->assertSessionHas('success');

        $settings = FinancialSetting::forTenant($this->tenant->id);
        $this->assertEquals('nuevo.admin.finanzas@dropana.bo', $settings->admin_notification_email);
        $this->assertEquals('Banco Bisa S.A.', $settings->bank_name);
        $this->assertEquals('555-998877-BOB', $settings->bank_account_number);
        $this->assertNotNull($settings->qr_deposit_image_url);

        Storage::disk('public')->assertExists($settings->qr_deposit_image_url);
    }
}
