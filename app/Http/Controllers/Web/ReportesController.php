<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Analytics\ProviderMetricsService;
use Illuminate\Http\Request;

class ReportesController extends Controller
{
    // =========================================================================
    // HUB PRINCIPAL — /reportes
    // =========================================================================

    /**
     * Hub principal — /reportes
     */
    public function index(Request $request)
    {
        return $this->proveedor($request);
    }

    /**
     * Vista completa del reporte del proveedor (página dedicada).
     */
    public function proveedor(Request $request)
    {
        $authUser = $request->user();

        if (! $authUser || (! $authUser->isProvider() && ! $authUser->isTenantAdmin())) {
            abort(403, 'Acceso restringido a proveedores y administradores.');
        }

        $provider = $authUser->isProvider()
            ? $authUser
            : (
                $request->filled('provider_id')
                    ? User::where('role', 'provider')->find($request->input('provider_id'))
                    : User::where('role', 'provider')->first()
            );

        // Para admin: lista de proveedores para selector
        $providers = $authUser->isTenantAdmin()
            ? User::where('role', 'provider')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('reportes.index', compact('provider', 'providers'));
    }

    // =========================================================================
    // JSON API — /reportes/proveedor/metricas
    // =========================================================================

    /**
     * Endpoint JSON de métricas (misma lógica que el panel, URL más limpia).
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

    // =========================================================================
    // EXPORTAR CSV — /reportes/proveedor/exportar
    // =========================================================================

    public function exportProveedor(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
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
