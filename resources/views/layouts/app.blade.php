<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bolidrop | Ecosistema E-commerce COD Bolivia')</title>

    <!-- Google Fonts: Plus Jakarta Sans + Outfit + JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nordic: {
                            canvas: '#F8FAFC',   /* Fondo nórdico frío y limpio */
                            slate: '#0F172A',    /* Títulos oscuros de máximo contraste */
                            jade: '#0D9488',     /* Jade / Teal primario nórdico */
                            jadeHover: '#0F766E',
                            mint: '#14B8A6',     /* Menta acento y badges */
                            muted: '#64748B',    /* Texto secundario */
                            glassBorder: 'rgba(226, 232, 240, 0.8)',
                        },
                        fireworks: {
                            scarlet: '#0D9488', /* Primario Jade Nórdico */
                            amber: '#14B8A6',   /* Secundario Menta Nórdico */
                            carmine: '#0F172A', /* Terciario Slate Oscuro */
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        heading: ['"Outfit"', 'sans-serif'],
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
            --color-carmine: #0F172A; /* Nordic Slate */
            --color-amber: #14B8A6;   /* Mint */
            --color-scarlet: #0D9488; /* Jade */
            --nordic-canvas: #F8FAFC;
            --ease-spring: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.035em;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Sidebar Glassmorphism Nórdico (Efecto Vidrio Esmerilado Premium) */
        .sidebar-carmine {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(226, 232, 240, 0.85);
            color: #0F172A;
            box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.05);
        }

        /* Glass Card Utility */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 25px -5px rgba(13, 148, 136, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }

        /* Animaciones & Micro-interacciones (ui-motion-transitions standard) */
        .interactive-card {
            transition: transform 0.22s var(--ease-spring),
                        box-shadow 0.22s var(--ease-spring),
                        border-color 0.18s ease;
            will-change: transform;
        }
        .interactive-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -6px rgba(13, 148, 136, 0.10), 0 4px 8px -2px rgba(0, 0, 0, 0.03);
            border-color: #99F6E4;
        }
        .interactive-card:active {
            transform: translateY(0) scale(0.99);
        }

        /* Botón de Alta Conversión Jade Nórdico (#0D9488) */
        .btn-scarlet {
            background-color: #0D9488;
            color: #FFFFFF;
            transition: transform 0.2s var(--ease-spring),
                        background-color 0.18s ease,
                        box-shadow 0.2s var(--ease-spring);
            will-change: transform;
        }
        .btn-scarlet:hover {
            background-color: #0F766E;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(13, 148, 136, 0.35);
        }
        .btn-scarlet:active {
            transform: translateY(0) scale(0.98);
        }

        /* Botón Secundario Menta Pastel (#14B8A6) */
        .btn-amber {
            background-color: #14B8A6;
            color: #FFFFFF;
            transition: transform 0.2s var(--ease-spring),
                        background-color 0.18s ease,
                        box-shadow 0.2s var(--ease-spring);
            will-change: transform;
        }
        .btn-amber:hover {
            background-color: #0D9488;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(20, 184, 166, 0.3);
        }
        .btn-amber:active {
            transform: translateY(0) scale(0.98);
        }

        /* Botón Neutro / Ghost Nórdico */
        .btn-ghost {
            transition: transform 0.18s var(--ease-spring),
                        background-color 0.18s ease,
                        border-color 0.18s ease,
                        color 0.18s ease;
        }
        .btn-ghost:hover {
            transform: translateY(-1.5px);
            border-color: #CBD5E1;
            background-color: #FFFFFF;
        }
        .btn-ghost:active {
            transform: translateY(0);
        }

        /* Enlaces de Navegación del Sidebar Nórdico */
        .nav-item-transition {
            transition: transform 0.18s var(--ease-spring),
                        background-color 0.18s ease,
                        color 0.18s ease,
                        box-shadow 0.18s ease;
        }
        .nav-item-transition:hover {
            transform: translateX(3px);
        }

        /* Tarjetas Blancas (#FFFFFF) Nórdicas */
        .white-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        /* Animación suave para badges y píldoras */
        .badge-pulse {
            animation: subtlePulse 3s ease-in-out infinite;
        }
        @keyframes subtlePulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.03); }
        }

        /* Respetar configuración de accesibilidad (prefers-reduced-motion) */
        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: #F8FAFC;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row antialiased selection:bg-teal-500 selection:text-white bg-[#F8FAFC]" x-data="{ mobileMenuOpen: false }">

    <!-- SIDEBAR PRINCIPAL NÓRDICO (Vidrio Esmerilado Frosted Glass) -->
    @auth
    <aside class="sidebar-carmine w-full md:w-64 md:h-screen md:sticky md:top-0 flex flex-col justify-between shrink-0 z-40 overflow-y-auto">
        
        <div>
            <!-- Branding Header con Logo y Mascota Nano Banana Nórdica -->
            <div class="p-6 border-b border-slate-200/60 flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    <div class="relative w-10 h-10 rounded-2xl bg-white p-1 flex items-center justify-center border border-slate-200 shadow-xs group-hover:scale-105 transition">
                        <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-xl shadow-xs">
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-teal-500 rounded-full border-2 border-white"></span>
                    </div>
                    <div>
                        <div class="text-xl font-extrabold font-heading tracking-tight leading-none text-slate-900">
                            Bolidrop
                        </div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-teal-600">
                            Nordic COD
                        </span>
                    </div>
                </a>

                <!-- Mobile menu toggle -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-slate-600 hover:text-slate-900 p-2">
                    <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
                </button>
            </div>

            <!-- Navegación de Enlaces - Filtrada por Rol -->
            <nav class="p-4 space-y-1" :class="mobileMenuOpen ? 'block' : 'hidden md:block'">

                @auth
                    {{-- ================================================ --}}
                    {{-- ADMINISTRADOR (tenant_admin) — Vista global total  --}}
                    {{-- ================================================ --}}
                    {{-- ======================================================== --}}
                    {{-- ADMINISTRADOR (tenant_admin) — Control Total del Sistema --}}
                    {{-- ======================================================== --}}
                    @if(Auth::user()->role === 'tenant_admin')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Gestión General
                        </div>
                        <a href="{{ url('/panel/admin') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/admin') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->is('panel/admin') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Dashboard Admin</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('catalogo') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-5 text-center {{ request()->is('catalogo') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Megabodega Nacional</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('checkout-demo') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-5 text-center {{ request()->is('checkout-demo') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>

                        <div class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Finanzas & Liquidaciones
                        </div>
                        <a href="{{ url('/settlements') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('settlements*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-building-columns w-5 text-center {{ request()->is('settlements*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Liquidaciones Bancarias</span>
                        </a>
                        <a href="{{ url('/reports') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('reports*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-chart-line w-5 text-center {{ request()->is('reports*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Reportes & Analytics</span>
                        </a>

                        <div class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Logística & Operaciones
                        </div>
                        <a href="{{ route('admin.kardex.index') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/admin/kardex*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-clipboard-list w-5 text-center {{ request()->is('panel/admin/kardex*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Kardex Global & Stock</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('returns*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Devoluciones & Log. Inv.</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('carrier/cobro*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-qrcode w-5 text-center {{ request()->is('carrier/cobro*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Cobro QR Repartidor</span>
                        </a>
                        <a href="{{ url('/panel/fulfillment') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/fulfillment') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-boxes-packing w-5 text-center {{ request()->is('panel/fulfillment') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Fulfillment & Tiendas</span>
                        </a>
                        <a href="{{ url('/notifications/whatsapp') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('notifications/whatsapp*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-brands fa-whatsapp w-5 text-center {{ request()->is('notifications/whatsapp*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>WhatsApp Bot (+591)</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- DROPSHIPPER — Venta sin stock, billetera, tienda  --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'dropshipper')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Mi Negocio Dropshipping
                        </div>
                        <a href="{{ url('/panel/dropshipper') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/dropshipper') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->is('panel/dropshipper') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('catalogo') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-5 text-center {{ request()->is('catalogo') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Megabodega</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('checkout-demo') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-5 text-center {{ request()->is('checkout-demo') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>
                        <a href="{{ url('/panel/dropshipper') }}#billetera" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium">
                            <i class="fa-solid fa-wallet w-5 text-center text-slate-400"></i>
                            <span>Mi Billetera & Ganancias</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('returns*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Mis Devoluciones</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- PROVEEDOR — Inventario, productos, stock           --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'provider')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Panel de Proveedor
                        </div>
                        <a href="{{ url('/panel/proveedor') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/proveedor') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->is('panel/proveedor') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('catalogo') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-5 text-center {{ request()->is('catalogo') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Catálogo Publicado</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#inventario" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium">
                            <i class="fa-solid fa-warehouse w-5 text-center text-slate-400"></i>
                            <span>Mi Inventario Físico</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#alertas" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium">
                            <i class="fa-solid fa-triangle-exclamation w-5 text-center text-slate-400"></i>
                            <span>Alertas de Stock</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- CARRIER (Repartidor) — Hoja de ruta, entregas     --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'carrier')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Repartidor / Courier
                        </div>
                        <a href="{{ url('/panel/carrier') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/carrier') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-truck-fast w-5 text-center {{ request()->is('panel/carrier') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Mi Hoja de Ruta</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('carrier/cobro*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-qrcode w-5 text-center {{ request()->is('carrier/cobro*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Cobro QR / Efectivo</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('returns*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Reportar Novedad</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- FULFILLMENT CLIENT — Tiendas online tercerizadas  --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'fulfillment_client')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Fulfillment e-Commerce
                        </div>
                        <a href="{{ url('/panel/fulfillment') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/fulfillment') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-store w-5 text-center {{ request()->is('panel/fulfillment') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Mi Tienda Online</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('checkout-demo') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-5 text-center {{ request()->is('checkout-demo') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Enviar Pedidos</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('returns*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Devoluciones</span>
                        </a>
                    @endif

                    {{-- ======================================================== --}}
                    {{-- ENLACES GENERALES / HERRAMIENTAS GLOBALES (Todos los roles) --}}
                    {{-- ======================================================== --}}
                    <div class="pt-2 mt-2 border-t border-slate-200/70 space-y-1">
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Herramientas
                        </div>
                        <a href="{{ url('/tracking') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 rounded-xl text-sm {{ request()->is('tracking*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-magnifying-glass-location w-5 text-center {{ request()->is('tracking*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Rastrear Guía</span>
                        </a>
                        <a href="{{ url('/contactenos') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 rounded-xl text-sm {{ request()->is('contactenos*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-map-location-dot w-5 text-center {{ request()->is('contactenos*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Sedes & Almacenes</span>
                        </a>
                        <a href="{{ url('/soporte') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 rounded-xl text-sm {{ request()->is('soporte*') ? 'bg-teal-50/90 text-teal-900 font-bold border border-teal-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 font-medium' }}">
                            <i class="fa-solid fa-headset w-5 text-center {{ request()->is('soporte*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span>Centro de Soporte</span>
                        </a>
                    </div>
                @endauth

            </nav>
        </div>

        <!-- Sidebar Footer / Tarjeta de Mascota Nano Banana Nórdica -->
        <div class="p-4 border-t border-slate-200/70 hidden md:block">
            <div class="bg-slate-50/90 rounded-2xl p-3.5 border border-slate-200/80 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                    <span class="text-[11px] font-bold text-teal-800 uppercase tracking-wider">Nano Banana Bot</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    Sincronización activa con DEX Bolivia y pasarela QR Simple.
                </p>
                <div class="text-[10px] text-slate-400 font-mono">
                    Red Nacional 9/9 Deptos
                </div>
            </div>
        </div>

    </aside>
    @endauth

    <!-- CONTENEDOR PRINCIPAL (#F8FAFC Nordic Canvas) -->
    <div class="flex-grow flex flex-col min-w-0 bg-[#F8FAFC]">
        
        <!-- TOPBAR SUPERIOR (Glassmorphism Nórdico) -->
        <header class="bg-white/85 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 px-4 sm:px-8 py-3.5 flex items-center justify-between gap-4 shadow-xs">
            
            @guest
            <!-- Logo Bolidrop para visitantes en Landing -->
            <a href="{{ url('/') }}" class="flex items-center space-x-2.5 group">
                <div class="relative w-8 h-8 rounded-lg bg-teal-50 p-1 flex items-center justify-center border border-teal-200">
                    <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-md shadow-xs">
                </div>
                <div>
                    <span class="text-lg font-black font-heading tracking-tight text-slate-900">Bolidrop</span>
                    <span class="text-[9px] font-bold text-teal-600 uppercase block leading-none">Bolivia</span>
                </div>
            </a>
            @endguest
            
            <!-- Tasa Oficial del BCB -->
            <div class="flex items-center space-x-3 text-xs">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-teal-50/80 border border-teal-200/70 text-teal-900 font-medium">
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-ping"></span>
                    <span>Tasa BCB Oficial:</span>
                    <strong class="font-mono text-teal-950 font-bold">{{ number_format($bcbOfficialRate ?? 12.06, 2) }} BOB</strong>
                </div>

                <div class="flex items-center gap-2 text-slate-500 text-xs">
                    <i class="fa-solid fa-truck-fast text-teal-600"></i>
                    <span class="hidden md:inline">Operador:</span>
                    <strong class="text-slate-800">DEX Bolivia Express</strong>
                </div>
            </div>

            <!-- Acciones Rápidas & Usuario -->
            <div class="flex items-center space-x-3">
                @auth
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-full bg-teal-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] text-teal-600 font-semibold capitalize">{{ str_replace('_', ' ', Auth::user()->role) }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                            @csrf
                            <button type="submit" title="Cerrar Sesión" class="text-slate-400 hover:text-slate-700 p-1 transition">
                                <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-xs">
                        <a href="{{ route('login') }}" class="font-bold text-slate-700 hover:text-teal-600 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition">
                            Ingresar
                        </a>
                        <a href="{{ route('register') }}" class="btn-scarlet px-3.5 py-1.5 rounded-lg font-bold shadow-xs">
                            Registrarse
                        </a>
                    </div>
                @endauth
            </div>
        </header>

        <!-- CONTENIDO DE LA VISTA (Nordic Canvas) -->
        <main class="px-4 py-3 sm:px-8 sm:py-5 flex-grow bg-[#F8FAFC]">
            @yield('content')
        </main>

        <!-- FOOTER DISCRETO Y COMPLETO NÓRDICO -->
        <footer class="bg-white border-t border-slate-200 px-6 sm:px-12 py-8 text-xs text-slate-500 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pb-6 border-b border-slate-100">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-base font-extrabold font-heading text-slate-900">Bolidrop</span>
                        <span class="text-[9px] font-bold text-teal-600 uppercase">Bolivia</span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Infraestructura e-commerce COD, Megabodega y Logística Express con cobertura en los 9 departamentos.
                    </p>
                </div>
                <div>
                    <h5 class="font-bold text-slate-800 uppercase tracking-wider text-[11px] mb-2.5">Ecosistema</h5>
                    <ul class="space-y-1.5 text-[11px]">
                        <li><a href="{{ url('/catalogo') }}" class="hover:text-teal-600 transition">Megabodega Catálogo</a></li>
                        <li><a href="{{ url('/panel/fulfillment') }}" class="hover:text-teal-600 transition">Fulfillment Tiendas</a></li>
                        <li><a href="{{ url('/checkout-demo') }}" class="hover:text-teal-600 transition">Checkout COD Simple</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-slate-800 uppercase tracking-wider text-[11px] mb-2.5">Atención & Ayuda</h5>
                    <ul class="space-y-1.5 text-[11px]">
                        <li><a href="{{ url('/tracking') }}" class="hover:text-teal-600 transition flex items-center gap-1.5"><i class="fa-solid fa-magnifying-glass text-teal-600"></i> Rastrear Mi Pedido</a></li>
                        <li><a href="{{ url('/soporte') }}" class="hover:text-teal-600 transition">Centro de Ayuda & FAQ</a></li>
                        <li><a href="{{ url('/contactenos') }}" class="hover:text-teal-600 transition">Contáctanos / Sedes</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-slate-800 uppercase tracking-wider text-[11px] mb-2.5">Red de Cobranza</h5>
                    <div class="space-y-2 text-[11px]">
                        <div class="flex items-center gap-2 text-teal-600 font-semibold">
                            <i class="fa-solid fa-qrcode"></i> QR Simple Bolivia
                        </div>
                        <div class="flex items-center gap-2 text-slate-700">
                            <i class="fa-solid fa-money-bill-wave text-teal-600"></i> Efectivo Contra Entrega (COD)
                        </div>
                        <div class="text-[10px] text-slate-400">
                            DEX Bolivia Express • Red 9/9 Deptos
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2">
                <div>
                    &copy; {{ date('Y') }} <strong class="text-slate-800">Bolidrop Bolivia</strong> — Todos los derechos reservados.
                </div>
                <div class="flex items-center space-x-4 text-[11px]">
                    <a href="{{ url('/tracking') }}" class="hover:text-teal-600">Rastreo de Guías</a>
                    <span>•</span>
                    <a href="{{ url('/soporte') }}" class="hover:text-teal-600">Soporte Técnico</a>
                    <span>•</span>
                    <a href="{{ url('/contactenos') }}" class="hover:text-teal-600">Ubicaciones & Bodegas</a>
                </div>
            </div>
        </footer>

    </div>

    @stack('scripts')
</body>
</html>



