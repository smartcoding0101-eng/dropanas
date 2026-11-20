@props([
    'title',
    'subtitle' => null,
    'icon' => 'fa-solid fa-chart-pie',
    'badge' => null,
])

@php
    $currentModuleTitle = match(true) {
        request()->is('panel/admin/kardex*')     => 'Kardex Global & Trazabilidad de Stock',
        request()->is('panel/admin*')            => 'Dashboard Central Administrador',
        request()->is('panel/dropshipper*')      => 'Panel de Vendedor Dropshipper',
        request()->is('panel/proveedor*')        => 'Panel de Proveedor Mayorista',
        request()->is('panel/carrier*')          => 'Panel de Repartidor DEX Express',
        request()->is('panel/fulfillment*')      => 'Panel de Cliente Fulfillment',
        request()->is('catalogo*')               => 'Megabodega Mayorista Nacional',
        request()->is('checkout-demo*')          => 'Generador de Pedidos Contra Entrega (COD)',
        request()->is('settlements*')            => 'Conciliación & Liquidaciones Bancarias',
        request()->is('reports*')                => 'Reportes Financieros & Analytics',
        request()->is('returns*')                => 'Logística Inversa & Novedades en Ruta',
        request()->is('carrier/cobro*')          => 'Terminal de Cobro en Puerta (Efectivo / QR)',
        request()->is('notifications/whatsapp*') => 'Centro Transaccional WhatsApp (+591)',
        request()->is('tracking*')               => 'Rastreo de Guías en Vivo',
        request()->is('soporte*')                => 'Centro de Ayuda & Soporte Técnico',
        request()->is('contactenos*')            => 'Red de Megabodegas & Almacenes',
        default                                  => 'Plataforma Operativa',
    };
@endphp

<!-- Stripe & Linear Executive Signature Page Header (Full-Bleed continuo sin franja blanca) -->
<header class="page-header-sb text-white pt-4 sm:pt-6 pb-16 sm:pb-20 px-4 sm:px-8 -mx-4 sm:-mx-8 !-mt-3 sm:!-mt-5 mb-0 relative overflow-hidden border-b border-indigo-500/20 shadow-xs">
    <!-- Subtle geometric & mesh glow overlay -->
    <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: radial-gradient(circle at 18% 50%, rgba(79,70,229,0.55) 0%, transparent 50%), radial-gradient(circle at 82% 20%, rgba(56,189,248,0.40) 0%, transparent 42%);"></div>
    <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-[#38BDF8]/10 blur-3xl pointer-events-none hero-floating-card"></div>
    <div class="absolute -bottom-24 left-1/4 w-64 h-64 rounded-full bg-[#4F46E5]/20 blur-3xl pointer-events-none"></div>

    <!-- Barra Contextual Integrada dentro del Hero (Wayfinding + Salto Rápido) -->
    <div class="max-w-7xl mx-auto relative z-10 mb-4 pb-3 border-b border-white/10 flex flex-wrap items-center justify-between gap-2 text-[11px] text-indigo-200/90">
        <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 font-medium">
            <a href="{{ $dashboardUrl ?? url('/') }}" class="text-indigo-200 hover:text-white transition flex items-center gap-1.5">
                <i class="fa-solid fa-house-chimney text-[10px] text-[#38BDF8]"></i>
                <span class="tracking-wide">Bolidrop</span>
            </a>
            <i class="fa-solid fa-chevron-right text-[8px] text-indigo-300/60"></i>
            <span class="font-bold text-white">{{ $currentModuleTitle }}</span>
        </nav>
        <div class="flex items-center gap-2">
            <button type="button" @click="cmdPaletteOpen = true; $nextTick(() => $refs.cmdInput?.focus())"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white/10 hover:bg-white/20 text-white border border-white/15 font-bold text-[10px] backdrop-blur-xs shadow-2xs transition cursor-pointer">
                <i class="fa-solid fa-bolt text-[9px] text-[#38BDF8]"></i>
                <span>Salto Rápido</span>
                <kbd class="hidden sm:inline font-mono text-[9px] text-indigo-200/70 bg-black/20 px-1 py-0.2 rounded border border-white/10">Ctrl+K</kbd>
            </button>
        </div>
    </div>
    <!-- Subtle geometric & mesh glow overlay -->
    <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: radial-gradient(circle at 18% 50%, rgba(79,70,229,0.55) 0%, transparent 50%), radial-gradient(circle at 82% 20%, rgba(56,189,248,0.40) 0%, transparent 42%);"></div>
    <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-[#38BDF8]/10 blur-3xl pointer-events-none hero-floating-card"></div>
    <div class="absolute -bottom-24 left-1/4 w-64 h-64 rounded-full bg-[#4F46E5]/20 blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5 min-w-0">
            @if($badge)
                <div class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-[9px] sm:text-[10px] font-extrabold uppercase tracking-wider text-indigo-100 mb-1 backdrop-blur-xs badge-pulse-glow max-w-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#38BDF8] animate-ping shrink-0"></span>
                    <span class="truncate">{{ $badge }}</span>
                </div>
            @endif
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold font-heading tracking-tight flex items-center gap-2.5 sm:gap-3 text-white group">
                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-[#38BDF8] text-base sm:text-lg shadow-xs shrink-0 backdrop-blur-xs transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                    <i class="{{ $icon }}"></i>
                </span>
                <span class="text-balance break-words min-w-0">{{ $title }}</span>
            </h1>
            @if($subtitle)
                <p class="text-xs sm:text-sm text-indigo-100/85 max-w-2xl leading-relaxed">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 shrink-0 w-full md:w-auto">
                {{ $actions }}
            </div>
        @endif
    </div>

    <!-- Live Operational Telemetry Strip (Alpine.js Auto-Rotating Stream) -->
    <div class="max-w-7xl mx-auto relative z-10 mt-4 sm:mt-5 pt-3 border-t border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[10px] sm:text-[11px] text-indigo-100/90"
         x-data="{
            idx: 0,
            events: [
                '⚡ DEX Bolivia Express: 42 despachos COD en ruta activa (Santa Cruz, La Paz, Cochabamba)',
                '💳 Conciliación QR Simple & BNB: Liquidación automática T+0 operativa al 100%',
                '📦 Kardex & Megabodega Nacional: Sincronización de stock multi-almacén en tiempo real',
                '🟢 Tasa de efectividad COD nacional: 94.0% con 2 intentos de entrega incluidos'
            ],
            init() { setInterval(() => { this.idx = (this.idx + 1) % this.events.length }, 4200); }
         }">
        <div class="flex items-center gap-2 min-w-0">
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#10B981]/20 border border-[#10B981]/40 text-[#10B981] font-mono font-extrabold text-[9px] sm:text-[10px] uppercase shrink-0">
                <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-ping"></span>
                LIVE
            </span>
            <span class="font-medium tracking-wide transition-all duration-300 truncate" x-text="events[idx]"></span>
        </div>
        <div class="hidden sm:flex items-center gap-4 font-mono text-[10px] text-indigo-200/80 shrink-0">
            <span><i class="fa-solid fa-server text-[#38BDF8] mr-1"></i>Latencia: <strong class="text-white">14ms</strong></span>
            <span><i class="fa-solid fa-shield-check text-[#10B981] mr-1"></i>Ledger: <strong class="text-[#10B981]">SYNC 100%</strong></span>
        </div>
    </div>
</header>
