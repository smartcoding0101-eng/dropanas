<?php

namespace App\Services\Finance;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletTopupService
{
    /**
     * Registra una nueva solicitud de recarga de saldo por parte de un vendedor o proveedor.
     */
    public function requestTopup(
        User $user,
        float $amount,
        string $paymentMethod = 'qr_simple',
        ?string $bankName = null,
        ?string $bankReference = null,
        ?UploadedFile $receiptFile = null,
        ?string $notes = null
    ): WalletTopup {
        if ($amount <= 0) {
            throw new RuntimeException('El monto de la recarga debe ser superior a 0 Bs.');
        }

        $wallet = Wallet::firstOrCreate(
            ['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        $receiptPath = null;
        if ($receiptFile) {
            $receiptPath = $receiptFile->store("topups/{$user->tenant_id}", 'public');
        }

        return WalletTopup::create([
            'tenant_id'       => $user->tenant_id,
            'user_id'         => $user->id,
            'wallet_id'       => $wallet->id,
            'topup_number'    => WalletTopup::generateTopupNumber(),
            'amount_bob'      => $amount,
            'payment_method'  => $paymentMethod,
            'bank_name'       => $bankName,
            'bank_reference'  => $bankReference,
            'receipt_path'    => $receiptPath,
            'status'          => WalletTopup::STATUS_PENDING,
            'admin_notes'     => $notes,
        ]);
    }

    /**
     * Aprueba la recarga, acreditando los fondos en la billetera virtual en tiempo real (Ledger Auditable).
     */
    public function approveTopup(WalletTopup $topup, User $admin, ?string $adminNotes = null): WalletTopup
    {
        return DB::transaction(function () use ($topup, $admin, $adminNotes) {
            $freshTopup = WalletTopup::where('id', $topup->id)->lockForUpdate()->firstOrFail();

            if ($freshTopup->status !== WalletTopup::STATUS_PENDING) {
                throw new RuntimeException('Esta solicitud de recarga ya fue procesada anteriormente.');
            }

            $wallet = Wallet::where('id', $freshTopup->wallet_id)->lockForUpdate()->firstOrFail();

            // 1. Acreditar saldo en la billetera
            $wallet->balance_available += (float) $freshTopup->amount_bob;
            $wallet->save();

            // 2. Registrar en el libro mayor de transacciones (Ledger)
            WalletTransaction::create([
                'tenant_id'        => $freshTopup->tenant_id,
                'wallet_id'        => $wallet->id,
                'transaction_type' => 'wallet_topup',
                'amount'           => $freshTopup->amount_bob,
                'balance_after'    => $wallet->balance_available,
                'reference_type'   => 'wallet_topup',
                'reference_id'     => $freshTopup->id,
                'description'      => "Recarga de saldo aprobada (#{$freshTopup->topup_number}) — {$freshTopup->bank_name} Ref: {$freshTopup->bank_reference}",
                'notes'            => $adminNotes ?? 'Aprobado por Administración Dropana',
            ]);

            // 3. Actualizar estado de la solicitud
            $freshTopup->update([
                'status'         => WalletTopup::STATUS_APPROVED,
                'approved_by_id' => $admin->id,
                'approved_at'    => now(),
                'admin_notes'    => $adminNotes ?? $freshTopup->admin_notes,
            ]);

            return $freshTopup;
        });
    }

    /**
     * Rechaza una solicitud de recarga con motivo.
     */
    public function rejectTopup(WalletTopup $topup, User $admin, string $reason): WalletTopup
    {
        return DB::transaction(function () use ($topup, $admin, $reason) {
            $freshTopup = WalletTopup::where('id', $topup->id)->lockForUpdate()->firstOrFail();

            if ($freshTopup->status !== WalletTopup::STATUS_PENDING) {
                throw new RuntimeException('Esta solicitud de recarga ya fue procesada anteriormente.');
            }

            $freshTopup->update([
                'status'         => WalletTopup::STATUS_REJECTED,
                'approved_by_id' => $admin->id,
                'rejected_at'    => now(),
                'admin_notes'    => $reason,
            ]);

            return $freshTopup;
        });
    }
}
