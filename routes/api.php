<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Dropana Bolivia E-commerce COD
|--------------------------------------------------------------------------
*/

// --- RUTAS PÚBLICAS ---
Route::prefix('v1')->group(function () {
    // Autenticación
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Cotización oficial del BCB en tiempo real (Pública para tiendas y cálculo de precios)
    Route::get('/exchange-rate/current', [ExchangeRateController::class, 'current']);

    // Catálogo público de productos
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    // Captación pública de Leads comerciales (Formulario Landing)
    Route::post('/leads', [\App\Http\Controllers\Api\LeadController::class, 'store']);

    // Webhooks Públicos de Tiendas Externas (Shopify / WooCommerce)
    Route::post('/webhooks/shopify/{secret}', [\App\Http\Controllers\Api\StoreIntegrationController::class, 'handleShopifyWebhook']);
    Route::post('/webhooks/woocommerce/{secret}', [\App\Http\Controllers\Api\StoreIntegrationController::class, 'handleWooCommerceWebhook']);

    // Webhook Oficial de Pagos Cripto (Binance Pay Merchant Instant Settlement)
    Route::post('/webhooks/binance-pay', [\App\Http\Controllers\Api\BinancePayWebhookController::class, 'handleWebhook']);

    // Tracking Público en Tiempo Real por Número de Guía o Pedido
    Route::get('/tracking/{guide}', function (string $guide) {
        $order = \App\Models\Order::withoutGlobalScopes()
            ->where(function ($q) use ($guide) {
                $q->where('tracking_number', $guide)
                  ->orWhere('order_number', $guide);
            })
            ->with(['items.product', 'carrier', 'warehouse'])
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró ningún pedido con la guía "' . $guide . '". Verifica el código enviado por WhatsApp o SMS.',
            ], 404);
        }

        // Timeline de eventos calculados según estado real
        $events = [];
        if ($order->status === 'delivered') {
            $events[] = [
                'label' => 'Paquete Entregado Exitosamente',
                'description' => 'Recaudación COD confirmada. Entregado al destinatario.',
                'date' => $order->delivered_at ? $order->delivered_at->format('d/m/Y H:i') : 'Reciente',
                'icon' => 'fa-check-circle',
                'color' => 'text-emerald-500'
            ];
        }

        if (in_array($order->status, ['in_transit', 'delivered'])) {
            $events[] = [
                'label' => 'En Ruta de Entrega con Repartidor',
                'description' => ($order->carrier ? $order->carrier->name : 'DEX Express') . ' — Móvil asignado.',
                'date' => $order->dispatched_at ? $order->dispatched_at->format('d/m/Y H:i') : 'Hoy',
                'icon' => 'fa-truck-fast',
                'color' => 'text-amber-500'
            ];
        }

        if ($order->novedad_reason) {
            $events[] = [
                'label' => 'Novedad en Visita: ' . ucfirst(str_replace('_', ' ', $order->novedad_reason)),
                'description' => $order->novedad_notes ?? 'Se coordinará nuevo intento de entrega.',
                'date' => $order->rescheduled_delivery_at ? 'Reprogramado para: ' . $order->rescheduled_delivery_at->format('d/m/Y H:i') : 'Hoy',
                'icon' => 'fa-triangle-exclamation',
                'color' => 'text-red-500'
            ];
        }

        $events[] = [
            'label' => 'Despachado desde Bodega',
            'description' => $order->warehouse ? $order->warehouse->name : 'Megabodega Central Dropana',
            'date' => $order->created_at->format('d/m/Y H:i'),
            'icon' => 'fa-boxes-packing',
            'color' => 'text-blue-500'
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_number' => $order->order_number,
                'guide_number' => $order->tracking_number ?? $order->order_number,
                'status' => $order->status,
                'customer_name' => $order->customer_name,
                'shipping_city' => $order->shipping_city,
                'shipping_department' => $order->shipping_department,
                'shipping_address' => $order->shipping_address,
                'shipping_reference' => $order->shipping_reference,
                'cod_amount' => number_format((float)$order->total_amount_bob, 2),
                'payment_method' => $order->payment_method,
                'delivery_attempts' => (int)$order->delivery_attempts,
                'carrier_name' => $order->carrier ? $order->carrier->name : 'DEX Bolivia Express',
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_name' => $item->product ? $item->product->name : 'Producto',
                        'quantity' => $item->quantity,
                        'subtotal' => number_format((float)$item->subtotal_bob, 2),
                    ];
                }),
                'events' => $events,
            ]
        ]);
    });
});

