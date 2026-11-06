<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dropana Bolivia | Ecosistema E-commerce COD')</title>

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
                        fireworks: {
                            scarlet: '#ED2100', /* Primario: Botones de Acción (CTA) */
                            amber: '#E88A38',   /* Secundario: Métricas, Badges y Soporte */
                            carmine: '#960016', /* Terciario: Sidebar y Títulos Estructurales */
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
            --color-carmine: #960016;
            --color-amber: #E88A38;
            --color-scarlet: #ED2100;
            --ease-spring: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background-color: #FFFFFF;
            color: #1E293B;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.02em;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Sidebar Carmín #960016 (Terciario Institucional) */
        .sidebar-carmine {
            background-color: #960016;
            color: #FFFFFF;
        }

        /* Animaciones & Micro-interacciones (ui-motion-transitions standard) */
        .interactive-card {
            transition: transform 0.24s var(--ease-spring),
                        box-shadow 0.24s var(--ease-spring),
                        border-color 0.2s ease;
            will-change: transform;
        }
        .interactive-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px -8px rgba(150, 0, 22, 0.08), 0 4px 10px -2px rgba(0, 0, 0, 0.03);
        }
        .interactive-card:active {
            transform: translateY(-1px) scale(0.99);
        }

        /* Botón de Alta Conversión (Primario #ED2100) */
        .btn-scarlet {
            background-color: #ED2100;
            color: #FFFFFF;
            transition: transform 0.2s var(--ease-spring),
                        background-color 0.18s ease,
                        box-shadow 0.2s var(--ease-spring);
            will-change: transform;
        }
        .btn-scarlet:hover {
            background-color: #D61D00;
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 10px 24px -4px rgba(237, 33, 0, 0.4);
        }
        .btn-scarlet:active {
            transform: translateY(0) scale(0.98);
        }

        /* Botón Secundario Ámbar */
        .btn-amber {
            background-color: #E88A38;
            color: #FFFFFF;
            transition: transform 0.2s var(--ease-spring),
                        background-color 0.18s ease,
                        box-shadow 0.2s var(--ease-spring);
            will-change: transform;
        }
        .btn-amber:hover {
            background-color: #D67A28;
            transform: translateY(-2px);
            box-shadow: 0 10px 24px -4px rgba(232, 138, 56, 0.35);
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
            background-color: #F8FAFC;
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
            transform: translateX(4px);
        }

        /* Tarjetas Blancas (#FFFFFF) */
        .white-card {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
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
<body class="min-h-screen flex flex-col md:flex-row antialiased selection:bg-red-500 selection:text-white bg-[#FFFFFF]" x-data="{ mobileMenuOpen: false }">

    <!-- SIDEBAR PRINCIPAL (Terciario Carmín #960016 - Sticky al 100% de la ventana) -->
    @auth
    <aside class="sidebar-carmine w-full md:w-64 md:h-screen md:sticky md:top-0 flex flex-col justify-between shrink-0 shadow-xl z-40 overflow-y-auto">
        
        <div>
            <!-- Branding Header con Logo y Mascota Nano Banana -->
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    <div class="relative w-10 h-10 rounded-xl bg-white/10 p-1 flex items-center justify-center border border-white/20 group-hover:scale-105 transition">
                        <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-lg shadow-sm">
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-amber-400 rounded-full border-2 border-[#960016]"></span>
                    </div>
                    <div>
                        <div class="text-xl font-extrabold font-heading tracking-tight leading-none text-white">
                            Dropana
                        </div>
                        <span class="text-[10px] font-bold tracking-widest uppercase text-amber-300">
                            Bolivia COD
                        </span>
                    </div>
                </a>

                <!-- Mobile menu toggle -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-white/80 hover:text-white p-2">
                    <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
                </button>
            </div>

            <!-- Navegación de Enlaces - Filtrada por Rol -->
            <nav class="p-4 space-y-1.5" :class="mobileMenuOpen ? 'block' : 'hidden md:block'">

                @auth
                    {{-- ================================================ --}}
                    {{-- ADMINISTRADOR (tenant_admin) — Vista global total  --}}
                    {{-- ================================================ --}}
                    {{-- ======================================================== --}}
                    {{-- ADMINISTRADOR (tenant_admin) — Control Total del Sistema --}}
                    {{-- ======================================================== --}}
                    @if(Auth::user()->role === 'tenant_admin')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-300/80">
                            Gestión General
                        </div>
                        <a href="{{ url('/panel/admin') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('panel/admin') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->is('panel/admin') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Dashboard Admin</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('catalogo') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-boxes-stacked w-5 text-center {{ request()->is('catalogo') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Megabodega Nacional</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('checkout-demo') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-cart-shopping w-5 text-center {{ request()->is('checkout-demo') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>

                        <div class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-300/80">
                            Finanzas & Liquidaciones
                        </div>
                        <a href="{{ url('/settlements') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('settlements*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-building-columns w-5 text-center {{ request()->is('settlements*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Liquidaciones Bancarias</span>
                        </a>
                        <a href="{{ url('/reports') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('reports*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-chart-line w-5 text-center {{ request()->is('reports*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Reportes & Analytics</span>
                        </a>

                        <div class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-300/80">
                            Logística & Operaciones
                        </div>
                        <a href="{{ route('admin.kardex.index') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('panel/admin/kardex*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-clipboard-list w-5 text-center {{ request()->is('panel/admin/kardex*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Kardex Global & Stock</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('returns*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Devoluciones & Log. Inv.</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('carrier/cobro*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-qrcode w-5 text-center {{ request()->is('carrier/cobro*') ? 'text-[#960016]' : 'text-emerald-400' }}"></i>
                            <span>Cobro QR Repartidor</span>
                        </a>
                        <a href="{{ url('/panel/fulfillment') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('panel/fulfillment') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-boxes-packing w-5 text-center {{ request()->is('panel/fulfillment') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Fulfillment & Tiendas</span>
                        </a>
                        <a href="{{ url('/notifications/whatsapp') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('notifications/whatsapp*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-brands fa-whatsapp w-5 text-center {{ request()->is('notifications/whatsapp*') ? 'text-[#960016]' : 'text-emerald-400' }}"></i>
                            <span>WhatsApp Bot (+591)</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- DROPSHIPPER — Venta sin stock, billetera, tienda  --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'dropshipper')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-300/80">
                            Mi Negocio Dropshipping
                        </div>
                        <a href="{{ url('/panel/dropshipper') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('panel/dropshipper') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->is('panel/dropshipper') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('catalogo') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-boxes-stacked w-5 text-center {{ request()->is('catalogo') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Megabodega</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('checkout-demo') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-cart-shopping w-5 text-center {{ request()->is('checkout-demo') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Crear Pedido COD</span>
                        </a>
                        <a href="{{ url('/panel/dropshipper') }}#billetera" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white">
                            <i class="fa-solid fa-wallet w-5 text-center text-amber-300"></i>
                            <span>Mi Billetera & Ganancias</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('returns*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Mis Devoluciones</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- PROVEEDOR — Inventario, productos, stock           --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'provider')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-300/80">
                            Panel de Proveedor
                        </div>
                        <a href="{{ url('/panel/proveedor') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('panel/proveedor') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->is('panel/proveedor') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('catalogo') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-boxes-stacked w-5 text-center {{ request()->is('catalogo') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Catálogo Publicado</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#inventario" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white">
                            <i class="fa-solid fa-warehouse w-5 text-center text-amber-300"></i>
                            <span>Mi Inventario Físico</span>
                        </a>
                        <a href="{{ url('/panel/proveedor') }}#alertas" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white">
                            <i class="fa-solid fa-triangle-exclamation w-5 text-center text-amber-300"></i>
                            <span>Alertas de Stock</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- CARRIER (Repartidor) — Hoja de ruta, entregas     --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'carrier')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-300/80">
                            Repartidor / Courier
                        </div>
                        <a href="{{ url('/panel/carrier') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('panel/carrier') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-truck-fast w-5 text-center {{ request()->is('panel/carrier') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Mi Hoja de Ruta</span>
                        </a>
                        <a href="{{ url('/carrier/cobro') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('carrier/cobro*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-qrcode w-5 text-center {{ request()->is('carrier/cobro*') ? 'text-[#960016]' : 'text-emerald-400' }}"></i>
                            <span>Cobro QR / Efectivo</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('returns*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Reportar Novedad</span>
                        </a>
                    @endif

                    {{-- ================================================ --}}
                    {{-- FULFILLMENT CLIENT — Tiendas online tercerizadas  --}}
                    {{-- ================================================ --}}
                    @if(Auth::user()->role === 'fulfillment_client')
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-300/80">
                            Fulfillment e-Commerce
                        </div>
                        <a href="{{ url('/panel/fulfillment') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('panel/fulfillment') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-store w-5 text-center {{ request()->is('panel/fulfillment') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Mi Tienda Online</span>
                        </a>
                        <a href="{{ url('/checkout-demo') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('checkout-demo') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-cart-shopping w-5 text-center {{ request()->is('checkout-demo') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Enviar Pedidos</span>
                        </a>
                        <a href="{{ url('/returns') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold {{ request()->is('returns*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-rotate-left w-5 text-center {{ request()->is('returns*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Devoluciones</span>
                        </a>
                    @endif

                    {{-- ======================================================== --}}
                    {{-- ENLACES GENERALES / HERRAMIENTAS GLOBALES (Todos los roles) --}}
                    {{-- ======================================================== --}}
                    <div class="pt-2 mt-2 border-t border-white/10 space-y-1">
                        <div class="px-3 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-300/80">
                            Herramientas
                        </div>
                        <a href="{{ url('/tracking') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 rounded-xl text-sm font-semibold {{ request()->is('tracking*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-magnifying-glass-location w-5 text-center {{ request()->is('tracking*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Rastrear Guía</span>
                        </a>
                        <a href="{{ url('/contactenos') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 rounded-xl text-sm font-semibold {{ request()->is('contactenos*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-map-location-dot w-5 text-center {{ request()->is('contactenos*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Sedes & Almacenes</span>
                        </a>
                        <a href="{{ url('/soporte') }}" class="nav-item-transition flex items-center gap-3 px-4 py-2 rounded-xl text-sm font-semibold {{ request()->is('soporte*') ? 'bg-white text-[#960016] shadow-md font-bold' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid fa-headset w-5 text-center {{ request()->is('soporte*') ? 'text-[#960016]' : 'text-amber-300' }}"></i>
                            <span>Centro de Soporte</span>
                        </a>
                    </div>
                @endauth

            </nav>
        </div>

        <!-- Sidebar Footer / Tarjeta de Mascota Nano Banana -->
        <div class="p-4 border-t border-white/10 hidden md:block">
            <div class="bg-black/20 rounded-2xl p-3.5 border border-white/10 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[11px] font-bold text-amber-300 uppercase tracking-wider">Nano Banana Bot</span>
                </div>
                <p class="text-[11px] text-white/80 leading-relaxed">
                    Sincronización activa con DEX Bolivia y pasarela QR Simple.
                </p>
                <div class="text-[10px] text-white/60 font-mono">
                    Red Nacional 9/9 Deptos
                </div>
            </div>
        </div>

    </aside>
    @endauth

    <!-- CONTENEDOR PRINCIPAL (#FFFFFF Blanco Puro) -->
    <div class="flex-grow flex flex-col min-w-0 bg-[#FFFFFF]">
        
        <!-- TOPBAR SUPERIOR (#FFFFFF con Borde Sutil) -->
        <header class="bg-[#FFFFFF] border-b border-slate-200 sticky top-0 z-30 px-4 sm:px-8 py-3.5 flex items-center justify-between gap-4 shadow-sm">
            
            @guest
            <!-- Logo Dropana para visitantes en Landing -->
            <a href="{{ url('/') }}" class="flex items-center space-x-2.5 group">
                <div class="relative w-8 h-8 rounded-lg bg-red-50 p-1 flex items-center justify-center border border-red-200">
                    <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-md shadow-xs">
                </div>
                <div>
                    <span class="text-lg font-black font-heading tracking-tight text-[#960016]">Dropana</span>
                    <span class="text-[9px] font-bold text-[#E88A38] uppercase block leading-none">Bolivia</span>
                </div>
            </a>
            @endguest
            
            <!-- Tasa Oficial del BCB -->
            <div class="flex items-center space-x-3 text-xs">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <span>Tasa BCB Oficial:</span>
                    <strong class="font-mono text-[#960016] font-bold">{{ number_format($bcbOfficialRate ?? 12.06, 2) }} BOB</strong>
                </div>

                <div class="flex items-center gap-2 text-slate-500 text-xs">
                    <i class="fa-solid fa-truck-fast text-[#E88A38]"></i>
                    <span class="hidden md:inline">Operador:</span>
                    <strong class="text-slate-800">DEX Bolivia Express</strong>
                </div>
            </div>

            <!-- Acciones Rápidas & Usuario -->
            <div class="flex items-center space-x-3">
                @auth
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-full bg-[#960016] text-white flex items-center justify-center font-bold text-xs shadow-sm">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-bold text-[#960016] leading-tight">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] text-[#E88A38] font-semibold capitalize">{{ str_replace('_', ' ', Auth::user()->role) }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                            @csrf
                            <button type="submit" title="Cerrar Sesión" class="text-slate-400 hover:text-[#960016] p-1 transition">
                                <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-xs">
                        <a href="{{ route('login') }}" class="font-bold text-[#960016] hover:text-[#ED2100] px-3 py-1.5 rounded-lg hover:bg-slate-50 transition">
                            Ingresar
                        </a>
                        <a href="{{ route('register') }}" class="btn-scarlet px-3.5 py-1.5 rounded-lg font-bold shadow-sm">
                            Registrarse
                        </a>
                    </div>
                @endauth
            </div>
        </header>

        <!-- CONTENIDO DE LA VISTA (#FFFFFF Canvas) -->
        <main class="px-4 py-3 sm:px-8 sm:py-5 flex-grow bg-[#FFFFFF]">
            @yield('content')
        </main>

        <!-- FOOTER DISCRETO Y COMPLETO BLANCO -->
        <footer class="bg-[#FFFFFF] border-t border-slate-200 px-6 sm:px-12 py-8 text-xs text-slate-500 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pb-6 border-b border-slate-100">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-base font-extrabold font-heading text-[#960016]">Dropana</span>
                        <span class="text-[9px] font-bold text-[#E88A38] uppercase">Bolivia</span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Infraestructura e-commerce COD, Megabodega y Logística Express con cobertura en los 9 departamentos.
                    </p>
                </div>
                <div>
                    <h5 class="font-bold text-slate-800 uppercase tracking-wider text-[11px] mb-2.5">Ecosistema</h5>
                    <ul class="space-y-1.5 text-[11px]">
                        <li><a href="{{ url('/catalogo') }}" class="hover:text-[#960016] transition">Megabodega Catálogo</a></li>
                        <li><a href="{{ url('/panel/fulfillment') }}" class="hover:text-[#960016] transition">Fulfillment Tiendas</a></li>
                        <li><a href="{{ url('/checkout-demo') }}" class="hover:text-[#960016] transition">Checkout COD Simple</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-slate-800 uppercase tracking-wider text-[11px] mb-2.5">Atención & Ayuda</h5>
                    <ul class="space-y-1.5 text-[11px]">
                        <li><a href="{{ url('/tracking') }}" class="hover:text-[#960016] transition flex items-center gap-1.5"><i class="fa-solid fa-magnifying-glass text-[#E88A38]"></i> Rastrear Mi Pedido</a></li>
                        <li><a href="{{ url('/soporte') }}" class="hover:text-[#960016] transition">Centro de Ayuda & FAQ</a></li>
                        <li><a href="{{ url('/contactenos') }}" class="hover:text-[#960016] transition">Contáctanos / Sedes</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-slate-800 uppercase tracking-wider text-[11px] mb-2.5">Red de Cobranza</h5>
                    <div class="space-y-2 text-[11px]">
                        <div class="flex items-center gap-2 text-emerald-600 font-semibold">
                            <i class="fa-solid fa-qrcode"></i> QR Simple Bolivia
                        </div>
                        <div class="flex items-center gap-2 text-slate-700">
                            <i class="fa-solid fa-money-bill-wave text-[#E88A38]"></i> Efectivo Contra Entrega (COD)
                        </div>
                        <div class="text-[10px] text-slate-400">
                            DEX Bolivia Express • Red 9/9 Deptos
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2">
                <div>
                    &copy; {{ date('Y') }} <strong class="text-[#960016]">Dropana Bolivia</strong> — Todos los derechos reservados.
                </div>
                <div class="flex items-center space-x-4 text-[11px]">
                    <a href="{{ url('/tracking') }}" class="hover:text-[#960016]">Rastreo de Guías</a>
                    <span>•</span>
                    <a href="{{ url('/soporte') }}" class="hover:text-[#960016]">Soporte Técnico</a>
                    <span>•</span>
                    <a href="{{ url('/contactenos') }}" class="hover:text-[#960016]">Ubicaciones & Bodegas</a>
                </div>
            </div>
        </footer>

    </div>

    @stack('scripts')
</body>
</html>
