<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\CarrierCoverage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CarrierManagementController extends Controller
{
    /**
     * Lista de Transportadoras / Couriers y sus Coberturas en Bolivia.
     */
    public function index(Request $request): View
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $tenantId = $admin?->tenant_id ?? '01a1f5b7-11c2-7350-89ce-f96f436c8ceb';

        $search = trim((string) $request->input('search', ''));

        $query = Carrier::where('tenant_id', $tenantId)
            ->with(['coverages' => fn($q) => $q->orderBy('department')])
            ->withCount(['orders', 'shippingManifests'])
            ->latest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        $carriers = $query->get();

        // KPIs
        $kpis = [
            'total_carriers'  => Carrier::where('tenant_id', $tenantId)->count(),
            'active_carriers' => Carrier::where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'cod_carriers'    => Carrier::where('tenant_id', $tenantId)->where('has_cod_service', true)->count(),
            'total_coverages' => CarrierCoverage::where('tenant_id', $tenantId)->count(),
        ];

        // Departamentos de Bolivia
        $boliviaDepartments = [
            'Santa Cruz', 'La Paz', 'Cochabamba',
            'Oruro', 'Potosí', 'Chuquisaca',
            'Tarija', 'Beni', 'Pando'
        ];

        return view('admin.carriers.index', compact(
            'carriers',
            'kpis',
            'search',
            'boliviaDepartments',
            'admin'
        ));
    }

    /**
     * Registrar una nueva Transportadora / Courier y opcionalmente sus tarifas base.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $tenantId = $admin?->tenant_id ?? '01a1f5b7-11c2-7350-89ce-f96f436c8ceb';

        $validated = $request->validate([
            'name'                     => 'required|string|max:100',
            'code'                     => 'nullable|string|max:40',
            'contact_name'             => 'nullable|string|max:120',
            'contact_phone'            => 'nullable|string|max:30',
            'contact_email'            => 'nullable|email|max:120',
            'has_cod_service'          => 'nullable|boolean',
            'integration_mode'         => 'nullable|string|in:manifest_file,b2b_webhook,direct_api',
            // Tarifas iniciales opcionales
            'default_shipping_scz_bob' => 'nullable|numeric|min:0',
            'default_shipping_lpz_bob' => 'nullable|numeric|min:0',
            'default_shipping_cbb_bob' => 'nullable|numeric|min:0',
        ]);

        $code = !empty($validated['code'])
            ? Str::slug($validated['code'], '_')
            : Str::slug($validated['name'], '_');

        // Evitar duplicados de código en el tenant
        $originalCode = $code;
        $counter = 1;
        while (Carrier::where('tenant_id', $tenantId)->where('code', $code)->exists()) {
            $code = "{$originalCode}_{$counter}";
            $counter++;
        }

        $carrier = Carrier::create([
            'tenant_id'        => $tenantId,
            'name'             => $validated['name'],
            'code'             => $code,
            'contact_name'     => $validated['contact_name'] ?? null,
            'contact_phone'    => $validated['contact_phone'] ?? null,
            'contact_email'    => $validated['contact_email'] ?? null,
            'has_cod_service'  => $request->boolean('has_cod_service', true),
            'integration_mode' => $validated['integration_mode'] ?? 'manifest_file',
            'is_active'        => true,
        ]);

        // Crear coberturas base si se proporcionaron
        if (!empty($validated['default_shipping_scz_bob'])) {
            CarrierCoverage::create([
                'tenant_id'              => $tenantId,
                'carrier_id'             => $carrier->id,
                'department'             => 'Santa Cruz',
                'city'                   => 'Santa Cruz de la Sierra',
                'zone_type'              => 'urban',
                'base_shipping_bob'      => (float) $validated['default_shipping_scz_bob'],
                'cod_fee_percentage'     => 0.00,
                'estimated_delivery_days'=> 1,
                'is_active'              => true,
            ]);
        }

        if (!empty($validated['default_shipping_lpz_bob'])) {
            CarrierCoverage::create([
                'tenant_id'              => $tenantId,
                'carrier_id'             => $carrier->id,
                'department'             => 'La Paz',
                'city'                   => 'La Paz / El Alto',
                'zone_type'              => 'urban',
                'base_shipping_bob'      => (float) $validated['default_shipping_lpz_bob'],
                'cod_fee_percentage'     => 0.00,
                'estimated_delivery_days'=> 2,
                'is_active'              => true,
            ]);
        }

        if (!empty($validated['default_shipping_cbb_bob'])) {
            CarrierCoverage::create([
                'tenant_id'              => $tenantId,
                'carrier_id'             => $carrier->id,
                'department'             => 'Cochabamba',
                'city'                   => 'Cochabamba',
                'zone_type'              => 'urban',
                'base_shipping_bob'      => (float) $validated['default_shipping_cbb_bob'],
                'cod_fee_percentage'     => 0.00,
                'estimated_delivery_days'=> 2,
                'is_active'              => true,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Transportadora '{$carrier->name}' creada exitosamente. Ya está disponible para selección de dropshippers.",
                'data'    => $carrier->load('coverages'),
            ], 201);
        }

        return back()->with('success', "Transportadora '{$carrier->name}' creada con éxito. Los dropshippers ya pueden seleccionarla al emitir órdenes.");
    }

    /**
     * Actualizar datos de la transportadora.
     */
    public function update(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $carrier = Carrier::where('tenant_id', $admin->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'contact_name'    => 'nullable|string|max:120',
            'contact_phone'   => 'nullable|string|max:30',
            'contact_email'   => 'nullable|email|max:120',
            'has_cod_service' => 'nullable|boolean',
            'is_active'       => 'nullable|boolean',
        ]);

        $carrier->update([
            'name'            => $validated['name'],
            'contact_name'    => $validated['contact_name'] ?? null,
            'contact_phone'   => $validated['contact_phone'] ?? null,
            'contact_email'   => $validated['contact_email'] ?? null,
            'has_cod_service' => $request->boolean('has_cod_service'),
            'is_active'       => $request->boolean('is_active'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Transportadora '{$carrier->name}' actualizada.",
                'data'    => $carrier,
            ]);
        }

        return back()->with('success', "Transportadora '{$carrier->name}' actualizada correctamente.");
    }

    /**
     * Activar o desactivar transportadora.
     */
    public function toggleStatus(string $id): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $carrier = Carrier::where('tenant_id', $admin->tenant_id)->findOrFail($id);

        $carrier->is_active = !$carrier->is_active;
        $carrier->save();

        $statusStr = $carrier->is_active ? 'activada' : 'desactivada';

        if (request()->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Transportadora {$statusStr} exitosamente.",
                'data'    => $carrier,
            ]);
        }

        return back()->with('success', "Transportadora '{$carrier->name}' {$statusStr}.");
    }

    /**
     * Añadir o actualizar cobertura y tarifa por departamento.
     */
    public function storeCoverage(Request $request, string $carrierId): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $carrier = Carrier::where('tenant_id', $admin->tenant_id)->findOrFail($carrierId);

        $validated = $request->validate([
            'department'              => 'required|string|max:50',
            'city'                    => 'required|string|max:100',
            'zone_type'               => 'nullable|string|in:urban,suburban,provincial,remote',
            'base_shipping_bob'       => 'required|numeric|min:0',
            'cod_fee_percentage'      => 'nullable|numeric|min:0|max:100',
            'estimated_delivery_days' => 'nullable|integer|min:1|max:30',
        ]);

        $coverage = CarrierCoverage::updateOrCreate(
            [
                'tenant_id'  => $carrier->tenant_id,
                'carrier_id' => $carrier->id,
                'department' => $validated['department'],
                'city'       => $validated['city'],
            ],
            [
                'zone_type'               => $validated['zone_type'] ?? 'urban',
                'base_shipping_bob'       => (float) $validated['base_shipping_bob'],
                'cod_fee_percentage'      => (float) ($validated['cod_fee_percentage'] ?? 0.00),
                'estimated_delivery_days' => (int) ($validated['estimated_delivery_days'] ?? 2),
                'is_active'               => true,
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Cobertura para {$coverage->city} ({$coverage->department}) registrada exitosamente.",
                'data'    => $coverage,
            ]);
        }

        return back()->with('success', "Tarifa de flete para {$coverage->city} ({$coverage->department}) guardada: Bs. {$coverage->base_shipping_bob}.");
    }

    /**
     * Eliminar cobertura.
     */
    public function deleteCoverage(string $coverageId): JsonResponse|RedirectResponse
    {
        $admin = Auth::user() ?? User::where('role', 'tenant_admin')->first();
        $coverage = CarrierCoverage::where('tenant_id', $admin->tenant_id)->findOrFail($coverageId);

        $city = $coverage->city;
        $coverage->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Cobertura de {$city} eliminada.",
            ]);
        }

        return back()->with('success', "Cobertura de {$city} eliminada.");
    }
}
