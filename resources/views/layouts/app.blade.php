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
                            primary: '#0061f2',
                            primaryHover: '#0052cc',
                            secondary: '#6900c7',
                            teal: '#00ac69',
                            cyan: '#00cfd5',
                            amber: '#f4a100',
                            danger: '#e81500',
                            canvas: '#f2f6fc',
                            dark: '#212832',
                            muted: '#69707a',
                        },
                        fireworks: {
                            scarlet: '#0061f2',
                            amber: '#00ac69',
                            carmine: '#212832',
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
            --sb-primary: #0061f2;
            --sb-secondary: #6900c7;
            --sb-teal: #00ac69;
            --sb-canvas: #f2f6fc;
            --sb-dark: #212832;
            --sb-card-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.12);
            --ease-spring: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f2f6fc;
            color: #212832;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            letter-spacing: -0.02em;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* SB Admin Pro Signature Gradient Header */
        .page-header-sb {
            background-color: #0061f2;
            background-image: linear-gradient(135deg, #0061f2 0%, rgba(105, 0, 199, 0.85) 100%);
        }

        /* SB Admin Pro Sidenav Light */
        .sidenav-light {
            background-color: #ffffff;
            color: #212832;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.10);
            border-right: 1px solid #e0e5ec;
        }

        /* SB Admin Pro Card Shadow Elevation */
        .bg-white.rounded-2xl,
        .bg-white.rounded-3xl,
        .bg-white.rounded-xl,
        .white-card,
        .sb-card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.10);
            border-color: rgba(33, 40, 50, 0.08);
        }

        .interactive-card {
            transition: transform 0.2s var(--ease-spring),
                        box-shadow 0.2s var(--ease-spring),
                        border-color 0.18s ease;
        }
        .interactive-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 2rem 0 rgba(33, 40, 50, 0.16);
            border-color: rgba(0, 97, 242, 0.35);
        }

        /* SB Admin Pro Primary Button (#0061f2) */
        .btn-scarlet {
            background-color: #0061f2;
            color: #FFFFFF;
            transition: transform 0.18s var(--ease-spring),
                        background-color 0.15s ease,
                        box-shadow 0.18s ease;
        }
        .btn-scarlet:hover {
            background-color: #0052cc;
            transform: translateY(-1px);
            box-shadow: 0 0.35rem 1rem rgba(0, 97, 242, 0.30);
        }

        /* SB Admin Pro Teal/Success Button (#00ac69) */
        .btn-amber {
            background-color: #00ac69;
            color: #FFFFFF;
            transition: transform 0.18s var(--ease-spring),
                        background-color 0.15s ease;
        }
        .btn-amber:hover {
            background-color: #008f57;
            transform: translateY(-1px);
        }

        .nav-item-transition {
            transition: color 0.15s ease, background-color 0.15s ease;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-[#0061f2] selection:text-white bg-[#f2f6fc]" x-data="{ sidenavOpen: true, mobileMenuOpen: false }">

    <!-- ===================================================================== -->
    <!-- SB ADMIN PRO TOPNAV (Fixed Full-Width White Navbar at Top)            -->
    <!-- ===================================================================== -->
    <nav class="sticky top-0 z-50 h-16 bg-white border-b border-[#e0e5ec] px-4 sm:px-6 flex items-center justify-between gap-4 shadow-[0_0.15rem_1.75rem_0_rgba(33,40,50,0.08)]">
        
        <!-- Left: Sidenav Toggle + Brand + Search -->
        <div class="flex items-center gap-3 sm:gap-5">
            @if(Auth::check() && !request()->is('/'))
            <button @click="sidenavOpen = !sidenavOpen; mobileMenuOpen = !mobileMenuOpen"
                class="w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500 hover:text-[#212832] flex items-center justify-center transition focus:outline-none"
                title="Alternar Menú Lateral">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
            @endif

            <!-- Brand Logo with Nano Banana -->
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 group shrink-0">
                <div class="relative w-9 h-9 rounded-lg bg-blue-50 p-0.5 flex items-center justify-center border border-blue-200">
                    <img src="{{ asset('images/nano_banana.jpg') }}" alt="Nano Banana" class="w-full h-full object-cover rounded-md">
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-[#00ac69] rounded-full border-2 border-white"></span>
                </div>
                <div class="leading-none">
                    <span class="text-base font-extrabold tracking-tight text-[#212832] group-hover:text-[#0061f2] transition">Bolidrop</span>
                    <span class="text-[9px] font-bold text-[#0061f2] uppercase tracking-widest block">Bolivia PRO</span>
                </div>
            </a>

            <!-- SB Admin Pro Global Search Input -->
            <form action="{{ url('/tracking') }}" method="GET" class="hidden md:flex items-center relative ml-2">
                <input type="text" name="guide" placeholder="Buscar guía, pedido o SKU..."
                    class="bg-[#eef2f8] border border-transparent text-[#212832] placeholder-[#69707a] rounded-lg pl-3.5 pr-9 py-2 text-xs font-medium focus:outline-none focus:bg-white focus:border-[#0061f2] focus:ring-2 focus:ring-[#0061f2]/15 transition w-64">
                <button type="submit" class="absolute right-3 text-[#69707a] hover:text-[#0061f2] transition">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </button>
            </form>
        </div>

        <!-- Center/Right: Public Navigation Links (Guests or Landing Page) -->
        @if(!Auth::check() || request()->is('/'))
        <div class="hidden lg:flex items-center gap-6 text-xs font-semibold text-[#69707a]">
            <a href="{{ url('/') }}" class="hover:text-[#0061f2] transition {{ request()->is('/') ? 'text-[#0061f2] font-bold' : '' }}">Inicio</a>
            <a href="{{ url('/catalogo') }}" class="hover:text-[#0061f2] transition {{ request()->is('catalogo*') ? 'text-[#0061f2] font-bold' : '' }}">Megabodega</a>
            <a href="{{ url('/checkout-demo') }}" class="hover:text-[#0061f2] transition {{ request()->is('checkout-demo*') ? 'text-[#0061f2] font-bold' : '' }}">Simulador COD</a>
            <a href="{{ url('/tracking') }}" class="hover:text-[#0061f2] transition {{ request()->is('tracking*') ? 'text-[#0061f2] font-bold' : '' }}">Rastrear Guía</a>
            <a href="{{ url('/soporte') }}" class="hover:text-[#0061f2] transition {{ request()->is('soporte*') ? 'text-[#0061f2] font-bold' : '' }}">Soporte</a>
        </div>
        @endif

        <!-- Right: BCB Rate, DEX Status & User Dropdown -->
        <div class="flex items-center gap-3">
            <!-- BCB Official Rate Pill -->
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[#eef2f8] text-[#212832] text-xs font-medium">
                <span class="w-2 h-2 rounded-full bg-[#00ac69]"></span>
                <span class="text-[#69707a] hidden xl:inline">BCB:</span>
                <strong class="font-mono text-[#0061f2] font-bold">{{ number_format($bcbOfficialRate ?? 12.06, 2) }} BOB</strong>
            </div>

            <div class="hidden lg:flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-[#00ac69] border border-emerald-200/70 text-xs font-bold">
                <i class="fa-solid fa-truck-fast"></i>
                <span>DEX 9/9</span>
            </div>

            @if(Auth::check() && !request()->is('/'))
                <div class="h-6 w-px bg-slate-200 mx-1 hidden sm:block"></div>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#0061f2] to-[#6900c7] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-[#212832] leading-tight">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] text-[#0061f2] font-bold uppercase tracking-wider">{{ str_replace('_', ' ', Auth::user()->role) }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                        @csrf
                        <button type="submit" title="Cerrar Sesión" class="w-8 h-8 rounded-full hover:bg-red-50 text-slate-400 hover:text-red-600 flex items-center justify-center transition">
                            <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex items-center gap-2 text-xs">
                    <a href="{{ route('login') }}" class="font-bold text-[#212832] hover:text-[#0061f2] px-3 py-2 rounded-lg hover:bg-slate-100 transition">
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
                        <div class="px-5 pt-4 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
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

                        <div class="px-5 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
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

                        <div class="px-5 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
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
                    <div class="pt-3 mt-3 border-t border-slate-100">
                        <div class="px-5 pt-2 pb-1.5 text-[10px] font-extrabold uppercase tracking-widest text-[#a7aeb8]">
                            Addons & Soporte
                        </div>
                        <a href="{{ url('/tracking') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2 text-[13px] {{ request()->is('tracking*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-magnifying-glass-location w-4 text-center {{ request()->is('tracking*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Rastrear Guía</span>
                        </a>
                        <a href="{{ url('/contactenos') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2 text-[13px] {{ request()->is('contactenos*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-map-location-dot w-4 text-center {{ request()->is('contactenos*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Sedes & Almacenes</span>
                        </a>
                        <a href="{{ url('/soporte') }}" class="nav-item-transition flex items-center gap-3 px-5 py-2 text-[13px] {{ request()->is('soporte*') ? 'text-[#0061f2] bg-blue-50/70 font-bold border-r-4 border-[#0061f2]' : 'text-[#212832] hover:text-[#0061f2] hover:bg-slate-50 font-medium' }}">
                            <i class="fa-solid fa-headset w-4 text-center {{ request()->is('soporte*') ? 'text-[#0061f2]' : 'text-[#a7aeb8]' }}"></i>
                            <span>Centro de Soporte</span>
                        </a>
                    </div>
                </nav>
            </div>

            <!-- SB Admin Pro Sidenav Footer -->
            <div class="bg-[#f2f6fc] border-t border-[#e0e5ec] px-5 py-3.5">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-2 h-2 rounded-full bg-[#00ac69] animate-pulse"></span>
                    <span class="text-[10px] font-extrabold text-[#00ac69] uppercase tracking-wider">Nano Banana Sync</span>
                </div>
                <div class="text-[11px] text-[#69707a]">Conectado como:</div>
                <div class="text-xs font-bold text-[#212832] truncate">{{ Auth::user()->name }}</div>
            </div>

        </aside>
        @endif

        <!-- ===================================================================== -->
        <!-- MAIN CONTENT AREA (#f2f6fc SB Admin Pro Canvas)                       -->
        <!-- ===================================================================== -->
        <div id="layoutSidenav_content" class="flex-grow flex flex-col min-w-0 bg-[#f2f6fc]">
            
            <main class="px-4 py-3 sm:px-8 sm:py-5 flex-grow">
                @yield('content')
            </main>

            <!-- SB ADMIN PRO FOOTER -->
            <footer class="bg-white border-t border-[#e0e5ec] px-6 sm:px-10 py-6 text-xs text-[#69707a]">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span>Copyright &copy; <strong class="text-[#212832]">Bolidrop Bolivia</strong> {{ date('Y') }}</span>
                        <span>•</span>
                        <span class="text-[11px]">DEX Bolivia Express & QR Simple (9/9 Deptos)</span>
                    </div>
                    <div class="flex items-center space-x-4 text-[11px] font-medium">
                        <a href="{{ url('/catalogo') }}" class="hover:text-[#0061f2] transition">Megabodega</a>
                        <span>&middot;</span>
                        <a href="{{ url('/tracking') }}" class="hover:text-[#0061f2] transition">Rastreo de Guías</a>
                        <span>&middot;</span>
                        <a href="{{ url('/soporte') }}" class="hover:text-[#0061f2] transition">Soporte</a>
                        <span>&middot;</span>
                        <a href="{{ url('/contactenos') }}" class="hover:text-[#0061f2] transition">Almacenes</a>
                    </div>
                </div>
            </footer>

        </div>
    </div>

    @stack('scripts')
</body>
</html>
