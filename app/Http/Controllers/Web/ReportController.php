<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Dashboard principal de reportes (vista HTML)
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $tenantId = $this->resolveTenantId();
        $period   = $request->get('period', '30d');

        $kpis              = $this->analytics->globalKpis($tenantId, $period);
        $statusDistribution= $this->analytics->statusDistribution($tenantId, $period);
        $byDepartment      = $this->analytics->byDepartment($tenantId, $period);
        $topDropshippers   = $this->analytics->topDropshippers($tenantId, 8, $period);
        $topProducts       = $this->analytics->topProducts($tenantId, 8, $period);
        $carrierPerformance= $this->analytics->carrierPerformance($tenantId, $period);
        $walletSummary     = $this->analytics->walletSummary($tenantId);
        $settlementSummary = $this->analytics->settlementSummary($tenantId);

        // Datos de la serie diaria (últimos 30 días para el gráfico)
        $dailySeries = $this->analytics->dailySeries($tenantId, 30);

        $periods = ['7d' => '7 días', '30d' => '30 días', '90d' => '90 días', '365d' => '1 año'];

        return view('reports.index', compact(
            'kpis', 'statusDistribution', 'byDepartment',
            'topDropshippers', 'topProducts', 'carrierPerformance',
            'walletSummary', 'settlementSummary', 'dailySeries',
            'period', 'periods'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API JSON — Serie diaria (para gráfico dinámico con JS)
    // ─────────────────────────────────────────────────────────────────────────
    public function dailySeriesJson(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        $days     = (int) $request->get('days', 30);
        $days     = min($days, 365); // máximo 1 año

        $series = $this->analytics->dailySeries($tenantId, $days);

        // Completar los días faltantes con ceros (para que el gráfico sea continuo)
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date  = now()->subDays($i)->format('Y-m-d');
            $entry = $series->get($date);
            $result[] = [
                'date'            => $date,
                'orders_count'    => $entry?->orders_count ?? 0,
                'volume_bob'      => round($entry?->volume_bob ?? 0, 2),
                'fees_bob'        => round($entry?->fees_bob ?? 0, 2),
                'delivered_count' => $entry?->delivered_count ?? 0,
            ];
        }

        return response()->json($result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API JSON — KPIs en tiempo real (polling cada 60s desde el frontend)
    // ─────────────────────────────────────────────────────────────────────────
    public function kpisJson(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        $period   = $request->get('period', '30d');

        return response()->json([
            'kpis'              => $this->analytics->globalKpis($tenantId, $period),
            'wallet'            => $this->analytics->walletSummary($tenantId),
            'settlements'       => $this->analytics->settlementSummary($tenantId),
            'by_department'     => $this->analytics->byDepartment($tenantId, $period),
            'carrier_performance'=> $this->analytics->carrierPerformance($tenantId, $period),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: resuelve el tenant_id del usuario autenticado (o primer tenant disponible en demo)
    // ─────────────────────────────────────────────────────────────────────────
    private function resolveTenantId(): string
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        // Modo demo: usa el primer tenant disponible
        return \App\Models\Tenant::first()?->id ?? '00000000-0000-0000-0000-000000000000';
    }
}
