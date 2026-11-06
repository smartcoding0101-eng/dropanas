<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProviderInterface;
use App\Models\Order;
use App\Services\Payments\Adapters\BinancePayAdapter;
use App\Services\Payments\Adapters\QrBoliviaSimpleAdapter;
use App\Services\Payments\Adapters\TakenosAdapter;
use InvalidArgumentException;

class PaymentManagerService
{
    protected array $adapters = [];

    public function __construct(
        QrBoliviaSimpleAdapter $qrAdapter,
        BinancePayAdapter $binanceAdapter,
        TakenosAdapter $takenosAdapter
    ) {
        $this->registerAdapter($qrAdapter);
        $this->registerAdapter($binanceAdapter);
        $this->registerAdapter($takenosAdapter);
    }

    public function registerAdapter(PaymentProviderInterface $adapter): void
    {
        $this->adapters[$adapter->getProviderCode()] = $adapter;
    }

    public function getAdapter(string $providerCode): PaymentProviderInterface
    {
        if (!isset($this->adapters[$providerCode])) {
            throw new InvalidArgumentException("El proveedor de pago [{$providerCode}] no está registrado.");
        }

        return $this->adapters[$providerCode];
    }

    /**
     * Genera el cobro a través del proveedor seleccionado.
     */
    public function initiatePayment(Order $order, string $providerCode): array
    {
        $adapter = $this->getAdapter($providerCode);
        return $adapter->createPaymentPayload($order);
    }
}
