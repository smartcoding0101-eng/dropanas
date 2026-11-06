<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isTenantAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Acceso denegado. Exclusivo para administradores del sistema (Kardex Global).',
                ], 403);
            }

            abort(403, 'Acceso denegado. Exclusivo para el Administrador del Sistema.');
        }

        return $next($request);
    }
}

