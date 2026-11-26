<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialReportController extends Controller
{
    /**
     * Muestra la vista principal del Reporte Comercial Adaptativo.
     */
    public function index(Request $request)
    {
        $authUser = $request->user();
        if (! $authUser) {
            abort(401, 'No autenticado');
        }

        $reportData = $this->buildReportData($request, $authUser);

        $providersList = $authUser->isTenantAdmin()
            ? User::where('role', 'provider')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $dropshippersList = $authUser->isTenantAdmin()
            ? User::where('role', 'dropshipper')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('reporte.index', array_merge($reportData, [
            'providersList'    => $providersList,
            'dropshippersList' => $dropshippersList,
            'authUser'         => $authUser,
        ]));
    }

    /**
     * Endpoint JSON reactivo para actualizar filtros desde Alpine.js.
     */
    public function dataJson(Request $request): JsonResponse
    {
        $authUser = $request->user();
        if (! $authUser) {
            return response()->json(['status' => 'error', 'message' => 'No autenticado'], 401);
        }

        $reportData = $this->buildReportData($request, $authUser);

        return response()->json([
            'status' => 'success',
            'data'   => $reportData,
        ]);
    }

    /**
     * Exporta el reporte comercial detallado en formato CSV compatible con Excel.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $authUser = $request->user();
        if (! $authUser) {
            abort(401);
        }

        $reportData = $this->buildReportData($request, $authUser, forExport: true);
        $kpis       = $reportData['kpis'];
        $orders     = $reportData['exportOrders'];

        $targetProviderId = null;
        if ($authUser->isProvider()) {
            $targetProviderId = $authUser->id;
        } elseif ($authUser->isTenantAdmin() && $request->filled('provider_id')) {
            $targetProviderId = $request->input('provider_id');
        }

        $filename = 'reporte_comercial_bolidrop_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($kpis, $orders, $reportData, $authUser, $targetProviderId) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['REPORTE COMERCIAL BOLIDROP - BOLIVIA (COD + QR)']);
            fputcsv($handle, ['Generado por:', $authUser->name . ' (' . $authUser->role . ')']);
            fputcsv($handle, ['Periodo:', $reportData['dateFrom'] . ' al ' . $reportData['dateTo']]);
            fputcsv($handle, ['Fecha de emision:', now()->format('d/m/Y H:i:s')]);
            fputcsv($handle, []);

            // Resumen de KPIs
            fputcsv($handle, ['INDICADOR COMERCIAL', 'VALOR']);
            fputcsv($handle, ['Total Ventas (BOB):', number_format($kpis['total_sales_bob'], 2)]);
            fputcsv($handle, ['Pedidos Totales:', $kpis['orders_count']]);
            fputcsv($handle, ['Pedidos Entregados:', $kpis['delivered_count']]);
            fputcsv($handle, ['Pedidos en Proceso / Transito:', $kpis['pending_count']]);
            fputcsv($handle, ['Novedades Logisticas:', $kpis['novelty_count']]);
            fputcsv($handle, ['Cancelados / Devoluciones:', $kpis['cancelled_count']]);
            fputcsv($handle, ['Tasa de Efectividad Entrega:', $kpis['delivery_rate'] . '%']);
            fputcsv($handle, ['Ingresos Mayoristas Proveedores (BOB):', number_format($kpis['provider_cost_bob'], 2)]);
            fputcsv($handle, ['Ganancias Netas Dropshippers (BOB):', number_format($kpis['dropshipper_profit_bob'], 2)]);
            fputcsv($handle, ['Comisiones Plataforma 4% (BOB):', number_format($kpis['platform_fee_bob'], 2)]);
            fputcsv($handle, ['Fletes Couriers (BOB):', number_format($kpis['shipping_fee_bob'], 2)]);
            fputcsv($handle, []);

            // Detalle de Pedidos
            fputcsv($handle, [
                'No Guia / Pedido',
                'Fecha',
                'Cliente',
                'Telefono',
                'Departamento',
                'Ciudad',
                'Metodo Pago',
                'Estado',
                'Total Venta (BOB)',
                'Costo Mayorista (BOB)',
                'Ganancia Vendedor (BOB)',
                'Comision 4% (BOB)',
                'Flete (BOB)',
            ]);

            foreach ($orders as $o) {
                $totalAmount  = (float) $o->total_amount_bob;
                $costAmount   = (float) $o->cost_amount_bob;
                $profitAmount = (float) $o->dropshipper_profit_bob;

                if ($targetProviderId) {
                    $providerItems = $o->items->filter(function ($i) use ($targetProviderId) {
                        return $i->provider_id === $targetProviderId || ($i->product && $i->product->provider_id === $targetProviderId);
                    });
                    $costAmount   = $providerItems->sum(fn ($i) => (float) $i->unit_cost_bob * $i->quantity);
                    $totalAmount  = $providerItems->sum(fn ($i) => (float) $i->subtotal_bob);
                    $profitAmount = max(0.0, round($totalAmount - $costAmount, 2));
                }

                fputcsv($handle, [
                    $o->order_number,
                    $o->created_at ? $o->created_at->format('d/m/Y H:i') : '',
                    $o->customer_name,
                    $o->customer_phone,
                    $o->shipping_department,
                    $o->shipping_city,
                    $o->payment_method === 'cash_on_delivery_bob' ? 'Efectivo COD' : 'QR Simple ACH',
                    $o->status,
                    number_format($totalAmount, 2, '.', ''),
                    number_format($costAmount, 2, '.', ''),
                    number_format($profitAmount, 2, '.', ''),
                    number_format((float) $o->platform_fee_bob, 2, '.', ''),
                    number_format((float) $o->shipping_fee_bob, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Construye las métricas comerciales agregadas según rol y fechas.
     */
    protected function buildReportData(Request $request, User $authUser, bool $forExport = false): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->subDays(30)->startOfDay();
        $to   = $dateTo   ? Carbon::parse($dateTo)->endOfDay()     : now()->endOfDay();

        // Query Base de Pedidos
        $ordersQuery = Order::with(['items.product', 'items.provider', 'warehouse', 'carrier', 'dropshipper'])
            ->whereBetween('created_at', [$from, $to]);

        $targetProviderId = null;

        // Filtro Multi-Rol
        if ($authUser->isProvider()) {
            $targetProviderId = $authUser->id;
            // Proveedor: pedidos que contengan productos suyos
            $ordersQuery->whereHas('items', function ($q) use ($authUser) {
                $q->where('provider_id', $authUser->id)
                  ->orWhereHas('product', fn ($pq) => $pq->where('provider_id', $authUser->id));
            });
        } elseif ($authUser->isDropshipper()) {
            // Dropshipper: solo pedidos generados por él
            $ordersQuery->where('dropshipper_id', $authUser->id);
        } else {
            // Administrador: puede filtrar opcionalmente por proveedor o dropshipper
            if ($request->filled('provider_id')) {
                $targetProviderId = $request->input('provider_id');
                $ordersQuery->whereHas('items', function ($q) use ($targetProviderId) {
                    $q->where('provider_id', $targetProviderId)
                      ->orWhereHas('product', fn ($pq) => $pq->where('provider_id', $targetProviderId));
                });
            }
            if ($request->filled('dropshipper_id')) {
                $ordersQuery->where('dropshipper_id', $request->input('dropshipper_id'));
            }
        }

        $orders = $ordersQuery->latest('created_at')->get();

        // Cálculo de KPIs
        $totalSalesBob        = 0.0;
        $totalCostBob         = 0.0;
        $totalProfitBob       = 0.0;
        $totalPlatformFeeBob  = 0.0;
        $totalShippingFeeBob  = 0.0;

        $deliveredCount = 0;
        $pendingCount   = 0;
        $noveltyCount   = 0;
        $cancelledCount = 0;

        $paymentDistribution = [
            'cash_on_delivery_bob' => ['count' => 0, 'amount' => 0.0, 'label' => 'Efectivo contra entrega (COD)'],
            'qr_interoperable'      => ['count' => 0, 'amount' => 0.0, 'label' => 'QR Simple Interoperable (ACH)'],
        ];

        $deptDistribution = [];
        $productsAggregate = [];

        foreach ($orders as $order) {
            $orderTotal  = (float) $order->total_amount_bob;
            $orderCost   = (float) $order->cost_amount_bob;
            $orderProfit = (float) $order->dropshipper_profit_bob;
            $orderPlat   = (float) $order->platform_fee_bob;
            $orderShip   = (float) $order->shipping_fee_bob;

            // Invariante de conciliación financiera:
            // Comisión Bolidrop = 4% de GMV si no fue precalculada
            if ($orderPlat <= 0 && $orderTotal > 0) {
                $orderPlat = round($orderTotal * 0.04, 2);
            }
            // Margen dropshipper si no fue asignado = GMV - Costo - Flete - Comisión
            if ($orderProfit <= 0 && $orderTotal > 0) {
                $orderProfit = max(0.0, round($orderTotal - $orderCost - $orderShip - $orderPlat, 2));
            }

            // Si es proveedor o se filtró por uno, calculamos la cuota que corresponde a sus productos
            if ($targetProviderId) {
                $providerItemsCost = $order->items
                    ->filter(fn ($i) => $i->provider_id === $targetProviderId || ($i->product && $i->product->provider_id === $targetProviderId))
                    ->sum(fn ($i) => (float) $i->unit_cost_bob * $i->quantity);

                $providerItemsSales = $order->items
                    ->filter(fn ($i) => $i->provider_id === $targetProviderId || ($i->product && $i->product->provider_id === $targetProviderId))
                    ->sum(fn ($i) => (float) $i->subtotal_bob);

                $totalCostBob  += $providerItemsCost;
                $totalSalesBob += $providerItemsSales;
            } else {
                $totalSalesBob += $orderTotal;
                $totalCostBob  += $orderCost;
            }

            $totalProfitBob      += $orderProfit;
            $totalPlatformFeeBob += $orderPlat;
            $totalShippingFeeBob += $orderShip;

            // Estados
            $st = strtolower($order->status);
            if (in_array($st, ['delivered', 'settled', 'entregado', 'pagado', 'liquidado'])) {
                $deliveredCount++;
            } elseif (in_array($st, ['novelty', 'en novedad', 'novedad'])) {
                $noveltyCount++;
            } elseif (in_array($st, ['cancelled', 'cancelado', 'devolucion', 'returned'])) {
                $cancelledCount++;
            } else {
                $pendingCount++;
            }

            // Métodos de pago
            $pm = $order->payment_method ?: 'cash_on_delivery_bob';
            if (isset($paymentDistribution[$pm])) {
                $paymentDistribution[$pm]['count']++;
                $paymentDistribution[$pm]['amount'] += $orderTotal;
            } else {
                $paymentDistribution['cash_on_delivery_bob']['count']++;
                $paymentDistribution['cash_on_delivery_bob']['amount'] += $orderTotal;
            }

            // Departamentos
            $dept = $order->shipping_department ?: 'Santa Cruz';
            if (! isset($deptDistribution[$dept])) {
                $deptDistribution[$dept] = ['count' => 0, 'amount' => 0.0];
            }
            $deptDistribution[$dept]['count']++;
            $deptDistribution[$dept]['amount'] += $orderTotal;

            // Productos
            foreach ($order->items as $item) {
                $isTargetProduct = true;
                if ($targetProviderId) {
                    $isTargetProduct = ($item->provider_id === $targetProviderId) || ($item->product && $item->product->provider_id === $targetProviderId);
                }

                if (! $isTargetProduct) {
                    continue;
                }

                $pId   = $item->product_id ?: 'prod-unknown';
                $pName = $item->product?->name ?: 'Producto de Catálogo';
                $pSku  = $item->product?->sku ?: 'SKU-N/D';

                if (! isset($productsAggregate[$pId])) {
                    $productsAggregate[$pId] = [
                        'name'         => $pName,
                        'sku'          => $pSku,
                        'units'        => 0,
                        'cost_total'   => 0.0,
                        'sales_total'  => 0.0,
                        'profit_total' => 0.0,
                    ];
                }

                $units = (int) $item->quantity;
                $cTot  = (float) $item->unit_cost_bob * $units;
                $sTot  = (float) $item->subtotal_bob;

                $productsAggregate[$pId]['units']        += $units;
                $productsAggregate[$pId]['cost_total']   += $cTot;
                $productsAggregate[$pId]['sales_total']  += $sTot;
                $productsAggregate[$pId]['profit_total'] += max(0, $sTot - $cTot);
            }
        }

        $ordersCount = $orders->count();
        $deliveryRate = $ordersCount > 0 ? round(($deliveredCount / $ordersCount) * 100, 1) : 0.0;

        // Ordenar top productos por ventas descendente
        uasort($productsAggregate, fn ($a, $b) => $b['sales_total'] <=> $a['sales_total']);
        $topProducts = array_slice(array_values($productsAggregate), 0, 8);

        // Ordenar departamentos por ventas descendente
        uasort($deptDistribution, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        // Formatear pedidos reactivos para la vista y Alpine.js
        $formattedRecentOrders = $orders->take(50)->map(function ($o) use ($targetProviderId) {
            $totalAmount  = (float) $o->total_amount_bob;
            $costAmount   = (float) $o->cost_amount_bob;
            $profitAmount = (float) $o->dropshipper_profit_bob;

            if ($targetProviderId) {
                $providerItems = $o->items->filter(function ($i) use ($targetProviderId) {
                    return $i->provider_id === $targetProviderId || ($i->product && $i->product->provider_id === $targetProviderId);
                });
                $costAmount   = $providerItems->sum(fn ($i) => (float) $i->unit_cost_bob * $i->quantity);
                $totalAmount  = $providerItems->sum(fn ($i) => (float) $i->subtotal_bob);
                $profitAmount = max(0.0, round($totalAmount - $costAmount, 2));
            }

            return [
                'id'                     => $o->id,
                'order_number'           => $o->order_number,
                'created_at_formatted'   => $o->created_at ? $o->created_at->format('d/m/Y H:i') : '—',
                'customer_name'          => $o->customer_name,
                'customer_phone'         => $o->customer_phone,
                'shipping_city'          => $o->shipping_city ?: 'Santa Cruz',
                'shipping_department'    => $o->shipping_department ?: 'Santa Cruz',
                'payment_method'         => $o->payment_method ?: 'cash_on_delivery_bob',
                'status'                 => $o->status,
                'total_amount_bob'       => round($totalAmount, 2),
                'cost_amount_bob'        => round($costAmount, 2),
                'dropshipper_profit_bob' => round($profitAmount, 2),
                'shipping_fee_bob'       => (float) $o->shipping_fee_bob,
                'platform_fee_bob'       => (float) $o->platform_fee_bob,
            ];
        })->values()->all();

        $result = [
            'dateFrom'            => $from->format('Y-m-d'),
            'dateTo'              => $to->format('Y-m-d'),
            'kpis'                => [
                'total_sales_bob'        => round($totalSalesBob, 2),
                'orders_count'           => $ordersCount,
                'delivered_count'        => $deliveredCount,
                'pending_count'          => $pendingCount,
                'novelty_count'          => $noveltyCount,
                'cancelled_count'        => $cancelledCount,
                'delivery_rate'          => $deliveryRate,
                'provider_cost_bob'      => round($totalCostBob, 2),
                'dropshipper_profit_bob' => round($totalProfitBob, 2),
                'platform_fee_bob'       => round($totalPlatformFeeBob, 2),
                'shipping_fee_bob'       => round($totalShippingFeeBob, 2),
            ],
            'paymentDistribution' => $paymentDistribution,
            'deptDistribution'    => $deptDistribution,
            'topProducts'         => $topProducts,
            'recentOrders'        => $formattedRecentOrders,
        ];

        if ($forExport) {
            $result['exportOrders'] = $orders;
        }

        return $result;
    }
}
