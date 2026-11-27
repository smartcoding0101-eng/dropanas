<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Finance\WalletWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected WalletWithdrawalService $withdrawalService
    ) {}
    /**
     * Consulta el estado de la billetera del usuario autenticado (Disponible, Retenido, Retirado).
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $wallet = Wallet::firstOrCreate(
            ['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'currency' => 'BOB'],
            ['balance_available' => 0.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'wallet' => $wallet,
                'currency' => 'BOB',
                'symbol' => 'Bs',
            ],
        ]);
    }

    /**
     * Historial de transacciones de la billetera con paginación.
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        $wallet = Wallet::where('user_id', $user->id)
            ->where('currency', 'BOB')
            ->firstOrFail();

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }

    /**
     * Solicitud de retiro de saldo disponible (Transferencia Bancaria QR o Binance USDT en Bolivia).
     */
    public function withdraw(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'payout_method' => 'required|string|in:bank_transfer,qr_simple,binance_pay',
            'account_details' => 'required|array',
            'account_details.beneficiary_name' => 'required|string|max:150',
            'account_details.document_id' => 'required|string|max:30',
            'account_details.bank_name' => 'nullable|string|max:100',
            'account_details.account_number' => 'nullable|string|max:60',
            'account_details.binance_pay_id' => 'nullable|string|max:60',
        ]);

        $amount = (float) $validated['amount'];

        $payoutData = [
            'payout_method'    => $validated['payout_method'],
            'beneficiary_name' => $validated['account_details']['beneficiary_name'],
            'document_id'      => $validated['account_details']['document_id'],
            'bank_name'        => $validated['account_details']['bank_name'] ?? null,
            'account_number'   => $validated['account_details']['account_number'] ?? null,
            'binance_pay_id'   => $validated['account_details']['binance_pay_id'] ?? null,
        ];

        try {
            $withdrawal = $this->withdrawalService->requestWithdrawal(
                $user,
                $amount,
                $payoutData,
                $request->file('qr_code')
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Solicitud de retiro procesada correctamente.',
                'data'    => [
                    'withdrawal' => $withdrawal,
                    'wallet'     => Wallet::where('user_id', $user->id)->where('currency', 'BOB')->first(),
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

