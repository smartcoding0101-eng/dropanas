<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bolidrop — Plataforma e-Commerce COD & Logística Nacional')</title>

    <!-- Google Fonts: Plus Jakarta Sans & Outfit (Fintech Executive Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        midnight: {
                            navy: '#0A1128',     /* Sidebar Imperial Midnight Navy */
                            card: '#0F172A',     /* Navy Profundo */
                            ice: '#F1F5F9',      /* Canvas Principal Platino Frío */
                            mint: '#00A884',     /* Menta Real / Primario CTA */
                            mintHover: '#047857',
                            emerald: '#10B981',  /* Verde Éxito */
                            silver: '#94A3B8',
                        },
                        fireworks: {
                            scarlet: '#00A884', /* Primario Royal Mint */
                            amber: '#10B981',   /* Secundario Esmeralda */
                            carmine: '#0A1128', /* Terciario Midnight Navy */
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
            --color-carmine: #0A1128; /* Midnight Navy */
            --color-amber: #10B981;   /* Emerald */
            --color-scarlet: #00A884; /* Royal Mint */
            --midnight-ice: #F1F5F9;
            --ease-spring: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background-color: #F1F5F9;
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

        /* Sidebar Imperial Midnight Navy (Propuesta C) */
        .sidebar-carmine {
            background-color: #0A1128;
            border-right: 1px solid #1E293B;
            color: #F8FAFC;
            box-shadow: 4px 0 24px -4px rgba(10, 17, 40, 0.35);
        }

        /* Glass Card Utility */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 10px 25px -5px rgba(0, 168, 132, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }

        /* Animaciones & Micro-interacciones */
        .interactive-card {
            transition: transform 0.22s var(--ease-spring),
                        box-shadow 0.22s var(--ease-spring),
                        border-color 0.18s ease;
            will-change: transform;
        }
        .interactive-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -6px rgba(0, 168, 132, 0.12), 0 4px 8px -2px rgba(0, 0, 0, 0.03);
            border-color: #A7F3D0;
        }
        .interactive-card:active {
            transform: translateY(0) scale(0.99);
        }

        /* Botón de Alta Conversión Royal Mint (#00A884) */
        .btn-scarlet {
            background-color: #00A884;
            color: #FFFFFF;
            transition: transform 0.2s var(--ease-spring),
                        background-color 0.18s ease,
                        box-shadow 0.2s var(--ease-spring);
            will-change: transform;
        }
        .btn-scarlet:hover {
            background-color: #047857;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(0, 168, 132, 0.35);
        }
        .btn-scarlet:active {
            transform: translateY(0) scale(0.98);
        }

        /* Botón Secundario Esmeralda (#10B981) */
        .btn-amber {
            background-color: #10B981;
            color: #FFFFFF;
            transition: transform 0.2s var(--ease-spring),
                        background-color 0.18s ease,
                        box-shadow 0.2s var(--ease-spring);
            will-change: transform;
        }
        .btn-amber:hover {
            background-color: #059669;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.3);
        }
        .btn-amber:active {
            transform: translateY(0) scale(0.98);
        }

        /* Botón Neutro / Ghost */
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

        /* Enlaces de Navegación del Sidebar */
        .nav-item-transition {
            transition: transform 0.18s var(--ease-spring),
                        background-color 0.18s ease,
                        color 0.18s ease,
                        box-shadow 0.18s ease;
        }
        .nav-item-transition:hover {
            transform: translateX(3px);
        }

        /* Tarjetas Blancas (#FFFFFF) */
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
            background: #0A1128;
        }
        ::-webkit-scrollbar-thumb {
            background: #1E293B;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row antialiased selection:bg-emerald-500 selection:text-white bg-[#F1F5F9]" x-data="{ mobileMenuOpen: false }">

    <!-- SIDEBAR PRINCIPAL (Imperial Midnight Navy #0A1128) -->
    @auth
    <aside class="sidebar-carmine w-full md:w-64 md:h-screen md:sticky md:top-0 flex flex-col justify-between shrink-0 z-40 overflow-y-auto">
        
        <div>
            <!-- Branding Header con Logo y Mascota Nano Banana -->
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    <div class="relative w-10 h-10 rounded-2xl bg-white/10 p-1 flex items-center justify-center border border-white/20 shadow-xs group-hover:scale-105 transition">
                        <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-xl shadow-xs">
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-[#00A884] rounded-full border-2 border-[#0A1128]"></span>
                    </div>
                    <div>
                        <div class="text-xl font-extrabold font-heading tracking-tight leading-none text-white">
                            Bolidrop
                        </div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-[#00A884]">
                            Executive COD
                        </span>
                    </div>
                </a>

                <!-- Mobile menu toggle -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-slate-300 hover:text-white p-2">
                    <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
                </button>
            </div>

            <!-- Navegación de Enlaces - Filtrada por Rol -->
            <nav class="p-4 space-y-1" :class="mobileMenuOpen ? 'block' : 'hidden md:block'">

                @auth
                    {{-- ================================================ --}}
                    {{-- ADMINISTRADOR (tenant_admin) — Control Total     --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'tenant_admin')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Gestión General
                        </div>
                        <a href="{{ url('/panel/admin') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/admin') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->is('panel/admin') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Dashboard Admin</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('catalogo') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-5 text-center {{ request()->is('catalogo') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Megabodega Nacional</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('checkout-demo') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-5 text-center {{ request()->is('checkout-demo') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>

                        <div class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Finanzas & Liquidaciones
                        </div>
                        <a href="{{ url('/settlements') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('settlements*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-building-columns w-5 text-center {{ request()->is('settlements*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Liquidaciones Bancarias</span>
                        </a>
                        <a href="{{ url('/reports') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('reports*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-chart-line w-5 text-center {{ request()->is('reports*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Reportes & Analytics</span>
                        </a>

                        <div class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Logística & Operaciones
                        </div>
                        <a href="{{ route('admin.kardex.index') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/admin/kardex*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-clipboard-list w-5 text-center {{ request()->is('panel/admin/kardex*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Kardex Global & Stock</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('returns*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Devoluciones & Log. Inv.</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('carrier/cobro*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-qrcode w-5 text-center {{ request()->is('carrier/cobro*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Cobro QR Repartidor</span>
                        </a>
                        <a href="{{ url('/panel/fulfillment') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/fulfillment') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-boxes-packing w-5 text-center {{ request()->is('panel/fulfillment') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Fulfillment & Tiendas</span>
                        </a>
                        <a href="{{ url('/notifications/whatsapp') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('notifications/whatsapp*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-brands fa-whatsapp w-5 text-center {{ request()->is('notifications/whatsapp*') ? 'text-white' : 'text-slate-400' }}"></i>
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
                        <a href="{{ url('/panel/dropshipper') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/dropshipper') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->is('panel/dropshipper') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('catalogo') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-5 text-center {{ request()->is('catalogo') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Megabodega</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('checkout-demo') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-5 text-center {{ request()->is('checkout-demo') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>
                        <a href="{{ url('/panel/dropshipper') }}#billetera" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-slate-300 hover:bg-white/10 hover:text-white font-medium">
                            <i class="fa-solid fa-wallet w-5 text-center text-slate-400"></i>
                            <span>Mi Billetera & Ganancias</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('returns*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-white' : 'text-slate-400' }}"></i>
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
                        <a href="{{ url('/panel/proveedor') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/proveedor') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->is('panel/proveedor') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('catalogo') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-boxes-stacked w-5 text-center {{ request()->is('catalogo') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Catálogo Publicado</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#inventario" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-slate-300 hover:bg-white/10 hover:text-white font-medium">
                            <i class="fa-solid fa-warehouse w-5 text-center text-slate-400"></i>
                            <span>Mi Inventario Físico</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#alertas" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-slate-300 hover:bg-white/10 hover:text-white font-medium">
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
                        <a href="{{ url('/panel/carrier') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/carrier') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-truck-fast w-5 text-center {{ request()->is('panel/carrier') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Mi Hoja de Ruta</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('carrier/cobro*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-qrcode w-5 text-center {{ request()->is('carrier/cobro*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Cobro QR / Efectivo</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('returns*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-white' : 'text-slate-400' }}"></i>
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
                        <a href="{{ url('/panel/fulfillment') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('panel/fulfillment') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-store w-5 text-center {{ request()->is('panel/fulfillment') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Mi Tienda Online</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('checkout-demo') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-cart-shopping w-5 text-center {{ request()->is('checkout-demo') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Enviar Pedidos</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm {{ request()->is('returns*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Devoluciones</span>
                        </a>
                    @endif

                    {{-- ======================================================== --}}
                    {{-- ENLACES GENERALES / HERRAMIENTAS GLOBALES (Todos los roles) --}}
                    {{-- ======================================================== --}}
                    <div class="pt-2 mt-2 border-t border-white/10 space-y-1">
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Herramientas
                        </div>
                        <a href="{{ url('/tracking') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 rounded-xl text-sm {{ request()->is('tracking*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-magnifying-glass-location w-5 text-center {{ request()->is('tracking*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Rastrear Guía</span>
                        </a>
                        <a href="{{ url('/contactenos') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 rounded-xl text-sm {{ request()->is('contactenos*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-map-location-dot w-5 text-center {{ request()->is('contactenos*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Sedes & Almacenes</span>
                        </a>
                        <a href="{{ url('/soporte') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 rounded-xl text-sm {{ request()->is('soporte*') ? 'bg-[#00A884] text-white font-bold shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                            <i class="fa-solid fa-headset w-5 text-center {{ request()->is('soporte*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span>Centro de Soporte</span>
                        </a>
                    </div>
                @endauth

            </nav>
        </div>

        <!-- Sidebar Footer / Tarjeta de Mascota Nano Banana -->
        <div class="p-4 border-t border-white/10 hidden md:block">
            <div class="bg-white/5 rounded-2xl p-3.5 border border-white/10 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#00A884] animate-pulse"></span>
                    <span class="text-[11px] font-bold text-[#00A884] uppercase tracking-wider">Nano Banana Bot</span>
                </div>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Sincronización activa con DEX Bolivia y pasarela QR Simple.
                </p>
                <div class="text-[10px] text-slate-400 font-mono">
                    Red Nacional 9/9 Deptos
                </div>
            </div>
        </div>

    </aside>
    @endauth

    <!-- CONTENEDOR PRINCIPAL (#F1F5F9 Platinum Ice Canvas) -->
    <div class="flex-grow flex flex-col min-w-0 bg-[#F1F5F9]">
        
        <!-- TOPBAR SUPERIOR (Imperial Midnight Navy con Sombra Tecnológica) -->
        <header class="bg-[#0A1128] border-b border-slate-800 sticky top-0 z-30 px-4 sm:px-8 py-3.5 flex items-center justify-between gap-4 shadow-md text-white">
            
            @guest
            <!-- Logo Bolidrop para visitantes en Landing -->
            <a href="{{ url('/') }}" class="flex items-center space-x-2.5 group">
                <div class="relative w-8 h-8 rounded-lg bg-white/10 p-1 flex items-center justify-center border border-white/20">
                    <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-md shadow-xs">
                </div>
                <div>
                    <span class="text-lg font-black font-heading tracking-tight text-white">Bolidrop</span>
                    <span class="text-[9px] font-bold text-emerald-400 uppercase block leading-none">Bolivia</span>
                </div>
            </a>
            @endguest
            
            <!-- Tasa Oficial del BCB -->
            <div class="flex items-center space-x-3 text-xs">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 font-medium">
                    <span class="w-2 h-2 rounded-full bg-[#10B981] animate-ping"></span>
                    <span>Tasa BCB Oficial:</span>
                    <strong class="font-mono text-emerald-400 font-bold">{{ number_format($bcbOfficialRate ?? 12.06, 2) }} BOB</strong>
                </div>

                <div class="flex items-center gap-2 text-slate-300 text-xs">
                    <i class="fa-solid fa-truck-fast text-emerald-400"></i>
                    <span class="hidden md:inline">Operador:</span>
                    <strong class="text-white">DEX Bolivia Express</strong>
                </div>
            </div>

            <!-- Acciones Rápidas & Usuario -->
            <div class="flex items-center space-x-3">
                @auth
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-full bg-[#131E35] text-emerald-400 border border-slate-700 flex items-center justify-center font-bold text-xs shadow-xs">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-bold text-white leading-tight">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] text-emerald-400 font-semibold capitalize">{{ str_replace('_', ' ', Auth::user()->role) }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                            @csrf
                            <button type="submit" title="Cerrar Sesión" class="text-slate-400 hover:text-white p-1 transition">
                                <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-xs">
                        <a href="{{ route('login') }}" class="font-bold text-slate-300 hover:text-white px-3 py-1.5 rounded-lg hover:bg-white/10 transition">
                            Ingresar
                        </a>
                        <a href="{{ route('register') }}" class="px-4 py-1.5 rounded-xl font-bold bg-[#10B981] hover:bg-[#059669] text-slate-950 shadow-[0_0_15px_rgba(16,185,129,0.35)] transition">
                            Registrarse
                        </a>
                    </div>
                @endauth
            </div>
        </header>

        <!-- CONTENIDO DE LA VISTA -->
        <main class="px-4 py-3 sm:px-8 sm:py-5 flex-grow">
            @yield('content')
        </main>

        <!-- FOOTER CORPORATIVO EJECUTIVO (Midnight Dark) -->
        <footer class="bg-[#0A1128] border-t border-slate-800 px-6 sm:px-12 py-8 text-xs text-slate-400 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pb-6 border-b border-slate-800/80">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-base font-extrabold font-heading text-white">Bolidrop</span>
                        <span class="text-[9px] font-bold text-emerald-400 uppercase">Bolivia</span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Infraestructura e-commerce COD, Megabodega y Logística Express con cobertura en los 9 departamentos.
                    </p>
                </div>
                <div>
                    <h5 class="font-bold text-white uppercase tracking-wider text-[11px] mb-2.5">Ecosistema</h5>
                    <ul class="space-y-1.5 text-[11px]">
                        <li><a href="{{ url('/catalogo') }}" class="hover:text-emerald-400 transition">Megabodega Catálogo</a></li>
                        <li><a href="{{ url('/panel/fulfillment') }}" class="hover:text-emerald-400 transition">Fulfillment Tiendas</a></li>
                        <li><a href="{{ url('/checkout-demo') }}" class="hover:text-emerald-400 transition">Checkout COD Simple</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-white uppercase tracking-wider text-[11px] mb-2.5">Atención & Ayuda</h5>
                    <ul class="space-y-1.5 text-[11px]">
                        <li><a href="{{ url('/tracking') }}" class="hover:text-emerald-400 transition flex items-center gap-1.5"><i class="fa-solid fa-magnifying-glass text-emerald-400"></i> Rastrear Mi Pedido</a></li>
                        <li><a href="{{ url('/soporte') }}" class="hover:text-emerald-400 transition">Centro de Ayuda & FAQ</a></li>
                        <li><a href="{{ url('/contactenos') }}" class="hover:text-emerald-400 transition">Contáctanos / Sedes</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-white uppercase tracking-wider text-[11px] mb-2.5">Red de Cobranza</h5>
                    <div class="space-y-2 text-[11px]">
                        <div class="flex items-center gap-2 text-emerald-400 font-semibold">
                            <i class="fa-solid fa-qrcode"></i> QR Simple Bolivia
                        </div>
                        <div class="flex items-center gap-2 text-slate-300">
                            <i class="fa-solid fa-money-bill-wave text-emerald-400"></i> Efectivo Contra Entrega (COD)
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono">
                            DEX Bolivia Express • Red 9/9 Deptos
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2">
                <div>
                    &copy; {{ date('Y') }} <strong class="text-white">Bolidrop Bolivia</strong> — Todos los derechos reservados.
                </div>
                <div class="flex items-center space-x-4 text-[11px]">
                    <a href="{{ url('/tracking') }}" class="hover:text-emerald-400">Rastreo de Guías</a>
                    <span>•</span>
                    <a href="{{ url('/soporte') }}" class="hover:text-emerald-400">Soporte Técnico</a>
                    <span>•</span>
                    <a href="{{ url('/contactenos') }}" class="hover:text-emerald-400">Ubicaciones & Bodegas</a>
                </div>
            </div>
        </footer>

    </div>

    @stack('scripts')
</body>
</html>
