<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /** Verifica si el usuario tiene privilegios de administración */
    private function checkAdminAccess(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->isTenantAdmin() 
            || $user->isSuperAdmin() 
            || in_array($user->role, ['tenant_admin', 'superadmin', 'admin'], true);
    }

    /** Listado de usuarios del tenant con filtros por rol y búsqueda */
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user();

        if (!$this->checkAdminAccess($admin)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autorizado. Solo los administradores pueden gestionar usuarios.',
            ], 403);
        }

        $tenantId = $admin->tenant_id ?? Tenant::first()?->id;
        $query = $tenantId ? User::where('tenant_id', $tenantId) : User::query();

        if ($request->has('role') && !empty($request->role)) {
            $query->where('role', $request->role);
        }

        if ($request->has('search') && !empty($request->search)) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('document_id', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $users = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data'   => $users,
        ]);
    }

    /** Crear un nuevo usuario (proveedor, dropshipper o transportista) */
    public function store(Request $request): JsonResponse
    {
        $admin = $request->user();

        if (!$this->checkAdminAccess($admin)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autorizado. Solo los administradores pueden crear usuarios.',
            ], 403);
        }

        $validated = $request->validate([
            'name'                  => 'required|string|max:150',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8',
            'role'                  => 'required|in:provider,dropshipper,carrier_agent,tenant_admin',
            'phone'                 => 'required|string|max:30',
            'document_id'           => 'required|string|max:30',
            'company_or_store_name' => 'nullable|string|max:150',
            'city'                  => 'required|string|max:100',
            'department'            => 'required|string|max:50',
        ]);

        $tenantId = $admin->tenant_id ?? Tenant::first()?->id ?? '01a1f5b7-11c2-7350-89ce-f96f436c8ceb';

        $user = User::create([
            'tenant_id'             => $tenantId,
            'name'                  => $validated['name'],
            'email'                 => $validated['email'],
            'password'              => Hash::make($validated['password']),
            'role'                  => $validated['role'],
            'phone'                 => $validated['phone'],
            'document_id'           => $validated['document_id'],
            'company_or_store_name' => $validated['company_or_store_name'] ?? null,
            'city'                  => $validated['city'],
            'department'            => $validated['department'],
            'is_active'             => true,
        ]);

        // Crear billetera en BOB si es dropshipper o proveedor
        if (in_array($user->role, ['dropshipper', 'provider'], true)) {
            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency' => 'BOB'],
                [
                    'tenant_id'           => $tenantId,
                    'balance_bob'         => 0.00,
                    'retained_balance_bob'=> 0.00,
                    'is_active'           => true,
                ]
            );
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Usuario con rol {$validated['role']} creado exitosamente.",
            'data'    => $user,
        ], 201);
    }

    /** Activar o suspender un usuario */
    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $admin = $request->user();

        if (!$this->checkAdminAccess($admin)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autorizado.',
            ], 403);
        }

        $tenantId = $admin->tenant_id ?? Tenant::first()?->id;
        $userQuery = $tenantId ? User::where('tenant_id', $tenantId) : User::query();
        $user = $userQuery->findOrFail($id);

        if ($user->id === $admin->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No puedes desactivar tu propia cuenta administradora.',
            ], 422);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $action = $user->is_active ? 'activada' : 'suspendida';

        return response()->json([
            'status'  => 'success',
            'message' => "Cuenta de {$user->name} {$action} correctamente.",
            'data'    => $user,
        ]);
    }
}

