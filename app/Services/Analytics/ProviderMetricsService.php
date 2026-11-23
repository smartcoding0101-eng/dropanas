<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProviderMetricsService
{
    /**
     * Obtiene el conjunto completo de métricas consolidadas del proveedor en el rango de fechas.
     */
    public function getMetrics(string $providerId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dates = $this->resolveDateRange($dateFrom, $dateTo);
        $from  = $dates['from'];
        $to    = $dates['to'];
        $days  = $dates['days'];

        $provider = User::where('id', $providerId)->first();
        $tenantId = $provider?->tenant_id ?? \App\Models\Tenant::value('id');

        // 1. Productos y Valorado de Inventario
        $products = Product::with(['category', 'warehouseInventories.warehouse'])
            ->where('provider_id', $providerId)
            ->get();

        $valuation = $this->calculateInventoryValuation($products, $providerId);

        // 2. Consulta base de OrderItems del proveedor en el periodo
        $orderItemsQuery = OrderItem::with(['order.carrier', 'order.warehouse', 'order.dropshipper', 'product'])
            ->where('provider_id', $providerId)
            ->whereHas('order', function ($q) use ($from, $to, $tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->whereBetween('created_at', [$from, $to]);
            });

        $orderItems = $orderItemsQuery->latest('created_at')->get();

        // 3. Flujo y Embudo de Ventas (Sales Flow Funnel)
        $funnel = $this->calculateSalesFunnel($orderItems);

        // 4. Rotación y Velocidad de Productos
        $rotation = $this->calculateProductRotation($products, $orderItems, $days);

        // 5. Desglose Geográfico (Ventas por Departamento)
        $geo = $this->calculateGeoDistribution($orderItems);

        // 6. Top Vendedores (Dropshippers que comercializan los productos)
        $topSellers = $this->calculateTopSellers($orderItems);

        // 7. Billetera y Liquidaciones
        $wallet = Wallet::where('user_id', $providerId)->first();

        // 8. Lista detallada de movimientos de ventas
        $movements = $this->formatSalesMovements($orderItems);

        return [
            'period' => [
                'from'        => $from->format('Y-m-d'),
                'to'          => $to->format('Y-m-d'),
                'days'        => $days,
                'label'       => $dates['label'],
            ],
            'funnel'          => $funnel,
            'valuation'       => $valuation,
            'rotation'        => $rotation,
            'geo'             => $geo,
            'top_sellers'     => $topSellers,
            'wallet'          => [
                'available_bob' => (float) ($wallet?->balance_available ?? 0),
                'pending_bob'   => (float) ($wallet?->balance_pending ?? 0),
                'withdrawn_bob' => (float) ($wallet?->balance_withdrawn ?? 0),
            ],
            'movements'       => $movements,
            'summary_kpis'    => [
                'total_units_sold'         => $funnel['total_units_sold'],
                'gross_revenue_cost_bob'   => $funnel['total_provider_cost_bob'],
                'delivered_cost_bob'       => $funnel['delivered_provider_cost_bob'],
                'pending_cost_bob'         => $funnel['pending_provider_cost_bob'],
                'delivery_success_rate'    => $funnel['delivery_rate_percent'],
                'total_inventory_cost_bob' => $valuation['total_valuation_cost_bob'],
                'total_stock_available'    => $valuation['total_stock_available'],
                'top_mover_product'        => $rotation['top_mover'] ?? null,
            ]
        ];
    }

    /**
     * Resuelve el rango de fechas con valores predeterminados seguros (últimos 30 días).
     */
    protected function resolveDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $now = Carbon::now();

        if ($dateFrom && $dateTo) {
            $from = Carbon::parse($dateFrom)->startOfDay();
            $to   = Carbon::parse($dateTo)->endOfDay();
            if ($from->gt($to)) {
                $temp = $from;
                $from = $to->copy()->startOfDay();
                $to   = $temp->copy()->endOfDay();
            }
            $days = max(1, (int) $from->diffInDays($to) + 1);
            $label = $from->format('d/m/Y') . ' al ' . $to->format('d/m/Y');
        } elseif ($dateFrom && !$dateTo) {
            $from  = Carbon::parse($dateFrom)->startOfDay();
            $to    = $now->copy()->endOfDay();
            $days  = max(1, (int) $from->diffInDays($to) + 1);
            $label = 'Desde ' . $from->format('d/m/Y');
        } else {
            // Predeterminado: Últimos 30 días
            $from  = $now->copy()->subDays(29)->startOfDay();
            $to    = $now->copy()->endOfDay();
            $days  = 30;
            $label = 'Últimos 30 días';
        }

        return [
            'from'  => $from,
            'to'    => $to,
            'days'  => $days,
            'label' => $label,
        ];
    }

    /**
     * Calcula el valorado contable y comercial del inventario en bodegas.
     */
    protected function calculateInventoryValuation(Collection $products, string $providerId): array
    {
        $totalPhysical  = 0;
        $totalReserved  = 0;
        $totalAvailable = 0;
        $totalCostBob   = 0.0;
        $totalSugBob    = 0.0;

        $productValuations = [];
        $warehouseTotals   = [];

        foreach ($products as $p) {
            $costPrice = (float) $p->cost_price_bob;
            $sugPrice  = (float) $p->suggested_price_bob;

            $pPhysical  = 0;
            $pReserved  = 0;
            $pAvailable = 0;

            foreach ($p->warehouseInventories as $inv) {
                $whId   = (string) $inv->warehouse_id;
                $whName = $inv->warehouse->name ?? 'Bodega Desconocida';
                $whCity = $inv->warehouse->city ?? 'Central';
                $whDept = $inv->warehouse->department ?? 'Santa Cruz';

                $phys = (int) $inv->stock_physical;
                $resv = (int) $inv->stock_reserved;
                $avail= (int) $inv->stock_available;

                $pPhysical  += $phys;
                $pReserved  += $resv;
                $pAvailable += $avail;

                if (!isset($warehouseTotals[$whId])) {
                    $warehouseTotals[$whId] = [
                        'warehouse_id'     => $whId,
                        'name'             => $whName,
                        'city'             => $whCity,
                        'department'       => $whDept,
                        'stock_physical'   => 0,
                        'stock_reserved'   => 0,
                        'stock_available'  => 0,
                        'valuation_cost_bob'=> 0.0,
                        'valuation_sug_bob' => 0.0,
                        'products_count'   => 0,
                    ];
                }

                $warehouseTotals[$whId]['stock_physical']   += $phys;
                $warehouseTotals[$whId]['stock_reserved']   += $resv;
                $warehouseTotals[$whId]['stock_available']  += $avail;
                $warehouseTotals[$whId]['valuation_cost_bob']+= round($avail * $costPrice, 2);
                $warehouseTotals[$whId]['valuation_sug_bob'] += round($avail * $sugPrice, 2);
                if ($avail > 0 || $phys > 0) {
                    $warehouseTotals[$whId]['products_count']++;
                }
            }

            $prodCostVal = round($pAvailable * $costPrice, 2);
            $prodSugVal  = round($pAvailable * $sugPrice, 2);
            $prodMargin  = round($prodSugVal - $prodCostVal, 2);

            $totalPhysical  += $pPhysical;
            $totalReserved  += $pReserved;
            $totalAvailable += $pAvailable;
            $totalCostBob   += $prodCostVal;
            $totalSugBob    += $prodSugVal;

            $productValuations[] = [
                'product_id'          => $p->id,
                'name'                => $p->name,
                'sku'                 => $p->sku,
                'category'            => $p->category?->name ?? 'General',
                'cost_price_bob'      => $costPrice,
                'suggested_price_bob' => $sugPrice,
                'stock_physical'      => $pPhysical,
                'stock_reserved'      => $pReserved,
                'stock_available'     => $pAvailable,
                'valuation_cost_bob'  => $prodCostVal,
                'valuation_sug_bob'   => $prodSugVal,
                'potential_margin_bob'=> $prodMargin,
            ];
        }

        $totalPotentialMargin = round($totalSugBob - $totalCostBob, 2);
        $markupPercent = $totalCostBob > 0 ? round(($totalPotentialMargin / $totalCostBob) * 100, 1) : 0;

        // Añadir porcentaje sobre el total a cada bodega
        $warehousesList = array_values(array_map(function ($wh) use ($totalCostBob) {
            $wh['share_percent'] = $totalCostBob > 0 ? round(($wh['valuation_cost_bob'] / $totalCostBob) * 100, 1) : 0;
            return $wh;
        }, $warehouseTotals));

        return [
            'total_stock_physical'      => $totalPhysical,
            'total_stock_reserved'      => $totalReserved,
            'total_stock_available'     => $totalAvailable,
            'total_valuation_cost_bob'  => round($totalCostBob, 2),
            'total_valuation_sug_bob'   => round($totalSugBob, 2),
            'total_potential_margin_bob'=> $totalPotentialMargin,
            'average_markup_percent'    => $markupPercent,
            'warehouses'                => $warehousesList,
            'products'                  => $productValuations,
        ];
    }

    /**
     * Calcula el embudo y ciclo de vida de los pedidos del proveedor.
     */
    protected function calculateSalesFunnel(Collection $orderItems): array
    {
        $uniqueOrders = $orderItems->pluck('order')->filter()->unique('id');

        $totalOrdersCount = $uniqueOrders->count();
        $totalUnitsSold   = (int) $orderItems->sum('quantity');

        // Valores monetarios
        $totalSalesPublicBob   = 0.0;
        $totalProviderCostBob  = 0.0;
        $deliveredProviderCost = 0.0;
        $pendingProviderCost   = 0.0;
        $cancelledProviderCost = 0.0;

        $countsByStatus = [
            'pending_confirmation' => 0,
            'confirmed'            => 0,
            'dispatched'           => 0,
            'in_transit'           => 0,
            'delivered'            => 0,
            'settled'              => 0,
            'cancelled'            => 0,
            'returned'             => 0,
        ];

        foreach ($orderItems as $item) {
            $costSubtotal  = (float) $item->unit_cost_bob * $item->quantity;
            $priceSubtotal = (float) ($item->unit_price_bob ?? 0) * $item->quantity;

            $totalProviderCostBob += $costSubtotal;
            $totalSalesPublicBob  += $priceSubtotal;

            $status = $item->order?->status ?? 'pending_confirmation';
            if (isset($countsByStatus[$status])) {
                $countsByStatus[$status]++;
            }

            if (in_array($status, ['delivered', 'settled'])) {
                $deliveredProviderCost += $costSubtotal;
            } elseif (in_array($status, ['pending_confirmation', 'confirmed', 'dispatched', 'in_transit'])) {
                $pendingProviderCost += $costSubtotal;
            } elseif (in_array($status, ['cancelled', 'returned'])) {
                $cancelledProviderCost += $costSubtotal;
            }
        }

        // Fases consolidadas del embudo
        $phase1Count = $uniqueOrders->whereIn('status', ['pending_confirmation', 'confirmed'])->count();
        $phase2Count = $uniqueOrders->whereIn('status', ['dispatched', 'in_transit'])->count();
        $phase3Count = $uniqueOrders->whereIn('status', ['delivered', 'settled'])->count();
        $phase4Count = $uniqueOrders->whereIn('status', ['cancelled', 'returned'])->count();

        // Tasa de éxito de entrega (Concluidas: delivered / (delivered + cancelled))
        $concludedCount = $phase3Count + $phase4Count;
        $deliveryRatePercent = $concludedCount > 0
            ? round(($phase3Count / $concludedCount) * 100, 1)
            : ($totalOrdersCount > 0 ? round(($phase3Count / $totalOrdersCount) * 100, 1) : 0);

        $avgTicketCostBob = $totalOrdersCount > 0 ? round($totalProviderCostBob / $totalOrdersCount, 2) : 0;

        return [
            'total_orders'               => $totalOrdersCount,
            'total_units_sold'           => $totalUnitsSold,
            'total_sales_public_bob'     => round($totalSalesPublicBob, 2),
            'total_provider_cost_bob'    => round($totalProviderCostBob, 2),
            'delivered_provider_cost_bob'=> round($deliveredProviderCost, 2),
            'pending_provider_cost_bob'  => round($pendingProviderCost, 2),
            'cancelled_provider_cost_bob'=> round($cancelledProviderCost, 2),
            'avg_ticket_cost_bob'        => $avgTicketCostBob,
            'delivery_rate_percent'      => $deliveryRatePercent,
            'phases' => [
                'phase_1_prep' => [
                    'label'       => 'Confirmación & Bodega',
                    'description' => 'Órdenes confirmadas preparándose para despacho en bodega',
                    'count'       => $phase1Count,
                    'status_list' => ['pending_confirmation', 'confirmed'],
                ],
                'phase_2_transit' => [
                    'label'       => 'En Ruta con DEX Courier',
                    'description' => 'Paquetes despachados en camino al cliente final (COD)',
                    'count'       => $phase2Count,
                    'status_list' => ['dispatched', 'in_transit'],
                ],
                'phase_3_delivered' => [
                    'label'       => 'Entregados & Recaudados',
                    'description' => 'Entregas efectivas con cobro contra entrega confirmado',
                    'count'       => $phase3Count,
                    'status_list' => ['delivered', 'settled'],
                ],
                'phase_4_novedades' => [
                    'label'       => 'Devoluciones / Novedades',
                    'description' => 'Entregas no concretadas o canceladas por el comprador',
                    'count'       => $phase4Count,
                    'status_list' => ['cancelled', 'returned'],
                ],
            ],
            'counts_by_status'           => $countsByStatus,
        ];
    }

    /**
     * Calcula la rotación, velocidad diaria y días de inventario restante de cada producto.
     */
    protected function calculateProductRotation(Collection $products, Collection $orderItems, int $daysPeriod): array
    {
        // Agrupar items vendidos por producto
        $salesByProduct = $orderItems->groupBy('product_id')->map(function ($items) {
            return [
                'units_sold'       => (int) $items->sum('quantity'),
                'total_cost_bob'   => (float) $items->sum(fn($i) => (float)$i->unit_cost_bob * $i->quantity),
                'delivered_units'  => (int) $items->filter(fn($i) => in_array($i->order?->status, ['delivered', 'settled']))->sum('quantity'),
                'orders_count'     => $items->pluck('order_id')->unique()->count(),
            ];
        });

        $itemsList = [];
        $topMover  = null;
        $maxSold   = -1;

        foreach ($products as $p) {
            $pid   = $p->id;
            $sales = $salesByProduct->get($pid, [
                'units_sold'      => 0,
                'total_cost_bob'  => 0.0,
                'delivered_units' => 0,
                'orders_count'    => 0,
            ]);

            $stockAvailable = (int) $p->warehouseInventories->sum('stock_available');
            $stockPhysical  = (int) $p->warehouseInventories->sum('stock_physical');
            $stockReserved  = (int) $p->warehouseInventories->sum('stock_reserved');

            $unitsSold = $sales['units_sold'];
            if ($unitsSold > $maxSold) {
                $maxSold  = $unitsSold;
                $topMover = [
                    'id'   => $p->id,
                    'name' => $p->name,
                    'sku'  => $p->sku,
                    'sold' => $unitsSold,
                ];
            }

            // Velocidad diaria (unidades por día)
            $velocityPerDay = round($unitsSold / max(1, $daysPeriod), 2);

            // Días de inventario restante (Runway)
            if ($stockAvailable <= 0) {
                $runwayDays = 0;
            } elseif ($velocityPerDay > 0) {
                $runwayDays = (int) round($stockAvailable / $velocityPerDay);
            } else {
                $runwayDays = 999; // Sin ventas en el período
            }

            // Tasa de rotación en el período (Turnover Ratio)
            $avgStock = max(1, ($stockAvailable + ($unitsSold / 2)));
            $turnoverRatio = round($unitsSold / $avgStock, 2);

            // Clasificación ABC y Semáforo
            if ($unitsSold >= 15 || $turnoverRatio >= 0.4 || ($velocityPerDay >= 0.5 && $runwayDays <= 15)) {
                $abcClass = 'A';
                $badgeColor = 'emerald';
                $badgeText = 'Alta Rotación 🚀';
            } elseif ($unitsSold > 0 || ($runwayDays >= 15 && $runwayDays <= 60)) {
                $abcClass = 'B';
                $badgeColor = 'indigo';
                $badgeText = 'Media / Estable 📦';
            } else {
                $abcClass = 'C';
                $badgeColor = 'amber';
                $badgeText = 'Baja / Estancado ⚠️';
            }

            // Recomendación de reabastecimiento
            if ($stockAvailable == 0) {
                $recommendation = '🔴 Quiebre de Stock: Fabricar o reabastecer de inmediato.';
            } elseif ($runwayDays <= 7) {
                $recommendation = "⚠️ Stock Crítico ({$runwayDays} días restantes): Generar orden de ingreso urgente.";
            } elseif ($runwayDays <= 20) {
                $recommendation = "🟢 Demanda Acelerada ({$runwayDays} días restantes): Planificar reposición.";
            } elseif ($runwayDays <= 60) {
                $recommendation = '✅ Inventario Óptimo: Nivel de stock equilibrado con la demanda.';
            } else {
                $recommendation = 'ℹ️ Rotación Lenta: Incentivar con dropshippers o activar promociones.';
            }

            $img = (!empty($p->images) && is_array($p->images)) ? $p->images[0] : null;

            $itemsList[] = [
                'product_id'        => $p->id,
                'name'              => $p->name,
                'sku'               => $p->sku,
                'category'          => $p->category?->name ?? 'General',
                'image_url'         => $img,
                'cost_price_bob'    => (float) $p->cost_price_bob,
                'suggested_price_bob'=> (float) $p->suggested_price_bob,
                'stock_available'   => $stockAvailable,
                'stock_physical'    => $stockPhysical,
                'stock_reserved'    => $stockReserved,
                'units_sold'        => $unitsSold,
                'delivered_units'   => $sales['delivered_units'],
                'orders_count'      => $sales['orders_count'],
                'revenue_cost_bob'  => round($sales['total_cost_bob'], 2),
                'velocity_per_day'  => $velocityPerDay,
                'runway_days'       => $runwayDays,
                'turnover_ratio'    => $turnoverRatio,
                'abc_class'         => $abcClass,
                'badge_color'       => $badgeColor,
                'badge_text'        => $badgeText,
                'recommendation'    => $recommendation,
            ];
        }

        // Ordenar por unidades vendidas descendente
        usort($itemsList, fn($a, $b) => $b['units_sold'] <=> $a['units_sold']);

        return [
            'top_mover' => $topMover,
            'items'     => $itemsList,
        ];
    }

    /**
     * Calcula la distribución geográfica de entregas en Bolivia.
     */
    protected function calculateGeoDistribution(Collection $orderItems): array
    {
        $byDept = [];
        $totalOrders = 0;

        foreach ($orderItems as $item) {
            $order = $item->order;
            if (!$order) continue;

            $dept = trim($order->shipping_department ?? 'Santa Cruz');
            if (!$dept) $dept = 'Santa Cruz';

            if (!isset($byDept[$dept])) {
                $byDept[$dept] = [
                    'department'   => $dept,
                    'orders_count' => 0,
                    'units_count'  => 0,
                    'cost_bob'     => 0.0,
                ];
            }

            $byDept[$dept]['orders_count']++;
            $byDept[$dept]['units_count'] += $item->quantity;
            $byDept[$dept]['cost_bob']    += ((float) $item->unit_cost_bob * $item->quantity);
            $totalOrders++;
        }

        $list = array_values($byDept);
        usort($list, fn($a, $b) => $b['orders_count'] <=> $a['orders_count']);

        return array_map(function ($d) use ($totalOrders) {
            $d['percentage'] = $totalOrders > 0 ? round(($d['orders_count'] / $totalOrders) * 100, 1) : 0;
            $d['cost_bob']   = round($d['cost_bob'], 2);
            return $d;
        }, $list);
    }

    /**
     * Calcula los vendedores dropshippers con mayor tracción comercial para este proveedor.
     */
    protected function calculateTopSellers(Collection $orderItems): array
    {
        $sellers = [];

        foreach ($orderItems as $item) {
            $dropshipper = $item->order?->dropshipper;
            if (!$dropshipper) continue;

            $dsId = $dropshipper->id;
            if (!isset($sellers[$dsId])) {
                $sellers[$dsId] = [
                    'id'           => $dsId,
                    'name'         => $dropshipper->name,
                    'store_name'   => $dropshipper->company_or_store_name ?? 'Tienda E-commerce',
                    'city'         => $dropshipper->city ?? 'Bolivia',
                    'department'   => $dropshipper->department ?? 'Nacional',
                    'orders_count' => 0,
                    'units_count'  => 0,
                    'volume_cost_bob' => 0.0,
                ];
            }

            $sellers[$dsId]['orders_count']++;
            $sellers[$dsId]['units_count'] += $item->quantity;
            $sellers[$dsId]['volume_cost_bob'] += ((float) $item->unit_cost_bob * $item->quantity);
        }

        $result = array_values($sellers);
        usort($result, fn($a, $b) => $b['units_count'] <=> $a['units_count']);

        return array_slice($result, 0, 10);
    }

    /**
     * Formatea los movimientos detallados de ventas para visualización y filtrado en frontend.
     */
    protected function formatSalesMovements(Collection $orderItems): array
    {
        return $orderItems->map(function ($item) {
            $order = $item->order;
            $costSubtotal = round((float) $item->unit_cost_bob * $item->quantity, 2);
            $status = $order?->status ?? 'pending_confirmation';

            // Estado de liquidación financiero
            if ($status === 'settled') {
                $settlementStatus = 'Acreditado en Billetera';
                $settlementBadge  = 'emerald';
            } elseif ($status === 'delivered') {
                $settlementStatus = 'Recaudado COD (Por Liquidar)';
                $settlementBadge  = 'blue';
            } elseif (in_array($status, ['dispatched', 'in_transit'])) {
                $settlementStatus = 'En Tránsito COD';
                $settlementBadge  = 'amber';
            } elseif (in_array($status, ['cancelled', 'returned'])) {
                $settlementStatus = 'Cancelado / No Cobrado';
                $settlementBadge  = 'red';
            } else {
                $settlementStatus = 'Pendiente Despacho';
                $settlementBadge  = 'slate';
            }

            return [
                'id'                 => $item->id,
                'order_id'           => $order?->id,
                'order_number'       => $order?->order_number ?? 'S/N',
                'tracking_number'    => $order?->tracking_number ?? ($order?->order_number ?? 'S/N'),
                'date'               => $order?->created_at ? $order->created_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
                'date_human'         => $order?->created_at ? $order->created_at->diffForHumans() : 'Reciente',
                'product_name'       => $item->product?->name ?? 'Producto',
                'product_sku'        => $item->product?->sku ?? 'S/SKU',
                'quantity'           => (int) $item->quantity,
                'unit_cost_bob'      => (float) $item->unit_cost_bob,
                'subtotal_cost_bob'  => $costSubtotal,
                'customer_name'      => $order?->customer_name ?? 'Consumidor Final',
                'destination_city'   => $order?->shipping_city ?? 'Santa Cruz',
                'destination_dept'   => $order?->shipping_department ?? 'Santa Cruz',
                'carrier_name'       => $order?->carrier?->name ?? 'DEX Bolivia Express',
                'order_status'       => $status,
                'order_status_label' => ucfirst(str_replace('_', ' ', $status)),
                'settlement_status'  => $settlementStatus,
                'settlement_badge'   => $settlementBadge,
            ];
        })->values()->toArray();
    }

    /**
     * Genera el contenido CSV con BOM UTF-8 para apertura directa en Microsoft Excel.
     */
    public function generateMetricsCsv(string $providerId, ?string $dateFrom = null, ?string $dateTo = null): string
    {
        $data = $this->getMetrics($providerId, $dateFrom, $dateTo);

        $output = fopen('php://temp', 'r+');

        // Escribir Byte Order Mark (BOM) UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // 1. Cabecera del Reporte
        fputcsv($output, ['REPORTE DE METRICAS, VENTAS Y ROTACION DE INVENTARIOS — BOLIDROP BOLIVIA']);
        fputcsv($output, ['Rango de Fechas:', $data['period']['from'] . ' al ' . $data['period']['to'] . ' (' . $data['period']['days'] . ' dias)']);
        fputcsv($output, ['Fecha de Generacion:', now()->format('d/m/Y H:i:s')]);
        fputcsv($output, []);

        // 2. Resumen Ejecutivo de Métricas
        fputcsv($output, ['--- RESUMEN EJECUTIVO DE VENTAS Y COBERTURA ---']);
        fputcsv($output, ['Indicador', 'Valor']);
        fputcsv($output, ['Unidades Vendidas:', $data['funnel']['total_units_sold']]);
        fputcsv($output, ['Ingresos Mayoristas Totales (BOB):', number_format($data['funnel']['total_provider_cost_bob'], 2)]);
        fputcsv($output, ['Ingresos Liquidados en Billetera (BOB):', number_format($data['funnel']['delivered_provider_cost_bob'], 2)]);
        fputcsv($output, ['Ingresos en Transito COD (BOB):', number_format($data['funnel']['pending_provider_cost_bob'], 2)]);
        fputcsv($output, ['Tasa de Efectividad de Entrega (%):', $data['funnel']['delivery_rate_percent'] . '%']);
        fputcsv($output, ['Valorado de Inventario al Costo (BOB):', number_format($data['valuation']['total_valuation_cost_bob'], 2)]);
        fputcsv($output, ['Valorado a Precio Sugerido (BOB):', number_format($data['valuation']['total_valuation_sug_bob'], 2)]);
        fputcsv($output, ['Margen Comercial Potencial (BOB):', number_format($data['valuation']['total_potential_margin_bob'], 2)]);
        fputcsv($output, []);

        // 3. Valorado por Almacenes
        fputcsv($output, ['--- VALORADO DE INVENTARIO POR ALMACEN ---']);
        fputcsv($output, ['Almacen', 'Departamento', 'Ciudad', 'Stock Disponible', 'Stock Fisico', 'Valorado Costo BOB', 'Valorado Sugerido BOB', 'Participacion %']);
        foreach ($data['valuation']['warehouses'] as $wh) {
            fputcsv($output, [
                $wh['name'],
                $wh['department'],
                $wh['city'],
                $wh['stock_available'],
                $wh['stock_physical'],
                number_format($wh['valuation_cost_bob'], 2),
                number_format($wh['valuation_sug_bob'], 2),
                $wh['share_percent'] . '%',
            ]);
        }
        fputcsv($output, []);

        // 4. Rotación de Productos
        fputcsv($output, ['--- ROTACION Y VELOCIDAD DE PRODUCTOS ---']);
        fputcsv($output, ['SKU', 'Producto', 'Categoria', 'Costo Unit BOB', 'Stock Disp', 'Uds Vendidas', 'Velocidad Uds/Dia', 'Dias Cobertura (Runway)', 'Tasa Rotacion', 'Clasificacion ABC', 'Recomendacion Operativa']);
        foreach ($data['rotation']['items'] as $item) {
            fputcsv($output, [
                $item['sku'],
                $item['name'],
                $item['category'],
                number_format($item['cost_price_bob'], 2),
                $item['stock_available'],
                $item['units_sold'],
                $item['velocity_per_day'],
                $item['runway_days'] === 999 ? 'Sin ventas' : $item['runway_days'],
                $item['turnover_ratio'],
                $item['abc_class'] . ' (' . $item['badge_text'] . ')',
                $item['recommendation'],
            ]);
        }
        fputcsv($output, []);

        // 5. Detalle de Movimientos de Ventas
        fputcsv($output, ['--- DETALLE DE MOVIMIENTOS DE VENTAS ---']);
        fputcsv($output, ['Numero Pedido', 'Guia DEX', 'Fecha', 'Producto', 'SKU', 'Cantidad', 'Costo Unit BOB', 'Subtotal BOB', 'Cliente', 'Ciudad', 'Departamento', 'Courier', 'Estado Entrega', 'Estado Liquidacion']);
        foreach ($data['movements'] as $m) {
            fputcsv($output, [
                $m['order_number'],
                $m['tracking_number'],
                $m['date'],
                $m['product_name'],
                $m['product_sku'],
                $m['quantity'],
                number_format($m['unit_cost_bob'], 2),
                number_format($m['subtotal_cost_bob'], 2),
                $m['customer_name'],
                $m['destination_city'],
                $m['destination_dept'],
                $m['carrier_name'],
                $m['order_status_label'],
                $m['settlement_status'],
            ]);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }
}
