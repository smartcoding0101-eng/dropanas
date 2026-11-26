<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CarrierCashSettlement;
use App\Models\FinancialSetting;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Services\Finance\CarrierSettlementService;
use App\Services\Finance\WalletTopupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FintechWalletController extends Controller
{
    public function __construct(
        protected WalletTopupService $topupService,
        protected CarrierSettlementService $carrierSettlementService
    ) {}

    /**
     * Vendedor o Proveedor envía una solicitud de recarga de saldo con comprobante.
     */
    public function submitTopup(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:qr_simple,bank_transfer,binance_pay',
            'bank_name'      => 'nullable|string|max:100',
            'bank_reference' => 'nullable|string|max:100',
            'receipt'        => 'nullable|image|max:5120', // Hasta 5MB
            'notes'          => 'nullable|string|max:255',
        ]);

        try {
            $topup = $this->topupService->requestTopup(
                $user,
                (float) $validated['amount'],
                $validated['payment_method'],
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

        $validated = $request->validate([
            'seller_commission_bob'   => 'required|numeric|min:0',
            'provider_commission_bob' => 'required|numeric|min:0',
            'bank_account_name'       => 'nullable|string|max:150',
            'bank_name'               => 'nullable|string|max:100',
            'bank_account_number'     => 'nullable|string|max:80',
            'recharge_instructions'   => 'nullable|string',
            'qr_image'                => 'nullable|image|max:5120',
        ]);

        $settings = FinancialSetting::forTenant($admin->tenant_id);

        if ($request->hasFile('qr_image')) {
            $settings->qr_deposit_image_url = $request->file('qr_image')->store("settings/{$admin->tenant_id}", 'public');
        }

        $settings->update([
            'seller_commission_bob'   => $validated['seller_commission_bob'],
            'provider_commission_bob' => $validated['provider_commission_bob'],
            'bank_account_name'       => $validated['bank_account_name'] ?? $settings->bank_account_name,
            'bank_name'               => $validated['bank_name'] ?? $settings->bank_name,
            'bank_account_number'     => $validated['bank_account_number'] ?? $settings->bank_account_number,
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
     * Vendedor o Proveedor solicita retiro de saldo a su cuenta bancaria personal.
     */
    public function submitWithdrawal(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:1',
            'bank_name'       => 'required|string|max:100',
            'account_number'  => 'required|string|max:60',
            'account_holder'  => 'required|string|max:150',
            'document_id'     => 'nullable|string|max:30',
            'notes'           => 'nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];

        return DB::transaction(function () use ($user, $amount, $validated, $request) {
            $wallet = Wallet::where('user_id', $user->id)
                ->where('tenant_id', $user->tenant_id)
                ->where('currency', 'BOB')
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $wallet->balance_available < $amount) {
                $errorMsg = "Saldo insuficiente. Disponible: Bs. {$wallet->balance_available}, solicitado: Bs. {$amount}.";
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $errorMsg], 422);
                }
                return back()->withErrors(['error' => $errorMsg]);
            }

            // Debitar saldo de la billetera
            $wallet->balance_available -= $amount;
            $wallet->balance_withdrawn += $amount;
            $wallet->save();

            // Registrar transacción en el Ledger
            $tx = WalletTransaction::create([
                'tenant_id'        => $user->tenant_id,
                'wallet_id'        => $wallet->id,
                'transaction_type' => 'withdrawal',
                'amount'           => -$amount,
                'balance_after'    => $wallet->balance_available,
                'reference_type'   => 'withdrawal_request',
                'description'      => "Solicitud de retiro bancario a {$validated['bank_name']} (Cta: {$validated['account_number']})",
                'notes'            => "Titular: {$validated['account_holder']} — " . ($validated['notes'] ?? ''),
                'metadata'         => [
                    'bank_name'      => $validated['bank_name'],
                    'account_number' => $validated['account_number'],
                    'account_holder' => $validated['account_holder'],
                    'document_id'    => $validated['document_id'] ?? null,
                ],
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => "Solicitud de retiro por Bs. {$amount} enviada al Administrador para transferencia bancaria.",
                    'data'    => $tx,
                ]);
            }

            return back()->with('success', "Solicitud de retiro por Bs. {$amount} enviada correctamente. El administrador realizará la transferencia a tu cuenta bancaria.");
        });
    }
}
