<?php

namespace App\Services\Integrations;

use App\Models\Carrier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreIntegration;
use App\Models\StoreWebhookLog;
use App\Models\Warehouse;
use App\Services\Finance\CommissionEngineService;
use App\Services\Logistics\Adapters\DexBoliviaAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreWebhookProcessorService
{
    public function __construct(
        protected CommissionEngineService $commissionEngine,
        protected DexBoliviaAdapter $dexAdapter
    ) {}

    /**
     * Procesa un webhook de Shopify y genera la orden en Dropana Bolivia.
     */
    public function processShopify(StoreIntegration $integration, array $payload, string $eventType): StoreWebhookLog
    {
        $externalOrderId = (string) ($payload['id'] ?? $payload['order_number'] ?? Str::uuid());

        $log = StoreWebhookLog::create([
            'tenant_id'            => $integration->tenant_id,
            'store_integration_id' => $integration->id,
            'platform'             => 'shopify',
            'event_type'           => $eventType,
            'external_order_id'    => $externalOrderId,
            'status'               => 'received',
            'payload'              => $payload,
        ]);

        if ($eventType !== 'orders/create' && $eventType !== 'orders/paid') {
            $log->update(['status' => 'ignored', 'error_message' => "Evento '{$eventType}' ignorado (solo se procesan pedidos nuevos)."]);
            return $log;
        }

        // Extracción de datos del cliente boliviano
        $shipping = $payload['shipping_address'] ?? $payload['customer']['default_address'] ?? [];
        $customerName = trim(($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? '')) ?: ($payload['customer']['first_name'] ?? 'Cliente Shopify');
        $customerPhone = $shipping['phone'] ?? $payload['phone'] ?? '+591 70000000';
        $department = $this->normalizeBolivianDepartment($shipping['province'] ?? $shipping['city'] ?? 'Santa Cruz');
        $city = $shipping['city'] ?? $department;
        $address = trim(($shipping['address1'] ?? '') . ' ' . ($shipping['address2'] ?? '')) ?: 'Dirección no especificada';

        // Buscar bodega y courier predeterminados
        $warehouse = Warehouse::first();
        $carrier = Carrier::first();

        // Extraer items
        $lineItems = $payload['line_items'] ?? [];
        if (empty($lineItems)) {
            $log->update(['status' => 'failed', 'error_message' => 'El pedido de Shopify no contiene productos.']);
            return $log;
        }

        try {
            DB::beginTransaction();

            $totalAmountBob = (float) ($payload['total_price'] ?? 0);
            $totalCostBob = 0;
            $itemsToCreate = [];

            foreach ($lineItems as $item) {
                $sku = $item['sku'] ?? null;
                $product = null;

                if ($sku) {
                    $product = Product::where('sku', $sku)->first();
                }

                if (!$product) {
                    // Si no encuentra por SKU, busca por título o toma el primer producto publicado
                    $product = Product::where('title', 'like', '%' . ($item['title'] ?? '') . '%')->first()
                        ?? Product::where('status', 'published')->first();
                }

                $quantity = (int) ($item['quantity'] ?? 1);
                $unitPriceBob = (float) ($item['price'] ?? 0);
                if ($unitPriceBob <= 0 && $product) {
                    $unitPriceBob = (float) $product->suggested_retail_price_bob;
                }
                $unitCostBob = $product ? (float) $product->wholesale_price_bob : ($unitPriceBob * 0.6);

                $totalCostBob += ($unitCostBob * $quantity);
                if ($totalAmountBob <= 0) {
                    $totalAmountBob += ($unitPriceBob * $quantity);
                }

                $itemsToCreate[] = [
                    'product'        => $product,
                    'quantity'       => $quantity,
                    'unit_price_bob' => $unitPriceBob,
                    'unit_cost_bob'  => $unitCostBob,
                    'title'          => $item['title'] ?? ($product ? $product->title : 'Producto Importado'),
                ];
            }

            $shippingFeeBob = 25.00; // Tarifa estándar courier Bolivia
            $platformFeeBob = round(($totalAmountBob * 0.05) + 2.00, 2); // Estimación inicial
            $dropshipperProfitBob = $totalAmountBob - $totalCostBob - $shippingFeeBob - $platformFeeBob;

            // Creación del Pedido en Dropana Bolivia
            $orderNumber = 'SHP-' . ($payload['order_number'] ?? rand(1000, 9999));
            $order = Order::create([
                'tenant_id'              => $integration->tenant_id,
                'dropshipper_id'         => $integration->dropshipper_id,
                'warehouse_id'           => $warehouse ? $warehouse->id : Str::uuid(),
                'carrier_id'             => $carrier ? $carrier->id : null,
                'order_number'           => $orderNumber,
                'customer_name'          => $customerName,
                'customer_phone'         => $customerPhone,
                'shipping_department'    => $department,
                'shipping_city'          => $city,
                'shipping_address'       => $address,
                'shipping_reference'     => 'Importado de Shopify: ' . ($payload['name'] ?? $orderNumber),
                'total_amount_bob'       => $totalAmountBob,
                'cost_amount_bob'        => $totalCostBob,
                'shipping_fee_bob'       => $shippingFeeBob,
                'platform_fee_bob'       => $platformFeeBob,
                'dropshipper_profit_bob' => $dropshipperProfitBob,
                'status'                 => $integration->auto_dispatch ? 'dispatched' : 'pending_confirmation',
                'payment_method'         => 'cash_on_delivery_bob',
            ]);

            foreach ($itemsToCreate as $itemData) {
                OrderItem::create([
                    'tenant_id'      => $integration->tenant_id,
                    'order_id'       => $order->id,
                    'product_id'     => $itemData['product'] ? $itemData['product']->id : null,
                    'provider_id'    => $itemData['product'] ? $itemData['product']->provider_id : $integration->dropshipper_id,
                    'quantity'       => $itemData['quantity'],
                    'unit_price_bob' => $itemData['unit_price_bob'],
                    'unit_cost_bob'  => $itemData['unit_cost_bob'],
                    'subtotal_bob'   => $itemData['unit_price_bob'] * $itemData['quantity'],
                ]);
            }

            $order->load('items');
            $actualPlatformFee = $this->commissionEngine->calculatePlatformFee($order);
            $order->platform_fee_bob = $actualPlatformFee;
            $order->dropshipper_profit_bob = $totalAmountBob - $totalCostBob - $shippingFeeBob - $actualPlatformFee;
            $order->save();

            // Si auto_dispatch está activo, generar guía con DEX Bolivia
            if ($integration->auto_dispatch && $carrier) {
                $shipment = $this->dexAdapter->createShipment($order);
                $order->tracking_number = $shipment['tracking_number'] ?? ('DEX-' . strtoupper(Str::random(8)));
                $order->dispatched_at = now();
                $order->save();
            }

            DB::commit();

            $integration->update(['last_synced_at' => now()]);
            $log->update([
                'status'   => 'processed',
                'order_id' => $order->id,
            ]);

            return $log;
        } catch (\Throwable $e) {
            DB::rollBack();
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $log;
        }
    }

    /**
     * Procesa un webhook de WooCommerce y genera la orden en Dropana Bolivia.
     */
    public function processWooCommerce(StoreIntegration $integration, array $payload, string $eventType): StoreWebhookLog
    {
        $externalOrderId = (string) ($payload['id'] ?? Str::uuid());

        $log = StoreWebhookLog::create([
            'tenant_id'            => $integration->tenant_id,
            'store_integration_id' => $integration->id,
            'platform'             => 'woocommerce',
            'event_type'           => $eventType,
            'external_order_id'    => $externalOrderId,
            'status'               => 'received',
            'payload'              => $payload,
        ]);

        $shipping = $payload['shipping'] ?? $payload['billing'] ?? [];
        $customerName = trim(($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? '')) ?: 'Cliente WooCommerce';
        $customerPhone = $payload['billing']['phone'] ?? '+591 70000000';
        $department = $this->normalizeBolivianDepartment($shipping['state'] ?? $shipping['city'] ?? 'Santa Cruz');
        $city = $shipping['city'] ?? $department;
        $address = trim(($shipping['address_1'] ?? '') . ' ' . ($shipping['address_2'] ?? '')) ?: 'Dirección no especificada';

        $warehouse = Warehouse::first();
        $carrier = Carrier::first();
        $lineItems = $payload['line_items'] ?? [];

        if (empty($lineItems)) {
            $log->update(['status' => 'failed', 'error_message' => 'El pedido de WooCommerce no contiene productos.']);
            return $log;
        }

        try {
            DB::beginTransaction();

            $totalAmountBob = (float) ($payload['total'] ?? 0);
            $totalCostBob = 0;
            $itemsToCreate = [];

            foreach ($lineItems as $item) {
                $sku = $item['sku'] ?? null;
                $product = $sku ? Product::where('sku', $sku)->first() : null;

                if (!$product) {
                    $product = Product::where('title', 'like', '%' . ($item['name'] ?? '') . '%')->first()
                        ?? Product::where('status', 'published')->first();
                }

                $quantity = (int) ($item['quantity'] ?? 1);
                $unitPriceBob = (float) ($item['price'] ?? ($item['total'] / max($quantity, 1)));
                if ($unitPriceBob <= 0 && $product) {
                    $unitPriceBob = (float) $product->suggested_retail_price_bob;
                }
                $unitCostBob = $product ? (float) $product->wholesale_price_bob : ($unitPriceBob * 0.6);

                $totalCostBob += ($unitCostBob * $quantity);
                if ($totalAmountBob <= 0) {
                    $totalAmountBob += ($unitPriceBob * $quantity);
                }

                $itemsToCreate[] = [
                    'product'        => $product,
                    'quantity'       => $quantity,
                    'unit_price_bob' => $unitPriceBob,
                    'unit_cost_bob'  => $unitCostBob,
                    'title'          => $item['name'] ?? 'Producto WooCommerce',
                ];
            }

            $shippingFeeBob = 25.00;
            $platformFeeBob = round(($totalAmountBob * 0.05) + 2.00, 2);
            $dropshipperProfitBob = $totalAmountBob - $totalCostBob - $shippingFeeBob - $platformFeeBob;
            $orderNumber = 'WC-' . ($payload['id'] ?? rand(1000, 9999));

            $order = Order::create([
                'tenant_id'              => $integration->tenant_id,
                'dropshipper_id'         => $integration->dropshipper_id,
                'warehouse_id'           => $warehouse ? $warehouse->id : Str::uuid(),
                'carrier_id'             => $carrier ? $carrier->id : null,
                'order_number'           => $orderNumber,
                'customer_name'          => $customerName,
                'customer_phone'         => $customerPhone,
                'shipping_department'    => $department,
                'shipping_city'          => $city,
                'shipping_address'       => $address,
                'shipping_reference'     => 'Importado de WooCommerce #' . ($payload['id'] ?? $orderNumber),
                'total_amount_bob'       => $totalAmountBob,
                'cost_amount_bob'        => $totalCostBob,
                'shipping_fee_bob'       => $shippingFeeBob,
                'platform_fee_bob'       => $platformFeeBob,
                'dropshipper_profit_bob' => $dropshipperProfitBob,
                'status'                 => $integration->auto_dispatch ? 'dispatched' : 'pending_confirmation',
                'payment_method'         => 'cash_on_delivery_bob',
            ]);

            foreach ($itemsToCreate as $itemData) {
                OrderItem::create([
                    'tenant_id'      => $integration->tenant_id,
                    'order_id'       => $order->id,
                    'product_id'     => $itemData['product'] ? $itemData['product']->id : null,
                    'provider_id'    => $itemData['product'] ? $itemData['product']->provider_id : $integration->dropshipper_id,
                    'quantity'       => $itemData['quantity'],
                    'unit_price_bob' => $itemData['unit_price_bob'],
                    'unit_cost_bob'  => $itemData['unit_cost_bob'],
                    'subtotal_bob'   => $itemData['unit_price_bob'] * $itemData['quantity'],
                ]);
            }

            $order->load('items');
            $actualPlatformFee = $this->commissionEngine->calculatePlatformFee($order);
            $order->platform_fee_bob = $actualPlatformFee;
            $order->dropshipper_profit_bob = $totalAmountBob - $totalCostBob - $shippingFeeBob - $actualPlatformFee;
            $order->save();

            if ($integration->auto_dispatch && $carrier) {
                $shipment = $this->dexAdapter->createShipment($order);
                $order->tracking_number = $shipment['tracking_number'] ?? ('DEX-' . strtoupper(Str::random(8)));
                $order->dispatched_at = now();
                $order->save();
            }

            DB::commit();

            $integration->update(['last_synced_at' => now()]);
            $log->update([
                'status'   => 'processed',
                'order_id' => $order->id,
            ]);

            return $log;
        } catch (\Throwable $e) {
            DB::rollBack();
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $log;
        }
    }

    /**
     * Normaliza los departamentos bolivianos contra variaciones comunes.
     */
    protected function normalizeBolivianDepartment(string $raw): string
    {
        $normalized = mb_strtolower(trim($raw), 'UTF-8');

        return match (true) {
            str_contains($normalized, 'santa') || str_contains($normalized, 'cruz') => 'Santa Cruz',
            str_contains($normalized, 'paz') => 'La Paz',
            str_contains($normalized, 'cocha') => 'Cochabamba',
            str_contains($normalized, 'oruro') => 'Oruro',
            str_contains($normalized, 'potos') => 'Potosí',
            str_contains($normalized, 'chuqui') || str_contains($normalized, 'sucre') => 'Chuquisaca',
            str_contains($normalized, 'tarija') => 'Tarija',
            str_contains($normalized, 'beni') || str_contains($normalized, 'trinidad') => 'Beni',
            str_contains($normalized, 'pando') || str_contains($normalized, 'cobija') => 'Pando',
            default => 'Santa Cruz',
        };
    }
}