// --- RUTAS PROTEGIDAS CON SANCTUM ---
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Perfil
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Sincronización forzada de tasa BCB
    Route::post('/exchange-rate/sync', [ExchangeRateController::class, 'sync']);

    // Publicación de productos (Proveedores)
    Route::post('/products', [ProductController::class, 'store']);

    // Gestión de Pedidos COD
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::match(['put', 'patch'], '/orders/{id}/status', [\App\Http\Controllers\Api\OrderStatusController::class, 'update']);

    // Billetera Virtual y Movimientos
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);

    // Manifiestos de Despacho COD
    Route::get('/manifests', [\App\Http\Controllers\Api\ShippingManifestController::class, 'index']);
    Route::post('/manifests', [\App\Http\Controllers\Api\ShippingManifestController::class, 'store']);
    Route::get('/manifests/{id}/download', [\App\Http\Controllers\Api\ShippingManifestController::class, 'download']);

    // ===== MÓDULO 1: INVENTARIO AVANZADO =====
    Route::get('/inventory', [\App\Http\Controllers\Api\InventoryController::class, 'index']);
    Route::post('/inventory/transfer', [\App\Http\Controllers\Api\InventoryController::class, 'transfer']);
    Route::get('/inventory/alerts', [\App\Http\Controllers\Api\InventoryController::class, 'alerts']);
    Route::post('/inventory/alerts', [\App\Http\Controllers\Api\InventoryController::class, 'setAlert']);

    // ===== MÓDULO 2: REPORTES Y ANALYTICS =====
    Route::get('/analytics/dashboard', [\App\Http\Controllers\Api\AnalyticsController::class, 'dashboard']);
    Route::get('/analytics/top-products', [\App\Http\Controllers\Api\AnalyticsController::class, 'topProducts']);
    Route::get('/analytics/dropshippers', [\App\Http\Controllers\Api\AnalyticsController::class, 'dropshippersPerformance']);

    // ===== MÓDULO 3: NOTIFICACIONES =====
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/send', [\App\Http\Controllers\Api\NotificationController::class, 'send']);

    // ===== MÓDULO 4: GESTIÓN DE CLIENTES (CRM) =====
    Route::get('/customers', [\App\Http\Controllers\Api\CustomerController::class, 'index']);
    Route::get('/customers/{id}', [\App\Http\Controllers\Api\CustomerController::class, 'show']);
    Route::post('/customers/sync', [\App\Http\Controllers\Api\CustomerController::class, 'syncFromOrder']);

    // ===== ADMINISTRACIÓN DE USUARIOS (TENANT ADMIN) =====
    Route::get('/admin/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
    Route::post('/admin/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'store']);
    Route::patch('/admin/users/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\UserController::class, 'toggleStatus']);

    // ===== KARDEX GLOBAL DE INVENTARIO (EXCLUSIVO TENANT ADMIN) =====
    Route::get('/admin/kardex', function (\Illuminate\Http\Request $request) {
        if (!$request->user()->isTenantAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Acceso denegado. Exclusivo para administradores.'], 403);
        }
        $query = \App\Models\InventoryKardex::withoutGlobalScopes()
            ->where('tenant_id', $request->user()->tenant_id)
            ->with(['warehouse', 'product', 'user'])
            ->latest('created_at');

        if ($request->filled('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->filled('product_id')) $query->where('product_id', $request->product_id);
        if ($request->filled('movement_type')) $query->where('movement_type', $request->movement_type);

        return response()->json([
            'status' => 'success',
            'data'   => $query->paginate($request->input('per_page', 25)),
        ]);
    });

    // ===== INTEGRACIÓN CON SHOPIFY / WOOCOMMERCE =====
    Route::get('/store-integrations', [\App\Http\Controllers\Api\StoreIntegrationController::class, 'index']);
    Route::post('/store-integrations', [\App\Http\Controllers\Api\StoreIntegrationController::class, 'store']);
    Route::get('/store-integrations/{id}/logs', [\App\Http\Controllers\Api\StoreIntegrationController::class, 'logs']);
});
