<?php

namespace App\Services\Logistics\Adapters;

use App\Contracts\Logistics\CarrierAdapterInterface;
use App\Models\Order;
use App\Models\ShippingManifest;
use Illuminate\Support\Collection;

class DexBoliviaAdapter implements CarrierAdapterInterface
{
    public function getCarrierCode(): string
    {
        return 'dex_bolivia';
    }

    public function generateWaybill(Order $order): array
    {
        return [
            'carrier' => 'DEX Bolivia Express',
            'waybill_number' => 'DEX-BO-' . $order->order_number,
            'service_type' => 'COD_STANDARD',
            'origin' => $order->warehouse->city ?? 'Santa Cruz',
            'destination_department' => $order->shipping_department,
            'destination_city' => $order->shipping_city,
            'destination_address' => $order->shipping_address,
            'consignee_name' => $order->customer_name,
            'consignee_phone' => $order->customer_phone,
            'cod_collection_amount_bob' => number_format((float)$order->total_amount_bob, 2, '.', ''),
            'barcode' => 'DEX' . preg_replace('/[^0-9]/', '', $order->order_number),
        ];
    }

    public function generateManifest(ShippingManifest $manifest, Collection $orders): string
    {
        // Genera CSV estandarizado para despacho masivo en oficinas de DEX
        $lines = [];
        $lines[] = 'NUM_MANIFIESTO,FECHA,GUIA_DEX,NUM_PEDIDO,DESTINATARIO,TELEFONO,DEPARTAMENTO,CIUDAD,DIRECCION,MONTO_COD_BOB';

        foreach ($orders as $order) {
            $lines[] = sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s",%.2f',
                $manifest->manifest_number,
                $manifest->manifest_date,
                'DEX-BO-' . $order->order_number,
                $order->order_number,
                $order->customer_name,
                $order->customer_phone,
                $order->shipping_department,
                $order->shipping_city,
                str_replace('"', '""', $order->shipping_address),
                $order->total_amount_bob
            );
        }

        return implode("\r\n", $lines);
    }

    public function parseSettlementFile(string $filePath): Collection
    {
        // Parsea liquidaciones recibidas de DEX
        $results = collect();
        if (!file_exists($filePath)) {
            return $results;
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle); // Leer cabecera

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 4) {
                $results->push([
                    'order_number' => $row[0] ?? null,
                    'status' => $row[1] ?? 'ENTREGADO',
                    'collected_amount_bob' => (float)($row[2] ?? 0),
                    'carrier_fee_bob' => (float)($row[3] ?? 0),
                    'delivery_date' => $row[4] ?? now()->toDateString(),
                ]);
            }
        }
        fclose($handle);

        return $results;
    }
}
