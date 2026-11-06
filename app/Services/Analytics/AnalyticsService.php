<?php

namespace App\Services\Analytics;

use App\Models\BankSettlementBatch;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    // ─────────────────────────────────────────────────────────────────────────
    // KPIs Globales del Tenant
    // ─────────────────────────────────────────────────────────────────────────
    public function globalKpis(string $tenantId, string $period = '30d'): array
    {
        $from = $this->periodStart($period);

        $ordersQuery = Order::where('tenant_id', $tenantId)->where('created_at', '>=', $from);
        $prevQuery   = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$this->periodStart($this->doublePeriod($period)), $from]);

        $current = [
            'total_orders'        => (clone $ordersQuery)->count(),
            'total_volume_bob'    => (float)(clone $ordersQuery)->sum('total_amount_bob'),
            'platform_fees_bob'   => (float)(clone $ordersQuery)->where('status', 'settled')->sum('platform_fee_bob'),
            'delivered_orders'    => (clone $ordersQuery)->whereIn('status', ['delivered', 'settled'])->count(),
            'cancelled_orders'    => (clone $ordersQuery)->whereIn('status', ['cancelled', 'returned'])->count(),
            'avg_order_value_bob' => (float)(clone $ordersQuery)->avg('total_amount_bob') ?? 0,
        ];

        $previous = [
            'total_orders'     => (clone $prevQuery)->count(),
            'total_volume_bob' => (float)(clone $prevQuery)->sum('total_amount_bob'),
        ];

        $current['delivery_rate']   = $current['total_orders'] > 0
            ? round(($current['delivered_orders'] / $current['total_orders']) * 100, 1)
            : 0;

        $current['orders_growth']   = $previous['total_orders'] > 0
            ? round((($current['total_orders'] - $previous['total_orders']) / $previous['total_orders']) * 100, 1)
            : 0;

        $current['volume_growth']   = $previous['total_volume_bob'] > 0
            ? round((($current['total_volume_bob'] - $previous['total_volume_bob']) / $previous['total_volume_bob']) * 100, 1)
            : 0;

        return $current;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Serie temporal: pedidos y volumen por día (últimos N días)
    // ─────────────────────────────────────────────────────────────────────────
    public function dailySeries(string $tenantId, int $days = 30): Collection
    {
        return Order::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->selectRaw("DATE(created_at) as date,
                         COUNT(*) as orders_count,
                         SUM(total_amount_bob) as volume_bob,
                         SUM(platform_fee_bob) as fees_bob,
                         SUM(CASE WHEN status IN ('delivered','settled') THEN 1 ELSE 0 END) as delivered_count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Distribución de pedidos por departamento boliviano
    // ─────────────────────────────────────────────────────────────────────────
    public function byDepartment(string $tenantId, string $period = '30d'): Collection
    {
        return Order::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $this->periodStart($period))
            ->selectRaw('shipping_department,
                         COUNT(*) as orders_count,
                         SUM(total_amount_bob) as volume_bob,
                         SUM(CASE WHEN status IN (\'delivered\',\'settled\') THEN 1 ELSE 0 END) as delivered_count')
            ->groupBy('shipping_department')
            ->orderByDesc('orders_count')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Top Dropshippers por ventas
    // ─────────────────────────────────────────────────────────────────────────
    public function topDropshippers(string $tenantId, int $limit = 10, string $period = '30d'): Collection
    {
        return Order::where('orders.tenant_id', $tenantId)
            ->where('orders.created_at', '>=', $this->periodStart($period))
            ->join('users', 'users.id', '=', 'orders.dropshipper_id')
            ->selectRaw('users.name,
                         users.city,
                         COUNT(orders.id) as orders_count,
                         SUM(orders.total_amount_bob) as volume_bob,
                         SUM(orders.dropshipper_profit_bob) as profit_bob,
                         SUM(CASE WHEN orders.status IN (\'delivered\',\'settled\') THEN 1 ELSE 0 END) as delivered_count')
            ->groupBy('users.id', 'users.name', 'users.city')
            ->orderByDesc('volume_bob')
            ->limit($limit)
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Top Productos más vendidos
    // ─────────────────────────────────────────────────────────────────────────
    public function topProducts(string $tenantId, int $limit = 10, string $period = '30d'): Collection
    {
        return DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.tenant_id', $tenantId)
            ->where('orders.created_at', '>=', $this->periodStart($period))
            ->selectRaw('products.name,
                         products.id as product_id,
                         SUM(order_items.quantity) as units_sold,
                         SUM(order_items.subtotal_bob) as revenue_bob,
                         COUNT(DISTINCT orders.id) as in_orders')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Distribución de estados de pedidos
    // ─────────────────────────────────────────────────────────────────────────
    public function statusDistribution(string $tenantId, string $period = '30d'): Collection
    {
        return Order::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $this->periodStart($period))
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount_bob) as volume_bob')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resumen de Billeteras del Tenant
    // ─────────────────────────────────────────────────────────────────────────
    public function walletSummary(string $tenantId): array
    {
        $wallets = Wallet::where('tenant_id', $tenantId)->get();

        return [
            'total_available_bob'  => (float)$wallets->sum('balance_available'),
            'total_pending_bob'    => (float)$wallets->sum('balance_pending'),
            'total_withdrawn_bob'  => (float)$wallets->sum('balance_withdrawn'),
            'active_wallets'       => $wallets->count(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resumen de Liquidaciones Bancarias
    // ─────────────────────────────────────────────────────────────────────────
    public function settlementSummary(string $tenantId): array
    {
        $batches = BankSettlementBatch::where('tenant_id', $tenantId)->get();

        return [
            'total_liquidated_bob' => (float)$batches->where('status', 'completed')->sum('total_amount_bob'),
            'pending_bob'          => (float)$batches->whereIn('status', ['pending', 'processing'])->sum('total_amount_bob'),
            'completed_count'      => $batches->where('status', 'completed')->count(),
            'pending_count'        => $batches->whereIn('status', ['pending', 'processing'])->count(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Rendimiento por carrier
    // ─────────────────────────────────────────────────────────────────────────
    public function carrierPerformance(string $tenantId, string $period = '30d'): Collection
    {
        return Order::where('orders.tenant_id', $tenantId)
            ->where('orders.created_at', '>=', $this->periodStart($period))
            ->whereNotNull('orders.carrier_id')
            ->join('carriers', 'carriers.id', '=', 'orders.carrier_id')
            ->selectRaw('carriers.name,
                         COUNT(orders.id) as total_orders,
                         SUM(CASE WHEN orders.status IN (\'delivered\',\'settled\') THEN 1 ELSE 0 END) as delivered,
                         SUM(CASE WHEN orders.status IN (\'failed_delivery\',\'returned\') THEN 1 ELSE 0 END) as failed,
                         SUM(orders.shipping_fee_bob) as shipping_fees_bob')
            ->groupBy('carriers.id', 'carriers.name')
            ->get()
            ->map(function ($row) {
                $row->success_rate = $row->total_orders > 0
                    ? round(($row->delivered / $row->total_orders) * 100, 1) : 0;
                return $row;
            });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────
    private function periodStart(string $period): \Carbon\Carbon
    {
        return match ($period) {
            '7d'   => now()->subDays(7)->startOfDay(),
            '30d'  => now()->subDays(30)->startOfDay(),
            '90d'  => now()->subDays(90)->startOfDay(),
            '365d' => now()->subDays(365)->startOfDay(),
            default=> now()->subDays(30)->startOfDay(),
        };
    }

    private function doublePeriod(string $period): string
    {
        return match ($period) {
            '7d'   => '14d',
            '30d'  => '60d',
            '90d'  => '180d',
            '365d' => '730d',
            default=> '60d',
        };
    }
}
