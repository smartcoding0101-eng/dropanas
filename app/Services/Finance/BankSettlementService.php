<?php

namespace App\Services\Finance;

use App\Models\BankSettlementBatch;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BankSettlementService
{
    /**
     * Crea un lote de liquidación agrupando retiros pendientes de un banco.
     * Toma todas las WalletTransactions de tipo 'withdrawal' sin batch asignado.
     */
    public function createBatch(
        string $tenantId,
        string $bankName,
        User $processedBy,
        ?string $notes = null
    ): BankSettlementBatch {
        return DB::transaction(function () use ($tenantId, $bankName, $processedBy, $notes) {
            // Buscar retiros pendientes sin batch para este tenant
            $pendingWithdrawals = WalletTransaction::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('transaction_type', ['withdrawal', 'withdrawal_request'])
                ->whereNull('bank_settlement_batch_id')
                ->get();

            if ($pendingWithdrawals->isEmpty()) {
                throw new \RuntimeException('No hay retiros pendientes para liquidar en este banco.');
            }

            $totalAmount = $pendingWithdrawals->sum('amount');

            $batch = BankSettlementBatch::create([
                'tenant_id'               => $tenantId,
                'processed_by_id'         => $processedBy->id,
                'batch_number'            => BankSettlementBatch::generateBatchNumber($bankName),
                'bank_name'               => $bankName,
                'total_withdrawals_count' => $pendingWithdrawals->count(),
                'total_amount_bob'        => abs($totalAmount),
                'status'                  => BankSettlementBatch::STATUS_PENDING,
                'notes'                   => $notes,
            ]);

            // Vincular todas las transacciones al lote
            WalletTransaction::whereIn('id', $pendingWithdrawals->pluck('id'))
                ->update(['bank_settlement_batch_id' => $batch->id]);

            return $batch;
        });
    }

    /**
     * Marca el lote como "En Proceso" (enviado al banco).
     */
    public function markAsProcessing(BankSettlementBatch $batch): BankSettlementBatch
    {
        if (! $batch->isPending()) {
            throw new \RuntimeException('El lote no está en estado pendiente.');
        }

        $batch->update(['status' => BankSettlementBatch::STATUS_PROCESSING]);
        return $batch->fresh();
    }

    /**
     * Completa el lote con número de referencia bancaria y sube el comprobante/voucher.
     */
    public function completeBatch(
        BankSettlementBatch $batch,
        string $bankReferenceNumber,
        ?UploadedFile $receiptFile = null,
        ?string $notes = null
    ): BankSettlementBatch {
        return DB::transaction(function () use ($batch, $bankReferenceNumber, $receiptFile, $notes) {
            $receiptPath = $batch->receipt_file_path;

            if ($receiptFile) {
                // Guardar en storage/app/public/settlements/{tenant_id}/
                $receiptPath = $receiptFile->store(
                    "settlements/{$batch->tenant_id}",
                    'public'
                );
            }

            $batch->update([
                'status'                 => BankSettlementBatch::STATUS_COMPLETED,
                'bank_reference_number'  => $bankReferenceNumber,
                'receipt_file_path'      => $receiptPath,
                'notes'                  => $notes ?? $batch->notes,
                'completed_at'           => now(),
            ]);

            return $batch->fresh();
        });
    }

    /**
     * Rechaza el lote con notas de motivo.
     */
    public function rejectBatch(BankSettlementBatch $batch, string $reason): BankSettlementBatch
    {
        return DB::transaction(function () use ($batch, $reason) {
            $batch->update([
                'status' => BankSettlementBatch::STATUS_REJECTED,
                'notes'  => $reason,
            ]);

            // Liberar las transacciones para que puedan ser reagrupadas
            WalletTransaction::where('bank_settlement_batch_id', $batch->id)
                ->update(['bank_settlement_batch_id' => null]);

            return $batch->fresh();
        });
    }

    /**
     * Resumen estadístico de liquidaciones para un tenant.
     */
    public function getSummary(string $tenantId): array
    {
        $batches = BankSettlementBatch::where('tenant_id', $tenantId)->get();

        return [
            'total_batches'          => $batches->count(),
            'pending_batches'        => $batches->where('status', BankSettlementBatch::STATUS_PENDING)->count(),
            'completed_batches'      => $batches->where('status', BankSettlementBatch::STATUS_COMPLETED)->count(),
            'total_liquidated_bob'   => $batches->where('status', BankSettlementBatch::STATUS_COMPLETED)->sum('total_amount_bob'),
            'pending_amount_bob'     => $batches->whereIn('status', [
                BankSettlementBatch::STATUS_PENDING,
                BankSettlementBatch::STATUS_PROCESSING,
            ])->sum('total_amount_bob'),
            'by_bank'                => $batches->groupBy('bank_name')->map(fn(Collection $g) => [
                'count'       => $g->count(),
                'total_bob'   => $g->sum('total_amount_bob'),
                'completed'   => $g->where('status', BankSettlementBatch::STATUS_COMPLETED)->count(),
            ]),
        ];
    }
}
