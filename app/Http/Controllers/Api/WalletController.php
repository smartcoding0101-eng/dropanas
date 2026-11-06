<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
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

        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $amount, $validated): JsonResponse {
            $wallet = Wallet::where('user_id', $user->id)
                ->where('currency', 'BOB')
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $wallet->balance_available < $amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Saldo disponible insuficiente para realizar el retiro.',
                    'balance_available' => (float) $wallet->balance_available,
                    'requested_amount' => $amount,
                ], 422);
            }

            $wallet->balance_available -= $amount;
            $wallet->balance_withdrawn += $amount;
            $wallet->save();

            $transaction = WalletTransaction::create([
                'tenant_id' => $user->tenant_id,
                'wallet_id' => $wallet->id,
                'transaction_type' => 'withdrawal_request',
                'amount' => -$amount,
                'balance_after' => $wallet->balance_available,
                'reference_type' => 'withdrawal_request',
                'reference_id' => null,
                'description' => "Solicitud de retiro de fondos ({$validated['payout_method']})",
                'metadata' => [
                    'payout_method' => $validated['payout_method'],
                    'details' => $validated['account_details'],
                    'status' => 'pending_payout',
                ],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Solicitud de retiro procesada correctamente.',
                'data' => [
                    'transaction' => $transaction,
                    'wallet' => $wallet->fresh(),
                ],
            ], 201);
        });
    }
}
