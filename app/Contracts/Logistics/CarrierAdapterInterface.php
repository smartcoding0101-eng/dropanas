<?php

namespace App\Contracts\Logistics;

use App\Models\Carrier;
use App\Models\Order;
use App\Models\ShippingManifest;
use Illuminate\Support\Collection;

interface CarrierAdapterInterface
{
    /**
     * Identificador único de la transportadora (ej. dex_bolivia, cartero_courier)
     */
    public function getCarrierCode(): string;

    /**
     * Genera la estructura de guía de remisión / etiqueta para el paquete en formato Bolivia.
     */
    public function generateWaybill(Order $order): array;

    /**
     * Genera el manifiesto de entrega consolidado (archivo CSV/Excel o payload API) para el courier.
     */
    public function generateManifest(ShippingManifest $manifest, Collection $orders): string;

    /**
     * Procesa la planilla de liquidación de entregas y recaudos COD enviada por la transportadora.
     */
    public function parseSettlementFile(string $filePath): Collection;
}
