<?php

namespace App\Services\Payments\Adapters;

use App\Contracts\Payments\PaymentProviderInterface;
use App\Models\Order;
use App\Services\Currency\BcbExchangeRateService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TakenosAdapter implements PaymentProviderInterface
{
    protected BcbExchangeRateService $exchangeService;

    public function __construct(BcbExchangeRateService $exchangeService)
    {
        $this->exchangeService = $exchangeService;
    }

    public function getProviderCode(): string
    {
        return 'takenos';
    }

    /**
     * Genera la solicitud de pago/enlace para liquidación con saldo Takenos (Dólares digitales / TakeCard).
     */
    public function createPaymentPayload(Order $order): array
    {
        $officialRate = $this->exchangeService->getCurrentSellRate();
        $amountBob = (float) $order->total_amount_bob;
        $amountUsd = round($amountBob / $officialRate, 2);
        $reference = 'TKN-' . Str::upper(Str::random(10));

        return [
            'provider' => $this->getProviderCode(),
            'reference' => $reference,
            'amount_usd' => $amountUsd,
            'amount_bob' => $amountBob,
            'exchange_rate' => $officialRate,
            'description' => 'Pago Pedido Dropana #' . $order->order_number,
            'payment_url' => 'https://app.takenos.com/pay/' . $reference,
            'payment_link' => 'https://app.takenos.com/pay/' . $reference,
            'supports_takecard' => true,
            'supports_qr_bridge' => true,
            'created_at' => Carbon::now()->toIso8601String(),
        ];
    }

    public function verifyWebhook(array $payload, array $headers): bool
    {
        return ($payload['status'] ?? null) === 'completed';
    }

    public function checkStatus(string $transactionReference): string
    {
        return 'PENDING';
    }
}
