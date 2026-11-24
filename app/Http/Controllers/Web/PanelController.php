<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use App\Services\Analytics\ProviderMetricsService;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    /**
     * Panel de control del Dropshipper:
     * muestra pedidos recientes, billetera y estadísticas de rendimiento.
     */
    public function dropshipper(Request $request)
    {
        $authUser = $request->user();
        $isAdmin = $authUser && ($authUser->isTenantAdmin() || $authUser->isSuperAdmin() || in_array($authUser->role, ['tenant_admin', 'admin']));

        $dropshipper = ($authUser && $authUser->role === 'dropshipper' && !$request->filled('dropshipper_id'))
            ? $authUser
            : ($request->filled('dropshipper_id') && ($isAdmin || !$authUser)
                ? User::where('role', 'dropshipper')->find($request->dropshipper_id)
                : User::where('role', 'dropshipper')->first());

        $allDropshippers = $isAdmin
            ? User::where('role', 'dropshipper')->orderBy('name')->get()
            : collect();

        $stats = [
            'total_orders'    => 0,
            'pending_orders'  => 0,
            'settled_orders'  => 0,
            'total_profit'    => 0.00,
        ];

        $recentOrders = collect();
        $wallet       = null;

        if ($dropshipper) {
            $stats['total_orders']   = Order::where('dropshipper_id', $dropshipper->id)->count();
            $stats['pending_orders'] = Order::where('dropshipper_id', $dropshipper->id)
                ->whereIn('status', ['pending_confirmation', 'confirmed', 'dispatched'])->count();
            $stats['settled_orders'] = Order::where('dropshipper_id', $dropshipper->id)
                ->where('status', 'settled')->count();
            $stats['total_profit'] = (float) Order::where('dropshipper_id', $dropshipper->id)
                ->where('status', 'settled')->sum('dropshipper_profit_bob');

            $recentOrders = Order::with(['items.product', 'carrier', 'warehouse'])
                ->where('dropshipper_id', $dropshipper->id)
                ->latest()
                ->limit(10)
                ->get();

            $wallet = Wallet::where('user_id', $dropshipper->id)->first();
        }

        $storeIntegrations = $dropshipper
            ? \App\Models\StoreIntegration::where('dropshipper_id', $dropshipper->id)->latest()->get()
            : collect();

        $apiToken = $dropshipper ? $dropshipper->createToken('dropshipper-web-session')->plainTextToken : '';

        return view('panels.dropshipper', compact('dropshipper', 'stats', 'recentOrders', 'wallet', 'storeIntegrations', 'apiToken', 'allDropshippers', 'isAdmin'));
    }

    /**
     * Panel de control del Proveedor:
     * muestra productos publicados, inventario en bodegas y liquidaciones.
     */
    public function proveedor(Request $request)
    {
        $authUser = $request->user();
        $isAdmin = $authUser && ($authUser->isTenantAdmin() || $authUser->isSuperAdmin() || in_array($authUser->role, ['tenant_admin', 'admin']));
        if (!$authUser || (!$authUser->isProvider() && !$isAdmin)) {
            abort(403, 'Acceso denegado. Exclusivo para proveedores mayoristas o administradores del sistema.');
        }

        $provider = ($authUser->isProvider() && !$request->filled('provider_id'))
            ? $authUser 
            : ($request->filled('provider_id') ? User::where('role', 'provider')->find($request->provider_id) : User::where('role', 'provider')->first());

        $allProviders = $isAdmin
            ? User::where('role', 'provider')->orderBy('name')->get()
            : collect();

        $stats = [
            'total_products'   => 0,
            'active_products'  => 0,
            'total_warehouses' => 0,
            'total_revenue'    => 0.00,
            'total_sales'      => 0,
            'pending_orders'   => 0,
            'delivered_orders' => 0,
            'total_stock'      => 0,
        ];

        $products   = collect();
        $warehouses = collect();
        $wallet     = null;
        $salesOrders = collect();
        $salesOrdersJson = [];
        $orderStatusCounts = [
            'total' => 0, 'pending' => 0, 'preparing' => 0, 'in_transit' => 0,
            'delivering' => 0, 'delivered' => 0, 'settled' => 0, 'cancelled' => 0,
            'returned' => 0, 'novedad' => 0, 'in_process' => 0,
        ];
        $recentKardex = collect();
        $stockTransfers = collect();
        $inventoryAlerts = collect();

        if ($provider) {
            $stats['total_products']   = Product::where('provider_id', $provider->id)->count();
            $stats['active_products']  = Product::where('provider_id', $provider->id)->where('status', 'published')->count();
            $stats['total_warehouses'] = Warehouse::where('provider_id', $provider->id)->count();
            
            // Total recaudado / ingresos liquidados por costo mayorista
            $stats['total_revenue'] = (float) OrderItem::where('provider_id', $provider->id)
                ->whereHas('order', fn ($q) => $q->where('status', 'settled'))
                ->selectRaw('SUM(unit_cost_bob * quantity) as total')
                ->value('total') ?? 0;

            // Cantidad de unidades y pedidos asociados
            $providerOrderIds = OrderItem::where('provider_id', $provider->id)->pluck('order_id')->unique();
            $stats['total_sales'] = $providerOrderIds->count();
            $stats['pending_orders'] = Order::whereIn('id', $providerOrderIds)
                ->whereIn('status', ['pending_confirmation', 'confirmed', 'dispatched', 'in_transit'])
                ->count();
            $stats['delivered_orders'] = Order::whereIn('id', $providerOrderIds)
                ->whereIn('status', ['delivered', 'settled'])
                ->count();

            $products = Product::with(['category', 'warehouseInventories.warehouse'])
                ->where('provider_id', $provider->id)
                ->latest()
                ->get();

            $stats['total_stock'] = (int) \App\Models\WarehouseInventory::whereIn('product_id', $products->pluck('id'))
                ->sum('stock_available');

            $warehouses = Warehouse::with(['inventories.product'])
                ->where('provider_id', $provider->id)
                ->get();

            $wallet = Wallet::where('user_id', $provider->id)->first();

            // Pedidos y ventas del proveedor con detalle de items, cliente, courier y destino
            $salesOrders = Order::with(['items' => fn($q) => $q->where('provider_id', $provider->id)->with('product'), 'carrier', 'warehouse', 'dropshipper'])
                ->whereIn('id', $providerOrderIds)
                ->latest()
                ->get();

            // Contadores de estado para los KPIs y pills de Dropanas
            $orderStatusCounts = [
                'total'      => $salesOrders->count(),
                'pending'    => $salesOrders->whereIn('status', ['pending_confirmation'])->count(),
                'preparing'  => $salesOrders->where('status', 'confirmed')->count(),
                'in_transit' => $salesOrders->where('status', 'in_transit')->count(),
                'delivering' => $salesOrders->where('status', 'dispatched')->count(),
                'delivered'  => $salesOrders->where('status', 'delivered')->count(),
                'settled'    => $salesOrders->where('status', 'settled')->count(),
                'cancelled'  => $salesOrders->where('status', 'cancelled')->count(),
                'returned'   => $salesOrders->where('status', 'returned')->count(),
                'novedad'    => $salesOrders->filter(fn($o) => !empty($o->novedad_reason))->count(),
                'in_process' => $salesOrders->whereIn('status', ['confirmed', 'in_transit', 'dispatched'])->count(),
            ];

            // Historial de Trazabilidad Kardex de sus productos en bodegas con relaciones completas
            $recentKardex = \App\Models\InventoryKardex::withoutGlobalScopes()
                ->whereIn('product_id', $products->pluck('id'))
                ->with(['warehouse', 'product.provider', 'user', 'order.dropshipper', 'order.carrier'])
                ->latest('created_at')
                ->limit(100)
                ->get();

            // Transferencias entre bodegas de sus productos
            $stockTransfers = \App\Models\StockTransfer::whereIn('product_id', $products->pluck('id'))
                ->with(['product', 'fromWarehouse', 'toWarehouse', 'requester'])
                ->latest()
                ->limit(10)
                ->get();

            // Alertas de stock crítico
            $inventoryAlerts = \App\Models\InventoryAlert::whereIn('product_id', $products->pluck('id'))
                ->with(['product', 'warehouse'])
                ->where('is_active', true)
                ->get();
        }

        $allWarehouses = Warehouse::all();
        $categories    = \App\Models\Category::withCount('products')->orderBy('name')->get();

        // Lista simplificada de productos para autocompletado en traspasos
        $transferProductsList = $products->map(fn($p) => [
            'id'            => $p->id,
            'name'          => $p->name,
            'sku'           => $p->sku,
            'internal_code' => $p->internal_code,
        ])->values();

        // Dropshippers activos para el módulo de reservas
        $dropshippers = User::where('role', 'dropshipper')
            ->where('is_active', true)
            ->select('id', 'name', 'company_or_store_name', 'email', 'city', 'department')
            ->orderBy('name')
            ->get();

        // JSON seguro para Alpine.js (pedidosModule) — preparado en el controlador para evitar
        // errores de compilación Blade con closures dentro de @json()
        $salesOrdersJson = $salesOrders->map(function ($order) use ($provider) {
            $providerSubtotal = $order->items->sum(fn($i) => (float)$i->unit_cost_bob * (int)$i->quantity);
            // Venta directa del proveedor vs venta por dropshipper
            $isDropshipperSale = !empty($order->dropshipper_id)
                && ($order->dropshipper_id !== ($provider->id ?? null));
            return [
                'id'                  => (string) $order->id,
                'order_number'        => $order->order_number,
                'tracking_number'     => $order->tracking_number,
                'customer_name'       => $order->customer_name,
                'shipping_city'       => $order->shipping_city,
                'shipping_department' => $order->shipping_department,
                'status'              => $order->status,
                'has_novedad'         => !empty($order->novedad_reason),
                'novedad_reason'      => $order->novedad_reason,
                'total_amount_bob'    => (float) $order->total_amount_bob,
                'provider_subtotal'   => round($providerSubtotal, 2),
                'payment_method'      => $order->payment_method,
                'carrier_name'        => $order->carrier?->name ?? 'DEX Bolivia',
                'warehouse_name'      => $order->warehouse?->name ?? null,
                'dropshipper_name'    => $order->dropshipper?->name ?? null,
                'dropshipper_id'      => $order->dropshipper_id,
                'is_dropshipper_sale' => $isDropshipperSale,
                'date_fmt'            => $order->created_at->format('d/m/Y'),
                'time_fmt'            => $order->created_at->format('H:i') . ' hrs',
                'created_at_ts'       => $order->created_at->timestamp,
                'items'               => $order->items->map(fn($i) => [
                    'id'            => (string) $i->id,
                    'product_name'  => $i->product?->name ?? 'Producto',
                    'quantity'      => (int) $i->quantity,
                    'unit_cost_bob' => (float) $i->unit_cost_bob,
                ])->values()->all(),
            ];
        })->values()->all();

        return view('panels.proveedor', compact(
            'provider', 'stats', 'products', 'warehouses', 'allWarehouses',
            'wallet', 'categories', 'salesOrders', 'salesOrdersJson', 'orderStatusCounts',
            'recentKardex', 'stockTransfers', 'inventoryAlerts',
            'dropshippers', 'transferProductsList', 'allProviders', 'isAdmin'
        ));
    }

    /**
     * Panel de Administración Global del Tenant:
     * Métricas generales de la plataforma e-commerce COD Bolivia.
     */
    public function admin(Request $request)
    {
        $authUser = $request->user();
        $admin = ($authUser && in_array($authUser->role, ['tenant_admin', 'admin']))
            ? $authUser
            : User::where('role', 'tenant_admin')->first();

        $stats = [
            'total_orders' => Order::count(),
            'total_volume_bob' => (float) Order::sum('total_amount_bob'),
            'pending_cod_bob' => (float) Order::whereIn('status', ['pending_confirmation', 'confirmed', 'dispatched', 'in_transit'])->sum('total_amount_bob'),
            'active_dispatches' => Order::whereIn('status', ['dispatched', 'in_transit'])->count(),
            'delivered_orders' => Order::whereIn('status', ['delivered', 'settled'])->count(),
            'pending_orders' => Order::whereIn('status', ['pending_confirmation', 'confirmed', 'dispatched', 'in_transit'])->count(),
            'platform_fees_bob' => (float) Order::where('status', 'settled')->sum('platform_fee_bob'),
            'total_dropshippers' => User::where('role', 'dropshipper')->count(),
            'total_providers' => User::where('role', 'provider')->count(),
            'total_products' => Product::count(),
        ];

        $recentOrders = Order::with(['dropshipper', 'carrier', 'warehouse'])
            ->latest()
            ->limit(10)
            ->get();

        $carriers = Carrier::withCount('orders')->get();

        $manifests = \App\Models\ShippingManifest::with('carrier')
            ->latest()
            ->limit(10)
            ->get();

        // Módulos 1, 2, 3, 4: datos para el panel admin
        $stockTransfers = \App\Models\StockTransfer::with(['product', 'fromWarehouse', 'toWarehouse', 'requester'])
            ->latest()
            ->limit(5)
            ->get();

        $notifications = \App\Models\NotificationLog::latest()
            ->limit(5)
            ->get();

        $customers = \App\Models\Customer::orderByDesc('total_spent_bob')
            ->limit(5)
            ->get();

        $warehouses = Warehouse::all();
        $products = Product::where('status', 'published')->get();

        $users = User::latest()->get();

        $apiToken = $admin ? $admin->createToken('admin-web-session')->plainTextToken : '';

        return view('panels.admin', compact(
            'admin', 'stats', 'recentOrders', 'carriers', 'manifests',
            'stockTransfers', 'notifications', 'customers', 'warehouses', 'products', 'users', 'apiToken'
        ));
    }

    /**
     * Panel de Operador Logístico / Transportista (DEX Bolivia Express).
     * Gestión de ruta de entregas, recaudación COD y reporte de novedades.
     */
    public function carrier(Request $request)
    {
        $authUser = $request->user();
        $isAdmin = $authUser && ($authUser->isTenantAdmin() || $authUser->isSuperAdmin() || in_array($authUser->role, ['tenant_admin', 'admin']));
        $carrierUser = ($authUser && in_array($authUser->role, ['carrier', 'carrier_agent']))
            ? $authUser
            : (User::where('role', 'carrier')->first() ?? $authUser ?? User::where('role', 'tenant_admin')->first());
        $carrier = Carrier::first();
        $allCarriers = Carrier::all();

        $stats = [
            'total_assigned'    => Order::whereIn('status', ['dispatched', 'delivered', 'failed_delivery'])->count(),
            'in_route'          => Order::where('status', 'dispatched')->count(),
            'delivered_today'   => Order::where('status', 'delivered')->count(),
            'novedades_active'  => Order::where('status', 'failed_delivery')->count(),
            'cod_to_collect_bob'=> (float) Order::where('status', 'dispatched')->sum('total_amount_bob'),
            'cod_collected_bob' => (float) Order::whereIn('status', ['delivered', 'settled'])->sum('total_amount_bob'),
        ];

        $ordersInRoute = Order::with(['items.product', 'dropshipper', 'warehouse'])
            ->whereIn('status', ['dispatched', 'failed_delivery'])
            ->latest()
            ->get();

        $recentDelivered = Order::with(['items.product', 'dropshipper'])
            ->where('status', 'delivered')
            ->latest('delivered_at')
            ->limit(5)
            ->get();

        return view('panels.carrier', compact('carrierUser', 'carrier', 'stats', 'ordersInRoute', 'recentDelivered', 'allCarriers', 'isAdmin'));
    }

    /**
     * Panel del Cliente de Fulfillment (Tienda Online):
     * Tú vendes — Bolidrop almacena, empaca y entrega.
     */
    public function fulfillment(Request $request)
    {
        $user = User::where('role', 'fulfillment_client')->first()
            ?? User::where('role', 'tenant_admin')->first();

        $stats = [
            'orders_month'    => Order::count(),
            'delivered'       => Order::where('status', 'delivered')->count(),
            'in_transit'      => Order::where('status', 'dispatched')->count(),
            'stock_units'     => (int) \App\Models\WarehouseInventory::sum('stock_available'),
        ];

        $recentOrders = Order::with(['items.product', 'carrier'])
            ->latest()
            ->limit(10)
            ->get();

        return view('panels.fulfillment', compact('user', 'stats', 'recentOrders'));
    }

    /**
     * Vista de Cobro COD en Campo para el Repartidor (Carrier).
     * Permite buscar pedido por guía, seleccionar método de pago y confirmar entrega o novedad.
     */
    public function carrierCobro(Request $request)
    {
        $carrierUser = User::where('role', 'carrier')->first()
            ?? User::where('role', 'tenant_admin')->first();

        return view('carrier.cobro', compact('carrierUser'));
    }

    /**
     * Actualiza manualmente la Tasa de Cambio (USD/BOB) o resincroniza con el portal del BCB.
     */
    public function updateExchangeRate(Request $request, \App\Services\Currency\BcbExchangeRateService $exchangeService)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['tenant_admin', 'admin', 'superadmin'], true)) {
            abort(403, 'Solo el Administrador puede modificar la tasa de cambio.');
        }

        // Si solicita resincronización automática con el BCB
        if ($request->input('mode') === 'sync_bcb') {
            $record = $exchangeService->syncRate($user->tenant_id);
            return back()->with('status', "Tipo de cambio sincronizado con BCB: Venta Bs " . number_format((float) $record->sell_rate, 2));
        }

        $validated = $request->validate([
            'sell_rate' => 'required|numeric|min:1|max:100',
            'buy_rate'  => 'nullable|numeric|min:1|max:100',
        ]);

        $sellRate = (float) $validated['sell_rate'];
        $buyRate  = isset($validated['buy_rate']) && $validated['buy_rate'] !== ''
            ? (float) $validated['buy_rate']
            : round(max(1.00, $sellRate - 0.10), 2);

        $record = $exchangeService->setManualRate($sellRate, $buyRate, $user->tenant_id, $user->name);

        return back()->with('status', "Tasa de cambio configurada manualmente: Compra Bs " . number_format((float) $record->buy_rate, 2) . " / Venta Bs " . number_format((float) $record->sell_rate, 2));
    }

    /**
     * Exportación filtrada del Kardex de Inventario del Proveedor en CSV con BOM UTF-8 para Excel.
     */
    public function exportProviderKardex(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $authUser = $request->user();
        if (!$authUser || (!$authUser->isProvider() && !$authUser->isTenantAdmin())) {
            abort(403, 'Acceso denegado. Exclusivo para proveedores mayoristas o administradores del sistema.');
        }

        $provider = $authUser->isProvider() 
            ? $authUser 
            : ($request->filled('provider_id') ? User::where('role', 'provider')->find($request->provider_id) : User::where('role', 'provider')->first());

        if (!$provider) {
            abort(404, 'Proveedor no encontrado');
        }

        $productIds = Product::where('provider_id', $provider->id)->pluck('id');

        $query = \App\Models\InventoryKardex::withoutGlobalScopes()
            ->whereIn('product_id', $productIds)
            ->with(['warehouse', 'product.provider', 'user', 'order.dropshipper', 'order.carrier'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('category')) {
            $query->where('movement_category', $request->category);
        }
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filename = 'kardex_proveedor_' . ($provider->company_or_store_name ? \Illuminate\Support\Str::slug($provider->company_or_store_name) : 'bolidrop') . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            fputcsv($handle, [
                'ID Kardex',
                'Fecha / Hora',
                'Flujo (Entrada/Salida/Hold)',
                'Tipo de Movimiento',
                'Producto',
                'SKU',
                'Bodega',
                'Ciudad',
                'Cantidad (Uds)',
                'Costo Unitario (BOB)',
                'Total Valuado (BOB)',
                'Fisico Antes',
                'Fisico Despues',
                'Disponible Antes',
                'Disponible Despues',
                'Proveedor Abastecedor',
                'Vendedor / Dropshipper',
                'Comprador Final COD',
                'Ciudad Destino',
                'Guia / Doc Ref',
                'Transportadora / Courier',
                'Operador / Usuario',
                'Glosa / Notas',
            ]);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $order = $row->order;
                    $providerName = $row->product?->provider?->company_or_store_name ?? $row->product?->provider?->name ?? 'Megabodega Bolidrop';
                    $sellerName   = $order?->dropshipper?->company_or_store_name ?? $order?->dropshipper?->name ?? 'Operación Directa / Almacén';
                    $buyerName    = $order?->customer_name ?? 'Almacén Central / Stock';
                    $buyerCity    = $order ? ($order->shipping_city . ' (' . $order->shipping_department . ')') : ($row->warehouse?->city ?? 'N/A');
                    $courierName  = $order?->carrier?->name ?? 'DEX Express Bolivia';

                    fputcsv($handle, [
                        $row->id,
                        $row->created_at->format('Y-m-d H:i:s'),
                        strtoupper($row->movement_category),
                        $row->movement_type,
                        $row->product?->name ?? 'N/A',
                        $row->product?->sku ?? 'N/A',
                        $row->warehouse?->name ?? 'N/A',
                        $row->warehouse?->city ?? 'N/A',
                        $row->quantity,
                        number_format((float) $row->unit_cost_bob, 2, '.', ''),
                        number_format((float) $row->total_cost_bob, 2, '.', ''),
                        $row->stock_physical_before,
                        $row->stock_physical_after,
                        $row->stock_available_before,
                        $row->stock_available_after,
                        $providerName,
                        $sellerName,
                        $buyerName,
                        $buyerCity,
                        $row->document_number ?? $order?->order_number ?? 'S/N',
                        $courierName,
                        $row->user?->name ?? 'Sistema Automático',
                        $row->notes ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // =========================================================
    // REPORTE GLOBAL DE MÉTRICAS DEL PROVEEDOR
    // =========================================================

    /**
     * API JSON: métricas completas filtradas por rango de fechas.
     */
    public function providerMetricsJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $authUser = $request->user();
        if (! $authUser || (! $authUser->isProvider() && ! $authUser->isTenantAdmin())) {
            return response()->json(['status' => 'error', 'message' => 'Sin permisos.'], 403);
        }

        $provider = $authUser->isProvider()
            ? $authUser
            : (
                $request->filled('provider_id')
                    ? User::where('role', 'provider')->find($request->input('provider_id'))
                    : User::where('role', 'provider')->first()
            );

        if (! $provider) {
            return response()->json(['status' => 'error', 'message' => 'Proveedor no encontrado.'], 404);
        }

        $service = app(ProviderMetricsService::class);
        $data    = $service->getMetrics(
            $provider->id,
            $request->input('date_from'),
            $request->input('date_to')
        );

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * Exportar CSV completo de métricas del proveedor.
     */
    public function exportProviderMetrics(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $authUser = $request->user();
        if (! $authUser || (! $authUser->isProvider() && ! $authUser->isTenantAdmin())) {
            abort(403);
        }

        $provider = $authUser->isProvider()
            ? $authUser
            : (
                $request->filled('provider_id')
                    ? User::where('role', 'provider')->find($request->input('provider_id'))
                    : User::where('role', 'provider')->first()
            );

        if (! $provider) {
            abort(404);
        }

        $service  = app(ProviderMetricsService::class);
        $filename = 'reporte_proveedor_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($service, $provider, $request) {
            echo $service->generateMetricsCsv(
                $provider->id,
                $request->input('date_from'),
                $request->input('date_to')
            );
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}


