<?php

namespace App\Contracts\Payments;

use App\Models\Order;

interface PaymentProviderInterface
{
    /**
     * Identificador del proveedor de pago (ej: qr_interoperable, binance_pay, takenos)
     */
    public function getProviderCode(): string;

    /**
     * Genera la carga útil de cobro (código QR, deep link o dirección de wallet).
     */
    public function createPaymentPayload(Order $order): array;

    /**
     * Valida la notificación de pago recibida por webhook o callback.
     */
    public function verifyWebhook(array $payload, array $headers): bool;

    /**
     * Consulta el estado de una transacción ante la pasarela.
     */
    public function checkStatus(string $transactionReference): string;
}
