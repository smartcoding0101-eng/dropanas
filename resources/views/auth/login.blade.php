@extends('layouts.app')

@section('title', 'Iniciar Sesión | Bolidrop Bolivia')

@section('content')
<div class="max-w-5xl mx-auto my-6 sm:my-10">
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden grid grid-cols-1 lg:grid-cols-12">
        
        <!-- ===================================================================== -->
        <!-- COLUMNA IZQUIERDA (50% / 6 cols): Vitrina Corporativa Midnight Slate  -->
        <!-- ===================================================================== -->
        <div class="lg:col-span-6 relative overflow-hidden p-8 sm:p-10 text-white flex flex-col justify-between"
             style="background-color: #0F172A; background-image: radial-gradient(circle at 85% 15%, rgba(56, 189, 248, 0.25) 0%, transparent 45%), radial-gradient(circle at 15% 85%, rgba(79, 70, 229, 0.35) 0%, transparent 50%), linear-gradient(145deg, #0F172A 0%, #1E1B4B 48%, #312E81 100%);">
            
            <!-- Top Badge & Branding -->
            <div class="relative z-10 space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-white/10 text-indigo-100 border border-white/15 backdrop-blur-xs">
                    <span class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse"></span>
                    <span>Infraestructura Logística & Fintech COD</span>
                </div>

                <div class="flex items-center gap-4">
                    <div class="relative w-14 h-14 rounded-2xl bg-white/10 p-1 border border-white/25 shadow-lg shrink-0">
                        <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-xl">
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-[#10B981] rounded-full border-2 border-[#0F172A] flex items-center justify-center">
                            <i class="fa-solid fa-check text-[8px] text-white"></i>
                        </span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-extrabold tracking-tight text-white font-heading">Bolidrop Bolivia</h2>
                        <p class="text-xs text-[#38BDF8] font-bold uppercase tracking-widest">Ecosistema Nacional 9/9 Deptos</p>
                    </div>
                </div>

                <p class="text-xs sm:text-sm text-indigo-100/85 leading-relaxed">
                    Gestiona tu operación de e-Commerce Contra Entrega (COD), sincroniza stock en Megabodegas de Santa Cruz, La Paz y Cochabamba, y liquida tus recaudos al instante vía QR Simple o USDT.
                </p>
            </div>

            <!-- Middle Glassmorphic Live Cards -->
            <div class="relative z-10 my-8 space-y-3">
                <div class="p-4 rounded-2xl bg-white/10 border border-white/15 backdrop-blur-md flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#4F46E5]/40 border border-indigo-400/30 flex items-center justify-center text-[#38BDF8] text-base">
                            <i class="fa-solid fa-truck-fast"></i>
                        </div>
                        <div>
                            <div class="text-xs font-extrabold text-white">Cobertura DEX Bolivia Express</div>
                            <div class="text-[11px] text-indigo-200/80">Despacho diario y cobro en puerta en los 9 departamentos</div>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 border border-emerald-400/30 text-[#10B981] font-mono font-extrabold text-xs">
                        94% Efectividad
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3.5 rounded-2xl bg-white/8 border border-white/10 backdrop-blur-xs">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-200/75">Liquidación Bancaria</div>
                        <div class="text-base font-extrabold text-white font-mono mt-0.5">QR Simple & BNB</div>
                        <div class="text-[10px] text-[#10B981] font-semibold mt-0.5"><i class="fa-solid fa-bolt mr-1"></i>Conciliación T+0</div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-white/8 border border-white/10 backdrop-blur-xs">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-200/75">Kardex & Stock</div>
                        <div class="text-base font-extrabold text-white font-mono mt-0.5">100% Sincronizado</div>
                        <div class="text-[10px] text-[#38BDF8] font-semibold mt-0.5"><i class="fa-solid fa-shield-halved mr-1"></i>Auditoría Ledger</div>
                    </div>
                </div>
            </div>

            <!-- Footer Trust Badges -->
            <div class="relative z-10 pt-4 border-t border-white/10 flex items-center justify-between text-[11px] text-indigo-200/75">
                <span class="flex items-center gap-1.5">
                    <i class="fa-solid fa-lock text-[#38BDF8]"></i> Encriptación TLS / Multi-Tenant
                </span>
                <span class="font-mono font-bold text-white">BOB • USD • USDT</span>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- COLUMNA DERECHA (50% / 6 cols): Formulario Ejecutivo de Acceso        -->
        <!-- ===================================================================== -->
        <div class="lg:col-span-6 p-7 sm:p-10 lg:p-12 flex flex-col justify-center bg-white">
            <div class="mb-7">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-[#4F46E5] border border-indigo-200/80 mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#4F46E5]"></span>
                    Portal Ejecutivo Bolidrop
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] font-heading">Ingreso a la Plataforma</h1>
                <p class="text-slate-500 text-xs sm:text-sm mt-1">Accede a tu panel de control, megabodega y liquidaciones.</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 bg-rose-50 border border-rose-200 rounded-xl p-3.5 text-xs text-rose-700 space-y-1">
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
                    <label for="email" class="block text-xs font-bold text-[#0F172A] uppercase tracking-wider mb-1.5">
                        Correo Electrónico
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                            <i class="fa-regular fa-envelope text-sm"></i>
                        </span>
                        <input type="email" name="email" id="email" required value="{{ old('email') }}"
                            placeholder="tu-correo@bolidrop.bo"
                            class="w-full pl-10 pr-3.5 py-3 rounded-xl border border-slate-200 bg-[#F8FAFC] text-[#0F172A] text-sm focus:bg-white focus:outline-none focus:border-[#4F46E5] focus:ring-2 focus:ring-[#4F46E5]/15 transition">
                    </div>
                </div>

                <div x-data="{ showPassword: false }">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold text-[#0F172A] uppercase tracking-wider">
                            Contraseña
                        </label>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required
                            placeholder="••••••••"
                            class="w-full pl-10 pr-11 py-3 rounded-xl border border-slate-200 bg-[#F8FAFC] text-[#0F172A] text-sm focus:bg-white focus:outline-none focus:border-[#4F46E5] focus:ring-2 focus:ring-[#4F46E5]/15 transition">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-[#4F46E5] focus:outline-none transition"
                            :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'">
                            <i class="fa-regular text-sm" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#4F46E5] focus:ring-[#4F46E5]">
                        <span>Recordar sesión</span>
                    </label>
                    <span class="text-slate-400 font-medium">Soporte DEX 24/7</span>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full btn-scarlet py-3.5 rounded-xl font-bold text-sm shadow-md flex items-center justify-center gap-2">
                        <span>Entrar al Panel</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </form>

            <div class="mt-7 pt-5 border-t border-slate-100 text-center text-xs text-slate-500">
                ¿No tienes cuenta aún? 
                <a href="{{ route('register') }}" class="font-bold text-[#4F46E5] hover:text-[#312E81] transition ml-1">
                    Regístrate como Dropshipper o Proveedor →
                </a>
            </div>
        </div>

    </div>
</div>
@endsection



