@props([
    'title',
    'subtitle' => null,
    'icon' => 'fa-solid fa-chart-pie',
    'badge' => null,
])

<!-- SB Admin Pro Signature Page Header (bg-gradient-primary-to-secondary + Overlap) -->
<header class="page-header-sb text-white pt-10 pb-24 px-4 sm:px-8 -mx-4 sm:-mx-8 -mt-3 sm:-mt-5 mb-0 relative overflow-hidden">
    <!-- Subtle geometric pattern overlay -->
    <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.35) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.25) 0%, transparent 40%);"></div>

    <div class="max-w-7xl mx-auto relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            @if($badge)
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/15 border border-white/25 text-[10px] font-bold uppercase tracking-wider text-white mb-1">
                    {{ $badge }}
                </div>
            @endif
            <h1 class="text-2xl sm:text-3xl font-bold font-heading tracking-tight flex items-center gap-3 text-white">
                <span class="w-10 h-10 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center text-white/90 text-lg shadow-xs shrink-0">
                    <i class="{{ $icon }}"></i>
                </span>
                <span>{{ $title }}</span>
            </h1>
            @if($subtitle)
                <p class="text-xs sm:text-sm text-white/80 max-w-2xl">
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
