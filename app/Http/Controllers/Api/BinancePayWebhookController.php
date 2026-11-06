<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WalletTransaction;
use App\Services\Payments\Adapters\BinancePayAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BinancePayWebhookController extends Controller
{
    /**
     * Webhook oficial de Binance Pay (C2B / Merchant Notifications)
     * Recibe confirmación cripto en tiempo real (USDT) y transiciona el pedido a 'confirmed'.
     */
    public function handleWebhook(Request $request, BinancePayAdapter $binanceAdapter): JsonResponse
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        Log::info('Binance Pay Webhook recibido:', ['payload' => $payload]);

        // 1. Validar la autenticidad del webhook
        if (!$binanceAdapter->verifyWebhook($payload, $headers)) {
            Log::warning('Firma o estado inválido en Webhook de Binance Pay');
            return response()->json([
                'returnCode'    => 'FAIL',
                'returnMessage' => 'Invalid signature or status',
            ], 400);
        }

        $bizStatus = $payload['bizStatus'] ?? null;
        if ($bizStatus !== 'PAY_SUCCESS') {
            return response()->json(['returnCode' => 'SUCCESS', 'returnMessage' => 'Status ignored']);
        }

        // 2. Extraer datos del pedido
        $rawBizData = $payload['data'] ?? [];
        if (is_string($rawBizData)) {
            $bizData = json_decode($rawBizData, true) ?: [];
        } elseif (is_array($rawBizData)) {
            $bizData = $rawBizData;
        } else {
            $bizData = [];
        }

        $merchantTradeNo = $bizData['merchantTradeNo'] ?? $payload['merchantTradeNo'] ?? null;
        $orderNumber = $bizData['orderNumber'] ?? null;

        $order = null;
        if ($merchantTradeNo) {
            $order = Order::where('order_number', $merchantTradeNo)->first();
        }
        if (!$order && $orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
        }
        if (!$order && !empty($bizData['productName'])) {
            // Intentar extraer de "Pedido Dropana #ORD-XXX"
            if (preg_match('/#([A-Z0-9\-]+)/', $bizData['productName'], $m)) {
                $order = Order::where('order_number', $m[1])->first();
            }
        }

        if (!$order) {
            // Si viene con ID interno
            $orderId = $bizData['productDetail'] ?? null;
            if ($orderId) {
                $order = Order::find($orderId);
            }
        }

        if (!$order) {
            Log::error('Orden no encontrada para webhook Binance Pay', ['merchantTradeNo' => $merchantTradeNo]);
            return response()->json(['returnCode' => 'FAIL', 'returnMessage' => 'Order not found'], 404);
        }

        // 3. Confirmar pago y orden atómicamente
        DB::transaction(function () use ($order, $bizData) {
            if ($order->status === 'pending_confirmation') {
                $order->status = 'confirmed';
                $order->save();

                // Registrar en log o metadatos de la orden
                Log::info("Pedido #{$order->order_number} confirmado exitosamente vía Binance Pay (USDT).", [
                    'amount_usdt' => $bizData['totalFee'] ?? $order->total_amount_bob,
                    'transaction_id' => $bizData['transactionId'] ?? null,
                ]);
            }
        });

        // Respuesta estándar requerida por el protocolo Binance Pay
        return response()->json([
            'returnCode'    => 'SUCCESS',
            'returnMessage' => null,
        ]);
    }
}
