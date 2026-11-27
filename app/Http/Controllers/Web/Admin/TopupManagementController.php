<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialSetting;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\Finance\WalletTopupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TopupManagementController extends Controller
{
    public function __construct(
        protected WalletTopupService $topupService
    ) {}

    /**
     * Historial independiente de solicitudes de recarga de saldo.
     */
    public function index(Request $request): View
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $tenantId = $admin?->tenant_id ?? '01a1f5b7-11c2-7350-89ce-f96f436c8ceb';

        $statusFilter = $request->input('status', 'all');
        $search = trim((string) $request->input('search', ''));

        // Query principal con filtros
        $query = WalletTopup::where('tenant_id', $tenantId)
            ->with(['user', 'wallet', 'approvedBy'])
            ->latest();

        if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $statusFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('topup_number', 'like', "%{$search}%")
                  ->orWhere('bank_reference', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $topups = $query->paginate(20)->withQueryString();

        // KPIs ejecutivos
        $kpis = [
            'total_count'        => WalletTopup::where('tenant_id', $tenantId)->count(),
            'pending_count'      => WalletTopup::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'approved_count'     => WalletTopup::where('tenant_id', $tenantId)->where('status', 'approved')->count(),
            'rejected_count'     => WalletTopup::where('tenant_id', $tenantId)->where('status', 'rejected')->count(),
            'total_approved_bob' => (float) WalletTopup::where('tenant_id', $tenantId)->where('status', 'approved')->sum('amount_bob'),
            'pending_amount_bob' => (float) WalletTopup::where('tenant_id', $tenantId)->where('status', 'pending')->sum('amount_bob'),
        ];

        $financialSettings = FinancialSetting::forTenant($tenantId);

        return view('admin.recargas.index', compact(
            'topups',
            'kpis',
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
