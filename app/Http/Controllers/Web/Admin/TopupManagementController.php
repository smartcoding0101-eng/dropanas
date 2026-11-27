<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialSetting;
use App\Models\User;
use App\Models\WalletTopup;
use App\Models\WalletWithdrawal;
use App\Services\Finance\WalletTopupService;
use App\Services\Finance\WalletWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TopupManagementController extends Controller
{
    public function __construct(
        protected WalletTopupService $topupService,
        protected WalletWithdrawalService $withdrawalService
    ) {}

    /**
     * Centro de Conciliación y Liquidaciones Dropana:
     * Pestaña 1: Solicitudes de Recarga de Saldo (Entradas)
     * Pestaña 2: Solicitudes de Retiro de Fondos (Salidas / Desembolsos)
     */
    public function index(Request $request): View
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $tenantId = $admin?->tenant_id ?? '01a1f5b7-11c2-7350-89ce-f96f436c8ceb';

        $activeTab = $request->input('tab', 'topups');
        if (!in_array($activeTab, ['topups', 'withdrawals'])) {
            $activeTab = 'topups';
        }

        $statusFilter = $request->input('status', 'all');
        $search = trim((string) $request->input('search', ''));

        // 1. Query para Recargas (Topups)
        $topupQuery = WalletTopup::where('tenant_id', $tenantId)
            ->with(['user', 'wallet', 'approvedBy'])
            ->latest();

        if ($activeTab === 'topups' && $statusFilter !== 'all' && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
            $topupQuery->where('status', $statusFilter);
        }

        if ($activeTab === 'topups' && !empty($search)) {
            $topupQuery->where(function ($q) use ($search) {
                $q->where('topup_number', 'like', "%{$search}%")
                  ->orWhere('bank_reference', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $topups = $topupQuery->paginate(15, ['*'], 'topups_page')->withQueryString();

        $kpis = [
            'total_count'        => WalletTopup::where('tenant_id', $tenantId)->count(),
            'pending_count'      => WalletTopup::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'approved_count'     => WalletTopup::where('tenant_id', $tenantId)->where('status', 'approved')->count(),
            'rejected_count'     => WalletTopup::where('tenant_id', $tenantId)->where('status', 'rejected')->count(),
            'total_approved_bob' => (float) WalletTopup::where('tenant_id', $tenantId)->where('status', 'approved')->sum('amount_bob'),
            'pending_amount_bob' => (float) WalletTopup::where('tenant_id', $tenantId)->where('status', 'pending')->sum('amount_bob'),
        ];

        // 2. Query para Retiros (Withdrawals)
        $withdrawalQuery = WalletWithdrawal::where('tenant_id', $tenantId)
            ->with(['user', 'wallet', 'processedBy'])
            ->latest();

        if ($activeTab === 'withdrawals' && $statusFilter !== 'all' && in_array($statusFilter, ['pending', 'completed', 'rejected'])) {
            $withdrawalQuery->where('status', $statusFilter);
        }

        if ($activeTab === 'withdrawals' && !empty($search)) {
            $withdrawalQuery->where(function ($q) use ($search) {
                $q->where('withdrawal_number', 'like', "%{$search}%")
                  ->orWhere('beneficiary_name', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('transfer_reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $withdrawals = $withdrawalQuery->paginate(15, ['*'], 'withdrawals_page')->withQueryString();

        $withdrawalKpis = [
            'total_count'         => WalletWithdrawal::where('tenant_id', $tenantId)->count(),
            'pending_count'       => WalletWithdrawal::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'completed_count'     => WalletWithdrawal::where('tenant_id', $tenantId)->where('status', 'completed')->count(),
            'rejected_count'      => WalletWithdrawal::where('tenant_id', $tenantId)->where('status', 'rejected')->count(),
            'total_completed_bob' => (float) WalletWithdrawal::where('tenant_id', $tenantId)->where('status', 'completed')->sum('amount_bob'),
            'pending_amount_bob'  => (float) WalletWithdrawal::where('tenant_id', $tenantId)->where('status', 'pending')->sum('amount_bob'),
        ];

        $financialSettings = FinancialSetting::forTenant($tenantId);

        return view('admin.recargas.index', compact(
            'topups',
            'kpis',
            'withdrawals',
            'withdrawalKpis',
            'activeTab',
            'statusFilter',
            'search',
            'financialSettings',
            'admin'
        ));
    }

    /**
     * Admin aprueba la recarga, acreditando los fondos en tiempo real al saldo del vendedor.
     */
    public function approve(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $topup = WalletTopup::where('tenant_id', $admin->tenant_id)->findOrFail($id);

        try {
            $notes = $request->input('notes', 'Transferencia verificada y aprobada por Administración Dropana');
            $this->topupService->approveTopup($topup, $admin, $notes);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Recarga #{$topup->topup_number} por Bs. {$topup->amount_bob} aprobada y acreditada exitosamente.",
                ]);
            }

            return back()->with('success', "Recarga #{$topup->topup_number} por Bs. {$topup->amount_bob} aprobada. Saldo acreditado a {$topup->user?->name}.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin rechaza la solicitud de recarga registrando el motivo.
     */
    public function reject(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $topup = WalletTopup::where('tenant_id', $admin->tenant_id)->findOrFail($id);

        $reason = $request->input('reason', 'Comprobante no válido o depósito no encontrado en extracto bancario.');

        try {
            $this->topupService->rejectTopup($topup, $admin, $reason);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Recarga #{$topup->topup_number} rechazada.",
                ]);
            }

            return back()->with('status', "Recarga #{$topup->topup_number} rechazada: {$reason}");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin confirma el desembolso bancario / QR Simple al vendedor o dropshipper.
     */
    public function approveWithdrawal(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $withdrawal = WalletWithdrawal::where('tenant_id', $admin->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'transfer_reference' => 'required|string|max:100',
            'admin_notes'        => 'nullable|string|max:500',
            'receipt'            => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
        ]);

        try {
            $this->withdrawalService->approveWithdrawal(
                $withdrawal,
                $admin,
                $validated['transfer_reference'],
                $validated['admin_notes'] ?? null,
                $request->file('receipt')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Retiro #{$withdrawal->withdrawal_number} por Bs. " . number_format((float) $withdrawal->amount_bob, 2) . " marcado como pagado exitosamente.",
                ]);
            }

            return back()->with('success', "Desembolso #{$withdrawal->withdrawal_number} por Bs. " . number_format((float) $withdrawal->amount_bob, 2) . " completado correctamente a {$withdrawal->beneficiary_name}.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin rechaza la solicitud de retiro.
     * Reembolsa automáticamente los fondos congelados a la billetera virtual del vendedor.
     */
    public function rejectWithdrawal(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $withdrawal = WalletWithdrawal::where('tenant_id', $admin->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->withdrawalService->rejectWithdrawal($withdrawal, $admin, $validated['reason']);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Retiro #{$withdrawal->withdrawal_number} rechazado. Los fondos (Bs. " . number_format((float) $withdrawal->amount_bob, 2) . ") fueron reembolsados automáticamente al vendedor.",
                ]);
            }

            return back()->with('status', "Retiro #{$withdrawal->withdrawal_number} rechazado. Se devolvieron Bs. " . number_format((float) $withdrawal->amount_bob, 2) . " al saldo disponible de {$withdrawal->user?->name}.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin actualiza los datos oficiales de acreditación: Correo de notificación, banco, cuenta y Código QR.
     */
    public function updateSettings(Request $request): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();

        $validated = $request->validate([
            'admin_notification_email' => 'required|email|max:150',
            'bank_name'                => 'required|string|max:100',
            'bank_account_number'      => 'required|string|max:80',
            'bank_account_name'        => 'required|string|max:150',
            'recharge_instructions'    => 'nullable|string',
            'qr_image'                 => 'nullable|image|max:5120',
        ]);

        $settings = FinancialSetting::forTenant($admin->tenant_id);

        if ($request->hasFile('qr_image')) {
            $settings->qr_deposit_image_url = $request->file('qr_image')->store("settings/{$admin->tenant_id}", 'public');
        }

        $settings->update([
            'admin_notification_email' => $validated['admin_notification_email'],
            'bank_name'                => $validated['bank_name'],
            'bank_account_number'      => $validated['bank_account_number'],
            'bank_account_name'        => $validated['bank_account_name'],
            'recharge_instructions'    => $validated['recharge_instructions'] ?? $settings->recharge_instructions,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Configuración de acreditación bancaria y correo del administrador actualizada correctamente.',
                'data'    => $settings,
            ]);
        }

        return back()->with('success', 'Configuración de acreditación bancaria, correo de notificación y Código QR actualizados exitosamente.');
    }
}
