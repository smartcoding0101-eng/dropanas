@extends('layouts.app')

@section('title', 'Dropana Bolivia | El Ecosistema de Dropshipping COD y Fulfillment')

@section('content')
<div class="space-y-16 pb-12 pt-1 sm:pt-2 bg-[#FFFFFF]" x-data="landingLeadMagnet()">

    <!-- Toast Notification -->
    <div x-show="toast.show" x-cloak
        class="fixed top-20 right-6 z-50 p-4 rounded-2xl shadow-xl text-xs font-semibold flex items-center gap-3 border transition duration-300"
        :class="toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
        <i class="fa-solid" :class="toast.type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-600'"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. HERO PRINCIPAL (Estilo Fireworks 60/30/10: Fondo blanco, Título Carmín, Botón Escarlata) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-0">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            
            <div class="lg:col-span-7 space-y-5">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-50 border border-red-200 text-xs font-bold text-[#960016]">
                    <span class="flex h-2 w-2 rounded-full bg-[#ED2100] animate-pulse"></span>
                    <span>El Ecosistema de Dropshipping más grande de Bolivia</span>
                </div>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight text-[#960016] leading-tight font-heading">
                    Conectamos Proveedores, Vendedores y Tiendas con el Consumidor Final.
                </h1>

                <p class="text-slate-600 text-base sm:text-lg leading-relaxed max-w-xl">
                    Almacenaje, Fulfillment y Logística con <strong class="text-slate-900 font-bold">Pago Contra Entrega (COD)</strong> en Santa Cruz, La Paz, Cochabamba y El Alto. Recaudación en Bolivianos en efectivo o mediante <strong class="text-[#E88A38]">QR Simple</strong> al momento de recibir el paquete.
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="#lead-magnet" class="btn-scarlet px-8 py-3.5 rounded-2xl text-white font-bold text-sm shadow-md flex items-center gap-2">
                        <span>Comienza a Vender Ahora</span>
                        <i class="fa-solid fa-arrow-down text-xs"></i>
                    </a>
                    <a href="{{ url('/catalogo') }}" class="px-6 py-3.5 rounded-2xl bg-white hover:bg-slate-50 border border-slate-300 text-slate-800 font-semibold text-sm transition shadow-xs flex items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-[#E88A38]"></i>
                        <span>Ver Catálogo Mayorista</span>
                    </a>
                </div>

                <!-- Métricas destacadas en barra ámbar -->
                <div class="grid grid-cols-3 gap-4 pt-6 border-t border-slate-100">
                    <div class="space-y-1">
                        <div class="text-2xl sm:text-3xl font-black text-[#960016] font-heading">9 / 9</div>
                        <div class="text-xs text-slate-500 font-medium">Deptos. de Cobertura</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-2xl sm:text-3xl font-black text-[#E88A38] font-heading">{{ number_format($bcbOfficialRate ?? 12.06, 2) }} Bs</div>
                        <div class="text-xs text-slate-500 font-medium">Tasa Oficial BCB</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-2xl sm:text-3xl font-black text-[#ED2100] font-heading">24 hrs</div>
                        <div class="text-xs text-slate-500 font-medium">Retiros a tu Billetera</div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Tarjeta Visual de Producto Ganador & Logística -->
            <div class="lg:col-span-5">
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xl space-y-6 relative hover:border-amber-400 transition">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-red-50 text-[#960016] border border-red-200 flex items-center justify-center font-bold">
                                <i class="fa-solid fa-truck-fast"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">Envíos DEX Bolivia</h4>
                                <span class="text-[11px] text-emerald-600 font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check text-[9px]"></i> 2 Intentos de Entrega Gratis
                                </span>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-[#E88A38] border border-amber-200 uppercase">
                            Red COD Activa
                        </span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between items-center py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-500">Recaudación:</span>
                            <span class="font-bold text-slate-800">Efectivo + QR Simple Bolivia</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-500">Costo Almacenaje:</span>
                            <span class="font-black text-emerald-600 text-sm">Bs 0.00 (Gratis)</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 rounded-xl bg-red-50/50 border border-red-100">
                            <span class="text-[#960016] font-semibold">Tus Ganancias:</span>
                            <span class="font-black text-[#960016] text-sm font-mono">+100% Retiro en 24h</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 flex items-center gap-3">
                        <i class="fa-solid fa-shield-halved text-[#E88A38] text-xl"></i>
                        <p class="text-[11px] text-slate-700 leading-snug">
                            <strong>Call Center de Novedades:</strong> Nuestro equipo contacta al cliente final en caso de reprogramación de entrega para no perder tu venta.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- 2. SECCIÓN: ¿CÓMO QUIERES USAR DROPANAS? (Los 4 Perfiles de Dropanas.com) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-2">
            <h2 class="text-xs font-bold uppercase tracking-widest text-[#E88A38] font-heading">
                Adaptado a tu Modelo de Negocio
            </h2>
            <h3 class="text-2xl sm:text-4xl font-extrabold text-[#960016] font-heading">
                ¿Cómo quieres usar Dropana Bolivia?
            </h3>
            <p class="text-xs sm:text-sm text-slate-500">
                Selecciona la forma en que deseas impulsar tus ventas y aprovechar nuestra infraestructura física.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- Card 1: DROPSHIPPERS (Venta sin Stock) -->
            <div class="interactive-card bg-white rounded-3xl p-7 border border-slate-200 shadow-sm space-y-5 hover:border-amber-400 flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-[#ED2100] border border-red-200 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#E88A38]">Perfil 1</span>
                        <h4 class="text-lg font-bold text-[#960016] font-heading">DROPSHIPPERS</h4>
                        <p class="text-xs font-semibold text-slate-700">Venta sin Inventario Propio</p>
                    </div>
                    <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Vende productos de la megabodega sin invertir en stock anticipado.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Dropana empaca, despacha y cobra contra entrega por ti.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Retiros de utilidades en 24h vía QR Simple o Binance.</span>
                        </li>
                    </ul>
                </div>
                <a href="{{ url('/catalogo') }}" class="w-full btn-scarlet py-2.5 rounded-xl text-center text-xs font-bold shadow-xs block">
                    Ver Catálogo
                </a>
            </div>

            <!-- Card 2: PROVEEDORES MAYORISTAS -->
            <div class="interactive-card bg-white rounded-3xl p-7 border border-slate-200 shadow-sm space-y-5 hover:border-amber-400 flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-[#E88A38] border border-amber-200 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#E88A38]">Perfil 2</span>
                        <h4 class="text-lg font-bold text-[#960016] font-heading">PROVEEDORES</h4>
                        <p class="text-xs font-semibold text-slate-700">Abastecimiento Mayorista</p>
                    </div>
                    <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Pon tu mercancía frente a cientos de vendedores en Bolivia.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Almacenaje Bs 0. Sin gastos fijos de personal ni alquiler.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Liquidación de tu costo mayorista al confirmarse la entrega.</span>
                        </li>
                    </ul>
                </div>
                <a href="{{ url('/panel/proveedor') }}" class="w-full py-2.5 rounded-xl text-center text-xs font-bold border border-slate-300 text-slate-800 hover:bg-slate-50 transition block">
                    Panel Proveedor
                </a>
            </div>

            <!-- Card 3: TIENDAS ONLINE (Fulfillment) -->
            <div class="interactive-card bg-white rounded-3xl p-7 border border-slate-200 shadow-sm space-y-5 hover:border-amber-400 flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-cart-flatbed"></i>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#E88A38]">Perfil 3</span>
                        <h4 class="text-lg font-bold text-[#960016] font-heading">TIENDAS ONLINE</h4>
                        <p class="text-xs font-semibold text-slate-700">Fulfillment para Marcas Propias</p>
                    </div>
                    <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Tú vendes desde tu Shopify o web y Dropana entrega y cobra.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Integración directa con tarifa plana de envío DEX Bolivia.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Gestión de novedades en tiempo real con 2 intentos de reparto.</span>
                        </li>
                    </ul>
                </div>
                <a href="#lead-magnet" class="w-full py-2.5 rounded-xl text-center text-xs font-bold border border-slate-300 text-slate-800 hover:bg-slate-50 transition block">
                    Cotizar Fulfillment
                </a>
            </div>

            <!-- Card 4: OPERADOR LOGÍSTICO & BODEGAS -->
            <div class="interactive-card bg-white rounded-3xl p-7 border border-slate-200 shadow-sm space-y-5 hover:border-amber-400 flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#E88A38]">Perfil 4</span>
                        <h4 class="text-lg font-bold text-[#960016] font-heading">OPERADOR LOGÍSTICO</h4>
                        <p class="text-xs font-semibold text-slate-700">Racks y Despacho Masivo</p>
                    </div>
                    <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Infraestructura para grandes volúmenes y contenedores.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Recepción de mercadería con órdenes de compra (PO).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-[11px]"></i>
                            <span>Control de inventario en tiempo real con alerta de quiebre.</span>
                        </li>
                    </ul>
                </div>
                <a href="#lead-magnet" class="w-full py-2.5 rounded-xl text-center text-xs font-bold border border-slate-300 text-slate-800 hover:bg-slate-50 transition block">
                    Almacén Industrial
                </a>
            </div>

        </div>
    <!-- 2.5 SECCIÓN DE VENTAJAS INTEGRALES (Replicada de dropanas.com: En Dropana vender en línea nunca fue tan fácil) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-red-50/70 via-white to-amber-50/70 rounded-3xl p-8 sm:p-12 border border-red-100 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-5 space-y-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#960016] bg-red-100/80 px-3 py-1 rounded-full border border-red-200">
                        Infraestructura 360° en Bolivia
                    </span>
                    <h3 class="text-2xl sm:text-3xl font-extrabold text-[#960016] font-heading leading-snug">
                        En Dropana vender en línea nunca fue tan fácil.
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Nuestra plataforma es la única herramienta del país que se encarga de todos los procesos logísticos de extremo a extremo: almacenaje, empaque, despacho y recaudación COD en efectivo o QR Simple.
                    </p>
                </div>

                <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-2 hover:border-amber-400 transition">
                        <div class="w-8 h-8 rounded-lg bg-red-50 text-[#ED2100] flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <h4 class="font-bold text-slate-800 text-xs">Catálogo Listo para Vender</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Productos con inventario físico verificado en Santa Cruz y El Alto listos para comercializar a nivel nacional.
                        </p>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-2 hover:border-amber-400 transition">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-[#E88A38] flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-users-viewfinder"></i>
                        </div>
                        <h4 class="font-bold text-slate-800 text-xs">Stock Garantizado & Networking</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Proveedores directos verificados con NIT para acuerdos de distribución y reposición constante de inventario.
                        </p>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-2 hover:border-amber-400 transition">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <h4 class="font-bold text-slate-800 text-xs">Empaque & Despacho 24h</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Al confirmar una orden nosotros armamos el paquete con etiquetas térmicas Zebra y lo entregamos al courier.
                        </p>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-2 hover:border-amber-400 transition">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <h4 class="font-bold text-slate-800 text-xs">Wallet & Retiros Diarios</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Dispersión automática de ganancias tras la entrega con retiros en 24 horas a BNB, BCP, Unión o Binance.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2.6 SERVICIOS PARA PROVEEDORES (Replicado de dropanas.com) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="text-center max-w-2xl mx-auto space-y-2">
            <h2 class="text-xs font-bold uppercase tracking-widest text-[#E88A38] font-heading">
                Para Importadores y Fabricantes
            </h2>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-[#960016] font-heading">
                ¿Tienes inventario en Bolivia? Escálalo con Dropana
            </h3>
            <p class="text-xs sm:text-sm text-slate-500">
                Ponemos tu mercancía frente a un ejército de vendedores digitales sin costo de operación fijo.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4 hover:border-red-400 transition">
                <div class="w-10 h-10 rounded-xl bg-red-50 text-[#960016] border border-red-200 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <h4 class="font-bold text-slate-800 text-sm">Ventas sin Gasto Publicitario</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Cientos de vendedores pautan y promocionan tus productos en Facebook, TikTok y WhatsApp asumiendo ellos el costo de los anuncios.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4 hover:border-amber-400 transition">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-[#E88A38] border border-amber-200 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <h4 class="font-bold text-slate-800 text-sm">Costo Operativo Bs 0</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Sin sueldos fijos de almaceneros, sin alquiler de galpones ni facturas de servicios. Solo abonas el costo de fulfillment por orden entregada.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4 hover:border-emerald-400 transition">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <h4 class="font-bold text-slate-800 text-sm">Liquidación Inmediata</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                    DEX recauda el total y el costo mayorista acordado se abona de inmediato en tu saldo de proveedor para transferir a tu cuenta bancaria.
                </p>
            </div>
        </div>
    </section>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="profitCalculator()">
        <div class="text-center max-w-2xl mx-auto space-y-2 mb-10">
            <h2 class="text-xs font-bold uppercase tracking-widest text-[#E88A38] font-heading">
                Simulador de Rentabilidad
            </h2>
            <h3 class="text-2xl sm:text-4xl font-extrabold text-[#960016] font-heading">
                ¿Cuánto puedes ganar con Dropana?
            </h3>
            <p class="text-xs sm:text-sm text-slate-500">
                Calcula tu ganancia real en Bolivianos ajustando precio de venta, costo y volumen mensual.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Panel Izquierdo: Inputs -->
            <div class="lg:col-span-5 bg-white rounded-3xl p-7 border border-slate-200 shadow-sm space-y-5">
                <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-[#E88A38]"></i>
                    Configura tu Producto
                </h4>

                <!-- Precio de Venta -->
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <label class="font-semibold text-slate-700">Precio de Venta (COD):</label>
                        <span class="font-black text-[#960016]" x-text="'Bs ' + salePrice"></span>
                    </div>
                    <input type="range" x-model.number="salePrice" min="50" max="1000" step="5"
                        class="w-full h-2 rounded-full appearance-none cursor-pointer accent-[#ED2100]">
                    <div class="flex justify-between text-[10px] text-slate-400">
                        <span>Bs 50</span><span>Bs 1,000</span>
                    </div>
                </div>

                <!-- Costo del Producto -->
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <label class="font-semibold text-slate-700">Costo del Producto:</label>
                        <span class="font-black text-slate-700" x-text="'Bs ' + productCost"></span>
                    </div>
                    <input type="range" x-model.number="productCost" min="10" max="500" step="5"
                        class="w-full h-2 rounded-full appearance-none cursor-pointer accent-slate-500">
                    <div class="flex justify-between text-[10px] text-slate-400">
                        <span>Bs 10</span><span>Bs 500</span>
                    </div>
                </div>

                <!-- Pedidos al Mes -->
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <label class="font-semibold text-slate-700">Pedidos al Mes:</label>
                        <span class="font-black text-[#E88A38]" x-text="orders + ' pedidos'"></span>
                    </div>
                    <input type="range" x-model.number="orders" min="10" max="500" step="10"
                        class="w-full h-2 rounded-full appearance-none cursor-pointer accent-[#E88A38]">
                    <div class="flex justify-between text-[10px] text-slate-400">
                        <span>10</span><span>500</span>
                    </div>
                </div>

                <!-- Departamento destino -->
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-700">Departamento de Entrega:</label>
                    <select x-model="selectedDept"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 focus:outline-none focus:border-[#E88A38]">
                        <template x-for="dept in depts" :key="dept.name">
                            <option :value="dept.name" x-text="dept.name + ' — Bs ' + dept.rate"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Panel Derecho: Resultados -->
            <div class="lg:col-span-7 space-y-4">
                <!-- Desglose de costos -->
                <div class="bg-white rounded-3xl p-7 border border-slate-200 shadow-sm space-y-3 text-xs">
                    <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-receipt text-[#960016]"></i>
                        Desglose por Pedido
                    </h4>
                    <div class="flex justify-between items-center py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500">Precio de Venta COD:</span>
                        <span class="font-bold text-slate-800" x-text="'+ Bs ' + salePrice.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500">(-) Costo del Producto:</span>
                        <span class="font-bold text-red-600" x-text="'- Bs ' + productCost.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500">(-) Tarifa Envío DEX:</span>
                        <span class="font-bold text-red-600" x-text="'- Bs ' + shippingRate.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500">(-) Almacenaje Dropana:</span>
                        <span class="font-bold text-emerald-600">- Bs 0.00 (Gratis)</span>
                    </div>
                    <div class="flex justify-between items-center py-2.5 px-3 rounded-xl bg-red-50 border border-red-200 mt-1">
                        <span class="font-bold text-[#960016]">Ganancia Neta por Pedido:</span>
                        <span class="font-black text-[#960016] text-sm font-mono" x-text="'Bs ' + profitPerOrder.toFixed(2)"></span>
                    </div>
                </div>

                <!-- Resultado mensual prominente -->
                <div class="rounded-3xl p-7 border-2 border-[#ED2100] bg-gradient-to-br from-[#960016] to-[#ED2100] text-white shadow-xl space-y-4">
                    <div class="text-xs font-bold uppercase tracking-widest text-red-200">Proyección Mensual</div>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="space-y-1">
                            <div class="text-2xl sm:text-3xl font-black font-mono" x-text="'Bs ' + monthlyProfit.toLocaleString('es-BO', {minimumFractionDigits:0, maximumFractionDigits:0})"></div>
                            <div class="text-[10px] text-red-200 uppercase font-bold">Ganancias Netas</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-2xl sm:text-3xl font-black font-mono" x-text="profitMarginPct + '%'"></div>
                            <div class="text-[10px] text-red-200 uppercase font-bold">Margen</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-2xl sm:text-3xl font-black font-mono" x-text="'Bs ' + annualProfit.toLocaleString('es-BO', {minimumFractionDigits:0, maximumFractionDigits:0})"></div>
                            <div class="text-[10px] text-red-200 uppercase font-bold">Proyección Anual</div>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-red-400/50 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-red-100">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Tasa de entrega estimada del <strong>85%</strong>. Almacenaje <strong class="text-white">Bs 0</strong>.
                        </p>
                        <a href="#lead-magnet" class="px-4 py-2 rounded-xl bg-white text-[#960016] text-xs font-black hover:bg-red-50 transition shadow">
                            Empezar Ahora →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. RED COD POR DEPARTAMENTO (Tarifas Planas DEX Bolivia) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto space-y-2 mb-10">
            <h2 class="text-xs font-bold uppercase tracking-widest text-[#E88A38] font-heading">
                Cobertura Nacional
            </h2>
            <h3 class="text-2xl sm:text-4xl font-extrabold text-[#960016] font-heading">
                Red COD en los 9 Departamentos de Bolivia
            </h3>
            <p class="text-xs sm:text-sm text-slate-500">
                Tarifas planas con DEX Bolivia Express. Sin sobrecargos, sin sorpresas. Cobro en efectivo o QR Simple al cliente.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">

            <!-- Santa Cruz -->
            <div class="bg-white rounded-2xl p-5 border-2 border-[#ED2100] shadow-sm relative overflow-hidden hover:shadow-lg transition">
                <div class="absolute top-3 right-3">
                    <span class="px-2 py-0.5 rounded-full bg-[#ED2100] text-white text-[9px] font-black uppercase tracking-wider">Hub Principal</span>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-[#ED2100] text-sm font-black flex-shrink-0">SC</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900">Santa Cruz</h4>
                        <p class="text-[10px] text-slate-500 mb-2">Santa Cruz de la Sierra · Plan 3000</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tarifa Urbana:</span>
                                <span class="font-black text-[#960016]">Bs 8.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Interprovincial:</span>
                                <span class="font-bold text-slate-700">Bs 15.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Intentos COD:</span>
                                <span class="font-bold text-emerald-600">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- La Paz -->
            <div class="interactive-card bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:border-amber-400">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-[#E88A38] text-sm font-black flex-shrink-0">LP</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900">La Paz</h4>
                        <p class="text-[10px] text-slate-500 mb-2">La Paz · El Alto</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tarifa Urbana:</span>
                                <span class="font-black text-[#960016]">Bs 12.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">El Alto:</span>
                                <span class="font-bold text-slate-700">Bs 15.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Intentos COD:</span>
                                <span class="font-bold text-emerald-600">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cochabamba -->
            <div class="interactive-card bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:border-amber-400">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 text-sm font-black flex-shrink-0">CB</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900">Cochabamba</h4>
                        <p class="text-[10px] text-slate-500 mb-2">Quillacollo · Sacaba · Tiquipaya</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tarifa Urbana:</span>
                                <span class="font-black text-[#960016]">Bs 10.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Interprovincial:</span>
                                <span class="font-bold text-slate-700">Bs 18.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Intentos COD:</span>
                                <span class="font-bold text-emerald-600">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Oruro -->
            <div class="interactive-card bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:border-amber-400">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 text-sm font-black flex-shrink-0">OR</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900">Oruro</h4>
                        <p class="text-[10px] text-slate-500 mb-2">Ciudad de Oruro</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tarifa Urbana:</span>
                                <span class="font-black text-[#960016]">Bs 18.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tiempo estimado:</span>
                                <span class="font-bold text-slate-700">48–72 hrs</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Intentos COD:</span>
                                <span class="font-bold text-emerald-600">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Potosí -->
            <div class="interactive-card bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:border-amber-400">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 text-sm font-black flex-shrink-0">PT</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900">Potosí</h4>
                        <p class="text-[10px] text-slate-500 mb-2">Ciudad de Potosí · Llallagua</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tarifa Urbana:</span>
                                <span class="font-black text-[#960016]">Bs 20.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tiempo estimado:</span>
                                <span class="font-bold text-slate-700">48–72 hrs</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Intentos COD:</span>
                                <span class="font-bold text-emerald-600">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chuquisaca -->
            <div class="interactive-card bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:border-amber-400">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 text-sm font-black flex-shrink-0">CH</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900">Chuquisaca</h4>
                        <p class="text-[10px] text-slate-500 mb-2">Sucre</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tarifa Urbana:</span>
                                <span class="font-black text-[#960016]">Bs 20.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tiempo estimado:</span>
                                <span class="font-bold text-slate-700">48–72 hrs</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Intentos COD:</span>
                                <span class="font-bold text-emerald-600">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarija -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:border-amber-400 hover:shadow-lg transition">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 text-sm font-black flex-shrink-0">TJ</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900">Tarija</h4>
                        <p class="text-[10px] text-slate-500 mb-2">Ciudad de Tarija · Yacuiba</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tarifa Urbana:</span>
                                <span class="font-black text-[#960016]">Bs 22.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tiempo estimado:</span>
                                <span class="font-bold text-slate-700">48–72 hrs</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Intentos COD:</span>
                                <span class="font-bold text-emerald-600">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Beni -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:border-amber-400 hover:shadow-lg transition">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 text-sm font-black flex-shrink-0">BE</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900">Beni</h4>
                        <p class="text-[10px] text-slate-500 mb-2">Trinidad · Riberalta</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tarifa Urbana:</span>
                                <span class="font-black text-[#960016]">Bs 25.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tiempo estimado:</span>
                                <span class="font-bold text-slate-700">48–96 hrs</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Intentos COD:</span>
                                <span class="font-bold text-emerald-600">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pando -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:border-amber-400 hover:shadow-lg transition">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 text-sm font-black flex-shrink-0">PA</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900">Pando</h4>
                        <p class="text-[10px] text-slate-500 mb-2">Cobija</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tarifa Urbana:</span>
                                <span class="font-black text-[#960016]">Bs 28.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tiempo estimado:</span>
                                <span class="font-bold text-slate-700">72–96 hrs</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Intentos COD:</span>
                                <span class="font-bold text-emerald-600">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Banner COD informativo -->
        <div class="rounded-2xl p-5 bg-amber-50 border border-amber-200 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-circle-info text-[#E88A38] text-xl mt-0.5"></i>
                <p class="text-xs text-slate-700 leading-relaxed max-w-2xl">
                    <strong class="text-[#960016]">Política de Devoluciones COD:</strong>
                    En caso de no entrega tras 2 intentos, el paquete regresa a bodega y se descuenta el costo de retorno al saldo del dropshipper.
                    La tasa de entrega promedio en Bolivia es del <strong>85%</strong>. El Call Center de Novedades gestiona reprogramaciones para reducir devoluciones.
                </p>
            </div>
            <a href="#lead-magnet" class="flex-shrink-0 btn-scarlet px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm">
                Activar mi Red COD →
            </a>
        </div>
    </section>

    <!-- 5. FAQ / CENTRO DE SOPORTE -->
    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ openFaq: null }">
        <div class="text-center max-w-2xl mx-auto space-y-2 mb-10">
            <h2 class="text-xs font-bold uppercase tracking-widest text-[#E88A38] font-heading">
                Centro de Soporte
            </h2>
            <h3 class="text-2xl sm:text-4xl font-extrabold text-[#960016] font-heading">
                Preguntas Frecuentes
            </h3>
            <p class="text-xs sm:text-sm text-slate-500">
                Todo lo que necesitas saber antes de empezar a vender con Dropana Bolivia.
            </p>
        </div>

        <div class="space-y-3">

            @php
            $faqs = [
                ['q' => '¿Cómo funciona el modelo de Dropshipping COD en Bolivia?',
                 'a' => 'Tú te encargas de conseguir el pedido (por redes sociales, WhatsApp, etc.) y registrarlo en el panel. Dropana Bolivia empaca, despacha el paquete con DEX Bolivia y cobra al cliente en efectivo o QR Simple al momento de la entrega. La ganancia neta llega a tu billetera virtual en un máximo de 24 horas tras la confirmación de entrega.'],
                ['q' => '¿Cuánto cuesta almacenar mis productos en la megabodega?',
                 'a' => 'El almacenaje en la megabodega de Dropana Bolivia es completamente GRATIS. No pagas renta, ni personal, ni servicios. Solo pagas la tarifa de envío DEX cuando se concreta una venta. Sin stock mínimo ni mensualidades.'],
                ['q' => '¿Cuáles son los métodos de retiro de ganancias disponibles?',
                 'a' => 'Puedes retirar tus ganancias mediante: (1) QR Simple / Código QR Bolivia a cualquier banco local, (2) Transferencia bancaria a BNB, BCP, Banco Unión, Banco Mercantil, (3) Billetera virtual Binance (USDT). El tiempo de procesamiento es de 24 horas hábiles tras solicitar el retiro desde tu panel.'],
                ['q' => '¿Qué pasa si el cliente no recibe el paquete?',
                 'a' => 'DEX Bolivia realiza hasta 2 intentos de entrega sin costo adicional. Ante el primer intento fallido, nuestro Call Center de Novedades contacta al cliente final para reprogramar la entrega. Si tras el segundo intento no se concreta, el paquete retorna a bodega y se descuenta el costo de logística de retorno de tu saldo, siguiendo la política boliviana de logística inversa.'],
                ['q' => '¿Cómo me convierto en Proveedor Mayorista de Dropana?',
                 'a' => 'Completa el formulario de afiliación en esta página seleccionando "Ser Proveedor Mayorista". Un asesor te contactará por WhatsApp para validar tu mercancía, acordar el precio mayorista, programar el ingreso de stock a bodega y configurar tu panel de proveedor con visibilidad sobre ventas, inventario y liquidaciones.'],
                ['q' => '¿Puedo usar Dropana con mi Tienda Online ya existente (Shopify, Wix, etc.)?',
                 'a' => 'Sí. Si tienes una tienda online propia, puedes usar nuestro servicio de Fulfillment: tus clientes compran en tu tienda y Dropana procesa el picking, packing, envío y cobro COD. Ofrecemos integración con Shopify, WooCommerce y cualquier plataforma vía webhook/API. Consulta tarifas en el formulario de cotización.'],
                ['q' => '¿En qué ciudades de Bolivia entregan?',
                 'a' => 'Cubrimos los 9 departamentos de Bolivia con DEX Bolivia Express. Ciudades principales: Santa Cruz de la Sierra, La Paz, El Alto, Cochabamba, Oruro, Potosí, Sucre, Tarija, Trinidad (Beni) y Cobija (Pando). Las tarifas varían por departamento y se detallan en la sección de Red COD de esta página.'],
                ['q' => '¿Cuál es el mínimo de pedidos para empezar?',
                 'a' => 'No hay mínimo. Puedes comenzar con 1 solo pedido. Sin embargo, para aprovechar al máximo la plataforma y obtener tarifas especiales, recomendamos un volumen de al menos 50 pedidos mensuales. Escala a tu ritmo, sin compromisos de volumen obligatorio.'],
            ];
            @endphp

            @foreach($faqs as $index => $faq)
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:border-amber-300 transition"
                 x-data="{ open: false }">
                <button @click="open = !open"
                    class="w-full flex items-center justify-between p-5 text-left gap-4 focus:outline-none">
                    <span class="text-sm font-bold text-slate-800 leading-snug">{{ $faq['q'] }}</span>
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-50 border border-red-200 flex items-center justify-center transition-transform"
                         :class="{ 'rotate-45': open }">
                        <i class="fa-solid fa-plus text-[#960016] text-[10px]"></i>
                    </div>
                </button>
                <div x-show="open" x-collapse>
                    <div class="px-5 pb-5 text-xs text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                        {{ $faq['a'] }}
                    </div>
                </div>
            </div>
            @endforeach

        </div>

        <!-- Botón WhatsApp soporte -->
        <div class="mt-8 text-center">
            <p class="text-xs text-slate-500 mb-3">¿No encontraste tu respuesta? Habla con un asesor en Bolivia:</p>
            <a href="https://wa.me/59171234567?text=Hola!%20Quiero%20información%20sobre%20Dropana%20Bolivia"
               target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-2.5 px-6 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold shadow-md transition">
                <i class="fa-brands fa-whatsapp text-lg"></i>
                Contactar Asesor en WhatsApp
            </a>
        </div>
    </section>

    <!-- 6. FORMULARIO "LEAD MAGNET" (Inspirado en el formulario comercial de dropanas.com) -->
    <section id="lead-magnet" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-slate-50 border border-slate-200 p-8 sm:p-12 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <div class="lg:col-span-5 space-y-4">
                <span class="text-xs font-bold uppercase tracking-wider text-[#960016] bg-red-50 border border-red-200 px-3 py-1 rounded-full">
                    Formulario de Capacitación & Afiliación
                </span>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-[#960016] font-heading">
                    Únete a la Red Dropana Bolivia
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Completa tus datos para recibir asesoría personalizada sobre cómo empezar a vender sin stock o integrar tus productos a la megabodega nacional.
                </p>
                <div class="space-y-2 pt-2 text-xs text-slate-600">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-check-circle text-emerald-600"></i>
                        <span>Sin costo de registro ni mensualidades fijas.</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-check-circle text-emerald-600"></i>
                        <span>Acceso inmediato a la red de cobro contra entrega DEX.</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-check-circle text-emerald-600"></i>
                        <span>Contacto directo vía WhatsApp con un asesor en Bolivia.</span>
                    </div>
                </div>
            </div>

            <!-- Formulario Reactivo con Alpine.js hacia /api/v1/leads -->
            <div class="lg:col-span-7 bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-md">
                <form @submit.prevent="submitLead()" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nombre Completo:</label>
                            <input type="text" x-model="form.full_name" required placeholder="Tu nombre y apellido"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">WhatsApp (+591 Bolivia):</label>
                            <input type="text" x-model="form.phone" required placeholder="Ej: +591 71234567"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Me interesa:</label>
                            <select x-model="form.interest_role" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                                <option value="dropshipper">Ser Dropshipper (Venta sin Stock)</option>
                                <option value="provider">Ser Proveedor Mayorista</option>
                                <option value="fulfillment">Fulfillment para mi Tienda</option>
                                <option value="industrial_warehouse">Almacén Industrial & Racks</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Volumen Estimado:</label>
                            <select x-model="form.estimated_monthly_volume" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                                <option value="1-50 envíos mes">1 - 50 pedidos al mes</option>
                                <option value="50-200 envíos mes">50 - 200 pedidos al mes</option>
                                <option value="+200 envíos mes">+200 pedidos al mes</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Departamento:</label>
                            <select x-model="form.department" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                                <option value="Santa Cruz">Santa Cruz</option>
                                <option value="La Paz">La Paz</option>
                                <option value="Cochabamba">Cochabamba</option>
                                <option value="Oruro">Oruro</option>
                                <option value="Potosí">Potosí</option>
                                <option value="Chuquisaca">Chuquisaca</option>
                                <option value="Tarija">Tarija</option>
                                <option value="Beni">Beni</option>
                                <option value="Pando">Pando</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Correo Electrónico (opcional):</label>
                            <input type="email" x-model="form.email" placeholder="correo@ejemplo.com"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" :disabled="submitting"
                            class="w-full btn-scarlet py-3.5 rounded-xl text-center text-sm font-bold shadow-md flex items-center justify-center gap-2">
                            <span x-show="!submitting">Recibir Información y Capacitación Ahora</span>
                            <span x-show="submitting">Enviando solicitud...</span>
                            <i class="fa-solid fa-arrow-right text-xs" x-show="!submitting"></i>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </section>

</div>

<script>
function profitCalculator() {
    return {
        salePrice: 200,
        productCost: 80,
        orders: 100,
        selectedDept: 'Santa Cruz',
        depts: [
            { name: 'Santa Cruz',   rate: 8  },
            { name: 'La Paz',       rate: 12 },
            { name: 'El Alto',      rate: 15 },
            { name: 'Cochabamba',   rate: 10 },
            { name: 'Oruro',        rate: 18 },
            { name: 'Potosí',       rate: 20 },
            { name: 'Chuquisaca',   rate: 20 },
            { name: 'Tarija',       rate: 22 },
            { name: 'Beni',         rate: 25 },
            { name: 'Pando',        rate: 28 },
        ],
        get shippingRate() {
            const d = this.depts.find(d => d.name === this.selectedDept);
            return d ? d.rate : 8;
        },
        get profitPerOrder() {
            return Math.max(0, this.salePrice - this.productCost - this.shippingRate);
        },
        get monthlyProfit() {
            // 85% tasa de entrega efectiva
            return Math.round(this.profitPerOrder * this.orders * 0.85);
        },
        get annualProfit() {
            return this.monthlyProfit * 12;
        },
        get profitMarginPct() {
            if (this.salePrice === 0) return 0;
            return Math.round((this.profitPerOrder / this.salePrice) * 100);
        }
    }
}

function landingLeadMagnet() {
    return {
        submitting: false,
        toast: { show: false, message: '', type: 'success' },
        form: {
            full_name: '',
            phone: '+591 ',
            email: '',
            interest_role: 'dropshipper',
            estimated_monthly_volume: '1-50 envíos mes',
            department: 'Santa Cruz',
            city: 'Santa Cruz de la Sierra'
        },

        async submitLead() {
            this.submitting = true;
            try {
                const response = await fetch('/api/v1/leads', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    this.showToast(data.message, 'success');
                    this.form.full_name = '';
                    this.form.phone = '+591 ';
                    this.form.email = '';
                } else {
                    this.showToast(data.message || 'Error al enviar el formulario.', 'error');
                }
            } catch (err) {
                this.showToast('Error de conexión con el servidor.', 'error');
            } finally {
                this.submitting = false;
            }
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 5000);
        }
    }
}
</script>
@endsection
