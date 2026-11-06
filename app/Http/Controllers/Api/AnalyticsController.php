<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /** Métricas ejecutivas generales de la plataforma */
    public function dashboard(Request $request): JsonResponse
    {
        $totalOrders = Order::count();
        $deliveredOrders = Order::where('status', 'settled')->count();
        $cancelledOrders = Order::where('status', 'cancelled')->count();

        $grossRevenueBob = (float) Order::sum('total_amount_bob');
        $settledRevenueBob = (float) Order::where('status', 'settled')->sum('total_amount_bob');
        $platformFeesBob = (float) Order::where('status', 'settled')->sum('platform_fee_bob');
        $dropshipperProfitsBob = (float) Order::where('status', 'settled')->sum('dropshipper_profit_bob');

        $deliverySuccessRate = $totalOrders > 0
            ? round(($deliveredOrders / $totalOrders) * 100, 2)
            : 0;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'summary' => [
                    'total_orders'           => $totalOrders,
                    'delivered_orders'       => $deliveredOrders,
                    'cancelled_orders'       => $cancelledOrders,
                    'delivery_success_rate'  => $deliverySuccessRate . '%',
                    'gross_volume_bob'       => $grossRevenueBob,
                    'settled_volume_bob'     => $settledRevenueBob,
                    'platform_earnings_bob'  => $platformFeesBob,
                    'dropshipper_payout_bob' => $dropshipperProfitsBob,
                ],
                'currency' => 'BOB',
                'symbol'   => 'Bs',
            ],
        ]);
    }

    /** Top productos más vendidos y rentables en Bolivia */
    public function topProducts(Request $request): JsonResponse
    {
        $topProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_units_sold'), DB::raw('SUM(subtotal_bob) as total_gross_bob'))
            ->groupBy('product_id')
            ->orderByDesc('total_units_sold')
            ->with('product')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $topProducts,
        ]);
    }

    /** Desempeño y ROI por dropshipper */
    public function dropshippersPerformance(Request $request): JsonResponse
    {
        $performance = User::where('role', 'dropshipper')
            ->withCount(['orders'])
            ->get()
            ->map(function ($dropshipper) {
                $settledProfit = (float) Order::where('dropshipper_id', $dropshipper->id)
                    ->where('status', 'settled')
                    ->sum('dropshipper_profit_bob');

                $deliveredOrders = Order::where('dropshipper_id', $dropshipper->id)
                    ->where('status', 'settled')
                    ->count();

                $successRate = $dropshipper->orders_count > 0
                    ? round(($deliveredOrders / $dropshipper->orders_count) * 100, 2)
                    : 0;

                return [
                    'id'               => $dropshipper->id,
                    'name'             => $dropshipper->name,
                    'email'            => $dropshipper->email,
                    'total_orders'     => $dropshipper->orders_count,
                    'delivered_orders' => $deliveredOrders,
                    'success_rate'     => $successRate . '%',
                    'profit_earned_bob'=> $settledProfit,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $performance,
        ]);
    }
}
