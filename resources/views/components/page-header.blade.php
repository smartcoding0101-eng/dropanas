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

    <div class="max-w-7xl mx-auto relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            @if($badge)
                <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-[10px] font-extrabold uppercase tracking-wider text-indigo-100 mb-1 backdrop-blur-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#38BDF8]"></span>
                    <span>{{ $badge }}</span>
                </div>
            @endif
            <h1 class="text-2xl sm:text-3xl font-bold font-heading tracking-tight flex items-center gap-3 text-white">
                <span class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-[#38BDF8] text-lg shadow-xs shrink-0 backdrop-blur-xs">
                    <i class="{{ $icon }}"></i>
                </span>
                <span>{{ $title }}</span>
            </h1>
            @if($subtitle)
                <p class="text-xs sm:text-sm text-indigo-100/85 max-w-2xl">
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
