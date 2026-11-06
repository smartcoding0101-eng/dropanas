<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreIntegration;
use App\Models\StoreWebhookLog;
use App\Services\Integrations\StoreWebhookProcessorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreIntegrationController extends Controller
{
    /**
     * Listado de tiendas conectadas del dropshipper autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $integrations = StoreIntegration::withCount('webhookLogs')
            ->where('dropshipper_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $integrations,
        ]);
    }

    /**
     * Conectar una nueva tienda Shopify o WooCommerce.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'platform'      => 'required|in:shopify,woocommerce',
            'store_name'    => 'required|string|max:150',
            'store_url'     => 'required|url|max:255',
            'api_key'       => 'nullable|string|max:255',
            'api_secret'    => 'nullable|string|max:255',
            'auto_dispatch' => 'boolean',
        ]);

        $webhookSecret = 'whsec_' . Str::random(32);

        $integration = StoreIntegration::create([
            'tenant_id'      => $user->tenant_id,
            'dropshipper_id' => $user->id,
            'platform'       => $validated['platform'],
            'store_name'     => $validated['store_name'],
            'store_url'      => $validated['store_url'],
            'api_key'        => $validated['api_key'] ?? null,
            'api_secret'     => $validated['api_secret'] ?? null,
            'webhook_secret' => $webhookSecret,
            'auto_dispatch'  => $validated['auto_dispatch'] ?? false,
            'is_active'      => true,
        ]);

        $webhookUrl = url("/api/v1/webhooks/{$integration->platform}/{$integration->webhook_secret}");

        return response()->json([
            'status'  => 'success',
            'message' => 'Tienda conectada exitosamente. Configura la URL del Webhook en tu tienda.',
            'data'    => [
                'integration' => $integration,
                'webhook_url' => $webhookUrl,
            ],
        ], 201);
    }

    /**
     * Endpoint público del Webhook de Shopify.
     */
    public function handleShopifyWebhook(
        Request $request,
        string $secret,
        StoreWebhookProcessorService $processor
    ): JsonResponse {
        $integration = StoreIntegration::where('webhook_secret', $secret)
            ->where('platform', 'shopify')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return response()->json(['status' => 'error', 'message' => 'Integración no encontrada o inactiva.'], 404);
        }

        $eventType = $request->header('X-Shopify-Topic', 'orders/create');
        $payload = $request->all();

        $log = $processor->processShopify($integration, $payload, $eventType);

        return response()->json([
            'status'  => 'success',
            'message' => 'Webhook de Shopify recibido y procesado.',
            'data'    => [
                'log_id'        => $log->id,
                'order_id'      => $log->order_id,
                'status'        => $log->status,
                'error_message' => $log->error_message,
            ],
        ]);
    }

    /**
     * Endpoint público del Webhook de WooCommerce.
     */
    public function handleWooCommerceWebhook(
        Request $request,
        string $secret,
        StoreWebhookProcessorService $processor
    ): JsonResponse {
        $integration = StoreIntegration::where('webhook_secret', $secret)
            ->where('platform', 'woocommerce')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return response()->json(['status' => 'error', 'message' => 'Integración no encontrada o inactiva.'], 404);
        }

        $eventType = $request->header('X-WC-Webhook-Topic', 'woocommerce_order_created');
        $payload = $request->all();

        $log = $processor->processWooCommerce($integration, $payload, $eventType);

        return response()->json([
            'status'  => 'success',
            'message' => 'Webhook de WooCommerce recibido y procesado.',
            'data'    => [
                'log_id'        => $log->id,
                'order_id'      => $log->order_id,
                'status'        => $log->status,
                'error_message' => $log->error_message,
            ],
        ]);
    }

    /**
     * Historial de eventos y logs de webhooks de una tienda.
     */
    public function logs(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $integration = StoreIntegration::where('id', $id)
            ->where('dropshipper_id', $user->id)
            ->firstOrFail();

        $logs = StoreWebhookLog::with('order')
            ->where('store_integration_id', $integration->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $logs,
        ]);
    }
}
