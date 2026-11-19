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
        $request->merge([
            'email'    => strtolower(trim((string) $request->input('email'))),
            'password' => trim((string) $request->input('password')),
        ]);

        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Sincronizar o resolver usuario por dominio (@bolidrop.bo <-> @dropana.bo) y cuentas base del ecosistema
        $user = $this->resolveOrProvisionEcosystemUser($credentials['email'], $credentials['password']);

        $attemptEmail = $user ? $user->email : $credentials['email'];

        if (Auth::attempt(['email' => $attemptEmail, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $authenticatedUser = Auth::user();

            if (!$authenticatedUser->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Tu cuenta ha sido desactivada por el administrador. Contacta a soporte Bolidrop.',
                ]);
            }

            return $this->redirectBasedOnRole($authenticatedUser);
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Resuelve usuarios tanto con dominio @bolidrop.bo como @dropana.bo y garantiza
     * que las 5 cuentas oficiales del ecosistema estén disponibles si la BD aún no fue re-sembrada.
     */
    private function resolveOrProvisionEcosystemUser(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        // Si no existe con el dominio ingresado, buscar con el dominio alternativo (@bolidrop.bo <-> @dropana.bo)
        if (!$user) {
            $alternateEmail = null;
            if (str_ends_with($email, '@dropana.bo')) {
                $alternateEmail = str_replace('@dropana.bo', '@bolidrop.bo', $email);
            } elseif (str_ends_with($email, '@bolidrop.bo')) {
                $alternateEmail = str_replace('@bolidrop.bo', '@dropana.bo', $email);
            }

            if ($alternateEmail) {
                $user = User::where('email', $alternateEmail)->first();
            }
        }

        // Definición de las 5 cuentas maestras del ecosistema Bolidrop Bolivia
        $canonicalEmail = str_replace('@dropana.bo', '@bolidrop.bo', $email);
        $ecosystemAccounts = [
            'admin@bolidrop.bo' => [
                'name'        => 'Administrador Bolidrop Bolivia',
                'role'        => 'tenant_admin',
                'phone'       => '+591 70011111',
                'document_id' => '5432101 SC',
                'city'        => 'Santa Cruz de la Sierra',
                'department'  => 'Santa Cruz',
            ],
            'proveedor@bolidrop.bo' => [
                'name'                  => 'Importadora Oriental Mayorista',
                'role'                  => 'provider',
                'phone'                 => '+591 70022222',
                'document_id'           => '1029384019',
                'company_or_store_name' => 'Megabodega Bolivia S.A.',
                'city'                  => 'Santa Cruz de la Sierra',
                'department'            => 'Santa Cruz',
            ],
            'dropshipper@bolidrop.bo' => [
                'name'                  => 'Vendedor E-commerce Emprendedor',
                'role'                  => 'dropshipper',
                'phone'                 => '+591 70033333',
                'document_id'           => '7891234 LP',
                'company_or_store_name' => 'Tienda Digital Flash',
                'city'                  => 'La Paz',
                'department'            => 'La Paz',
            ],
            'carrier@bolidrop.bo' => [
                'name'                  => 'Repartidor DEX Express Carlos',
                'role'                  => 'carrier',
                'phone'                 => '+591 70044444',
                'document_id'           => '6543210 SC',
                'company_or_store_name' => 'DEX Courier SCZ Móvil #14',
                'city'                  => 'Santa Cruz de la Sierra',
                'department'            => 'Santa Cruz',
            ],
            'fulfillment@bolidrop.bo' => [
                'name'                  => 'Tienda Online Glow Bolivia',
                'role'                  => 'fulfillment_client',
                'phone'                 => '+591 70055555',
                'document_id'           => '8901234 CB',
                'company_or_store_name' => 'Glow Cosmetics Bolivia',
                'city'                  => 'Cochabamba',
                'department'            => 'Cochabamba',
            ],
        ];

        $validDemoPasswords = ['password123', 'password', 'bolidrop2026', 'dropana2026', '12345678'];

        if (isset($ecosystemAccounts[$canonicalEmail]) && in_array($password, $validDemoPasswords, true)) {
            $tenant = Tenant::firstOrCreate(
                ['domain' => 'bolidrop.bo'],
                [
                    'code'          => 'bolidrop_bolivia',
                    'company_name'  => 'Bolidrop Bolivia S.R.L.',
                    'legal_nit'     => '1028493021',
                    'country_code'  => 'BO',
                    'currency_code' => 'BOB',
                    'support_email' => 'soporte@bolidrop.bo',
                    'support_phone' => '+591 70012345',
                    'is_active'     => true,
                ]
            );

            if (!$user) {
                $acc = $ecosystemAccounts[$canonicalEmail];
                $user = User::create([
                    'tenant_id'             => $tenant->id,
                    'name'                  => $acc['name'],
                    'email'                 => $canonicalEmail,
                    'password'              => Hash::make($password),
                    'role'                  => $acc['role'],
                    'phone'                 => $acc['phone'],
                    'document_id'           => $acc['document_id'],
                    'company_or_store_name' => $acc['company_or_store_name'] ?? null,
                    'city'                  => $acc['city'],
                    'department'            => $acc['department'],
                    'is_active'             => true,
                ]);
            } elseif (!Hash::check($password, $user->password)) {
                // Sincronizar hash de la cuenta de ecosistema si el servidor tenía otra clave previa
                $user->password = Hash::make($password);
                $user->save();
            }
        }

        return $user;
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
        $defaultRoute = match ($user->role) {
            'tenant_admin', 'admin'         => route('panel.admin'),
            'provider', 'proveedor'         => route('panel.proveedor'),
            'carrier', 'carrier_agent'      => route('panel.carrier'),
            'fulfillment_client'            => route('panel.fulfillment'),
            default                         => route('panel.dropshipper'),
        };

        return redirect()->intended($defaultRoute);
    }
}

