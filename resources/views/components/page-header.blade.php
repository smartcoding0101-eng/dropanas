@props([
    'title',
    'subtitle' => null,
    'icon' => 'fa-solid fa-chart-pie',
    'badge' => null,
])

<!-- SB Admin Pro Signature Page Header with Overlap Support -->
<header class="bg-gradient-to-r from-[#0A1128] via-[#0F172A] to-[#00A884] text-white pt-8 pb-20 px-4 sm:px-8 -mx-4 sm:-mx-8 -mt-3 sm:-mt-5 mb-0 shadow-md relative overflow-hidden">
    <!-- Subtle decorative glow -->
    <div class="absolute right-0 top-0 w-96 h-96 bg-emerald-400/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>

    <div class="max-w-7xl mx-auto relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            @if($badge)
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/10 border border-white/20 text-[10px] font-bold uppercase tracking-wider text-emerald-300 mb-1">
                    {{ $badge }}
                </div>
            @endif
            <h1 class="text-2xl sm:text-3xl font-extrabold font-heading tracking-tight flex items-center gap-3 text-white">
                <span class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-emerald-300 text-lg shadow-inner shrink-0">
                    <i class="{{ $icon }}"></i>
                </span>
                <span>{{ $title }}</span>
            </h1>
            @if($subtitle)
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl pl-13">
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
</header>
