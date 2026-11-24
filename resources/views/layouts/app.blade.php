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

        html {
            scrollbar-gutter: stable;
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

        /* Modo Colapsado / Mini Sidebar (Ocultar textos y mostrar solo iconos) */
        .sidebar-mini a span,
        .sidebar-mini .sidebar-heading,
        .sidebar-mini nav > div.sidebar-heading,
        .sidebar-mini .sidebar-hide-mini {
            display: none !important;
        }
        .sidebar-mini a {
            justify-content: center !important;
            padding-left: 0.25rem !important;
            padding-right: 0.25rem !important;
            margin-left: 0.4rem !important;
            margin-right: 0.4rem !important;
            border-radius: 0.75rem !important;
        }
        .sidebar-mini a i {
            margin: 0 !important;
            width: auto !important;
            font-size: 1.15rem !important;
        }
        .sidebar-mini .sidebar-footer-text {
            display: none !important;
        }
        .sidebar-mini .sidebar-footer-wrapper {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
            justify-content: center !important;
        }

        /* Prevenir salto o desplazamiento visual del Hero y Sidebar al refrescar la página */
        @media (min-width: 768px) {
            html.sidebar-is-collapsed #layoutSidenav_content {
                margin-left: 5rem !important; /* 80px / md:ml-20 */
            }
            html.sidebar-is-collapsed aside.sidenav-light {
                width: 5rem !important; /* 80px / md:w-20 */
            }
            html.sidebar-is-collapsed .sidebar-mini a span,
            html.sidebar-is-collapsed .sidebar-heading,
            html.sidebar-is-collapsed .sidebar-hide-mini {
                display: none !important;
            }
        }

        /* Stripe & Linear Executive Card Shadow Elevation */
        .bg-white.rounded-2xl,
        .bg-white.rounded-3xl,
        .bg-white.rounded-xl,
        .white-card,
        .sb-card {
            background-color: #ffffff;
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
        /* PREMIUM UI/UX MOTION & INTERACTIVE CHOREOGRAPHY ENGINE V2             */
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
            50%      { transform: translateY(-7px) rotate(0.4deg); }
        }
        @keyframes textShimmer {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes fadeUpSpring {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes barGrowUp {
            from { transform: scaleY(0); }
            to   { transform: scaleY(1); }
        }
        @keyframes barEqualizer {
            0%, 100% { transform: scaleY(1); }
            50%      { transform: scaleY(0.84); }
        }
        @keyframes ringSweep {
            from { transform: rotate(-180deg); opacity: 0; }
            to   { transform: rotate(-45deg); opacity: 1; }
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.45); }
            50%      { box-shadow: 0 0 0 9px rgba(56, 189, 248, 0); }
        }
        @keyframes gridScan {
            0%   { transform: translateY(-100%); opacity: 0; }
            45%  { opacity: 0.45; }
            100% { transform: translateY(100%); opacity: 0; }
        }
        @keyframes borderBeamRotate {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .page-header-sb {
            background-size: 200% 200%;
            animation: auroraShift 12s ease infinite;
        }
        /* Interactive Perspective Grid Overlay inside Heroes */
        .page-header-sb::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(ellipse at 50% 45%, black 35%, transparent 80%);
            -webkit-mask-image: radial-gradient(ellipse at 50% 45%, black 35%, transparent 80%);
            pointer-events: none;
        }
        .page-header-sb::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(550px circle at var(--hero-x, 70%) var(--hero-y, 30%), rgba(56, 189, 248, 0.22), transparent 55%);
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        /* 2. Gradient Shimmer Text & Hero Typography Balance */
        .text-gradient-shimmer {
            background: linear-gradient(90deg, #FFFFFF 0%, #38BDF8 35%, #A5B4FC 70%, #FFFFFF 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: textShimmer 5s linear infinite;
        }
        .hero-floating-card {
            animation: floatSlow 5.5s ease-in-out infinite;
            will-change: transform;
        }

        /* 3. Staggered Entrance & Scroll-Reveal Choreography */
        .grid > div,
        .bg-white.rounded-2xl,
        .bg-white.rounded-3xl,
        .white-card {
            animation: fadeUpSpring 0.55s var(--ease-spring) both;
        }
        .grid > div:nth-child(1) { animation-delay: 0.04s; }
        .grid > div:nth-child(2) { animation-delay: 0.10s; }
        .grid > div:nth-child(3) { animation-delay: 0.16s; }
        .grid > div:nth-child(4) { animation-delay: 0.22s; }
        .grid > div:nth-child(5) { animation-delay: 0.28s; }
        .grid > div:nth-child(6) { animation-delay: 0.34s; }

        /* 4. Interactive 3D Spotlight & Border Glow on Cards */
        .bg-white.rounded-2xl,
        .bg-white.rounded-3xl,
        .white-card,
        .kpi-card,
        .card {
            position: relative;
            overflow: hidden;
            transition: transform 0.22s var(--ease-spring),
                        box-shadow 0.24s var(--ease-spring),
                        border-color 0.2s ease;
            will-change: transform;
        }
        /* Cursor-following radial spotlight inside every card */
        .bg-white.rounded-2xl::before,
        .bg-white.rounded-3xl::before,
        .white-card::before,
        .kpi-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(
                380px circle at var(--mouse-x, -500px) var(--mouse-y, -500px),
                rgba(79, 70, 229, 0.085),
                rgba(56, 189, 248, 0.04) 40%,
                transparent 70%
            );
            opacity: 0;
            transition: opacity 0.25s ease;
            pointer-events: none;
            z-index: 0;
        }
        .bg-white.rounded-2xl:hover::before,
        .bg-white.rounded-3xl:hover::before,
        .white-card:hover::before,
        .kpi-card:hover::after {
            opacity: 1;
        }
        /* Top animated gradient beam on card hover */
        .bg-white.rounded-2xl::after,
        .white-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4F46E5, #38BDF8, #10B981, #4F46E5);
            background-size: 200% 100%;
            opacity: 0;
            transition: opacity 0.22s ease;
            animation: borderBeamRotate 4s linear infinite;
            pointer-events: none;
        }
        .bg-white.rounded-2xl:hover::after,
        .white-card:hover::after {
            opacity: 1;
        }
        .bg-white.rounded-2xl:hover,
        .bg-white.rounded-3xl:hover,
        .white-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px -8px rgba(15, 23, 42, 0.12), 0 6px 16px -3px rgba(79, 70, 229, 0.12);
            border-color: rgba(79, 70, 229, 0.42);
        }

        /* 5. Button Shine Sweep, Ripple & Active Press Physics */
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.30), transparent);
            transform: skewX(-20deg);
            transition: left 0.55s var(--ease-spring);
            pointer-events: none;
        }
        .btn-scarlet:hover::after {
            left: 140%;
        }
        .btn-scarlet:active,
        .btn-amber:active {
            transform: translateY(0) scale(0.96) !important;
        }

        /* 6. Table Row Micro-Slide & Left Electric Indigo Bar */
        tbody tr {
            position: relative;
            transition: background-color 0.18s ease, transform 0.2s var(--ease-spring), box-shadow 0.2s ease;
        }
        tbody tr:hover {
            background-color: rgba(79, 70, 229, 0.045) !important;
            box-shadow: inset 3px 0 0 #4F46E5;
        }

        /* 7. Live Equalizer Sparkline Bars & Floating Tooltip */
        .sparkline-bar {
            position: relative;
            transform-origin: bottom;
            animation: barGrowUp 0.75s var(--ease-spring) both, barEqualizer 3.2s ease-in-out infinite;
            transition: transform 0.2s var(--ease-spring), background-color 0.2s ease, filter 0.2s ease;
        }
        .sparkline-bar:nth-child(1) { animation-delay: 0.10s, 0.8s; }
        .sparkline-bar:nth-child(2) { animation-delay: 0.16s, 1.2s; }
        .sparkline-bar:nth-child(3) { animation-delay: 0.22s, 1.6s; }
        .sparkline-bar:nth-child(4) { animation-delay: 0.28s, 2.0s; }
        .sparkline-bar:nth-child(5) { animation-delay: 0.34s, 2.4s; }
        .sparkline-bar:nth-child(6) { animation-delay: 0.40s, 2.8s; }
        .sparkline-bar:hover {
            animation-play-state: paused;
            transform: scaleY(1.16) !important;
            background-color: #38BDF8 !important;
            filter: drop-shadow(0 0 8px rgba(56, 189, 248, 0.75));
        }

        .progress-ring-animated {
            animation: ringSweep 1.1s var(--ease-spring) both;
            animation-delay: 0.25s;
        }

        .badge-pulse-glow {
            animation: pulseGlow 2.4s infinite;
        }

        /* Live Telemetry Ticker Pill */
        .telemetry-pill {
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(56, 189, 248, 0.30);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.25);
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

        /* ===================================================================== */
        /* 9. OMNI-SCREEN RESPONSIVE ARCHITECTURE (320px Mobile -> 4K/8K SmartTV)*/
        /* ===================================================================== */
        html, body {
            max-width: 100vw;
            overflow-x: clip;
            -webkit-text-size-adjust: 100%;
        }

        /* Smooth horizontal scroll for wide data tables on Mobile & Tablet */
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.4) transparent;
        }
        .overflow-x-auto::-webkit-scrollbar {
            height: 6px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, 0.4);
            border-radius: 9999px;
        }

        /* Small Mobile Viewports (320px - 480px) */
        @media (max-width: 480px) {
            .page-header-sb {
                padding-top: 1.5rem !important;
                padding-bottom: 4.25rem !important;
            }
            .page-header-sb h1 {
                font-size: 1.35rem !important;
                line-height: 1.25 !important;
            }
        }

        /* Ultra-Wide Monitors & 4K Smart TVs (2560px+) */
        @media (min-width: 2200px) {
            html {
                font-size: 18px;
            }
            .max-w-7xl {
                max-width: 92rem !important;
            }
        }

        [x-cloak] { display: none !important; }

        /* 10. WCAG 2.2 AA Accessible Keyboard Focus Rings & Tactile Feedback */
        :focus-visible {
            outline: 2px solid #4F46E5 !important;
            outline-offset: 2px !important;
        }
        .skip-to-main {
            position: fixed;
            top: -100px;
            left: 1rem;
            z-index: 200;
            background: #4F46E5;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 0.75rem;
            padding: 0.65rem 1.1rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
            transition: top 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .skip-to-main:focus {
            top: 0.75rem;
        }
    </style>

    <!-- Anti-flicker script: Determina estado de sidebar colapsado antes del primer renderizado visual -->
    <script>
        (function() {
            try {
                if (localStorage.getItem('dropana_sidebar_collapsed') === 'true') {
                    document.documentElement.classList.add('sidebar-is-collapsed');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-[#4F46E5] selection:text-white bg-[#F8FAFC]"
      x-data="{ 
          sidenavOpen: true, 
          sidebarCollapsed: localStorage.getItem('dropana_sidebar_collapsed') === 'true', 
          mobileMenuOpen: false, 
          logoutModalOpen: false, 
          cmdPaletteOpen: false, 
          cmdQuery: '',
          toggleSidebar() {
              this.sidebarCollapsed = !this.sidebarCollapsed;
              try {
                  localStorage.setItem('dropana_sidebar_collapsed', this.sidebarCollapsed);
                  if (this.sidebarCollapsed) {
                      document.documentElement.classList.add('sidebar-is-collapsed');
                  } else {
                      document.documentElement.classList.remove('sidebar-is-collapsed');
                  }
              } catch (e) {}
          }
      }"
      @keydown.escape.window="logoutModalOpen = false; cmdPaletteOpen = false"
      @keydown.ctrl.k.window.prevent="cmdPaletteOpen = !cmdPaletteOpen; if(cmdPaletteOpen) $nextTick(() => $refs.cmdInput?.focus())"
      @keydown.meta.k.window.prevent="cmdPaletteOpen = !cmdPaletteOpen; if(cmdPaletteOpen) $nextTick(() => $refs.cmdInput?.focus())">

    <!-- WCAG 2.2 AA Skip Navigation Link -->
    <a href="#main-content" class="skip-to-main">Saltar al contenido principal</a>

    <!-- ===================================================================== -->
    <!-- STRIPE & LINEAR EXECUTIVE TOPNAV (Fixed Full-Width Frosted Navbar)    -->
    <!-- ===================================================================== -->
    <nav class="sticky top-0 z-50 h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-3 sm:px-6 flex items-center justify-between gap-2 sm:gap-4 shadow-[0_2px_15px_-3px_rgba(15,23,42,0.06)]">
        
        <!-- Left: Sidenav Toggle + Brand + Search -->
        <div class="flex items-center gap-2 sm:gap-5 min-w-0">
            @if(Auth::check() && !request()->is('/'))
            <button @click="
                if (window.innerWidth < 768) {
                    mobileMenuOpen = !mobileMenuOpen;
                } else {
                    sidenavOpen = !sidenavOpen;
                }
            "
                class="w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500 hover:text-[#0F172A] flex items-center justify-center transition focus:outline-none shrink-0"
                title="Alternar Menú Lateral">
                <i class="fa-solid text-sm" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
            </button>
            @endif

            <!-- Brand Logo with Nano Banana -->
            <a href="{{ url('/') }}" class="flex items-center gap-2 sm:gap-2.5 group shrink-0">
                <div class="relative w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-indigo-50 p-0.5 flex items-center justify-center border border-indigo-200/80 shadow-xs">
                    <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-md">
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-[#10B981] rounded-full border-2 border-white"></span>
                </div>
                <div class="leading-none">
                    <span class="text-sm sm:text-base font-extrabold tracking-tight text-[#0F172A] group-hover:text-[#4F46E5] transition">Bolidrop</span>
                    <span class="text-[8px] sm:text-[9px] font-bold text-[#4F46E5] uppercase tracking-widest block">Bolivia PRO</span>
                </div>
            </a>

            @if(Auth::check() && !request()->is('/'))
            <!-- Global Search Input & Command Palette Trigger (Ctrl+K) -->
            <div class="hidden lg:flex items-center gap-2 ml-2">
                <form action="{{ url('/tracking') }}" method="GET" class="flex items-center relative">
                    <input type="text" name="guide" placeholder="Buscar guía, pedido o SKU..."
                        aria-label="Buscar guía, pedido o SKU"
                        class="bg-[#F1F5F9] border border-slate-200/70 text-[#0F172A] placeholder-[#64748B] rounded-lg pl-3.5 pr-9 py-2 text-xs font-medium focus:outline-none focus:bg-white focus:border-[#4F46E5] focus:ring-2 focus:ring-[#4F46E5]/15 transition w-52 xl:w-60">
                    <button type="submit" aria-label="Ejecutar búsqueda" class="absolute right-3 text-[#64748B] hover:text-[#4F46E5] transition cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </button>
                </form>
                <button type="button" @click="cmdPaletteOpen = true; $nextTick(() => $refs.cmdInput?.focus())"
                    title="Abrir Paleta de Comandos Rápida (Ctrl + K)"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 text-[#64748B] hover:text-[#4F46E5] border border-slate-200/80 text-[11px] font-bold flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fa-solid fa-terminal text-[10px]"></i>
                    <kbd class="font-mono text-[10px] bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[#0F172A]">Ctrl K</kbd>
                </button>
            </div>
            @endif
        </div>

        <!-- Right: Cotizaciones del Boliviano (BOB), Divisas & Auth Buttons -->
        <div class="flex items-center gap-1.5 sm:gap-3 shrink-0" x-data="{ ratesOpen: false }">
            @php
                $sellRate = (float) ($bcbOfficialRate ?? 11.75);
                $buyRate  = (float) ($bcbBuyRate ?? round($sellRate - 0.10, 2));
                $rateSource = $bcbRateSource ?? 'Banco Central de Bolivia (www.bcb.gob.bo)';
                $isManualRate = str_starts_with((string) $rateSource, 'Configuración Manual');
                $usdtRate = round($sellRate + 0.15, 2);
                $eurRate  = round($sellRate * 1.08, 2);
                $cnyRate  = round($sellRate * 0.14, 2);
                $brlRate  = round($sellRate * 0.20, 2);
            @endphp

            <!-- Ticker de Tipo de Cambio en el Navbar con Desplegable de Monedas -->
            <div class="relative">
                <button @click="ratesOpen = !ratesOpen" @click.away="ratesOpen = false"
                    class="flex items-center gap-1.5 sm:gap-2.5 px-2 sm:px-3 py-1.5 rounded-lg bg-[#F1F5F9] hover:bg-indigo-50/70 border border-slate-200/80 text-[#0F172A] text-[11px] sm:text-xs transition cursor-pointer"
                    title="Ver todas las cotizaciones del Boliviano (BOB)">
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#10B981] animate-pulse shrink-0"></span>
                    <div class="flex items-center gap-1">
                        <span class="hidden sm:inline text-[#64748B] font-semibold">USD/BOB:</span>
                        <span class="sm:hidden text-[#64748B] font-semibold">BCB:</span>
                        <span class="hidden md:inline text-[11px] text-[#64748B]">C: <strong class="font-mono text-[#0F172A]">{{ number_format($buyRate, 2) }}</strong></span>
                        <span class="text-[11px] text-[#64748B]"><span class="hidden sm:inline">V: </span><strong class="font-mono text-[#4F46E5] font-bold">{{ number_format($sellRate, 2) }} Bs</strong></span>
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
                    <i class="fa-solid fa-chevron-down text-[9px] sm:text-[10px] text-[#64748B] ml-0.5"></i>
                </button>

                <!-- Dropdown Detallado de Monedas frente al Boliviano (BOB) -->
                <div x-show="ratesOpen" x-cloak
                    x-transition
                    class="fixed sm:absolute left-3 right-3 sm:left-auto sm:right-0 mt-2 sm:w-84 bg-white rounded-xl border border-slate-200 shadow-2xl p-4 z-50 text-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div>
                            <div class="font-extrabold text-[#0F172A] text-xs">Tipo de Cambio — BCB Bolivia (BOB)</div>
                            <div class="text-[10px] text-[#64748B] truncate max-w-[200px]" title="{{ $rateSource }}">
                                {{ $isManualRate ? 'Tasa Manual Configurada por Admin' : 'Sincronizado con www.bcb.gob.bo' }}
                            </div>
                        </div>
                        @if($isManualRate)
                            <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-bold text-[10px]">Manual</span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-emerald-50 text-[#10B981] font-bold text-[10px]">BCB En Vivo</span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-indigo-50/70 border border-indigo-200/70">
                            <div>
                                <div class="font-bold text-[#0F172A]">🇧🇴 Dólar BCB / Referencial</div>
                                <div class="text-[10px] text-[#64748B]">{{ $isManualRate ? 'Ajuste Manual Activo' : 'Banco Central de Bolivia (TCO)' }}</div>
                            </div>
                            <div class="text-right font-mono">
                                <div class="text-[10px] text-[#64748B]">Compra: <strong>{{ number_format($buyRate, 2) }} Bs</strong></div>
                                <div class="text-xs font-extrabold text-[#4F46E5]">Venta: {{ number_format($sellRate, 2) }} Bs</div>
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

                        @if(Auth::check() && in_array(Auth::user()->role, ['tenant_admin', 'admin', 'superadmin']))
                        <!-- Formulario de Configuración Manual del Tipo de Cambio (Exclusivo Admin) -->
                        <div class="pt-2.5 mt-2 border-t border-slate-200/80 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#0F172A]">
                                    <i class="fa-solid fa-sliders text-[#4F46E5] mr-1"></i> Configurar Tasa Manual
                                </span>
                                <form method="POST" action="{{ route('admin.exchange-rate.update') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="mode" value="sync_bcb">
                                    <button type="submit" class="text-[10px] font-bold text-[#4F46E5] hover:underline cursor-pointer" title="Volver a sincronizar con www.bcb.gob.bo">
                                        <i class="fa-solid fa-rotate mr-0.5"></i> Auto BCB
                                    </button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('admin.exchange-rate.update') }}" class="space-y-2">
                                @csrf
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Compra (Bs)</label>
                                        <input type="number" step="0.01" min="1" max="100" name="buy_rate" value="{{ number_format($buyRate, 2, '.', '') }}" required
                                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-[#0F172A] font-mono text-xs focus:bg-white focus:outline-none focus:border-[#4F46E5]">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Venta (Bs)</label>
                                        <input type="number" step="0.01" min="1" max="100" name="sell_rate" value="{{ number_format($sellRate, 2, '.', '') }}" required
                                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-[#0F172A] font-mono text-xs font-bold focus:bg-white focus:outline-none focus:border-[#4F46E5]">
                                    </div>
                                </div>
                                <button type="submit" class="w-full py-1.5 px-3 rounded-lg bg-[#4F46E5] hover:bg-[#312E81] text-white font-bold text-[11px] transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                                    <i class="fa-solid fa-floppy-disk text-[10px]"></i>
                                    <span>Guardar Tipo de Cambio</span>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if(Auth::check())
                @if(!request()->is('/'))
                <div class="hidden lg:flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-[#10B981] border border-emerald-200/70 text-xs font-bold">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>DEX 9/9</span>
                </div>
                @endif

                <div class="h-6 w-px bg-slate-200 mx-1 hidden sm:block"></div>
                <div class="flex items-center gap-2.5">
                    @php
                        $dashboardUrl = match(Auth::user()->role) {
                            'tenant_admin' => url('/panel/admin'),
                            'dropshipper' => url('/panel/dropshipper'),
                            'provider' => url('/panel/proveedor'),
                            'carrier', 'carrier_agent' => url('/panel/carrier'),
                            'fulfillment_client' => url('/panel/fulfillment'),
                            default => url('/panel/admin'),
                        };
                    @endphp
                    @if(request()->is('/'))
                        <a href="{{ $dashboardUrl }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-[#4F46E5] hover:bg-indigo-100 border border-indigo-200/80 text-xs font-bold transition">
                            <i class="fa-solid fa-gauge-high text-[11px]"></i>
                            <span>Mi Panel</span>
                        </a>
                    @endif
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#0F172A] via-[#312E81] to-[#4F46E5] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-[#0F172A] leading-tight">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] text-[#4F46E5] font-bold uppercase tracking-wider">{{ str_replace('_', ' ', Auth::user()->role) }}</div>
                    </div>
                    <!-- Botón Salida que activa la Tarjeta Tailwind de Advertencia antes de salir -->
                    <button type="button" @click="logoutModalOpen = true" title="Salir del Sistema"
                        class="ml-1 px-2.5 py-1.5 rounded-lg bg-rose-50/80 hover:bg-rose-100 text-rose-600 border border-rose-200/80 flex items-center gap-1.5 text-xs font-bold transition cursor-pointer shadow-2xs">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        <span class="hidden md:inline">Salir</span>
                    </button>
                </div>
            @else
                <div class="flex items-center gap-1.5 sm:gap-2 text-xs">
                    <a href="{{ route('login') }}" class="font-bold text-[#0F172A] hover:text-[#4F46E5] px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-lg border border-slate-200 hover:border-[#4F46E5] hover:bg-indigo-50/40 transition whitespace-nowrap">
                        Ingresar
                    </a>
                    <a href="{{ route('register') }}" class="btn-scarlet px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-lg font-bold shadow-xs whitespace-nowrap">
                        Registrarse <span class="hidden sm:inline">→</span>
                    </a>
                </div>
            @endif
        </div>
    </nav>

    <!-- ===================================================================== -->
    <!-- SB ADMIN PRO LAYOUT SIDENAV + CONTENT WRAPPER                         -->
    <!-- ===================================================================== -->
    <div id="layoutSidenav" class="flex-grow flex flex-col md:flex-row min-h-[calc(100vh-4rem)] relative">

        @if(Auth::check() && !request()->is('/'))
        <!-- Mobile Backdrop Overlay (< 768px) -->
        <div x-show="mobileMenuOpen" x-cloak
             @click="mobileMenuOpen = false"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 top-16 bg-[#0F172A]/60 backdrop-blur-xs z-30 md:hidden"></div>

        <!-- SIDENAV (Fixed Left Sidebar locked to viewport on Mobile, Tablet, Desktop & 4K TV) -->
        <aside :class="[
                mobileMenuOpen ? 'translate-x-0 flex' : '-translate-x-full hidden md:translate-x-0',
                sidenavOpen ? 'md:flex' : 'md:hidden',
                sidebarCollapsed ? 'md:w-20 sidebar-mini' : 'md:w-64'
            ]"
            class="sidenav-light fixed top-16 left-0 bottom-0 w-72 max-w-[82vw] h-[calc(100vh-4rem)] hidden md:flex flex-col justify-between shrink-0 z-40 overflow-y-auto overflow-x-hidden transition-all duration-300 ease-in-out group/sidebar">
            
            <div class="py-1">
                <!-- Sidenav Header Bar: Título & Botón Colapsar/Expandir en la parte superior derecha -->
                <div class="px-3 pt-3 pb-2.5 mb-1 border-b border-white/10 flex items-center justify-between gap-2 shrink-0">
                    <div x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="flex items-center gap-2 overflow-hidden pl-1">
                        <span class="w-1.5 h-4 bg-[#38BDF8] rounded-full"></span>
                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-300">Menú</span>
                    </div>
                    <button @click="toggleSidebar()"
                            type="button"
                            class="w-8 h-8 rounded-lg bg-white/10 hover:bg-[#4F46E5] text-slate-300 hover:text-white flex items-center justify-center transition-all duration-200 cursor-pointer shadow-xs group/btn shrink-0"
                            :class="sidebarCollapsed ? 'mx-auto' : 'ml-auto'"
                            :title="sidebarCollapsed ? 'Mostrar menú completo' : 'Ocultar textos y mostrar solo iconos'"
                            aria-label="Alternar ancho del menú">
                        <i class="fa-solid text-xs transition-transform duration-300"
                           :class="sidebarCollapsed ? 'fa-angles-right text-[#38BDF8] group-hover/btn:scale-125' : 'fa-angles-left group-hover/btn:-translate-x-0.5'"></i>
                    </button>
                </div>

                <!-- Buscador Rápido Visible en Móvil y Tablet (< 1024px) -->
                <div class="sidebar-hide-mini px-4 pt-2 pb-3 lg:hidden">
                    <form action="{{ url('/tracking') }}" method="GET" class="relative">
                        <input type="text" name="guide" placeholder="Buscar guía o pedido..."
                            class="w-full bg-white/10 border border-white/15 text-white placeholder-slate-400 rounded-lg pl-3 pr-8 py-2 text-xs focus:outline-none focus:border-[#38BDF8]">
                        <button type="submit" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-300 hover:text-[#38BDF8]">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </button>
                    </form>
                </div>

                <!-- Sidenav Menu -->
                <nav class="space-y-0.5">

                    {{-- ================================================ --}}
                    {{-- ADMINISTRADOR (tenant_admin)                     --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'tenant_admin')
                        <div class="sidebar-heading px-6 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Core / Dashboards
                        </div>
                        <a href="{{ url('/panel/admin') }}" title="Dashboard Central" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/admin') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-gauge-high w-4 text-center {{ request()->is('panel/admin') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Dashboard Central</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" title="Megabodega Nacional" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('catalogo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-4 text-center {{ request()->is('catalogo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Megabodega Nacional</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" title="Crear Pedido COD" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('checkout-demo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-4 text-center {{ request()->is('checkout-demo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>

                        <div class="sidebar-heading px-6 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Finanzas & Conciliación
                        </div>
                        <a href="{{ url('/reporte') }}" title="Reporte Comercial" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('reporte') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-file-invoice-dollar w-4 text-center {{ request()->is('reporte') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Reporte Comercial</span>
                        </a>
                        <a href="{{ url('/settlements') }}" title="Liquidaciones Bancarias" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('settlements*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-building-columns w-4 text-center {{ request()->is('settlements*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Liquidaciones Bancarias</span>
                        </a>
                        <a href="{{ url('/reports') }}" title="Reportes & Analytics" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('reports*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-chart-line w-4 text-center {{ request()->is('reports*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Reportes & Analytics</span>
                        </a>
                        <a href="{{ url('/reportes') }}" title="Métricas Proveedor & Valorado" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('reportes*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-chart-pie w-4 text-center {{ request()->is('reportes*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Métricas Proveedor & Valorado</span>
                        </a>

                        <div class="sidebar-heading px-6 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Logística & Operaciones
                        </div>
                        <a href="{{ route('admin.kardex.index') }}" title="Kardex Global & Stock" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/admin/kardex*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-clipboard-list w-4 text-center {{ request()->is('panel/admin/kardex*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Kardex Global & Stock</span>
                        </a>
                        <a href="{{ url('/returns') }}" title="Devoluciones & Log. Inv." class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('returns*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-4 text-center {{ request()->is('returns*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Devoluciones & Log. Inv.</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" title="Cobro QR Repartidor" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('carrier/cobro*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-qrcode w-4 text-center {{ request()->is('carrier/cobro*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Cobro QR Repartidor</span>
                        </a>
                        <a href="{{ url('/panel/fulfillment') }}" title="Fulfillment & Tiendas" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/fulfillment') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-boxes-packing w-4 text-center {{ request()->is('panel/fulfillment') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Fulfillment & Tiendas</span>
                        </a>
                        <a href="{{ url('/notifications/whatsapp') }}" title="WhatsApp Bot (+591)" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('notifications/whatsapp*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-brands fa-whatsapp w-4 text-center {{ request()->is('notifications/whatsapp*') ? 'text-[#0061f2]' : 'text-[#00ac69]' }}"></i>
                            <span>WhatsApp Bot (+591)</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- DROPSHIPPER                                      --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'dropshipper')
                        <div class="sidebar-heading px-5 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Mi Negocio Dropshipping
                        </div>
                        <a href="{{ url('/panel/dropshipper') }}" title="Mi Dashboard" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/dropshipper') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-gauge-high w-4 text-center {{ request()->is('panel/dropshipper') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" title="Megabodega" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('catalogo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-4 text-center {{ request()->is('catalogo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Megabodega</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" title="Crear Pedido COD" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('checkout-demo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-4 text-center {{ request()->is('checkout-demo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>
                        <a href="{{ url('/panel/dropshipper') }}#billetera" title="Mi Billetera & Ganancias" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium">
                            <i class="fa-solid fa-wallet w-4 text-center text-[#a7aeb8]"></i>
                            <span>Mi Billetera & Ganancias</span>
                        </a>
                        <a href="{{ url('/reporte') }}" title="Reporte Comercial" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('reporte') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-file-invoice-dollar w-4 text-center {{ request()->is('reporte') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Reporte Comercial</span>
                        </a>
                        <a href="{{ url('/returns') }}" title="Mis Devoluciones" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('returns*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-4 text-center {{ request()->is('returns*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mis Devoluciones</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- PROVEEDOR                                        --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'provider')
                        <div class="sidebar-heading px-5 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Panel de Proveedor
                        </div>
                        <a href="{{ url('/panel/proveedor') }}" title="Mi Dashboard" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/proveedor') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-gauge-high w-4 text-center {{ request()->is('panel/proveedor') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" title="Catálogo Publicado" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('catalogo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-4 text-center {{ request()->is('catalogo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Catálogo Publicado</span>
                        </a>
                        <a href="{{ url('/reporte') }}" title="Reporte Comercial" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('reporte') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-file-invoice-dollar w-4 text-center {{ request()->is('reporte') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Reporte Comercial</span>
                        </a>
                        <a href="{{ url('/reportes') }}" title="Reportes & Métricas" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('reportes*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-chart-pie w-4 text-center {{ request()->is('reportes*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Reportes & Métricas</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#inventario" title="Mi Inventario Físico" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium">
                            <i class="fa-solid fa-warehouse w-4 text-center text-[#a7aeb8]"></i>
                            <span>Mi Inventario Físico</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#alertas" title="Alertas de Stock" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium">
                            <i class="fa-solid fa-triangle-exclamation w-4 text-center text-[#a7aeb8]"></i>
                            <span>Alertas de Stock</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- CARRIER                                          --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'carrier')
                        <div class="sidebar-heading px-5 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Repartidor / Courier
                        </div>
                        <a href="{{ url('/panel/carrier') }}" title="Mi Hoja de Ruta" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/carrier') && !request()->is('panel/carrier/reportes*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-truck-fast w-4 text-center {{ request()->is('panel/carrier') && !request()->is('panel/carrier/reportes*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mi Hoja de Ruta</span>
                        </a>
                        <a href="{{ url('/panel/carrier/reportes') }}" title="Reportes & Métricas COD" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/carrier/reportes*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-chart-pie w-4 text-center {{ request()->is('panel/carrier/reportes*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Reportes &amp; Métricas COD</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" title="Cobro QR / Efectivo" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('carrier/cobro*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-qrcode w-4 text-center {{ request()->is('carrier/cobro*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Cobro QR / Efectivo</span>
                        </a>
                        <a href="{{ url('/returns') }}" title="Reportar Novedad" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('returns*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-4 text-center {{ request()->is('returns*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Reportar Novedad</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- FULFILLMENT CLIENT                               --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'fulfillment_client')
                        <div class="sidebar-heading px-5 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Fulfillment e-Commerce
                        </div>
                        <a href="{{ url('/panel/fulfillment') }}" title="Mi Tienda Online" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('panel/fulfillment') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-store w-4 text-center {{ request()->is('panel/fulfillment') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Mi Tienda Online</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" title="Enviar Pedidos" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('checkout-demo') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-4 text-center {{ request()->is('checkout-demo') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Enviar Pedidos</span>
                        </a>
                        <a href="{{ url('/returns') }}" title="Devoluciones" class="nav-item-transition flex items-center gap-3 px-5 py-2.5 text-[13px] {{ request()->is('returns*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-4 text-center {{ request()->is('returns*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Devoluciones</span>
                        </a>
                    @endif

                    {{-- ======================================================== --}}
                    {{-- ADDONS / HERRAMIENTAS GLOBALES                           --}}
                    {{-- ======================================================== --}}
                    <div class="pt-3 mt-3 border-t border-white/10">
                        <div class="sidebar-heading px-6 pt-2 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Addons & Soporte
                        </div>
                        <a href="{{ url('/tracking') }}" title="Rastrear Guía" class="nav-item-transition flex items-center gap-3 px-4 py-2 text-[13px] {{ request()->is('tracking*') ? 'text-[#4F46E5] bg-blue-50/70 font-bold border-r-4 border-[#38BDF8]' : 'text-[#0F172A] hover:text-[#4F46E5] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-magnifying-glass-location w-4 text-center {{ request()->is('tracking*') ? 'text-[#38BDF8]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Rastrear Guía</span>
                        </a>
                        <a href="{{ url('/contactenos') }}" title="Sedes & Almacenes" class="nav-item-transition flex items-center gap-3 px-4 py-2 text-[13px] {{ request()->is('contactenos*') ? 'text-[#4F46E5] bg-blue-50/70 font-bold border-r-4 border-[#38BDF8]' : 'text-[#0F172A] hover:text-[#4F46E5] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-map-location-dot w-4 text-center {{ request()->is('contactenos*') ? 'text-[#38BDF8]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Sedes & Almacenes</span>
                        </a>
                        <a href="{{ url('/soporte') }}" title="Centro de Soporte" class="nav-item-transition flex items-center gap-3 px-4 py-2 text-[13px] {{ request()->is('soporte*') ? 'text-[#4F46E5] bg-blue-50/70 font-bold border-r-4 border-[#38BDF8]' : 'text-[#0F172A] hover:text-[#4F46E5] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-headset w-4 text-center {{ request()->is('soporte*') ? 'text-[#38BDF8]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Centro de Soporte</span>
                        </a>
                    </div>
                </nav>
            </div>

            <!-- Stripe & Linear Executive Sidenav Footer (#0F172A Midnight Slate Glass) -->
            <div class="sidebar-footer-wrapper bg-[#090D16]/75 border-t border-white/10 px-6 py-4 backdrop-blur-sm transition-all duration-200">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#38BDF8] animate-pulse shrink-0"></span>
                        <span class="sidebar-footer-text text-[10px] font-extrabold text-[#38BDF8] uppercase tracking-wider truncate">Nano Banana Sync</span>
                    </div>
                    <button type="button" @click="logoutModalOpen = true" title="Salir del Sistema"
                        class="text-[11px] font-bold text-rose-400 hover:text-rose-300 flex items-center gap-1 transition cursor-pointer shrink-0">
                        <i class="fa-solid fa-power-off text-[10px]"></i>
                        <span class="sidebar-footer-text">Salir</span>
                    </button>
                </div>
                <div class="sidebar-footer-text text-[11px] text-slate-400 font-medium">Conectado como:</div>
                <div class="sidebar-footer-text text-xs font-bold text-white truncate">{{ Auth::user()->name }}</div>
            </div>

        </aside>
        @endif

        <!-- ===================================================================== -->
        <!-- MAIN CONTENT AREA (#F8FAFC Alabaster Canvas)                          -->
        <!-- ===================================================================== -->
        <div id="layoutSidenav_content"
             @if(Auth::check() && !request()->is('/'))
             :class="{
                 'md:ml-64': sidenavOpen && !sidebarCollapsed,
                 'md:ml-20': sidenavOpen && sidebarCollapsed,
                 'md:!ml-0': !sidenavOpen
             }"
             class="md:ml-64 flex-grow flex flex-col min-w-0 bg-[#F8FAFC] transition-[margin] duration-300 ease-in-out"
             @else
             class="flex-grow flex flex-col min-w-0 bg-[#F8FAFC]"
             @endif>
            
            <main id="main-content" class="px-4 py-3 sm:px-8 sm:py-5 pb-20 md:pb-6 flex-grow">
                {{-- Tarjetas de Notificaciones y Alertas Flash del Servidor (Tailwind CSS) --}}
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-cloak
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200/90 text-emerald-900 text-xs font-semibold flex items-start justify-between shadow-xs ring-1 ring-emerald-500/10">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 border border-emerald-200 text-emerald-700 flex items-center justify-center shrink-0 text-sm">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="pt-0.5 leading-relaxed">
                                <span class="font-bold text-emerald-950 block text-xs">Operación Exitosa</span>
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                        <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-900 w-7 h-7 rounded-lg hover:bg-emerald-100 flex items-center justify-center transition cursor-pointer shrink-0">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                @if (session('status'))
                    <div x-data="{ show: true }" x-show="show" x-cloak
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="mb-4 p-4 rounded-2xl bg-indigo-50 border border-indigo-200/90 text-indigo-950 text-xs font-semibold flex items-start justify-between shadow-xs ring-1 ring-[#4F46E5]/10">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 border border-indigo-200 text-[#4F46E5] flex items-center justify-center shrink-0 text-sm">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <div class="pt-0.5 leading-relaxed">
                                <span class="font-bold text-indigo-950 block text-xs">Información del Sistema</span>
                                <span>{{ session('status') }}</span>
                            </div>
                        </div>
                        <button type="button" @click="show = false" class="text-indigo-600 hover:text-indigo-900 w-7 h-7 rounded-lg hover:bg-indigo-100 flex items-center justify-center transition cursor-pointer shrink-0">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                @if (session('warning'))
                    <div x-data="{ show: true }" x-show="show" x-cloak
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="mb-4 p-4 rounded-2xl bg-amber-50 border border-amber-200/90 text-amber-950 text-xs font-semibold flex items-start justify-between shadow-xs ring-1 ring-amber-500/10">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-amber-100 border border-amber-200 text-amber-700 flex items-center justify-center shrink-0 text-sm">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div class="pt-0.5 leading-relaxed">
                                <span class="font-bold text-amber-950 block text-xs">Advertencia</span>
                                <span>{{ session('warning') }}</span>
                            </div>
                        </div>
                        <button type="button" @click="show = false" class="text-amber-600 hover:text-amber-900 w-7 h-7 rounded-lg hover:bg-amber-100 flex items-center justify-center transition cursor-pointer shrink-0">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div x-data="{ show: true }" x-show="show" x-cloak
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200/90 text-rose-950 text-xs font-semibold flex items-start justify-between shadow-xs ring-1 ring-rose-500/10">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0 text-sm">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </div>
                            <div class="pt-0.5 leading-relaxed">
                                <span class="font-bold text-rose-950 block text-xs">Error en la Solicitud</span>
                                <span>{{ session('error') }}</span>
                            </div>
                        </div>
                        <button type="button" @click="show = false" class="text-rose-600 hover:text-rose-900 w-7 h-7 rounded-lg hover:bg-rose-100 flex items-center justify-center transition cursor-pointer shrink-0">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div x-data="{ show: true }" x-show="show" x-cloak
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200/90 text-rose-950 text-xs font-semibold flex items-start justify-between shadow-xs ring-1 ring-rose-500/10">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0 text-sm">
                                <i class="fa-solid fa-circle-exclamation"></i>
                            </div>
                            <div class="pt-0.5 leading-relaxed">
                                <span class="font-bold text-rose-950 block text-xs">Revisa las siguientes observaciones:</span>
                                <ul class="list-disc list-inside mt-1 space-y-0.5 text-rose-800">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" @click="show = false" class="text-rose-600 hover:text-rose-900 w-7 h-7 rounded-lg hover:bg-rose-100 flex items-center justify-center transition cursor-pointer shrink-0">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif
                @yield('content')
            </main>

            <!-- STRIPE & LINEAR EXECUTIVE FOOTER -->
            <footer class="bg-white border-t border-slate-200/80 px-6 sm:px-10 py-6 pb-20 md:pb-6 text-xs text-[#64748B]">
                <div class="w-full flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                        <span>Copyright &copy; <strong class="text-[#0F172A]">Bolidrop Bolivia</strong> {{ date('Y') }}</span>
                        <span>•</span>
                        <span class="text-[11px]">DEX Bolivia Express & QR Simple (9/9 Deptos)</span>
                        <span>•</span>
                        <span class="text-[11px] font-semibold text-[#4F46E5]">Creado por <strong class="text-[#0F172A]">SmartCoding S.A</strong> {{ date('Y') }}</span>
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

    @if(Auth::check())
    <!-- ===================================================================== -->
    <!-- BARRA INFERIOR TÁCTIL MÓVIL (Mobile Bottom Navigation Dock < 768px)   -->
    <!-- ===================================================================== -->
    @if(!request()->is('/'))
    <nav aria-label="Navegación rápida móvil"
         class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_20px_rgba(15,23,42,0.06)] px-2 py-1.5 flex items-center justify-around">
        <a href="{{ $dashboardUrl ?? url('/panel/admin') }}"
           class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] px-2 py-1 rounded-xl transition {{ request()->is('panel/*') && !request()->is('panel/admin/kardex*') ? 'text-[#4F46E5] font-extrabold bg-indigo-50/70' : 'text-[#64748B] hover:text-[#0F172A] font-medium' }}">
            <i class="fa-solid fa-gauge-high text-sm mb-0.5"></i>
            <span class="text-[10px] leading-none">Panel</span>
        </a>
        <a href="{{ url('/catalogo') }}"
           class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] px-2 py-1 rounded-xl transition {{ request()->is('catalogo*') ? 'text-[#4F46E5] font-extrabold bg-indigo-50/70' : 'text-[#64748B] hover:text-[#0F172A] font-medium' }}">
            <i class="fa-solid fa-boxes-stacked text-sm mb-0.5"></i>
            <span class="text-[10px] leading-none">Catálogo</span>
        </a>
        <a href="{{ url('/checkout-demo') }}"
           class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] px-3 py-1 rounded-xl bg-gradient-to-r from-[#4F46E5] to-[#312E81] text-white font-extrabold shadow-sm">
            <i class="fa-solid fa-plus text-sm mb-0.5"></i>
            <span class="text-[10px] leading-none">Pedido COD</span>
        </a>
        <a href="{{ url('/tracking') }}"
           class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] px-2 py-1 rounded-xl transition {{ request()->is('tracking*') ? 'text-[#4F46E5] font-extrabold bg-indigo-50/70' : 'text-[#64748B] hover:text-[#0F172A] font-medium' }}">
            <i class="fa-solid fa-magnifying-glass-location text-sm mb-0.5"></i>
            <span class="text-[10px] leading-none">Rastreo</span>
        </a>
        <button type="button" @click="cmdPaletteOpen = true; $nextTick(() => $refs.cmdInput?.focus())"
           class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] px-2 py-1 rounded-xl text-[#64748B] hover:text-[#4F46E5] font-medium transition cursor-pointer">
            <i class="fa-solid fa-compass text-sm mb-0.5"></i>
            <span class="text-[10px] leading-none">Módulos</span>
        </button>
    </nav>
    @endif

    <!-- ===================================================================== -->
    <!-- PALETA DE COMANDOS EJECUTIVA (Command Palette Ctrl+K — Stripe/Linear) -->
    <!-- ===================================================================== -->
    <div x-show="cmdPaletteOpen" x-cloak
         class="fixed inset-0 z-[110] flex items-start justify-center pt-14 sm:pt-24 p-4 bg-[#0F172A]/60 backdrop-blur-xs"
         role="dialog" aria-modal="true" aria-label="Paleta de navegación rápida"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-120"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="cmdPaletteOpen = false"
             class="w-full max-w-lg bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden">
            <div class="p-3.5 border-b border-slate-100 flex items-center gap-3 bg-slate-50/60">
                <i class="fa-solid fa-magnifying-glass text-[#4F46E5] text-sm ml-1"></i>
                <input type="text" x-ref="cmdInput" x-model="cmdQuery"
                       placeholder="Ir a módulo, crear pedido COD, abrir Kardex, liquidaciones..."
                       class="w-full bg-transparent text-xs sm:text-sm text-[#0F172A] placeholder-slate-400 focus:outline-none font-medium">
                <button type="button" @click="cmdPaletteOpen = false"
                        class="px-2 py-0.5 rounded bg-slate-200/70 text-[10px] font-mono font-bold text-slate-600 hover:bg-slate-300 cursor-pointer">
                    ESC
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto p-2 space-y-1 text-xs">
                <div class="px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                    Accesos Directos del Ecosistema Bolidrop
                </div>
                @php
                    $quickActions = [
                        ['label' => 'Mi Panel Principal (Dashboard)', 'desc' => 'Métricas COD, pedidos y billetera en vivo', 'url' => $dashboardUrl ?? url('/panel/admin'), 'icon' => 'fa-solid fa-gauge-high', 'tag' => 'Panel'],
                        ['label' => 'Megabodega Mayorista Nacional', 'desc' => 'Catálogo verificado con stock en Santa Cruz, La Paz y Cochabamba', 'url' => url('/catalogo'), 'icon' => 'fa-solid fa-boxes-stacked', 'tag' => 'Catálogo'],
                        ['label' => 'Crear Nuevo Pedido Contra Entrega (COD)', 'desc' => 'Registrar orden con guía DEX y cobro Efectivo o QR Simple', 'url' => url('/checkout-demo'), 'icon' => 'fa-solid fa-cart-plus', 'tag' => 'Venta COD'],
                        ['label' => 'Rastreo de Guías en Vivo', 'desc' => 'Consultar estatus logístico DRP-2026-XXXXXX', 'url' => url('/tracking'), 'icon' => 'fa-solid fa-magnifying-glass-location', 'tag' => 'Tracking'],
                        ['label' => 'Logística Inversa & Novedades en Ruta', 'desc' => 'Reprogramar entregas fallidas o procesar devoluciones', 'url' => url('/returns'), 'icon' => 'fa-solid fa-rotate-left', 'tag' => 'Logística'],
                        ['label' => 'Terminal de Cobro Repartidor (QR / Efectivo)', 'desc' => 'Cobrar entrega en puerta con QR Simple o Binance USDT', 'url' => url('/carrier/cobro'), 'icon' => 'fa-solid fa-qrcode', 'tag' => 'Courier DEX'],
                        ['label' => 'Centro de Reportes & Métricas de Proveedor', 'desc' => 'Rotación ABC, inventario valorado y flujo COD por fechas', 'url' => url('/reportes'), 'icon' => 'fa-solid fa-chart-pie', 'tag' => 'Métricas'],
                    ];
                    if (in_array(Auth::user()->role, ['tenant_admin', 'admin', 'superadmin'])) {
                        $quickActions[] = ['label' => 'Liquidaciones Bancarias & Lotes BNB', 'desc' => 'Aprobar retiros a vendedores y proveedores', 'url' => url('/settlements'), 'icon' => 'fa-solid fa-building-columns', 'tag' => 'Finanzas'];
                        $quickActions[] = ['label' => 'Kardex Global & Auditoría de Inventario', 'desc' => 'Trazabilidad física e invariante contable de bodegas', 'url' => route('admin.kardex.index'), 'icon' => 'fa-solid fa-clipboard-list', 'tag' => 'Admin'];
                        $quickActions[] = ['label' => 'Reportes Financieros & Analytics', 'desc' => 'Efectividad de entrega por departamento y transportadora', 'url' => url('/reports'), 'icon' => 'fa-solid fa-chart-line', 'tag' => 'Analytics'];
                        $quickActions[] = ['label' => 'Centro Transaccional WhatsApp (+591)', 'desc' => 'Confirmación automática de pedidos con compradores', 'url' => url('/notifications/whatsapp'), 'icon' => 'fa-brands fa-whatsapp', 'tag' => 'Bot +591'];
                    }
                @endphp

                @foreach($quickActions as $qa)
                    <a href="{{ $qa['url'] }}"
                       x-show="!cmdQuery || '{{ strtolower($qa['label'] . ' ' . $qa['desc'] . ' ' . $qa['tag']) }}'.includes(cmdQuery.toLowerCase())"
                       class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl hover:bg-indigo-50/80 text-[#0F172A] group transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 group-hover:bg-[#4F46E5] text-[#4F46E5] group-hover:text-white flex items-center justify-center transition shrink-0">
                                <i class="{{ $qa['icon'] }} text-xs"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-[#0F172A] group-hover:text-[#4F46E5] truncate">{{ $qa['label'] }}</div>
                                <div class="text-[10px] text-slate-500 truncate">{{ $qa['desc'] }}</div>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 group-hover:bg-indigo-100 text-slate-600 group-hover:text-[#4F46E5] font-bold text-[10px] shrink-0">
                            {{ $qa['tag'] }} →
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TARJETA TAILWIND DE ADVERTENCIA ANTES DE SALIR DEL SISTEMA            -->
    <!-- ===================================================================== -->
    <div x-show="logoutModalOpen" x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-[#0F172A]/65 backdrop-blur-sm"
         role="dialog" aria-modal="true" aria-labelledby="logout-modal-title"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="logoutModalOpen = false"
             x-show="logoutModalOpen"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-95 translate-y-3"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-md bg-white rounded-3xl border border-slate-200/90 shadow-2xl overflow-hidden">

            <!-- Franja Superior de Advertencia (Amber / Rose Gradient) -->
            <div class="h-1.5 w-full bg-gradient-to-r from-amber-500 via-rose-500 to-[#4F46E5]"></div>

            <div class="p-6 sm:p-7 space-y-5">
                <!-- Encabezado con Icono de Advertencia -->
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-600 flex items-center justify-center text-xl shadow-xs shrink-0">
                            <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                        </div>
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200">
                                Advertencia de Seguridad
                            </span>
                            <h3 id="logout-modal-title" class="text-lg font-extrabold text-[#0F172A] font-heading mt-1">
                                ¿Deseas salir del sistema?
                            </h3>
                        </div>
                    </div>
                    <button type="button" @click="logoutModalOpen = false"
                            class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Cuerpo de la Tarjeta de Advertencia -->
                <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/70 space-y-2 text-xs">
                    <div class="font-bold text-amber-950 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-amber-600"></i>
                        <span>Sesión activa: {{ Auth::user()->name }}</span>
                    </div>
                    <p class="text-slate-600 leading-relaxed text-[11px]">
                        Estás a punto de cerrar tu sesión en <strong class="text-[#0F172A]">Bolidrop Bolivia</strong>. Si tienes manifiestos DEX, pedidos COD o transferencias de Kardex sin guardar, asegúrate de haberlos confirmado antes de salir.
                    </p>
                </div>

                <!-- Botones de Acción (Cancelar / Confirmar Salida) -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                    <button type="button" @click="logoutModalOpen = false"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-[#0F172A] font-bold text-xs transition cursor-pointer">
                        Permanecer en el Sistema
                    </button>

                    <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white font-bold text-xs shadow-md hover:shadow-lg flex items-center justify-center gap-2 transition cursor-pointer">
                            <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                            <span>Sí, Cerrar Sesión</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ===================================================================== -->
    <!-- GLOBAL INTERACTIVE CHOREOGRAPHY ENGINE (Cursor Spotlight, 3D Tilt,     -->
    <!-- Animated Number Counters & Hero Parallax)                             -->
    <!-- ===================================================================== -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) return;

            // 1. Cursor-Following Spotlight & 3D Tilt on Cards
            const interactiveCards = document.querySelectorAll('.bg-white.rounded-2xl, .bg-white.rounded-3xl, .white-card, .kpi-card');
            interactiveCards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);

                    if (card.classList.contains('interactive-card') || card.classList.contains('kpi-card')) {
                        const centerX = rect.width / 2;
                        const centerY = rect.height / 2;
                        const rotateX = ((y - centerY) / centerY) * -2.2;
                        const rotateY = ((x - centerX) / centerX) * 2.2;
                        card.style.transform = `perspective(900px) translateY(-4px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg)`;
                    }
                });

                card.addEventListener('mouseleave', () => {
                    if (card.classList.contains('interactive-card') || card.classList.contains('kpi-card')) {
                        card.style.transform = '';
                    }
                });
            });

            // 2. Hero Interactive Cursor Light Follower
            const heroes = document.querySelectorAll('.page-header-sb');
            heroes.forEach(hero => {
                hero.addEventListener('mousemove', (e) => {
                    const rect = hero.getBoundingClientRect();
                    const xPct = (((e.clientX - rect.left) / rect.width) * 100).toFixed(1);
                    const yPct = (((e.clientY - rect.top) / rect.height) * 100).toFixed(1);
                    hero.style.setProperty('--hero-x', `${xPct}%`);
                    hero.style.setProperty('--hero-y', `${yPct}%`);
                });
            });

            // 3. Smooth Spring Count-Up Animation for KPI Numbers
            const countTargets = document.querySelectorAll('[data-countup], .kpi-value');
            const animateCounter = (el) => {
                if (el.dataset.counted === 'true') return;
                el.dataset.counted = 'true';
                const rawText = (el.dataset.targetText || el.textContent).trim();
                el.dataset.targetText = rawText;

                // Match prefix (e.g. "Bs ", "+Bs "), numeric body, and suffix (e.g. "%", " uds")
                const match = rawText.match(/^([^\d-]*)(-?[\d,]+(?:\.\d+)?)(.*)$/);
                if (!match) return;

                const prefix = match[1];
                const numStr = match[2].replace(/,/g, '');
                const suffix = match[3];
                const targetVal = parseFloat(numStr);
                if (isNaN(targetVal) || targetVal === 0) return;

                const decimals = (numStr.split('.')[1] || '').length;
                const hasCommas = match[2].includes(',');
                const duration = 1050;
                const startTime = performance.now();

                const formatNum = (val) => {
                    const fixed = val.toFixed(decimals);
                    if (!hasCommas) return fixed;
                    const parts = fixed.split('.');
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    return parts.join('.');
                };

                const step = (now) => {
                    const progress = Math.min((now - startTime) / duration, 1);
                    // Spring easeOutExpo curve
                    const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                    const current = targetVal * eased;
                    el.textContent = `${prefix}${formatNum(current)}${suffix}`;
                    if (progress < 1) {
                        requestAnimationFrame(step);
                    } else {
                        el.textContent = rawText;
                    }
                };
                requestAnimationFrame(step);
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });

            countTargets.forEach(el => observer.observe(el));
        });
    </script>

    <!-- Modal Global de Detalle de Producto COD (Completamente aislado del grid del catálogo) -->
    <x-product-detail-modal />

    <!-- Sistema Global de Alertas, Toasts y Modales Tailwind (Reemplaza los cuadros nativos 'localhost dice') -->
    <x-global-alerts />

    @stack('scripts')
</body>
</html>
