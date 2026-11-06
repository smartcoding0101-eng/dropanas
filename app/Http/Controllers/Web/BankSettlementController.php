<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BankSettlementBatch;
use App\Services\Finance\BankSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankSettlementController extends Controller
{
    public function __construct(
        protected BankSettlementService $settlementService
    ) {}

    // ─────────────────────────────────────────
    // Listado de lotes de liquidación
    // ─────────────────────────────────────────
    public function index(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;

        $batches = BankSettlementBatch::where('tenant_id', $tenantId)
            ->with('processedBy')
            ->when($request->bank, fn($q) => $q->where('bank_name', $request->bank))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at')
            ->paginate(20);

        $summary = $this->settlementService->getSummary($tenantId);
        $banks   = BankSettlementBatch::BANKS;
        $statuses= BankSettlementBatch::STATUSES;

        // Contar retiros pendientes (aún sin batch) para mostrar la alerta de acción
        $pendingCount = \App\Models\WalletTransaction::where('tenant_id', $tenantId)
            ->where('transaction_type', 'withdrawal')
            ->whereNull('bank_settlement_batch_id')
            ->count();

        return view('settlements.index', compact(
            'batches', 'summary', 'banks', 'statuses', 'pendingCount'
        ));
    }

    // ─────────────────────────────────────────
    // Detalle de un lote
    // ─────────────────────────────────────────
    public function show(string $id): View
    {
        $batch = BankSettlementBatch::where('tenant_id', auth()->user()->tenant_id)
            ->with(['processedBy', 'walletTransactions.wallet.user'])
            ->findOrFail($id);

        return view('settlements.show', compact('batch'));
    }

    // ─────────────────────────────────────────
    // Crear nuevo lote
    // ─────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'bank_name' => ['required', 'string', 'in:' . implode(',', array_keys(BankSettlementBatch::BANKS))],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $batch = $this->settlementService->createBatch(
                tenantId:    auth()->user()->tenant_id,
                bankName:    $request->bank_name,
                processedBy: auth()->user(),
                notes:       $request->notes,
            );

            return redirect()->route('settlements.show', $batch->id)
                ->with('success', "✅ Lote {$batch->batch_number} creado con {$batch->total_withdrawals_count} retiros por Bs. " . number_format($batch->total_amount_bob, 2));

        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────
    // Marcar como "en proceso" (enviado al banco)
    // ─────────────────────────────────────────
    public function markProcessing(string $id): RedirectResponse
    {
        $batch = BankSettlementBatch::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        try {
            $this->settlementService->markAsProcessing($batch);
            return back()->with('success', "🔄 Lote {$batch->batch_number} marcado como en proceso.");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────
    // Completar lote (subir voucher/QR + N° referencia)
    // ─────────────────────────────────────────
    public function complete(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'bank_reference_number' => ['required', 'string', 'max:100'],
            'receipt_file'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB
            'notes'                 => ['nullable', 'string', 'max:500'],
        ]);

        $batch = BankSettlementBatch::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        try {
            $this->settlementService->completeBatch(
                batch:               $batch,
                bankReferenceNumber: $request->bank_reference_number,
                receiptFile:         $request->file('receipt_file'),
                notes:               $request->notes,
            );

            return redirect()->route('settlements.show', $batch->id)
                ->with('success', "✅ Lote {$batch->batch_number} completado exitosamente. Referencia: {$request->bank_reference_number}");

        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────
    // Rechazar lote
    // ─────────────────────────────────────────
    public function reject(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $batch = BankSettlementBatch::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        try {
            $this->settlementService->rejectBatch($batch, $request->reason);

            return redirect()->route('settlements.index')
                ->with('warning', "⚠️ Lote {$batch->batch_number} rechazado. Los retiros quedan disponibles para un nuevo lote.");

        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
