<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CarrierCashSettlement;
use App\Models\FinancialSetting;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Models\WalletWithdrawal;
use App\Services\Finance\CarrierSettlementService;
use App\Services\Finance\WalletTopupService;
use App\Services\Finance\WalletWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FintechWalletController extends Controller
{
    public function __construct(
        protected WalletTopupService $topupService,
        protected CarrierSettlementService $carrierSettlementService,
        protected WalletWithdrawalService $withdrawalService
    ) {}

    /**
     * Valida la frontera de autorización administrativa (Defense in Depth).
     */
    protected function ensureAdmin(?\App\Models\User $user): void
    {
        if (!$user || (!$user->isTenantAdmin() && !$user->isSuperAdmin() && !in_array($user->role, ['tenant_admin', 'superadmin', 'admin']))) {
            abort(403, 'Acceso denegado. Exclusivo para administradores del sistema.');
        }
    }

    /**
     * Vendedor o Proveedor envía una solicitud de recarga de saldo con comprobante.
     */
    public function submitTopup(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        // Normalizar inputs del formulario web (amount_bob y qr_bolivia)
        if (!$request->has('amount') && $request->has('amount_bob')) {
            $request->merge(['amount' => $request->input('amount_bob')]);
        }
        if ($request->input('payment_method') === 'qr_bolivia') {
            $request->merge(['payment_method' => 'qr_simple']);
        }

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:qr_simple,qr_bolivia,bank_transfer,binance_pay',
            'bank_name'      => 'nullable|string|max:100',
            'bank_reference' => 'nullable|string|max:100',
            'receipt'        => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240', // Imágenes o documentos PDF hasta 10MB
            'notes'          => 'nullable|string|max:255',
        ]);

        try {
            $topup = $this->topupService->requestTopup(
                $user,
                (float) $validated['amount'],
                $validated['payment_method'] === 'qr_bolivia' ? 'qr_simple' : $validated['payment_method'],
                $validated['bank_name'] ?? 'Banco BCP / QR Simple',
                $validated['bank_reference'] ?? null,
                $request->file('receipt'),
                $validated['notes'] ?? null
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Solicitud de recarga #{$topup->topup_number} enviada correctamente. El administrador validará el comprobante.",
                    'data'    => $topup,
                ]);
            }

            return back()->with('success', "Solicitud de recarga #{$topup->topup_number} por Bs. {$topup->amount_bob} enviada correctamente al Administrador.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin aprueba una recarga de saldo y acredita los fondos en tiempo real.
     */
    public function approveTopup(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user();
        $this->ensureAdmin($admin);
        $topup = WalletTopup::where('tenant_id', $admin->tenant_id)->findOrFail($id);

        try {
            $this->topupService->approveTopup($topup, $admin, $request->input('notes'));

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Recarga #{$topup->topup_number} por Bs. {$topup->amount_bob} aprobada y acreditada exitosamente.",
                ]);
            }

            return back()->with('success', "Recarga #{$topup->topup_number} por Bs. {$topup->amount_bob} aprobada y saldo acreditado.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin rechaza una recarga de saldo.
     */
    public function rejectTopup(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user();
        $this->ensureAdmin($admin);
        $topup = WalletTopup::where('tenant_id', $admin->tenant_id)->findOrFail($id);

        $reason = $request->input('reason', 'Comprobante no válido o depósito no verificado en cuenta bancaria.');

        try {
            $this->topupService->rejectTopup($topup, $admin, $reason);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Recarga #{$topup->topup_number} rechazada.",
                ]);
            }

            return back()->with('status', "Recarga #{$topup->topup_number} rechazada.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Courier envía la rendición de caja de su ruta con el comprobante de depósito a Dropana.
     */
    public function submitCarrierSettlement(Request $request): JsonResponse|RedirectResponse
    {
        $carrier = Auth::user();

        $validated = $request->validate([
            'order_ids'         => 'required|array|min:1',
            'order_ids.*'       => 'required|uuid|exists:orders,id',
            'total_collected'   => 'required|numeric|min:0',
            'fees_earned'       => 'required|numeric|min:0',
            'net_deposit'       => 'required|numeric|min:0',
            'payment_method'    => 'required|string|in:bank_transfer,qr_simple,cash_handover',
            'deposit_reference' => 'nullable|string|max:100',
            'receipt'           => 'nullable|image|max:5120',
            'notes'             => 'nullable|string|max:255',
        ]);

        try {
            $settlement = $this->carrierSettlementService->submitRouteSettlement(
                $carrier,
                $validated['order_ids'],
                (float) $validated['total_collected'],
                (float) $validated['fees_earned'],
                (float) $validated['net_deposit'],
                $validated['payment_method'],
                $validated['deposit_reference'] ?? null,
                $request->file('receipt'),
                $validated['notes'] ?? null
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Rendición de ruta #{$settlement->settlement_number} enviada a Administración para validación.",
                    'data'    => $settlement,
                ]);
            }

            return back()->with('success', "Rendición de ruta #{$settlement->settlement_number} enviada exitosamente para validación del Administrador.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin aprueba la rendición del courier y dispersa fondos a Vendedor y Proveedor.
     */
    public function approveCarrierSettlement(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user();
        $this->ensureAdmin($admin);
        $settlement = CarrierCashSettlement::where('tenant_id', $admin->tenant_id)->findOrFail($id);

        try {
            $this->carrierSettlementService->approveSettlement($settlement, $admin);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Rendición #{$settlement->settlement_number} aprobada y fondos dispersados exitosamente.",
                ]);
            }

            return back()->with('success', "Rendición #{$settlement->settlement_number} aprobada con éxito. Fondos dispersados a vendedores y proveedores.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin rechaza la rendición del courier.
     */
    public function rejectCarrierSettlement(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user();
        $this->ensureAdmin($admin);
        $settlement = CarrierCashSettlement::where('tenant_id', $admin->tenant_id)->findOrFail($id);
        $reason = $request->input('reason', 'Monto depositado no coincide con el recaudo auditado.');

        try {
            $this->carrierSettlementService->rejectSettlement($settlement, $admin, $reason);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Rendición #{$settlement->settlement_number} rechazada.",
                ]);
            }

            return back()->with('status', "Rendición #{$settlement->settlement_number} rechazada.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin actualiza la configuración de comisiones Dropana (5 Bs modificable) y datos bancarios.
     */
    public function updateFinancialSettings(Request $request): JsonResponse|RedirectResponse
    {
        $admin = Auth::user();
        $this->ensureAdmin($admin);

        $validated = $request->validate([
            'seller_commission_bob'   => 'nullable|numeric|min:0',
            'provider_commission_bob' => 'nullable|numeric|min:0',
            'bank_account_name'       => 'nullable|string|max:150',
            'bank_name'               => 'nullable|string|max:100',
            'bank_account_number'     => 'nullable|string|max:80',
            'admin_notification_email'=> 'nullable|email|max:150',
            'recharge_instructions'   => 'nullable|string',
            'qr_image'                => 'nullable|image|max:5120',
        ]);

        $settings = FinancialSetting::forTenant($admin->tenant_id);

        if ($request->hasFile('qr_image')) {
            $settings->qr_deposit_image_url = $request->file('qr_image')->store("settings/{$admin->tenant_id}", 'public');
        }

        $settings->update([
            'seller_commission_bob'   => $validated['seller_commission_bob'] ?? $settings->seller_commission_bob,
            'provider_commission_bob' => $validated['provider_commission_bob'] ?? $settings->provider_commission_bob,
            'bank_account_name'       => $validated['bank_account_name'] ?? $settings->bank_account_name,
            'bank_name'               => $validated['bank_name'] ?? $settings->bank_name,
            'bank_account_number'     => $validated['bank_account_number'] ?? $settings->bank_account_number,
            'admin_notification_email'=> $validated['admin_notification_email'] ?? $settings->admin_notification_email,
            'recharge_instructions'   => $validated['recharge_instructions'] ?? $settings->recharge_instructions,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Configuración de comisiones de Dropana actualizada correctamente.',
                'data'    => $settings,
            ]);
        }

        return back()->with('success', "Configuración de comisiones actualizada: Bs. {$settings->seller_commission_bob} (Vendedor) / Bs. {$settings->provider_commission_bob} (Proveedor).");
    }

    /**
     * Vendedor o Proveedor solicita retiro de saldo a su cuenta bancaria o QR Simple.
     */
    public function submitWithdrawal(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        // Normalizar estructura si los campos vienen agrupados o planos
        $beneficiaryName = $request->input('beneficiary_name') 
            ?? $request->input('account_holder') 
            ?? $request->input('account_details.beneficiary_name')
            ?? $user->name;

        $documentId = $request->input('document_id') 
            ?? $request->input('account_details.document_id');

        $bankName = $request->input('bank_name') 
            ?? $request->input('account_details.bank_name');

        $accountNumber = $request->input('account_number') 
            ?? $request->input('account_details.account_number');

        $binancePayId = $request->input('binance_pay_id') 
            ?? $request->input('account_details.binance_pay_id');

        $validated = $request->validate([
            'amount'           => 'required|numeric|min:10',
            'payout_method'    => 'nullable|string|in:qr_simple,bank_transfer,binance_pay',
            'qr_code'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $amount = (float) $validated['amount'];
        $payoutMethod = $validated['payout_method'] ?? 'qr_simple';

        $payoutData = [
            'payout_method'    => $payoutMethod,
            'beneficiary_name' => $beneficiaryName,
            'document_id'      => $documentId,
            'bank_name'        => $bankName,
            'account_number'   => $accountNumber,
            'binance_pay_id'   => $binancePayId,
        ];

        try {
            $withdrawal = $this->withdrawalService->requestWithdrawal(
                $user,
                $amount,
                $payoutData,
                $request->file('qr_code')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Solicitud de retiro #{$withdrawal->withdrawal_number} por Bs. " . number_format($amount, 2) . " enviada. Tus fondos fueron congelados de forma segura y el Administrador fue notificado para realizar la transferencia.",
                    'data'    => $withdrawal,
                ]);
            }

            return back()->with('success', "Solicitud de retiro #{$withdrawal->withdrawal_number} por Bs. " . number_format($amount, 2) . " enviada exitosamente. Tu saldo fue congelado y se notificó al Administrador para procesar el desembolso.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
