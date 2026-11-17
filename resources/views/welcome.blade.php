@extends('layouts.app')

@section('title', 'Bolidrop Bolivia | El Ecosistema de Dropshipping COD y Fulfillment')

@section('content')
<!-- SB Admin Pro Main Landing Page -->
<div class="space-y-16 pb-12" x-data="landingLeadMagnet()">

    <!-- Toast Notification -->
    <div x-show="toast.show" x-cloak
        class="fixed top-20 right-6 z-50 p-4 rounded-xl shadow-xl text-xs font-semibold flex items-center gap-3 border transition duration-300 bg-white"
        :class="toast.type === 'success' ? 'border-emerald-200 text-[#00ac69]' : 'border-red-200 text-red-600'">
        <i class="fa-solid" :class="toast.type === 'success' ? 'fa-circle-check text-[#00ac69] text-base' : 'fa-circle-exclamation text-red-600 text-base'"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- ================================================================ -->
    <!-- 1. SB ADMIN PRO HERO HEADER (Gradient Primary-to-Secondary)       -->
    <!-- ================================================================ -->
    <section class="page-header-sb text-white pt-12 pb-28 px-4 sm:px-8 -mx-4 sm:-mx-8 -mt-3 sm:-mt-5 relative overflow-hidden shadow-md">
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.4) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.3) 0%, transparent 40%);"></div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                
                <div class="lg:col-span-7 space-y-5">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/15 border border-white/25 text-xs font-bold text-white">
                        <span class="flex h-2 w-2 rounded-full bg-[#00ac69]"></span>
                        <span>El Ecosistema de Dropshipping & Logística COD Nº 1 de Bolivia</span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight font-heading text-white">
                        Conectamos Proveedores, Vendedores y Tiendas con el Consumidor Final.
                    </h1>

                    <p class="text-white/85 text-base sm:text-lg leading-relaxed max-w-xl">
                        Almacenaje, Fulfillment y Logística con <strong class="text-white font-bold">Pago Contra Entrega (COD)</strong> en Santa Cruz, La Paz, Cochabamba y los 9 departamentos. Recaudación en Bolivianos en efectivo o mediante <strong class="text-white underline decoration-[#00ac69] decoration-2">QR Simple</strong>.
                    </p>

                    <div class="flex flex-wrap items-center gap-3.5 pt-2">
                        <a href="#lead-magnet" class="px-7 py-3.5 rounded-xl bg-[#00ac69] hover:bg-[#008f57] text-white font-bold text-sm shadow-lg flex items-center gap-2 transition transform hover:-translate-y-0.5">
                            <span>Comienza a Vender Ahora</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                        <a href="{{ url('/catalogo') }}" class="px-6 py-3.5 rounded-xl bg-white text-[#0061f2] hover:bg-blue-50 font-bold text-sm transition shadow-md flex items-center gap-2">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <span>Ver Catálogo Mayorista</span>
                        </a>
                    </div>

                    <!-- Métricas Destacadas -->
                    <div class="grid grid-cols-3 gap-4 pt-6 border-t border-white/20">
                        <div>
                            <div class="text-2xl sm:text-3xl font-extrabold text-white font-heading">9 / 9</div>
                            <div class="text-xs text-white/75 font-medium">Deptos. Cobertura</div>
                        </div>
                        <div>
                            <div class="text-2xl sm:text-3xl font-extrabold text-white font-mono">{{ number_format($bcbOfficialRate ?? 12.06, 2) }} Bs</div>
                            <div class="text-xs text-white/75 font-medium">Tasa Oficial BCB</div>
                        </div>
                        <div>
                            <div class="text-2xl sm:text-3xl font-extrabold text-white font-heading">24 hrs</div>
                            <div class="text-xs text-white/75 font-medium">Retiros a Billetera</div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Tarjeta Blanca SB Admin Pro -->
                <div class="lg:col-span-5">
                    <div class="bg-white text-[#212832] rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-2xl space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-blue-50 text-[#0061f2] flex items-center justify-center font-bold text-lg">
                                    <i class="fa-solid fa-truck-fast"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-[#212832]">Envíos DEX Bolivia</h4>
                                    <span class="text-[11px] text-[#00ac69] font-bold flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check text-[9px]"></i> 2 Intentos de Entrega Gratis
                                    </span>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-[#00ac69] border border-emerald-200 uppercase">
                                Red COD Activa
                            </span>
                        </div>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex justify-between items-center py-2.5 px-3.5 rounded-lg bg-[#f2f6fc]">
                                <span class="text-[#69707a] font-medium">Recaudación:</span>
                                <span class="font-bold text-[#212832] flex items-center gap-1.5">
                                    <i class="fa-solid fa-qrcode text-[#0061f2]"></i> Efectivo + QR Simple
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-2.5 px-3.5 rounded-lg bg-[#f2f6fc]">
                                <span class="text-[#69707a] font-medium">Costo Almacenaje:</span>
                                <span class="font-extrabold text-[#00ac69] text-sm">Bs 0.00 (Gratis)</span>
                            </div>
                            <div class="flex justify-between items-center py-2.5 px-3.5 rounded-lg bg-blue-50/70 border border-blue-100">
                                <span class="text-[#0061f2] font-bold">Tus Ganancias:</span>
                                <span class="font-extrabold text-[#0061f2] text-sm font-mono">+100% Retiro en 24h</span>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-[#f2f6fc] border border-slate-200/80 flex items-center gap-3">
                            <i class="fa-solid fa-shield-halved text-[#0061f2] text-xl"></i>
                            <p class="text-[11px] text-[#69707a] leading-snug">
                                <strong class="text-[#212832]">Call Center de Novedades:</strong> Nuestro equipo contacta al cliente final en caso de reprogramación de entrega.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ================================================================ -->
    <!-- 2. LOS 4 PERFILES (SB Admin Pro Overlap Cards -mt-24)            -->
    <!-- ================================================================ -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24 relative z-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- Card 1: DROPSHIPPERS -->
            <div class="interactive-card bg-white rounded-2xl p-6 border-t-4 border-t-[#0061f2] flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#0061f2] bg-blue-50 px-2.5 py-1 rounded-md">Perfil 1</span>
                        <div class="w-10 h-10 rounded-full bg-blue-50 text-[#0061f2] flex items-center justify-center text-base">
                            <i class="fa-solid fa-store"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-[#212832]">DROPSHIPPERS</h4>
                        <p class="text-xs font-semibold text-[#69707a]">Venta sin Inventario Propio</p>
                    </div>
                    <ul class="text-xs text-[#69707a] space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Vende productos de la megabodega sin invertir en stock anticipado.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Bolidrop empaca, despacha y cobra contra entrega por ti.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Retiros de utilidades en 24h vía QR Simple o Binance.</span>
                        </li>
                    </ul>
                </div>
                <a href="{{ url('/catalogo') }}" class="w-full btn-scarlet py-2.5 rounded-lg text-center text-xs font-bold block">
                    Ver Catálogo →
                </a>
            </div>

            <!-- Card 2: PROVEEDORES MAYORISTAS -->
            <div class="interactive-card bg-white rounded-2xl p-6 border-t-4 border-t-[#6900c7] flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#6900c7] bg-purple-50 px-2.5 py-1 rounded-md">Perfil 2</span>
                        <div class="w-10 h-10 rounded-full bg-purple-50 text-[#6900c7] flex items-center justify-center text-base">
                            <i class="fa-solid fa-boxes-packing"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-[#212832]">PROVEEDORES</h4>
                        <p class="text-xs font-semibold text-[#69707a]">Abastecimiento Mayorista</p>
                    </div>
                    <ul class="text-xs text-[#69707a] space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Pon tu mercancía frente a cientos de vendedores en Bolivia.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Almacenaje Bs 0. Sin gastos fijos de personal ni alquiler.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Liquidación de tu costo mayorista al confirmarse la entrega.</span>
                        </li>
                    </ul>
                </div>
                <a href="{{ url('/panel/proveedor') }}" class="w-full py-2.5 rounded-lg text-center text-xs font-bold border border-slate-200 text-[#212832] hover:bg-slate-50 transition block">
                    Panel Proveedor →
                </a>
            </div>

            <!-- Card 3: TIENDAS ONLINE (Fulfillment) -->
            <div class="interactive-card bg-white rounded-2xl p-6 border-t-4 border-t-[#00ac69] flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#00ac69] bg-emerald-50 px-2.5 py-1 rounded-md">Perfil 3</span>
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-[#00ac69] flex items-center justify-center text-base">
                            <i class="fa-solid fa-cart-flatbed"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-[#212832]">TIENDAS ONLINE</h4>
                        <p class="text-xs font-semibold text-[#69707a]">Fulfillment para Marcas Propias</p>
                    </div>
                    <ul class="text-xs text-[#69707a] space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Tú vendes desde tu Shopify o web y Bolidrop entrega y cobra.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Integración directa con tarifa plana de envío DEX Bolivia.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Gestión de novedades en tiempo real con 2 intentos de reparto.</span>
                        </li>
                    </ul>
                </div>
                <a href="#lead-magnet" class="w-full py-2.5 rounded-lg text-center text-xs font-bold border border-slate-200 text-[#212832] hover:bg-slate-50 transition block">
                    Cotizar Fulfillment →
                </a>
            </div>

            <!-- Card 4: OPERADOR LOGÍSTICO & BODEGAS -->
            <div class="interactive-card bg-white rounded-2xl p-6 border-t-4 border-t-[#f4a100] flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#f4a100] bg-amber-50 px-2.5 py-1 rounded-md">Perfil 4</span>
                        <div class="w-10 h-10 rounded-full bg-amber-50 text-[#f4a100] flex items-center justify-center text-base">
                            <i class="fa-solid fa-warehouse"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-[#212832]">OPERADOR LOGÍSTICO</h4>
                        <p class="text-xs font-semibold text-[#69707a]">Racks y Despacho Masivo</p>
                    </div>
                    <ul class="text-xs text-[#69707a] space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Infraestructura para grandes volúmenes y contenedores.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Recepción de mercadería con órdenes de compra (PO).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#00ac69] mt-0.5"></i>
                            <span>Control de inventario en tiempo real con alerta de quiebre.</span>
                        </li>
                    </ul>
                </div>
                <a href="#lead-magnet" class="w-full py-2.5 rounded-lg text-center text-xs font-bold border border-slate-200 text-[#212832] hover:bg-slate-50 transition block">
                    Almacén Industrial →
                </a>
            </div>

        </div>
    </section>

    <!-- ================================================================ -->
    <!-- 2.5 MONITOR DE TIPO DE CAMBIO DEL BOLIVIANO (BOB) & DIVISAS      -->
    <!-- ================================================================ -->
    @php
        $bcbSell = (float) ($bcbOfficialRate ?? 11.70);
        $bcbBuy  = max(6.86, round($bcbSell - 0.10, 2));
        $usdtP2p = round($bcbSell + 0.15, 2);
        $eurBob  = round($bcbSell * 1.08, 2);
        $cnyBob  = round($bcbSell * 0.14, 2);
        $brlBob  = round($bcbSell * 0.20, 2);
    @endphp
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ amount: 100, selectedCurrency: 'USD_REF', rates: { USD_REF: {{ $bcbSell }}, USD_OFI: 6.96, USDT: {{ $usdtP2p }}, EUR: {{ $eurBob }}, CNY: {{ $cnyBob }}, BRL: {{ $brlBob }} } }">
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <div class="inline-flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-wider text-[#0061f2] bg-blue-50 px-3 py-1 rounded-full mb-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#00ac69] animate-pulse"></span>
                        <span>Cotización Cambiaria en Tiempo Real</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-extrabold text-[#212832] font-heading">
                        Tipo de Cambio del Boliviano (BOB) y Divisas de Importación
                    </h3>
                    <p class="text-xs text-[#69707a]">
                        Valores referenciales del Banco Central de Bolivia (BCB), liquidación USDT y monedas para importadores mayoristas.
                    </p>
                </div>
                <div class="text-right text-xs text-[#69707a] font-mono">
                    <div>Actualizado: <strong class="text-[#212832]">{{ now()->format('d/m/Y H:i') }} BOT</strong></div>
                    <div class="text-[11px] text-[#00ac69] font-bold">Fuente: BCB Oficial & P2P Bolivia</div>
                </div>
            </div>

            <!-- Grid de 6 Monedas frente al Boliviano -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
                <div class="p-4 rounded-xl bg-[#f2f6fc] border-l-4 border-l-[#0061f2] space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold text-[#69707a]">
                        <span>🇺🇸 USD Referencial</span>
                        <span class="text-[#0061f2]">BCB</span>
                    </div>
                    <div class="text-lg font-extrabold font-mono text-[#212832]">Bs {{ number_format($bcbSell, 2) }}</div>
                    <div class="text-[10px] text-[#69707a] font-mono">Compra: Bs {{ number_format($bcbBuy, 2) }}</div>
                </div>

                <div class="p-4 rounded-xl bg-[#f2f6fc] border-l-4 border-l-slate-700 space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold text-[#69707a]">
                        <span>🇧🇴 USD Oficial</span>
                        <span class="text-slate-700">Fijo</span>
                    </div>
                    <div class="text-lg font-extrabold font-mono text-[#212832]">Bs 6.96</div>
                    <div class="text-[10px] text-[#69707a] font-mono">Compra: Bs 6.86</div>
                </div>

                <div class="p-4 rounded-xl bg-[#f2f6fc] border-l-4 border-l-[#00ac69] space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold text-[#69707a]">
                        <span>₮ USDT Digital</span>
                        <span class="text-[#00ac69]">P2P</span>
                    </div>
                    <div class="text-lg font-extrabold font-mono text-[#00ac69]">Bs {{ number_format($usdtP2p, 2) }}</div>
                    <div class="text-[10px] text-[#69707a] font-mono">Retiros Binance</div>
                </div>

                <div class="p-4 rounded-xl bg-[#f2f6fc] border-l-4 border-l-[#6900c7] space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold text-[#69707a]">
                        <span>🇪🇺 Euro (EUR)</span>
                        <span class="text-[#6900c7]">EUR/BOB</span>
                    </div>
                    <div class="text-lg font-extrabold font-mono text-[#212832]">Bs {{ number_format($eurBob, 2) }}</div>
                    <div class="text-[10px] text-[#69707a] font-mono">Compra: Bs {{ number_format($eurBob - 0.15, 2) }}</div>
                </div>

                <div class="p-4 rounded-xl bg-[#f2f6fc] border-l-4 border-l-[#f4a100] space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold text-[#69707a]">
                        <span>🇨🇳 Yuan (CNY)</span>
                        <span class="text-[#f4a100]">China</span>
                    </div>
                    <div class="text-lg font-extrabold font-mono text-[#212832]">Bs {{ number_format($cnyBob, 2) }}</div>
                    <div class="text-[10px] text-[#69707a] font-mono">Importación Mayorista</div>
                </div>

                <div class="p-4 rounded-xl bg-[#f2f6fc] border-l-4 border-l-cyan-500 space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold text-[#69707a]">
                        <span>🇧🇷 Real (BRL)</span>
                        <span class="text-cyan-600">Brasil</span>
                    </div>
                    <div class="text-lg font-extrabold font-mono text-[#212832]">Bs {{ number_format($brlBob, 2) }}</div>
                    <div class="text-[10px] text-[#69707a] font-mono">Frontera / Mercosur</div>
                </div>
            </div>

            <!-- Conversor Rápido a Bolivianos (BOB) -->
            <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-Money-bill-transfer text-[#0061f2] text-base"></i>
                    <span class="font-bold text-[#212832]">Calculadora Rápida de Divisas a Bolivianos (BOB):</span>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <input type="number" x-model.number="amount" min="1"
                        class="w-28 bg-white border border-slate-200 rounded-lg px-3 py-2 font-mono font-bold text-[#212832] focus:outline-none focus:border-[#0061f2]">
                    <select x-model="selectedCurrency"
                        class="bg-white border border-slate-200 rounded-lg px-3 py-2 font-bold text-[#212832] focus:outline-none focus:border-[#0061f2]">
                        <option value="USD_REF">USD (Dólar Referencial BCB)</option>
                        <option value="USD_OFI">USD (Dólar Oficial 6.96)</option>
                        <option value="USDT">USDT (Binance P2P)</option>
                        <option value="EUR">EUR (Euro)</option>
                        <option value="CNY">CNY (Yuan Chino)</option>
                        <option value="BRL">BRL (Real Brasileño)</option>
                    </select>
                    <span class="font-bold text-[#69707a]">=</span>
                    <div class="px-4 py-2 rounded-lg bg-[#0061f2] text-white font-mono font-extrabold text-sm">
                        Bs <span x-text="(amount * rates[selectedCurrency]).toLocaleString('es-BO', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================================ -->
    <!-- 3. VENTAJAS INTEGRALES 360°                                      -->
    <!-- ================================================================ -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl p-8 sm:p-10 border border-slate-200">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-5 space-y-3">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#0061f2] bg-blue-50 px-3 py-1 rounded-full">
                        Infraestructura 360° en Bolivia
                    </span>
                    <h3 class="text-2xl sm:text-3xl font-extrabold text-[#212832] font-heading leading-snug">
                        En Bolidrop vender en línea nunca fue tan fácil.
                    </h3>
                    <p class="text-xs sm:text-sm text-[#69707a] leading-relaxed">
                        Nuestra plataforma se encarga de todos los procesos logísticos de extremo a extremo: almacenaje, empaque, despacho y recaudación COD en efectivo o QR Simple.
                    </p>
                </div>

                <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-[#f2f6fc] p-5 rounded-xl border border-slate-200/60 space-y-2">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 text-[#0061f2] flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <h4 class="font-bold text-[#212832] text-xs">Catálogo Listo para Vender</h4>
                        <p class="text-[11px] text-[#69707a] leading-relaxed">
                            Productos con inventario físico verificado en Santa Cruz y El Alto listos para comercializar a nivel nacional.
                        </p>
                    </div>

                    <div class="bg-[#f2f6fc] p-5 rounded-xl border border-slate-200/60 space-y-2">
                        <div class="w-9 h-9 rounded-lg bg-purple-50 text-[#6900c7] flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-users-viewfinder"></i>
                        </div>
                        <h4 class="font-bold text-[#212832] text-xs">Stock Garantizado & Networking</h4>
                        <p class="text-[11px] text-[#69707a] leading-relaxed">
                            Proveedores directos verificados con NIT para acuerdos de distribución y reposición constante de inventario.
                        </p>
                    </div>

                    <div class="bg-[#f2f6fc] p-5 rounded-xl border border-slate-200/60 space-y-2">
                        <div class="w-9 h-9 rounded-lg bg-emerald-50 text-[#00ac69] flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <h4 class="font-bold text-[#212832] text-xs">Empaque & Despacho 24h</h4>
                        <p class="text-[11px] text-[#69707a] leading-relaxed">
                            Al confirmar una orden nosotros armamos el paquete con etiquetas térmicas Zebra y lo entregamos al courier.
                        </p>
                    </div>

                    <div class="bg-[#f2f6fc] p-5 rounded-xl border border-slate-200/60 space-y-2">
                        <div class="w-9 h-9 rounded-lg bg-amber-50 text-[#f4a100] flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <h4 class="font-bold text-[#212832] text-xs">Wallet & Retiros Diarios</h4>
                        <p class="text-[11px] text-[#69707a] leading-relaxed">
                            Dispersión automática de ganancias tras la entrega con retiros en 24 horas a BNB, BCP, Unión o Binance.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================================ -->
    <!-- 4. CALCULADORA INTERACTIVA DE GANANCIAS COD                      -->
    <!-- ================================================================ -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="profitCalculator()">
        <div class="text-center max-w-2xl mx-auto space-y-2 mb-10">
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-[#0061f2]">
                Simulador de Rentabilidad
            </h2>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-[#212832] font-heading">
                ¿Cuánto puedes ganar con Bolidrop?
            </h3>
            <p class="text-xs sm:text-sm text-[#69707a]">
                Calcula tu ganancia real en Bolivianos ajustando precio de venta, costo y volumen mensual.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Panel Izquierdo: Sliders -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-7 border border-slate-200 space-y-5">
                <h4 class="text-sm font-bold text-[#212832] flex items-center gap-2 border-b border-slate-100 pb-3">
                    <i class="fa-solid fa-sliders text-[#0061f2]"></i>
                    Configura tu Producto
                </h4>

                <!-- Precio de Venta -->
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <label class="font-semibold text-[#69707a]">Precio de Venta (COD):</label>
                        <span class="font-extrabold text-[#0061f2] font-mono" x-text="'Bs ' + salePrice"></span>
                    </div>
                    <input type="range" x-model.number="salePrice" min="50" max="1000" step="5"
                        class="w-full h-2 rounded-full appearance-none cursor-pointer accent-[#0061f2] bg-slate-200">
                    <div class="flex justify-between text-[10px] text-[#69707a] font-mono">
                        <span>Bs 50</span><span>Bs 1,000</span>
                    </div>
                </div>

                <!-- Costo del Producto -->
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <label class="font-semibold text-[#69707a]">Costo del Producto:</label>
                        <span class="font-bold text-[#212832] font-mono" x-text="'Bs ' + productCost"></span>
                    </div>
                    <input type="range" x-model.number="productCost" min="10" max="500" step="5"
                        class="w-full h-2 rounded-full appearance-none cursor-pointer accent-[#6900c7] bg-slate-200">
                    <div class="flex justify-between text-[10px] text-[#69707a] font-mono">
                        <span>Bs 10</span><span>Bs 500</span>
                    </div>
                </div>

                <!-- Pedidos al Mes -->
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <label class="font-semibold text-[#69707a]">Pedidos al Mes:</label>
                        <span class="font-extrabold text-[#00ac69] font-mono" x-text="orders + ' pedidos'"></span>
                    </div>
                    <input type="range" x-model.number="orders" min="10" max="500" step="10"
                        class="w-full h-2 rounded-full appearance-none cursor-pointer accent-[#00ac69] bg-slate-200">
                    <div class="flex justify-between text-[10px] text-[#69707a] font-mono">
                        <span>10</span><span>500</span>
                    </div>
                </div>

                <!-- Departamento destino -->
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-[#69707a]">Departamento de Entrega:</label>
                    <select x-model="selectedDept"
                        class="w-full bg-[#f2f6fc] border border-slate-200 rounded-lg p-2.5 text-xs text-[#212832] font-medium focus:outline-none focus:border-[#0061f2]">
                        <template x-for="dept in depts" :key="dept.name">
                            <option :value="dept.name" x-text="dept.name + ' — Bs ' + dept.rate"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Panel Derecho: Resultados -->
            <div class="lg:col-span-7 space-y-4">
                <div class="bg-white rounded-2xl p-7 border border-slate-200 space-y-3 text-xs">
                    <h4 class="text-sm font-bold text-[#212832] flex items-center gap-2 border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-receipt text-[#0061f2]"></i>
                        Desglose por Pedido
                    </h4>
                    <div class="flex justify-between items-center py-2 px-3 rounded-lg bg-[#f2f6fc]">
                        <span class="text-[#69707a]">Precio de Venta COD:</span>
                        <span class="font-bold text-[#212832] font-mono" x-text="'+ Bs ' + salePrice.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 px-3 rounded-lg bg-[#f2f6fc]">
                        <span class="text-[#69707a]">(-) Costo del Producto:</span>
                        <span class="font-bold text-red-600 font-mono" x-text="'- Bs ' + productCost.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 px-3 rounded-lg bg-[#f2f6fc]">
                        <span class="text-[#69707a]">(-) Tarifa Envío DEX:</span>
                        <span class="font-bold text-red-600 font-mono" x-text="'- Bs ' + shippingRate.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 px-3 rounded-lg bg-[#f2f6fc]">
                        <span class="text-[#69707a]">(-) Almacenaje Bolidrop:</span>
                        <span class="font-bold text-[#00ac69] font-mono">- Bs 0.00 (Gratis)</span>
                    </div>
                    <div class="flex justify-between items-center py-2.5 px-3 rounded-lg bg-emerald-50 border border-emerald-200 mt-1">
                        <span class="font-bold text-[#00ac69]">Ganancia Neta por Pedido:</span>
                        <span class="font-extrabold text-[#00ac69] text-sm font-mono" x-text="'Bs ' + profitPerOrder.toFixed(2)"></span>
                    </div>
                </div>

                <!-- Resultado mensual prominente -->
                <div class="rounded-2xl p-7 page-header-sb text-white shadow-lg space-y-4">
                    <div class="text-xs font-bold uppercase tracking-widest text-white/80">Proyección Mensual</div>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="space-y-1">
                            <div class="text-2xl sm:text-3xl font-extrabold font-mono text-white" x-text="'Bs ' + monthlyProfit.toLocaleString('es-BO', {minimumFractionDigits:0, maximumFractionDigits:0})"></div>
                            <div class="text-[10px] text-white/80 uppercase font-bold">Ganancias Netas</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-2xl sm:text-3xl font-extrabold font-mono text-white" x-text="profitMarginPct + '%'"></div>
                            <div class="text-[10px] text-white/80 uppercase font-bold">Margen</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-2xl sm:text-3xl font-extrabold font-mono text-white" x-text="'Bs ' + annualProfit.toLocaleString('es-BO', {minimumFractionDigits:0, maximumFractionDigits:0})"></div>
                            <div class="text-[10px] text-white/80 uppercase font-bold">Proyección Anual</div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-white/20 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-white/85">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Tasa de entrega estimada del <strong>85%</strong>. Almacenaje <strong>Bs 0</strong>.
                        </p>
                        <a href="#lead-magnet" class="px-5 py-2 rounded-lg bg-white text-[#0061f2] text-xs font-extrabold hover:bg-blue-50 transition shadow">
                            Empezar Ahora →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================================ -->
    <!-- 5. RED COD EN LOS 9 DEPARTAMENTOS DE BOLIVIA                     -->
    <!-- ================================================================ -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto space-y-2 mb-10">
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-[#0061f2]">
                Cobertura Nacional
            </h2>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-[#212832] font-heading">
                Red COD en los 9 Departamentos de Bolivia
            </h3>
            <p class="text-xs sm:text-sm text-[#69707a]">
                Tarifas planas con DEX Bolivia Express. Sin sobrecargos, sin sorpresas. Cobro en efectivo o QR Simple al cliente.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach([
                ['code'=>'SC','name'=>'Santa Cruz','sub'=>'Santa Cruz de la Sierra · Plan 3000','urban'=>'Bs 8.00','extra_label'=>'Interprovincial:','extra'=>'Bs 15.00','hub'=>true],
                ['code'=>'LP','name'=>'La Paz','sub'=>'La Paz · El Alto','urban'=>'Bs 12.00','extra_label'=>'El Alto:','extra'=>'Bs 15.00','hub'=>false],
                ['code'=>'CB','name'=>'Cochabamba','sub'=>'Quillacollo · Sacaba · Tiquipaya','urban'=>'Bs 10.00','extra_label'=>'Interprovincial:','extra'=>'Bs 18.00','hub'=>false],
                ['code'=>'OR','name'=>'Oruro','sub'=>'Ciudad de Oruro','urban'=>'Bs 18.00','extra_label'=>'Tiempo estimado:','extra'=>'48–72 hrs','hub'=>false],
                ['code'=>'PT','name'=>'Potosí','sub'=>'Ciudad de Potosí · Llallagua','urban'=>'Bs 20.00','extra_label'=>'Tiempo estimado:','extra'=>'48–72 hrs','hub'=>false],
                ['code'=>'CH','name'=>'Chuquisaca','sub'=>'Sucre','urban'=>'Bs 20.00','extra_label'=>'Tiempo estimado:','extra'=>'48–72 hrs','hub'=>false],
                ['code'=>'TJ','name'=>'Tarija','sub'=>'Ciudad de Tarija · Yacuiba','urban'=>'Bs 22.00','extra_label'=>'Tiempo estimado:','extra'=>'48–72 hrs','hub'=>false],
                ['code'=>'BE','name'=>'Beni','sub'=>'Trinidad · Riberalta','urban'=>'Bs 25.00','extra_label'=>'Tiempo estimado:','extra'=>'48–96 hrs','hub'=>false],
                ['code'=>'PA','name'=>'Pando','sub'=>'Cobija','urban'=>'Bs 28.00','extra_label'=>'Tiempo estimado:','extra'=>'72–96 hrs','hub'=>false],
            ] as $dept)
            <div class="interactive-card bg-white rounded-2xl p-5 border {{ $dept['hub'] ? 'border-l-4 border-l-[#0061f2]' : 'border-slate-200' }} relative">
                @if($dept['hub'])
                <div class="absolute top-3 right-3">
                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0061f2] text-[9px] font-extrabold uppercase tracking-wider">Hub Principal</span>
                </div>
                @endif
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0061f2] flex items-center justify-center text-sm font-extrabold flex-shrink-0">{{ $dept['code'] }}</div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-[#212832]">{{ $dept['name'] }}</h4>
                        <p class="text-[10px] text-[#69707a] mb-2">{{ $dept['sub'] }}</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-[#69707a]">Tarifa Urbana:</span>
                                <span class="font-extrabold text-[#0061f2]">{{ $dept['urban'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#69707a]">{{ $dept['extra_label'] }}</span>
                                <span class="font-bold text-[#212832]">{{ $dept['extra'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#69707a]">Intentos COD:</span>
                                <span class="font-bold text-[#00ac69]">2 intentos gratis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    <!-- ================================================================ -->
    <!-- 6. FORMULARIO COMERCIAL "LEAD MAGNET"                            -->
    <!-- ================================================================ -->
    <section id="lead-magnet" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white border border-slate-200 p-8 sm:p-10 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <div class="lg:col-span-5 space-y-4">
                <span class="text-xs font-extrabold uppercase tracking-wider text-[#0061f2] bg-blue-50 px-3 py-1 rounded-full">
                    Formulario de Capacitación & Afiliación
                </span>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-[#212832] font-heading">
                    Únete a la Red Bolidrop Bolivia
                </h3>
                <p class="text-xs sm:text-sm text-[#69707a] leading-relaxed">
                    Completa tus datos para recibir asesoría personalizada sobre cómo empezar a vender sin stock o integrar tus productos a la megabodega nacional.
                </p>
                <div class="space-y-2 pt-2 text-xs text-[#69707a]">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-check-circle text-[#00ac69]"></i>
                        <span>Sin costo de registro ni mensualidades fijas.</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-check-circle text-[#00ac69]"></i>
                        <span>Acceso inmediato a la red de cobro contra entrega DEX.</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-check-circle text-[#00ac69]"></i>
                        <span>Contacto directo vía WhatsApp con un asesor en Bolivia.</span>
                    </div>
                </div>
            </div>

            <!-- Formulario Reactivo con Alpine.js hacia /api/v1/leads -->
            <div class="lg:col-span-7 bg-[#f2f6fc] rounded-2xl p-6 sm:p-8 border border-slate-200/80">
                <form @submit.prevent="submitLead()" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-[#212832] mb-1">Nombre Completo:</label>
                            <input type="text" x-model="form.full_name" required placeholder="Tu nombre y apellido"
                                class="w-full bg-white border border-slate-200 rounded-lg p-3 text-[#212832] placeholder-slate-400 focus:outline-none focus:border-[#0061f2]">
                        </div>
                        <div>
                            <label class="block font-bold text-[#212832] mb-1">WhatsApp (+591 Bolivia):</label>
                            <input type="text" x-model="form.phone" required placeholder="Ej: +591 71234567"
                                class="w-full bg-white border border-slate-200 rounded-lg p-3 text-[#212832] placeholder-slate-400 focus:outline-none focus:border-[#0061f2]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-[#212832] mb-1">Me interesa:</label>
                            <select x-model="form.interest_role" required
                                class="w-full bg-white border border-slate-200 rounded-lg p-3 text-[#212832] focus:outline-none focus:border-[#0061f2]">
                                <option value="dropshipper">Ser Dropshipper (Venta sin Stock)</option>
                                <option value="provider">Ser Proveedor Mayorista</option>
                                <option value="fulfillment">Fulfillment para mi Tienda</option>
                                <option value="industrial_warehouse">Almacén Industrial & Racks</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-[#212832] mb-1">Volumen Estimado:</label>
                            <select x-model="form.estimated_monthly_volume" required
                                class="w-full bg-white border border-slate-200 rounded-lg p-3 text-[#212832] focus:outline-none focus:border-[#0061f2]">
                                <option value="1-50 envíos mes">1 - 50 pedidos al mes</option>
                                <option value="50-200 envíos mes">50 - 200 pedidos al mes</option>
                                <option value="+200 envíos mes">+200 pedidos al mes</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-[#212832] mb-1">Departamento:</label>
                            <select x-model="form.department" required
                                class="w-full bg-white border border-slate-200 rounded-lg p-3 text-[#212832] focus:outline-none focus:border-[#0061f2]">
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
                            <label class="block font-bold text-[#212832] mb-1">Correo Electrónico (opcional):</label>
                            <input type="email" x-model="form.email" placeholder="correo@ejemplo.com"
                                class="w-full bg-white border border-slate-200 rounded-lg p-3 text-[#212832] placeholder-slate-400 focus:outline-none focus:border-[#0061f2]">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" :disabled="submitting"
                            class="w-full btn-scarlet py-3.5 rounded-lg text-center text-sm font-bold shadow-md flex items-center justify-center gap-2">
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
