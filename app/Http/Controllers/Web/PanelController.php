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
use Illuminate\Http\Request;

class PanelController extends Controller
{
    /**
     * Panel de control del Dropshipper:
     * muestra pedidos recientes, billetera y estadísticas de rendimiento.
     */
    public function dropshipper(Request $request)
    {
        // Datos demo: usamos el dropshipper del seeder
        $dropshipper = User::where('role', 'dropshipper')->first();

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

        return view('panels.dropshipper', compact('dropshipper', 'stats', 'recentOrders', 'wallet', 'storeIntegrations'));
    }

    /**
     * Panel de control del Proveedor:
     * muestra productos publicados, inventario en bodegas y liquidaciones.
     */
    public function proveedor(Request $request)
    {
        $provider = User::where('role', 'provider')->first();

        $stats = [
            'total_products'   => 0,
            'active_products'  => 0,
            'total_warehouses' => 0,
            'total_revenue'    => 0.00,
        ];

        $products  = collect();
        $warehouses = collect();
        $wallet    = null;

        if ($provider) {
            $stats['total_products']   = Product::where('provider_id', $provider->id)->count();
            $stats['active_products']  = Product::where('provider_id', $provider->id)->where('status', 'published')->count();
            $stats['total_warehouses'] = Warehouse::where('provider_id', $provider->id)->count();
            $stats['total_revenue'] = (float) OrderItem::where('provider_id', $provider->id)
                ->whereHas('order', fn ($q) => $q->where('status', 'settled'))
                ->selectRaw('SUM(unit_cost_bob * quantity) as total')
                ->value('total') ?? 0;

            $products = Product::with(['category', 'warehouseInventories.warehouse'])
                ->where('provider_id', $provider->id)
                ->latest()
                ->get();

            $warehouses = Warehouse::with(['inventories.product'])
                ->where('provider_id', $provider->id)
                ->get();

            $wallet = Wallet::where('user_id', $provider->id)->first();
        }

        $categories = \App\Models\Category::all();

        return view('panels.proveedor', compact('provider', 'stats', 'products', 'warehouses', 'wallet', 'categories'));
    }

    /**
     * Panel de Administración Global del Tenant:
     * Métricas generales de la plataforma e-commerce COD Bolivia.
     */
    public function admin(Request $request)
    {
        $admin = User::where('role', 'tenant_admin')->first();

        $stats = [
            'total_orders' => Order::count(),
            'total_volume_bob' => (float) Order::sum('total_amount_bob'),
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

        return view('panels.admin', compact(
            'admin', 'stats', 'recentOrders', 'carriers', 'manifests',
            'stockTransfers', 'notifications', 'customers', 'warehouses', 'products', 'users'
        ));
    }

    /**
     * Panel de Operador Logístico / Transportista (DEX Bolivia Express).
     * Gestión de ruta de entregas, recaudación COD y reporte de novedades.
     */
    public function carrier(Request $request)
    {
        $carrierUser = User::where('role', 'carrier')->first() ?? User::where('role', 'tenant_admin')->first();
        $carrier = Carrier::first();

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

        return view('panels.carrier', compact('carrierUser', 'carrier', 'stats', 'ordersInRoute', 'recentDelivered'));
    }

    /**
     * Panel del Cliente de Fulfillment (Tienda Online):
     * Tú vendes — Dropana almacena, empaca y entrega.
     */
    public function fulfillment(Request $request)
    {
        $user = User::where('role', 'fulfillment_client')->first()
            ?? User::where('role', 'tenant_admin')->first();

        $stats = [
            'orders_month'    => Order::count(),
            'delivered'       => Order::where('status', 'delivered')->count(),
            'in_transit'      => Order::where('status', 'dispatched')->count(),
            'stock_units'     => 1240, // demo
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
}

