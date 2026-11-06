<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Muestra la vista de inicio de sesión.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Procesa la autenticación web.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Tu cuenta ha sido desactivada por el administrador. Contacta a soporte Dropana.',
                ]);
            }

            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Muestra el formulario de registro para nuevos Dropshippers y Proveedores.
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.register');
    }

    /**
     * Registra un nuevo usuario en el ecosistema COD Bolivia.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:150',
            'email'                 => 'required|string|email|max:255|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'role'                  => 'required|in:dropshipper,provider',
            'phone'                 => 'required|string|max:30',
            'identity_document_type'=> 'required|in:CI,NIT',
            'identity_document'     => 'required|string|max:30',
            'department'            => 'required|in:Santa Cruz,La Paz,Cochabamba,Oruro,Potosí,Chuquisaca,Tarija,Beni,Pando',
            'city'                  => 'nullable|string|max:100',
        ]);

        $tenant = Tenant::first();

        $user = User::create([
            'tenant_id'             => $tenant ? $tenant->id : null,
            'name'                  => $validated['name'],
            'email'                 => $validated['email'],
            'password'              => Hash::make($validated['password']),
            'role'                  => $validated['role'],
            'phone'                 => $validated['phone'],
            'document_id'           => $validated['identity_document'],
            'department'            => $validated['department'],
            'city'                  => $validated['city'] ?? $validated['department'],
            'is_active'             => true,
        ]);

        // Crear billetera en BOB si es dropshipper o proveedor
        if ($tenant && in_array($user->role, ['dropshipper', 'provider'])) {
            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency' => 'BOB'],
                [
                    'tenant_id'          => $tenant->id,
                    'balance_bob'        => 0.00,
                    'retained_balance_bob'=> 0.00,
                    'is_active'          => true,
                ]
            );
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user);
    }

    /**
     * Cierra la sesión activa.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Redirige al panel correspondiente según el rol del usuario.
     */
    private function redirectBasedOnRole($user)
    {
        return match ($user->role) {
            'tenant_admin' => redirect()->route('panel.admin'),
            'provider'     => redirect()->route('panel.proveedor'),
            'carrier'      => redirect()->route('panel.carrier'),
            default        => redirect()->route('panel.dropshipper'),
        };
    }
}
