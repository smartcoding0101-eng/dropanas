<?php

namespace App\Services\Finance;

use App\Mail\WalletWithdrawalRequestedMail;
use App\Models\FinancialSetting;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletWithdrawal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class WalletWithdrawalService
{
    /**
     * Dropshipper o Proveedor solicita retiro de efectivo.
     * El saldo se congela de balance_available a balance_pending inmediatamente por seguridad.
     */
    public function requestWithdrawal(
        User $user,
        float $amount,
        array $payoutData,
        ?UploadedFile $qrFile = null
    ): WalletWithdrawal {
        if ($amount < 10) {
            throw new RuntimeException('El monto mínimo para retiro de fondos es de Bs. 10.00.');
        }

        return DB::transaction(function () use ($user, $amount, $payoutData, $qrFile) {
            $wallet = Wallet::where('user_id', $user->id)
                ->where('tenant_id', $user->tenant_id)
                ->where('currency', 'BOB')
                ->lockForUpdate()
                ->first();

            if (!$wallet || (float) $wallet->balance_available < $amount) {
                $available = $wallet ? $wallet->balance_available : 0.00;
                throw new RuntimeException("Saldo insuficiente para retiro. Disponible: Bs. " . number_format((float) $available, 2) . ", Solicitado: Bs. " . number_format($amount, 2));
            }

            // 1. Congelar saldo: Deduce de balance_available y lo coloca en balance_pending
            $wallet->balance_available -= $amount;
            $wallet->balance_pending += $amount;
            $wallet->save();

            // 2. Guardar imagen de QR si fue adjuntada
            $qrPath = null;
            if ($qrFile) {
                $qrPath = $qrFile->store("withdrawals/{$user->tenant_id}/qr", 'public');
            }

            // 3. Crear registro de retiro
            $withdrawal = WalletWithdrawal::create([
                'tenant_id'         => $user->tenant_id,
                'user_id'           => $user->id,
                'wallet_id'         => $wallet->id,
                'withdrawal_number' => WalletWithdrawal::generateWithdrawalNumber(),
                'amount_bob'        => $amount,
                'payout_method'     => $payoutData['payout_method'] ?? 'qr_simple',
                'beneficiary_name'  => $payoutData['beneficiary_name'] ?? $user->name,
                'document_id'       => $payoutData['document_id'] ?? null,
                'bank_name'         => $payoutData['bank_name'] ?? null,
                'account_number'    => $payoutData['account_number'] ?? null,
                'binance_pay_id'    => $payoutData['binance_pay_id'] ?? null,
                'qr_code_path'      => $qrPath,
                'status'            => WalletWithdrawal::STATUS_PENDING,
            ]);

            // 4. Registrar en el libro contable (Ledger)
            WalletTransaction::create([
                'tenant_id'        => $user->tenant_id,
                'wallet_id'        => $wallet->id,
                'transaction_type' => 'withdrawal',
                'amount'           => -$amount,
                'balance_after'    => $wallet->balance_available,
                'reference_type'   => 'withdrawal_request',
                'reference_id'     => $withdrawal->id,
                'description'      => "Solicitud de retiro #{$withdrawal->withdrawal_number} en proceso (Saldo congelado)",
                'notes'            => "Destino: {$withdrawal->bank_name} - Cuenta/QR: " . ($withdrawal->account_number ?? $withdrawal->binance_pay_id ?? 'QR Adjunto'),
                'metadata'         => [
                    'withdrawal_id'     => $withdrawal->id,
                    'withdrawal_number' => $withdrawal->withdrawal_number,
                    'payout_method'     => $withdrawal->payout_method,
                    'qr_code_path'      => $qrPath,
                ],
            ]);

            // 5. Notificar al Administrador por Correo Electrónico
            $this->notifyAdminOfWithdrawalRequest($withdrawal, $user);

            return $withdrawal;
        });
    }

    /**
     * Envía correo al Administrador alertando sobre la solicitud de desembolso y su QR adjunto.
     */
    protected function notifyAdminOfWithdrawalRequest(WalletWithdrawal $withdrawal, User $user): void
    {
        try {
            $settings = FinancialSetting::forTenant($user->tenant_id);
            $adminEmail = $settings->getEffectiveAdminEmail();

            if (!empty($adminEmail)) {
                Mail::to($adminEmail)->send(new WalletWithdrawalRequestedMail($withdrawal, $adminEmail));

                NotificationLog::create([
                    'tenant_id'      => $user->tenant_id,
                    'user_id'        => $user->id,
                    'channel'        => 'email',
                    'event_type'     => 'wallet_withdrawal_requested',
                    'recipient'      => $adminEmail,
                    'subject'        => "💸 Solicitud de Retiro #{$withdrawal->withdrawal_number} - Bs. " . number_format((float) $withdrawal->amount_bob, 2),
                    'body'           => "El vendedor {$user->name} solicitó un desembolso de Bs. {$withdrawal->amount_bob}. Banco: {$withdrawal->bank_name}, Beneficiario: {$withdrawal->beneficiary_name}",
                    'status'         => 'sent',
                    'reference_type' => 'wallet_withdrawal',
                    'reference_id'   => $withdrawal->id,
                    'metadata'       => [
                        'withdrawal_id'     => $withdrawal->id,
                        'withdrawal_number' => $withdrawal->withdrawal_number,
                        'amount_bob'        => $withdrawal->amount_bob,
                        'qr_code_path'      => $withdrawal->qr_code_path,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudo enviar el correo de solicitud de retiro #{$withdrawal->withdrawal_number} al admin: " . $e->getMessage());
        }
    }

    /**
     * Administrador confirma y liquida la transferencia al dropshipper (Desembolso completado).
     */
    public function approveWithdrawal(
        WalletWithdrawal $withdrawal,
        User $admin,
        string $transferReference,
        ?string $adminNotes = null,
        ?UploadedFile $receiptFile = null
    ): WalletWithdrawal {
        return DB::transaction(function () use ($withdrawal, $admin, $transferReference, $adminNotes, $receiptFile) {
            $fresh = WalletWithdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status !== WalletWithdrawal::STATUS_PENDING) {
                throw new RuntimeException("Esta solicitud de retiro ya fue procesada anteriormente con estado: {$fresh->status}.");
            }

            $wallet = Wallet::where('id', $fresh->wallet_id)->lockForUpdate()->firstOrFail();

            // 1. Mover saldo congelado a efectivamente retirado
            $wallet->balance_pending = max(0, (float) $wallet->balance_pending - (float) $fresh->amount_bob);
            $wallet->balance_withdrawn = (float) $wallet->balance_withdrawn + (float) $fresh->amount_bob;
            $wallet->save();

            // 2. Guardar comprobante de transferencia bancaria si se adjuntó
            $receiptPath = null;
            if ($receiptFile) {
                $receiptPath = $receiptFile->store("withdrawals/{$fresh->tenant_id}/receipts", 'public');
            }

            // 3. Actualizar registro del retiro
            $fresh->update([
                'status'                 => WalletWithdrawal::STATUS_COMPLETED,
                'transfer_reference'     => $transferReference,
                'admin_notes'            => $adminNotes,
                'transfer_receipt_path'  => $receiptPath ?? $fresh->transfer_receipt_path,
                'processed_by_id'        => $admin->id,
                'processed_at'           => now(),
            ]);

            // 4. Registrar en Ledger
            WalletTransaction::create([
                'tenant_id'        => $fresh->tenant_id,
                'wallet_id'        => $wallet->id,
                'transaction_type' => 'withdrawal',
                'amount'           => 0, // El débito financiero ya ocurrió al congelarse
                'balance_after'    => $wallet->balance_available,
                'reference_type'   => 'withdrawal_completed',
                'reference_id'     => $fresh->id,
                'description'      => "Desembolso completado de Retiro #{$fresh->withdrawal_number} (Ref: {$transferReference})",
                'notes'            => "Transferido por {$admin->name}. " . ($adminNotes ?? ''),
                'metadata'         => [
                    'withdrawal_id'      => $fresh->id,
                    'transfer_reference' => $transferReference,
                    'receipt_path'       => $receiptPath,
                ],
            ]);

            return $fresh;
        });
    }

    /**
     * Administrador rechaza la solicitud de retiro.
     * SEGURIDAD CRÍTICA: Reembolsa de inmediato los fondos congelados al balance disponible del usuario.
     */
    public function rejectWithdrawal(
        WalletWithdrawal $withdrawal,
        User $admin,
        string $reason
    ): WalletWithdrawal {
        return DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $fresh = WalletWithdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status !== WalletWithdrawal::STATUS_PENDING) {
                throw new RuntimeException("Esta solicitud de retiro ya fue procesada anteriormente con estado: {$fresh->status}.");
            }

            $wallet = Wallet::where('id', $fresh->wallet_id)->lockForUpdate()->firstOrFail();

            // 1. Reembolso inmediato al balance disponible
            $wallet->balance_pending = max(0, (float) $wallet->balance_pending - (float) $fresh->amount_bob);
            $wallet->balance_available = (float) $wallet->balance_available + (float) $fresh->amount_bob;
            $wallet->save();

            // 2. Marcar como rechazado
            $fresh->update([
                'status'          => WalletWithdrawal::STATUS_REJECTED,
                'admin_notes'     => $reason,
                'processed_by_id' => $admin->id,
                'rejected_at'     => now(),
            ]);

            // 3. Registrar transacción de reembolso en Ledger
            WalletTransaction::create([
                'tenant_id'        => $fresh->tenant_id,
                'wallet_id'        => $wallet->id,
                'transaction_type' => 'refund',
                'amount'           => (float) $fresh->amount_bob,
                'balance_after'    => $wallet->balance_available,
                'reference_type'   => 'withdrawal_rejected',
                'reference_id'     => $fresh->id,
                'description'      => "Reembolso automático por Retiro rechazado #{$fresh->withdrawal_number}",
                'notes'            => "Motivo del rechazo: {$reason}",
                'metadata'         => [
                    'withdrawal_id' => $fresh->id,
                    'rejected_by'   => $admin->id,
                    'reason'        => $reason,
                ],
            ]);

            return $fresh;
        });
    }
}
