<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /** Capturar lead desde el formulario de la landing page (público) */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name'                => 'required|string|max:150',
            'phone'                    => 'required|string|max:30', // WhatsApp Bolivia +591
            'email'                    => 'nullable|email|max:150',
            'interest_role'            => 'required|string|in:dropshipper,provider,fulfillment,industrial_warehouse',
            'estimated_monthly_volume' => 'required|string|max:50',
            'department'               => 'nullable|string|max:60',
            'city'                     => 'nullable|string|max:100',
            'notes'                    => 'nullable|string|max:500',
        ]);

        $tenant = Tenant::where('code', 'dropana_bolivia')->first() ?? Tenant::first();

        $lead = Lead::create([
            'tenant_id'                => $tenant?->id,
            'full_name'                => $validated['full_name'],
            'phone'                    => $validated['phone'],
            'email'                    => $validated['email'] ?? null,
            'interest_role'            => $validated['interest_role'],
            'estimated_monthly_volume' => $validated['estimated_monthly_volume'],
            'department'               => $validated['department'] ?? 'Santa Cruz',
            'city'                     => $validated['city'] ?? 'Santa Cruz de la Sierra',
            'notes'                    => $validated['notes'] ?? null,
            'status'                   => 'new',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => '¡Gracias por tu interés en Dropana Bolivia! Un asesor comercial se pondrá en contacto contigo vía WhatsApp.',
            'data'    => $lead,
        ], 201);
    }

    /** Listar leads capturados (para el administrador) */
    public function index(Request $request): JsonResponse
    {
        $leads = Lead::latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $leads,
        ]);
    }
}
