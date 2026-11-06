<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registro de nuevo usuario (Dropshipper o Proveedor) en Bolivia.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_code' => 'nullable|string|exists:tenants,code',
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:dropshipper,provider',
            'phone' => 'required|string|max:30', // Ej: +591 70012345
            'document_id' => 'required|string|max:30', // CI o NIT en Bolivia
            'company_or_store_name' => 'nullable|string|max:150',
            'city' => 'required|string|max:100', // Santa Cruz, La Paz, Cochabamba, etc.
            'department' => 'required|string|max:50',
        ]);

        // Si no envía tenant_code, se asigna al tenant oficial por defecto de Bolivia
        $tenant = $validated['tenant_code'] ?? false
            ? Tenant::where('code', $validated['tenant_code'])->first()
            : Tenant::where('code', 'dropana_bolivia')->first();

        if (!$tenant) {
            $tenant = Tenant::first();
        }

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'],
            'document_id' => $validated['document_id'],
            'company_or_store_name' => $validated['company_or_store_name'] ?? null,
            'city' => $validated['city'],
            'department' => $validated['department'],
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario registrado exitosamente en Dropana Bolivia.',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'role', 'phone', 'city', 'department']),
                'tenant' => $tenant->only(['id', 'code', 'company_name', 'currency_code']),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Inicio de sesión.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Esta cuenta se encuentra inactiva. Contacte a soporte.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión iniciada exitosamente.',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'role', 'phone', 'city', 'department']),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Perfil del usuario autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load('tenant'),
        ]);
    }

    /**
     * Cierre de sesión (revocación de tokens).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
