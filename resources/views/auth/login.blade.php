@extends('layouts.app')

@section('title', 'Iniciar Sesión | Dropana Bolivia')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="text-center mb-6">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 mb-3">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Plataforma Segura COD Bolivia
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#960016]">Ingreso a la Plataforma</h1>
        <p class="text-slate-500 text-sm mt-1">Accede a tus pedidos, megabodega y liquidaciones bancarias.</p>
    </div>

    <div class="white-card rounded-2xl p-6 sm:p-8 shadow-lg border border-slate-200">
        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-3.5 text-xs text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Correo Electrónico
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                        <i class="fa-regular fa-envelope text-sm"></i>
                    </span>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}"
                        placeholder="tu-correo@ejemplo.com"
                        class="w-full pl-10 pr-3.5 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38] focus:ring-2 focus:ring-[#E88A38]/20 transition">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Contraseña
                    </label>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </span>
                    <input type="password" name="password" id="password" required
                        placeholder="••••••••"
                        class="w-full pl-10 pr-3.5 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38] focus:ring-2 focus:ring-[#E88A38]/20 transition">
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#960016] focus:ring-[#960016]">
                    <span>Recordar sesión</span>
                </label>
                <span class="text-slate-400">Soporte DEX 24/7</span>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full btn-scarlet py-3.5 rounded-xl font-bold text-sm shadow-md flex items-center justify-center gap-2">
                    <span>Entrar al Panel</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-500">
            ¿No tienes cuenta aún? 
            <a href="{{ route('register') }}" class="font-bold text-[#960016] hover:text-[#ED2100] transition ml-1">
                Regístrate como Dropshipper o Proveedor
            </a>
        </div>
    </div>

    <!-- Credenciales Demo Rápidas -->
    <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1.5">
        <div class="font-bold text-slate-700 flex items-center gap-1.5">
            <i class="fa-solid fa-key text-[#E88A38]"></i>
            <span>Cuentas Demo para Pruebas:</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1">
            <div class="bg-white p-2 rounded border border-slate-200">
                <strong class="text-[#960016]">Dropshipper:</strong><br>
                <span>dropshipper@dropana.bo</span><br>
                <span class="text-slate-400 font-mono">password123</span>
            </div>
            <div class="bg-white p-2 rounded border border-slate-200">
                <strong class="text-[#960016]">Administrador:</strong><br>
                <span>admin@dropana.bo</span><br>
                <span class="text-slate-400 font-mono">password123</span>
            </div>
        </div>
    </div>
</div>
@endsection
