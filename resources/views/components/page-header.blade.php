@props([
    'title',
    'subtitle' => null,
    'icon' => 'fa-solid fa-chart-pie',
    'badge' => null,
])

<!-- Stripe & Linear Executive Signature Page Header (#0F172A -> #1E1B4B -> #312E81 -> #4F46E5 + Overlap) -->
<header class="page-header-sb text-white pt-8 pb-20 px-4 sm:px-8 -mx-4 sm:-mx-8 !-mt-3 sm:!-mt-5 mb-0 relative overflow-hidden border-b border-indigo-500/20">
    <!-- Subtle geometric & mesh glow overlay -->
    <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: radial-gradient(circle at 18% 50%, rgba(79,70,229,0.55) 0%, transparent 50%), radial-gradient(circle at 82% 20%, rgba(56,189,248,0.40) 0%, transparent 42%);"></div>
    <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-[#38BDF8]/10 blur-3xl pointer-events-none hero-floating-card"></div>
    <div class="absolute -bottom-24 left-1/4 w-64 h-64 rounded-full bg-[#4F46E5]/20 blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            @if($badge)
                <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-[10px] font-extrabold uppercase tracking-wider text-indigo-100 mb-1 backdrop-blur-xs badge-pulse-glow">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#38BDF8] animate-ping"></span>
                    <span>{{ $badge }}</span>
                </div>
            @endif
            <h1 class="text-2xl sm:text-3xl font-bold font-heading tracking-tight flex items-center gap-3 text-white group">
                <span class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-[#38BDF8] text-lg shadow-xs shrink-0 backdrop-blur-xs transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                    <i class="{{ $icon }}"></i>
                </span>
                <span class="text-balance">{{ $title }}</span>
            </h1>
            @if($subtitle)
                <p class="text-xs sm:text-sm text-indigo-100/85 max-w-2xl leading-relaxed">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>

    <!-- Live Operational Telemetry Strip (Alpine.js Auto-Rotating Stream) -->
    <div class="max-w-7xl mx-auto relative z-10 mt-5 pt-3.5 border-t border-white/10 flex flex-wrap items-center justify-between gap-3 text-[11px] text-indigo-100/90"
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
        <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#10B981]/20 border border-[#10B981]/40 text-[#10B981] font-mono font-extrabold text-[10px] uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-ping"></span>
                LIVE
            </span>
            <span class="font-medium tracking-wide transition-all duration-300" x-text="events[idx]"></span>
        </div>
        <div class="hidden sm:flex items-center gap-4 font-mono text-[10px] text-indigo-200/80">
            <span><i class="fa-solid fa-server text-[#38BDF8] mr-1"></i>Latencia: <strong class="text-white">14ms</strong></span>
            <span><i class="fa-solid fa-shield-check text-[#10B981] mr-1"></i>Ledger: <strong class="text-[#10B981]">SYNC 100%</strong></span>
        </div>
    </div>
</header>
