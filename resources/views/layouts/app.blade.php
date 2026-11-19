<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bolidrop — Plataforma e-Commerce COD & Logística Nacional')</title>

    <!-- Google Fonts: Metrópolis / Plus Jakarta Sans (SB Admin Pro Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        sb: {
                            primary: '#4F46E5',
                            primaryHover: '#4338CA',
                            secondary: '#312E81',
                            teal: '#10B981',
                            cyan: '#38BDF8',
                            amber: '#F59E0B',
                            danger: '#F43F5E',
                            canvas: '#F8FAFC',
                            dark: '#0F172A',
                            muted: '#64748B',
                        },
                        fireworks: {
                            scarlet: '#4F46E5',
                            amber: '#10B981',
                            carmine: '#0F172A',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', '-apple-system', 'sans-serif'],
                        heading: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --sb-primary: #4F46E5;
            --sb-secondary: #312E81;
            --sb-teal: #10B981;
            --sb-canvas: #F8FAFC;
            --sb-dark: #0F172A;
            --sb-card-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06);
            --ease-spring: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            letter-spacing: -0.02em;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Stripe & Linear Executive Signature Gradient Header (#1E1B4B -> #312E81 -> #4F46E5) */
        .page-header-sb {
            background-color: #1E1B4B;
            background-image: radial-gradient(circle at 85% 15%, rgba(56, 189, 248, 0.22) 0%, transparent 45%),
                              linear-gradient(135deg, #0F172A 0%, #1E1B4B 38%, #312E81 74%, #4F46E5 100%);
        }

        /* Diseño 1: Sidebar Midnight Slate (#0F172A -> #1E293B) & Electric Indigo (#4F46E5) */
        .sidenav-light {
            background-color: #0F172A;
            background-image: radial-gradient(circle at 0% 0%, rgba(79, 70, 229, 0.24) 0%, transparent 50%),
                              linear-gradient(180deg, #0F172A 0%, #1E293B 100%);
            color: #FFFFFF;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.35);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.25) transparent;
        }
        /* Scrollbar Ultraligero Integrado (Sin franja gris/blanca nativa) */
        .sidenav-light::-webkit-scrollbar {
            width: 4px;
        }
        .sidenav-light::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidenav-light::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, 0.25);
            border-radius: 9999px;
        }
        .sidenav-light:hover::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, 0.45);
        }

        .sidenav-light .text-\[\#a7aeb8\] {
            color: #94A3B8 !important;
            letter-spacing: 0.08em;
        }
        .sidenav-light a {
            position: relative;
            color: #CBD5E1 !important;
            margin: 3px 12px;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.24s cubic-bezier(0.16, 1, 0.3, 1),
                        background-color 0.2s ease,
                        box-shadow 0.22s ease,
                        color 0.18s ease !important;
        }
        .sidenav-light a i {
            color: #94A3B8 !important;
            transition: transform 0.24s cubic-bezier(0.16, 1, 0.3, 1), color 0.2s ease;
        }
        /* Efecto Hover en Ítems del Menú (Midnight Slate Glass) */
        .sidenav-light a:hover:not(.bg-blue-50\/70) {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #FFFFFF !important;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.20), inset 0 1px 0 rgba(255, 255, 255, 0.10);
        }
        .sidenav-light a:hover i {
            color: #38BDF8 !important;
            transform: scale(1.16) rotate(-4deg);
        }
        /* Efecto Ítem Activo en el Menú (Cápsula Electric Indigo #4F46E5 + Cian #38BDF8) */
        .sidenav-light a.bg-blue-50\/70 {
            background: linear-gradient(135deg, #4F46E5 0%, #3730A3 100%) !important;
            color: #FFFFFF !important;
            border-right: 3px solid #38BDF8 !important;
            box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.28);
        }
        .sidenav-light a.bg-blue-50\/70 i {
            color: #38BDF8 !important;
            transform: scale(1.12);
        }

        /* Stripe & Linear Executive Card Shadow Elevation */
        .bg-white.rounded-2xl,
        .bg-white.rounded-3xl,
        .bg-white.rounded-xl,
        .white-card,
        .sb-card {
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 1px 3px 0 rgba(15, 23, 42, 0.03);
            border-color: #E2E8F0;
        }

        .interactive-card {
            transition: transform 0.2s var(--ease-spring),
                        box-shadow 0.2s var(--ease-spring),
                        border-color 0.18s ease;
        }
        .interactive-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.12);
            border-color: rgba(79, 70, 229, 0.40);
        }

        /* Stripe & Linear Primary Button (#4F46E5 Electric Indigo) */
        .btn-scarlet {
            background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
            color: #FFFFFF;
            transition: transform 0.18s var(--ease-spring),
                        background-color 0.15s ease,
                        box-shadow 0.18s ease;
        }
        .btn-scarlet:hover {
            background: linear-gradient(135deg, #4338CA 0%, #3730A3 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.45);
        }

        /* Emerald Success Button (#10B981) */
        .btn-amber {
            background-color: #10B981;
            color: #FFFFFF;
            transition: transform 0.18s var(--ease-spring),
                        background-color 0.15s ease;
        }
        .btn-amber:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }

        .nav-item-transition {
            transition: color 0.15s ease, background-color 0.15s ease;
        }

        /* ===================================================================== */
        /* PREMIUM UI/UX MOTION & INTERACTIVE CHOREOGRAPHY ENGINE                */
        /* Skills: ui-motion-transitions, ui-ux-pro-max, frontend-design-eng     */
        /* ===================================================================== */

        /* 1. Animated Aurora & Mesh Gradient Background for Main Heroes */
        @keyframes auroraShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50%      { transform: translateY(-6px) rotate(0.5deg); }
        }
        @keyframes textShimmer {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes fadeUpSpring {
            from { opacity: 0; transform: translateY(18px) scale(0.985); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes barGrowUp {
            from { transform: scaleY(0); }
            to   { transform: scaleY(1); }
        }
        @keyframes ringSweep {
            from { transform: rotate(-180deg); opacity: 0; }
            to   { transform: rotate(-45deg); opacity: 1; }
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.45); }
            50%      { box-shadow: 0 0 0 8px rgba(56, 189, 248, 0); }
        }

        .page-header-sb {
            background-size: 200% 200%;
            animation: auroraShift 14s ease infinite;
        }

        /* 2. Gradient Shimmer Text & Hero Typography Balance */
        .text-gradient-shimmer {
            background: linear-gradient(90deg, #FFFFFF 0%, #38BDF8 35%, #A5B4FC 70%, #FFFFFF 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: textShimmer 6s linear infinite;
        }
        .hero-floating-card {
            animation: floatSlow 6s ease-in-out infinite;
            will-change: transform;
        }

        /* 3. Staggered Entrance Choreography for Panels & Grids */
        .grid > div,
        .bg-white.rounded-2xl,
        .bg-white.rounded-3xl,
        .white-card {
            animation: fadeUpSpring 0.52s var(--ease-spring) both;
        }
        .grid > div:nth-child(1) { animation-delay: 0.04s; }
        .grid > div:nth-child(2) { animation-delay: 0.09s; }
        .grid > div:nth-child(3) { animation-delay: 0.14s; }
        .grid > div:nth-child(4) { animation-delay: 0.19s; }
        .grid > div:nth-child(5) { animation-delay: 0.24s; }
        .grid > div:nth-child(6) { animation-delay: 0.29s; }

        /* 4. Interactive Card Spotlight & Tactile Micro-Interactions (<240ms) */
        .bg-white.rounded-2xl,
        .bg-white.rounded-3xl,
        .white-card {
            position: relative;
            transition: transform 0.24s var(--ease-spring),
                        box-shadow 0.24s var(--ease-spring),
                        border-color 0.2s ease;
            will-change: transform;
        }
        .bg-white.rounded-2xl:hover,
        .bg-white.rounded-3xl:hover,
        .white-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 32px -6px rgba(15, 23, 42, 0.10), 0 4px 12px -2px rgba(79, 70, 229, 0.08);
            border-color: rgba(79, 70, 229, 0.35);
        }

        /* 5. Button Shine Sweep & Active Press Physics */
        .btn-scarlet,
        .btn-amber {
            position: relative;
            overflow: hidden;
            will-change: transform;
        }
        .btn-scarlet::after {
            content: '';
            position: absolute;
            top: 0;
            left: -120%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.26), transparent);
            transform: skewX(-20deg);
            transition: left 0.55s var(--ease-spring);
            pointer-events: none;
        }
        .btn-scarlet:hover::after {
            left: 140%;
        }
        .btn-scarlet:active,
        .btn-amber:active {
            transform: translateY(0) scale(0.97) !important;
        }

        /* 6. Table Row Micro-Slide & Left Accent Highlight */
        tbody tr {
            transition: background-color 0.18s ease, transform 0.2s var(--ease-spring);
        }
        tbody tr:hover {
            background-color: rgba(79, 70, 229, 0.035) !important;
        }

        /* 7. Animated Mini-Charts & Sparkline Bars in Panels */
        .sparkline-bar {
            transform-origin: bottom;
            animation: barGrowUp 0.75s var(--ease-spring) both;
            transition: transform 0.2s var(--ease-spring), background-color 0.2s ease, filter 0.2s ease;
        }
        .sparkline-bar:nth-child(1) { animation-delay: 0.10s; }
        .sparkline-bar:nth-child(2) { animation-delay: 0.16s; }
        .sparkline-bar:nth-child(3) { animation-delay: 0.22s; }
        .sparkline-bar:nth-child(4) { animation-delay: 0.28s; }
        .sparkline-bar:nth-child(5) { animation-delay: 0.34s; }
        .sparkline-bar:nth-child(6) { animation-delay: 0.40s; }
        .sparkline-bar:hover {
            transform: scaleY(1.12);
            background-color: #38BDF8 !important;
            filter: drop-shadow(0 0 6px rgba(56, 189, 248, 0.6));
        }

        .progress-ring-animated {
            animation: ringSweep 0.9s var(--ease-spring) both;
            animation-delay: 0.25s;
        }

        .badge-pulse-glow {
            animation: pulseGlow 2.4s infinite;
        }

        /* 8. Accessibility: Respect Vestibular Motion Preferences (WCAG 2.1 AA) */
        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-[#4F46E5] selection:text-white bg-[#F8FAFC]" x-data="{ sidenavOpen: true, mobileMenuOpen: false }">

    <!-- ===================================================================== -->
    <!-- STRIPE & LINEAR EXECUTIVE TOPNAV (Fixed Full-Width Frosted Navbar)    -->
    <!-- ===================================================================== -->
    <nav class="sticky top-0 z-50 h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between gap-4 shadow-[0_2px_15px_-3px_rgba(15,23,42,0.06)]">
        
        <!-- Left: Sidenav Toggle + Brand + Search -->
        <div class="flex items-center gap-3 sm:gap-5">
            @if(Auth::check() && !request()->is('/'))
            <button @click="sidenavOpen = !sidenavOpen; mobileMenuOpen = !mobileMenuOpen"
                class="w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500 hover:text-[#0F172A] flex items-center justify-center transition focus:outline-none"
                title="Alternar Menú Lateral">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
            @endif

            <!-- Brand Logo with Nano Banana -->
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 group shrink-0">
                <div class="relative w-9 h-9 rounded-lg bg-indigo-50 p-0.5 flex items-center justify-center border border-indigo-200/80 shadow-xs">
                    <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-md">
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-[#10B981] rounded-full border-2 border-white"></span>
                </div>
                <div class="leading-none">
                    <span class="text-base font-extrabold tracking-tight text-[#0F172A] group-hover:text-[#4F46E5] transition">Bolidrop</span>
                    <span class="text-[9px] font-bold text-[#4F46E5] uppercase tracking-widest block">Bolivia PRO</span>
                </div>
            </a>

            @if(Auth::check() && !request()->is('/'))
            <!-- Global Search Input (Solo en Paneles Internos) -->
            <form action="{{ url('/tracking') }}" method="GET" class="hidden md:flex items-center relative ml-2">
                <input type="text" name="guide" placeholder="Buscar guía, pedido o SKU..."
                    class="bg-[#F1F5F9] border border-slate-200/70 text-[#0F172A] placeholder-[#64748B] rounded-lg pl-3.5 pr-9 py-2 text-xs font-medium focus:outline-none focus:bg-white focus:border-[#4F46E5] focus:ring-2 focus:ring-[#4F46E5]/15 transition w-64">
                <button type="submit" class="absolute right-3 text-[#64748B] hover:text-[#4F46E5] transition">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </button>
            </form>
            @endif
        </div>

        <!-- Right: Cotizaciones del Boliviano (BOB), Divisas & Auth Buttons -->
        <div class="flex items-center gap-2.5 sm:gap-3" x-data="{ ratesOpen: false }">
            @php
                $sellRate = (float) ($bcbOfficialRate ?? 11.70);
                $buyRate  = max(6.86, round($sellRate - 0.10, 2));
                $usdtRate = round($sellRate + 0.15, 2);
                $eurRate  = round($sellRate * 1.08, 2);
                $cnyRate  = round($sellRate * 0.14, 2);
                $brlRate  = round($sellRate * 0.20, 2);
            @endphp

            <!-- Ticker de Tipo de Cambio en el Navbar con Desplegable de Monedas -->
            <div class="relative">
                <button @click="ratesOpen = !ratesOpen" @click.away="ratesOpen = false"
                    class="flex items-center gap-2 sm:gap-3 px-3 py-1.5 rounded-lg bg-[#F1F5F9] hover:bg-indigo-50/70 border border-slate-200/80 text-[#0F172A] text-xs transition cursor-pointer"
                    title="Ver todas las cotizaciones del Boliviano (BOB)">
                    <span class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse"></span>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[#64748B] font-semibold">USD/BOB:</span>
                        <span class="hidden md:inline text-[11px] text-[#64748B]">C: <strong class="font-mono text-[#0F172A]">{{ number_format($buyRate, 2) }}</strong></span>
                        <span class="text-[11px] text-[#64748B]">V: <strong class="font-mono text-[#4F46E5] font-bold">{{ number_format($sellRate, 2) }} Bs</strong></span>
                    </div>
                    <span class="hidden lg:inline text-slate-300">|</span>
                    <div class="hidden lg:flex items-center gap-1 text-[11px]">
                        <span class="text-[#64748B] font-semibold">USDT:</span>
                        <strong class="font-mono text-[#10B981] font-bold">{{ number_format($usdtRate, 2) }} Bs</strong>
                    </div>
                    <span class="hidden xl:inline text-slate-300">|</span>
                    <div class="hidden xl:flex items-center gap-1 text-[11px]">
                        <span class="text-[#64748B] font-semibold">EUR:</span>
                        <strong class="font-mono text-[#4F46E5] font-bold">{{ number_format($eurRate, 2) }} Bs</strong>
                    </div>
                    <i class="fa-solid fa-chevron-down text-[10px] text-[#64748B] ml-0.5"></i>
                </button>

                <!-- Dropdown Detallado de Monedas frente al Boliviano (BOB) -->
                <div x-show="ratesOpen" x-cloak
                    x-transition
                    class="absolute right-0 mt-2 w-80 bg-white rounded-xl border border-slate-200 shadow-2xl p-4 z-50 text-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div>
                            <div class="font-extrabold text-[#0F172A] text-xs">Tipo de Cambio — Bolivia (BOB)</div>
                            <div class="text-[10px] text-[#64748B]">Cotizaciones referenciales e importación</div>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-[#10B981] font-bold text-[10px]">En Vivo</span>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg bg-[#F8FAFC]">
                            <div>
                                <div class="font-bold text-[#0F172A]">🇺🇸 Dólar BCB Referencial</div>
                                <div class="text-[10px] text-[#64748B]">Comercio Exterior / Importación</div>
                            </div>
                            <div class="text-right font-mono">
                                <div class="text-[10px] text-[#64748B]">Compra: <strong>{{ number_format($buyRate, 2) }} Bs</strong></div>
                                <div class="text-xs font-bold text-[#4F46E5]">Venta: {{ number_format($sellRate, 2) }} Bs</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg bg-[#F8FAFC]">
                            <div>
                                <div class="font-bold text-[#0F172A]">🇧🇴 Dólar Oficial BCB</div>
                                <div class="text-[10px] text-[#64748B]">Tasa Fija Bancaria</div>
                            </div>
                            <div class="text-right font-mono">
                                <div class="text-[10px] text-[#64748B]">Compra: <strong>6.86 Bs</strong></div>
                                <div class="text-xs font-bold text-[#0F172A]">Venta: 6.96 Bs</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg bg-[#F8FAFC]">
                            <div>
                                <div class="font-bold text-[#0F172A]">₮ USDT (Binance P2P)</div>
                                <div class="text-[10px] text-[#64748B]">Liquidación Cripto / QR Simple</div>
                            </div>
                            <div class="text-right font-mono">
                                <div class="text-xs font-bold text-[#10B981]">{{ number_format($usdtRate, 2) }} Bs</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <div class="p-2 rounded-lg border border-slate-200/80 flex items-center justify-between">
                                <span class="font-semibold text-[#64748B]">🇪🇺 EUR:</span>
                                <strong class="font-mono text-[#0F172A]">{{ number_format($eurRate, 2) }} Bs</strong>
                            </div>
                            <div class="p-2 rounded-lg border border-slate-200/80 flex items-center justify-between">
                                <span class="font-semibold text-[#64748B]">🇨🇳 CNY:</span>
                                <strong class="font-mono text-[#0F172A]">{{ number_format($cnyRate, 2) }} Bs</strong>
                            </div>
                            <div class="p-2 rounded-lg border border-slate-200/80 flex items-center justify-between">
                                <span class="font-semibold text-[#64748B]">🇧🇷 BRL:</span>
                                <strong class="font-mono text-[#0F172A]">{{ number_format($brlRate, 2) }} Bs</strong>
                            </div>
                            <div class="p-2 rounded-lg border border-slate-200/80 flex items-center justify-between">
                                <span class="font-semibold text-[#64748B]">🇵🇪 PEN:</span>
                                <strong class="font-mono text-[#0F172A]">{{ number_format($sellRate * 0.27, 2) }} Bs</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(Auth::check() && !request()->is('/'))
                <div class="hidden lg:flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-[#10B981] border border-emerald-200/70 text-xs font-bold">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>DEX 9/9</span>
                </div>

                <div class="h-6 w-px bg-slate-200 mx-1 hidden sm:block"></div>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#0F172A] via-[#312E81] to-[#4F46E5] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-[#0F172A] leading-tight">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] text-[#4F46E5] font-bold uppercase tracking-wider">{{ str_replace('_', ' ', Auth::user()->role) }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                        @csrf
                        <button type="submit" title="Cerrar Sesión" class="w-8 h-8 rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center transition">
                            <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex items-center gap-2 text-xs">
                    <a href="{{ route('login') }}" class="font-bold text-[#0F172A] hover:text-[#4F46E5] px-4 py-2 rounded-lg border border-slate-200 hover:border-[#4F46E5] hover:bg-indigo-50/40 transition">
                        Ingresar
                    </a>
                    <a href="{{ route('register') }}" class="btn-scarlet px-4 py-2 rounded-lg font-bold shadow-xs">
                        Registrarse →
                    </a>
                </div>
            @endif
        </div>
    </nav>

    <!-- ===================================================================== -->
    <!-- SB ADMIN PRO LAYOUT SIDENAV + CONTENT WRAPPER                         -->
    <!-- ===================================================================== -->
    <div id="layoutSidenav" class="flex-grow flex flex-col md:flex-row min-h-[calc(100vh-4rem)]">

        @if(Auth::check() && !request()->is('/'))
        <!-- SIDENAV LIGHT (SB Admin Pro Left Navigation - Hidden on Public Landing) -->
        <aside :class="sidenavOpen ? (mobileMenuOpen ? 'flex' : 'hidden md:flex') : 'hidden'"
            class="sidenav-light w-full md:w-64 md:sticky md:top-16 md:h-[calc(100vh-4rem)] flex-col justify-between shrink-0 z-40 overflow-y-auto">
            
            <div class="py-2">
                <!-- Sidenav Menu -->
                <nav class="space-y-0.5">

                    {{-- ================================================ --}}
                    {{-- ADMINISTRADOR (tenant_admin)                     --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'tenant_admin')
                        <div class="px-6 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Core / Dashboards
                        </div>
                        <a href="{{ url('/panel/admin') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/admin') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-gauge-high w-4 text-center {{ request()->is('panel/admin') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Dashboard Central</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('catalogo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-4 text-center {{ request()->is('catalogo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Megabodega Nacional</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('checkout-demo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-4 text-center {{ request()->is('checkout-demo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>

                        <div class="px-6 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Finanzas & Conciliación
                        </div>
                        <a href="{{ url('/settlements') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('settlements*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-building-columns w-4 text-center {{ request()->is('settlements*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Liquidaciones Bancarias</span>
                        </a>
                        <a href="{{ url('/reports') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('reports*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-chart-line w-4 text-center {{ request()->is('reports*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Reportes & Analytics</span>
                        </a>

                        <div class="px-6 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Logística & Operaciones
                        </div>
                        <a href="{{ route('admin.kardex.index') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/admin/kardex*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-clipboard-list w-4 text-center {{ request()->is('panel/admin/kardex*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Kardex Global & Stock</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('returns*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-4 text-center {{ request()->is('returns*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Devoluciones & Log. Inv.</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('carrier/cobro*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-qrcode w-4 text-center {{ request()->is('carrier/cobro*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Cobro QR Repartidor</span>
                        </a>
                        <a href="{{ url('/panel/fulfillment') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/fulfillment') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-boxes-packing w-4 text-center {{ request()->is('panel/fulfillment') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Fulfillment & Tiendas</span>
                        </a>
                        <a href="{{ url('/notifications/whatsapp') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('notifications/whatsapp*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-brands fa-whatsapp w-4 text-center {{ request()->is('notifications/whatsapp*') ? 'text-[#0061f2]' : 'text-[#00ac69]' }}"></i>
                            <span>WhatsApp Bot (+591)</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- DROPSHIPPER                                      --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'dropshipper')
                        <div class="px-5 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Mi Negocio Dropshipping
                        </div>
                        <a href="{{ url('/panel/dropshipper') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/dropshipper') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-gauge-high w-4 text-center {{ request()->is('panel/dropshipper') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('catalogo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-4 text-center {{ request()->is('catalogo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Megabodega</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('checkout-demo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-4 text-center {{ request()->is('checkout-demo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>
                        <a href="{{ url('/panel/dropshipper') }}#billetera" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium">
                            <i class="fa-solid fa-wallet w-4 text-center text-[#a7aeb8]"></i>
                            <span>Mi Billetera & Ganancias</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('returns*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-4 text-center {{ request()->is('returns*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mis Devoluciones</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- PROVEEDOR                                        --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'provider')
                        <div class="px-5 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Panel de Proveedor
                        </div>
                        <a href="{{ url('/panel/proveedor') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/proveedor') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-gauge-high w-4 text-center {{ request()->is('panel/proveedor') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('catalogo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-4 text-center {{ request()->is('catalogo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Catálogo Publicado</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#inventario" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium">
                            <i class="fa-solid fa-warehouse w-4 text-center text-[#a7aeb8]"></i>
                            <span>Mi Inventario Físico</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#alertas" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium">
                            <i class="fa-solid fa-triangle-exclamation w-4 text-center text-[#a7aeb8]"></i>
                            <span>Alertas de Stock</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- CARRIER                                          --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'carrier')
                        <div class="px-5 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Repartidor / Courier
                        </div>
                        <a href="{{ url('/panel/carrier') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/carrier') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-truck-fast w-4 text-center {{ request()->is('panel/carrier') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mi Hoja de Ruta</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('carrier/cobro*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-qrcode w-4 text-center {{ request()->is('carrier/cobro*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Cobro QR / Efectivo</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('returns*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-4 text-center {{ request()->is('returns*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Reportar Novedad</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- FULFILLMENT CLIENT                               --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'fulfillment_client')
                        <div class="px-5 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Fulfillment e-Commerce
                        </div>
                        <a href="{{ url('/panel/fulfillment') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/fulfillment') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-store w-4 text-center {{ request()->is('panel/fulfillment') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mi Tienda Online</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('checkout-demo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-4 text-center {{ request()->is('checkout-demo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Enviar Pedidos</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('returns*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-4 text-center {{ request()->is('returns*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Devoluciones</span>
                        </a>
                    @endif

                    {{-- ======================================================== --}}
                    {{-- ADDONS / HERRAMIENTAS GLOBALES                           --}}
                    {{-- ======================================================== --}}
                    <div class="pt-3 mt-3 border-t border-white/10">
                        <div class="px-6 pt-2 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Addons & Soporte
                        </div>
                        <a href="{{ url('/tracking') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 text-[13px] {{ request()->is('tracking*') ? 'text-[#4F46E5] bg-blue-50/70 font-bold border-r-4 border-[#38BDF8]' : 'text-[#0F172A] hover:text-[#4F46E5] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-magnifying-glass-location w-4 text-center {{ request()->is('tracking*') ? 'text-[#38BDF8]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Rastrear Guía</span>
                        </a>
                        <a href="{{ url('/contactenos') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 text-[13px] {{ request()->is('contactenos*') ? 'text-[#4F46E5] bg-blue-50/70 font-bold border-r-4 border-[#38BDF8]' : 'text-[#0F172A] hover:text-[#4F46E5] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-map-location-dot w-4 text-center {{ request()->is('contactenos*') ? 'text-[#38BDF8]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Sedes & Almacenes</span>
                        </a>
                        <a href="{{ url('/soporte') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 text-[13px] {{ request()->is('soporte*') ? 'text-[#4F46E5] bg-blue-50/70 font-bold border-r-4 border-[#38BDF8]' : 'text-[#0F172A] hover:text-[#4F46E5] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-headset w-4 text-center {{ request()->is('soporte*') ? 'text-[#38BDF8]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Centro de Soporte</span>
                        </a>
                    </div>
                </nav>
            </div>

            <!-- Stripe & Linear Executive Sidenav Footer (#0F172A Midnight Slate Glass) -->
            <div class="bg-[#090D16]/75 border-t border-white/10 px-6 py-4 backdrop-blur-sm">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-2 h-2 rounded-full bg-[#38BDF8] animate-pulse"></span>
                    <span class="text-[10px] font-extrabold text-[#38BDF8] uppercase tracking-wider">Nano Banana Sync</span>
                </div>
                <div class="text-[11px] text-slate-400 font-medium">Conectado como:</div>
                <div class="text-xs font-bold text-white truncate">{{ Auth::user()->name }}</div>
            </div>

        </aside>
        @endif

        <!-- ===================================================================== -->
        <!-- MAIN CONTENT AREA (#F8FAFC Alabaster Canvas)                          -->
        <!-- ===================================================================== -->
        <div id="layoutSidenav_content" class="flex-grow flex flex-col min-w-0 bg-[#F8FAFC]">
            
            <main class="px-4 py-3 sm:px-8 sm:py-5 flex-grow">
                @yield('content')
            </main>

            <!-- STRIPE & LINEAR EXECUTIVE FOOTER -->
            <footer class="bg-white border-t border-slate-200/80 px-6 sm:px-10 py-6 text-xs text-[#64748B]">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span>Copyright &copy; <strong class="text-[#0F172A]">Bolidrop Bolivia</strong> {{ date('Y') }}</span>
                        <span>•</span>
                        <span class="text-[11px]">DEX Bolivia Express & QR Simple (9/9 Deptos)</span>
                    </div>
                    <div class="flex items-center space-x-4 text-[11px] font-medium">
                        <a href="{{ url('/catalogo') }}" class="hover:text-[#4F46E5] transition">Megabodega</a>
                        <span>&middot;</span>
                        <a href="{{ url('/tracking') }}" class="hover:text-[#4F46E5] transition">Rastreo de Guías</a>
                        <span>&middot;</span>
                        <a href="{{ url('/soporte') }}" class="hover:text-[#4F46E5] transition">Soporte</a>
                        <span>&middot;</span>
                        <a href="{{ url('/contactenos') }}" class="hover:text-[#4F46E5] transition">Almacenes</a>
                    </div>
                </div>
            </footer>

        </div>
    </div>

    @stack('scripts')
</body>
</html>
