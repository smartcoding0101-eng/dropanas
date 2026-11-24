<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarrierReportController extends Controller
{
    /**
     * Muestra la vista principal del Reporte Logístico & Financiero para DEX Bolivia Express.
     */
    public function index(Request $request)
    {
        $authUser = $request->user();
        if (! $authUser || (! $authUser->isCarrier() && ! $authUser->isTenantAdmin() && ! $authUser->isSuperAdmin())) {
            abort(403, 'Acceso restringido a transportadoras oficiales y administradores del sistema.');
        }

        $reportData = $this->buildReportData($request, $authUser);

        $providersList = User::where('role', 'provider')->orderBy('name')->get(['id', 'name', 'email']);
        $dropshippersList = User::where('role', 'dropshipper')->orderBy('name')->get(['id', 'name', 'email']);
        $citiesList = Order::distinct()->whereNotNull('shipping_city')->pluck('shipping_city')->sort()->values();

        $carrier = Carrier::first();

        return view('carrier.reportes', array_merge($reportData, [
            'providersList'    => $providersList,
            'dropshippersList' => $dropshippersList,
            'citiesList'       => $citiesList,
            'carrier'          => $carrier,
            'authUser'         => $authUser,
        ]));
    }

    /**
     * Endpoint JSON reactivo para actualizar filtros desde Alpine.js sin recargar la página.
     */
    public function dataJson(Request $request): JsonResponse
    {
        $authUser = $request->user();
        if (! $authUser || (! $authUser->isCarrier() && ! $authUser->isTenantAdmin() && ! $authUser->isSuperAdmin())) {
            return response()->json(['status' => 'error', 'message' => 'No autorizado'], 403);
        }

        $reportData = $this->buildReportData($request, $authUser);

        return response()->json([
            'status' => 'success',
            'data'   => $reportData,
        ]);
    }

    /**
     * Exporta el reporte de operaciones y recaudación COD en formato CSV compatible con Microsoft Excel (BOM UTF-8).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $authUser = $request->user();
        if (! $authUser || (! $authUser->isCarrier() && ! $authUser->isTenantAdmin() && ! $authUser->isSuperAdmin())) {
            abort(403);
        }

        $reportData = $this->buildReportData($request, $authUser);
        $kpis       = $reportData['kpis'];
        $cierre     = $reportData['arqueoCaja'];
        $orders     = $reportData['ordersList'];

        $filename = 'reporte_dex_bolivia_express_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($kpis, $cierre, $orders, $reportData, $authUser) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para garantizar apertura con acentos y formato correcto en Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['DEX BOLIVIA EXPRESS - REPORTE LOGISTICO Y FINANCIERO COD']);
            fputcsv($handle, ['Generado por:', $authUser->name . ' (' . $authUser->role . ')']);
            fputcsv($handle, ['Periodo Consultado:', $reportData['dateFrom'] . ' al ' . $reportData['dateTo']]);
            fputcsv($handle, ['Fecha de Emision:', now()->format('d/m/Y H:i:s')]);
            fputcsv($handle, []);

            // Bloque 1: Resumen de Indicadores Clave (KPIs)
            fputcsv($handle, ['INDICADOR OPERATIVO / FINANCIERO', 'VALOR']);
            fputcsv($handle, ['Total Paquetes Gestionados:', $kpis['total_orders']]);
            fputcsv($handle, ['Total Recaudacion COD Cobrada (BOB):', number_format($kpis['cod_collected_bob'], 2)]);
            fputcsv($handle, ['Cobrado en Efectivo Fisico (BOB):', number_format($kpis['cash_collected_bob'], 2)]);
            fputcsv($handle, ['Cobrado en QR Interoperable (BOB):', number_format($kpis['qr_collected_bob'], 2)]);
            fputcsv($handle, ['Fletes Devengados DEX (BOB):', number_format($kpis['shipping_fees_earned_bob'], 2)]);
            fputcsv($handle, ['Saldo Neto por Rendir a Proveedores (BOB):', number_format($kpis['funds_to_settle_bob'], 2)]);
            fputcsv($handle, ['Paquetes en Ruta Activa:', $kpis['in_route_count']]);
            fputcsv($handle, ['Monto en Ruta (BOB):', number_format($kpis['in_route_amount_bob'], 2)]);
            fputcsv($handle, ['Paquetes Entregados:', $kpis['delivered_count']]);
            fputcsv($handle, ['Paquetes con Novedad / Fallidos:', $kpis['novedades_count']]);
            fputcsv($handle, ['Tasa de Efectividad en Entrega:', $kpis['delivery_rate'] . '%']);
            fputcsv($handle, []);

            // Bloque 2: Arqueo Diario de Caja
            fputcsv($handle, ['ARQUEO DE CAJA DIARIO (CIERRE DE JORNADA)']);
            fputcsv($handle, ['Fecha', 'Paquetes Entregados', 'Total Cobrado (BOB)', 'Efectivo Fisico (BOB)', 'QR Simple (BOB)', 'Flete DEX (BOB)', 'Neto a Rendir (BOB)']);
            foreach ($cierre as $dia) {
                fputcsv($handle, [
                    $dia['date'],
                    $dia['orders_count'],
                    number_format($dia['total_collected'], 2),
                    number_format($dia['cash_amount'], 2),
                    number_format($dia['qr_amount'], 2),
                    number_format($dia['shipping_fee'], 2),
                    number_format($dia['net_to_settle'], 2),
                ]);
            }
            fputcsv($handle, []);

            // Bloque 3: Detalle de Movimientos por Pedido / Cliente / Proveedor
            fputcsv($handle, ['MOVIMIENTOS ECONOMICOS Y DETALLE DE GUIAS']);
            fputcsv($handle, [
                'Numero Guia',
                'Codigo Pedido',
                'Fecha Gestion',
                'Cliente Final',
                'Telefono',
                'Departamento',
                'Ciudad',
                'Direccion Destino',
                'Metodo Pago',
                'Estado Logistico',
                'Proveedor / Origen',
                'Tienda Dropshipper',
                'Total COD Recaudado (BOB)',
                'Flete DEX (BOB)',
                'Costo Mercaderia (BOB)',
            ]);

            foreach ($orders as $o) {
                $isCash = stripos($o->payment_method ?? '', 'cash') !== false || stripos($o->payment_method ?? '', 'efectivo') !== false;
                $provName = $o->items->first()?->product?->provider?->company_or_store_name 
                    ?? $o->items->first()?->product?->provider?->name 
                    ?? $o->warehouse?->name 
                    ?? 'Megabodega Central';

                fputcsv($handle, [
                    $o->tracking_number ?? 'S/G',
                    $o->order_number,
                    $o->created_at ? $o->created_at->format('d/m/Y H:i') : '',
                    $o->customer_name,
                    $o->customer_phone,
                    $o->shipping_department,
                    $o->shipping_city,
                    $o->shipping_address,
                    $isCash ? 'Efectivo COD' : 'QR Interoperable',
                    $o->status,
                    $provName,
                    $o->dropshipper?->company_or_store_name ?? $o->dropshipper?->name ?? 'Directo Bolidrop',
                    number_format((float) $o->total_amount_bob, 2),
                    number_format((float) $o->shipping_fee_bob, 2),
                    number_format((float) $o->cost_amount_bob, 2),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Construye y agrega métricas financieras, operativas, arqueo y consolidados para el Courier.
     */
    protected function buildReportData(Request $request, User $authUser): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $statusFilter = $request->input('status');
        $paymentFilter = $request->input('payment_method');
        $providerFilter = $request->input('provider_id');
        $cityFilter = $request->input('city');
        $search = trim($request->input('search', ''));

        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->subDays(30)->startOfDay();
        $to   = $dateTo   ? Carbon::parse($dateTo)->endOfDay()     : now()->endOfDay();

        // Query principal de pedidos gestionados por el courier
        $query = Order::with(['items.product.provider', 'warehouse', 'carrier', 'dropshipper'])
            ->whereBetween('created_at', [$from, $to]);

        // Si no es admin y es agente de transporte, aseguramos visibilidad adecuada
        if ($authUser->isCarrier()) {
            // Incluye pedidos con carrier asignado o en estados de tránsito/reparto
            $query->where(function ($q) {
                $q->whereNotNull('carrier_id')
                  ->orWhereIn('status', ['dispatched', 'delivered', 'failed_delivery', 'settled', 'in_transit']);
            });
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (!empty($cityFilter)) {
            $query->where('shipping_city', $cityFilter);
        }

        if (!empty($providerFilter)) {
            $query->whereHas('items', function ($q) use ($providerFilter) {
                $q->where('provider_id', $providerFilter);
            });
        }

        if (!empty($paymentFilter)) {
            if ($paymentFilter === 'cash') {
                $query->where(function ($q) {
                    $q->where('payment_method', 'LIKE', '%cash%')
                      ->orWhere('payment_method', 'LIKE', '%efectivo%');
                });
            } elseif ($paymentFilter === 'qr') {
                $query->where(function ($q) {
                    $q->where('payment_method', 'LIKE', '%qr%')
                      ->orWhere('payment_method', 'LIKE', '%simple%');
                });
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhere('tracking_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                  ->orWhere('shipping_city', 'LIKE', "%{$search}%");
            });
        }

        $allOrders = $query->latest('created_at')->get();

        // 1. Cálculo de KPIs
        $totalOrders = $allOrders->count();
        $deliveredOrders = $allOrders->whereIn('status', ['delivered', 'settled']);
        $inRouteOrders = $allOrders->whereIn('status', ['dispatched', 'in_transit']);
        $failedOrders = $allOrders->where('status', 'failed_delivery');

        $codCollectedBob = (float) $deliveredOrders->sum('total_amount_bob');

        $cashCollectedBob = (float) $deliveredOrders->filter(function ($o) {
            return stripos($o->payment_method ?? '', 'cash') !== false || stripos($o->payment_method ?? '', 'efectivo') !== false;
        })->sum('total_amount_bob');

        $qrCollectedBob = $codCollectedBob - $cashCollectedBob;
        if ($qrCollectedBob < 0) $qrCollectedBob = 0;

        $shippingFeesEarnedBob = (float) $deliveredOrders->sum('shipping_fee_bob');
        $fundsToSettleBob = max(0, $codCollectedBob - $shippingFeesEarnedBob);

        $inRouteAmountBob = (float) $inRouteOrders->sum('total_amount_bob');
        $deliveredCount = $deliveredOrders->count();
        $inRouteCount = $inRouteOrders->count();
        $novedadesCount = $failedOrders->count();

        $completedCount = $deliveredCount + $novedadesCount;
        $deliveryRate = $completedCount > 0 ? round(($deliveredCount / $completedCount) * 100, 1) : ($totalOrders > 0 ? 100.0 : 0.0);

        $kpis = [
            'total_orders'            => $totalOrders,
            'cod_collected_bob'       => $codCollectedBob,
            'cash_collected_bob'      => $cashCollectedBob,
            'qr_collected_bob'        => $qrCollectedBob,
            'shipping_fees_earned_bob'=> $shippingFeesEarnedBob,
            'funds_to_settle_bob'     => $fundsToSettleBob,
            'in_route_count'          => $inRouteCount,
            'in_route_amount_bob'     => $inRouteAmountBob,
            'delivered_count'         => $deliveredCount,
            'novedades_count'         => $novedadesCount,
            'delivery_rate'           => $deliveryRate,
            'cash_percentage'         => $codCollectedBob > 0 ? round(($cashCollectedBob / $codCollectedBob) * 100, 1) : 0,
            'qr_percentage'           => $codCollectedBob > 0 ? round(($qrCollectedBob / $codCollectedBob) * 100, 1) : 0,
        ];

        // 2. Arqueo de Caja / Cierre Diario
        $arqueoCaja = [];
        $byDate = $deliveredOrders->groupBy(function ($o) {
            $dt = $o->delivered_at ?? $o->created_at;
            return $dt ? $dt->format('Y-m-d') : now()->format('Y-m-d');
        });

        foreach ($byDate as $dateKey => $group) {
            $totCobrado = (float) $group->sum('total_amount_bob');
            $efectivo = (float) $group->filter(fn($o) => stripos($o->payment_method ?? '', 'cash') !== false || stripos($o->payment_method ?? '', 'efectivo') !== false)->sum('total_amount_bob');
            $qr = max(0, $totCobrado - $efectivo);
            $flete = (float) $group->sum('shipping_fee_bob');
            $neto = max(0, $totCobrado - $flete);

            $arqueoCaja[] = [
                'date_raw'        => $dateKey,
                'date'            => Carbon::parse($dateKey)->format('d/m/Y'),
                'orders_count'    => $group->count(),
                'total_collected' => $totCobrado,
                'cash_amount'     => $efectivo,
                'qr_amount'       => $qr,
                'shipping_fee'    => $flete,
                'net_to_settle'   => $neto,
            ];
        }

        usort($arqueoCaja, fn($a, $b) => strcmp($b['date_raw'], $a['date_raw']));

        // 3. Consolidado por Proveedor
        $byProvider = [];
        foreach ($allOrders as $o) {
            $provider = $o->items->first()?->product?->provider;
            $provId = $provider?->id ?? ($o->warehouse?->provider_id ?? 'global');
            $provName = $provider?->company_or_store_name ?? $provider?->name ?? ($o->warehouse?->name ?? 'Megabodega Central SCZ');

            if (!isset($byProvider[$provId])) {
                $byProvider[$provId] = [
                    'id'               => $provId,
                    'name'             => $provName,
                    'orders_count'     => 0,
                    'delivered_count'  => 0,
                    'in_route_count'   => 0,
                    'failed_count'     => 0,
                    'total_cod_bob'    => 0.0,
                    'provider_cost_bob'=> 0.0,
                    'shipping_fee_bob' => 0.0,
                ];
            }

            $byProvider[$provId]['orders_count']++;
            if (in_array($o->status, ['delivered', 'settled'])) {
                $byProvider[$provId]['delivered_count']++;
                $byProvider[$provId]['total_cod_bob'] += (float) $o->total_amount_bob;
                $byProvider[$provId]['provider_cost_bob'] += (float) $o->cost_amount_bob;
                $byProvider[$provId]['shipping_fee_bob'] += (float) $o->shipping_fee_bob;
            } elseif (in_array($o->status, ['dispatched', 'in_transit'])) {
                $byProvider[$provId]['in_route_count']++;
            } elseif ($o->status === 'failed_delivery') {
                $byProvider[$provId]['failed_count']++;
            }
        }
        $consolidadoProveedores = array_values($byProvider);
        usort($consolidadoProveedores, fn($a, $b) => $b['total_cod_bob'] <=> $a['total_cod_bob']);

        // 4. Consolidado por Ciudad / Cliente Destino
        $byCity = [];
        foreach ($allOrders as $o) {
            $city = trim($o->shipping_city ?? 'Santa Cruz');
            $dept = trim($o->shipping_department ?? 'Santa Cruz');
            $key = $dept . ' - ' . $city;

            if (!isset($byCity[$key])) {
                $byCity[$key] = [
                    'city'            => $city,
                    'department'      => $dept,
                    'orders_count'    => 0,
                    'delivered_count' => 0,
                    'failed_count'    => 0,
                    'total_collected' => 0.0,
                ];
            }

            $byCity[$key]['orders_count']++;
            if (in_array($o->status, ['delivered', 'settled'])) {
                $byCity[$key]['delivered_count']++;
                $byCity[$key]['total_collected'] += (float) $o->total_amount_bob;
            } elseif ($o->status === 'failed_delivery') {
                $byCity[$key]['failed_count']++;
            }
        }
        $consolidadoCiudades = array_values($byCity);
        usort($consolidadoCiudades, fn($a, $b) => $b['orders_count'] <=> $a['orders_count']);

        // 5. Gráfica Diaria Interactiva (últimos 14 días en el rango)
        $chartDates = [];
        $chartRecaudacion = [];
        $chartEntregas = [];
        $chartNovedades = [];

        $periodDays = min(14, max(1, $from->diffInDays($to) + 1));
        $stepStart = $to->copy()->subDays($periodDays - 1)->startOfDay();

        for ($d = 0; $d < $periodDays; $d++) {
            $currentDay = $stepStart->copy()->addDays($d);
            $dayKey = $currentDay->format('Y-m-d');
            $label = $currentDay->format('d M');

            $dayOrders = $allOrders->filter(function ($o) use ($dayKey) {
                return $o->created_at && $o->created_at->format('Y-m-d') === $dayKey;
            });

            $chartDates[] = $label;
            $chartRecaudacion[] = (float) $dayOrders->whereIn('status', ['delivered', 'settled'])->sum('total_amount_bob');
            $chartEntregas[] = $dayOrders->whereIn('status', ['delivered', 'settled'])->count();
            $chartNovedades[] = $dayOrders->where('status', 'failed_delivery')->count();
        }

        // 6. Preparar array ligero de órdenes para tabla reactiva y modal
        $ordersList = $allOrders->map(function ($o) {
            $isCash = stripos($o->payment_method ?? '', 'cash') !== false || stripos($o->payment_method ?? '', 'efectivo') !== false;
            $prov = $o->items->first()?->product?->provider;
            return [
                'id'                 => $o->id,
                'order_number'       => $o->order_number,
                'tracking_number'    => $o->tracking_number ?? 'S/G',
                'created_at_fmt'     => $o->created_at ? $o->created_at->format('d/m/Y H:i') : '',
                'delivered_at_fmt'   => $o->delivered_at ? $o->delivered_at->format('d/m/Y H:i') : '',
                'customer_name'      => $o->customer_name,
                'customer_phone'     => $o->customer_phone,
                'shipping_department'=> $o->shipping_department,
                'shipping_city'      => $o->shipping_city,
                'shipping_address'   => $o->shipping_address,
                'shipping_reference' => $o->shipping_reference,
                'status'             => $o->status,
                'payment_method'     => $o->payment_method,
                'payment_method_label' => $isCash ? 'Efectivo COD' : 'QR Interoperable',
                'is_cash'            => $isCash,
                'total_amount_bob'   => (float) $o->total_amount_bob,
                'shipping_fee_bob'   => (float) $o->shipping_fee_bob,
                'cost_amount_bob'    => (float) $o->cost_amount_bob,
                'provider_name'      => $prov?->company_or_store_name ?? $prov?->name ?? ($o->warehouse?->name ?? 'Megabodega Central'),
                'dropshipper_name'   => $o->dropshipper?->company_or_store_name ?? $o->dropshipper?->name ?? 'Directo Bolidrop',
                'novedad_reason'     => $o->novedad_reason,
                'novedad_notes'      => $o->novedad_notes,
                'items'              => $o->items->map(function ($it) {
                    return [
                        'product_name' => $it->product?->name ?? 'Producto COD',
                        'quantity'     => $it->quantity,
                        'unit_price'   => (float) ($it->unit_price_bob ?? 0),
                        'subtotal'     => (float) ($it->subtotal_bob ?? 0),
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        return [
            'dateFrom'               => $from->format('Y-m-d'),
            'dateTo'                 => $to->format('Y-m-d'),
            'statusFilter'           => $statusFilter,
            'paymentFilter'          => $paymentFilter,
            'providerFilter'         => $providerFilter,
            'cityFilter'             => $cityFilter,
            'search'                 => $search,
            'kpis'                   => $kpis,
            'arqueoCaja'             => $arqueoCaja,
            'consolidadoProveedores' => $consolidadoProveedores,
            'consolidadoCiudades'    => $consolidadoCiudades,
            'chart'                  => [
                'labels'      => $chartDates,
                'recaudacion' => $chartRecaudacion,
                'entregas'    => $chartEntregas,
                'novedades'   => $chartNovedades,
            ],
            'ordersList'             => $ordersList,
        ];
    }
}
