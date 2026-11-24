@extends('layouts.app')

@section('title', 'DEX Bolivia Express — Reportes & Métricas COD')

@section('content')
<div class="space-y-6 pb-12" x-data="carrierReportApp()">

    <!-- Toast Notification -->
    <div x-show="toast.show" x-cloak
        x-transition
        class="fixed bottom-6 right-6 z-50 px-5 py-3.5 rounded-2xl shadow-xl text-sm font-semibold flex items-center gap-3 transition"
        :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-[#0061f2] text-white'">
        <i class="fa-solid" :class="toast.type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- Page Header -->
    <x-page-header
        title="DEX Bolivia Express — Reportes & Métricas COD"
        subtitle="Auditoría de recaudación en calle (Efectivo / QR), arqueo de caja diario, movimientos económicos por proveedor y efectividad de entrega."
        icon="fa-solid fa-chart-pie"
        badge="Centro Financiero & Arqueo COD">
        <x-slot:actions>
            <a href="{{ route('carrier.reportes.export') }}"
                :href="exportUrl"
                class="px-4 py-2.5 rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-white font-bold text-xs inline-flex items-center gap-2 shadow-md transition cursor-pointer">
                <i class="fa-solid fa-file-excel text-amber-200"></i> Exportar a Excel (CSV)
            </a>
            <a href="{{ route('panel.carrier') }}"
                class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-xs inline-flex items-center gap-2 backdrop-blur-sm transition">
                <i class="fa-solid fa-truck-fast text-sky-300"></i> Volver a Hoja de Ruta
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 4 Métricas Clave de Recaudación (Executive Overlap) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 !-mt-14 relative z-10">
        <!-- 1. Total Recaudación COD -->
        <div class="bg-gradient-to-br from-[#0F172A] via-[#1E1B4B] to-[#4F46E5] rounded-2xl p-5 text-white shadow-sm space-y-2 border border-indigo-400/20 interactive-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Recaudación Cobrada</span>
                <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-xs text-sky-300">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold font-mono" x-text="'Bs ' + formatNumber(kpis.cod_collected_bob)">
                Bs {{ number_format($kpis['cod_collected_bob'], 2) }}
            </div>
            <div class="flex items-center justify-between text-[11px] text-indigo-200/90 font-medium pt-1 border-t border-white/10">
                <span>Efectivo: <b x-text="'Bs ' + formatNumber(kpis.cash_collected_bob)">Bs {{ number_format($kpis['cash_collected_bob'], 2) }}</b></span>
                <span>QR: <b x-text="'Bs ' + formatNumber(kpis.qr_collected_bob)">Bs {{ number_format($kpis['qr_collected_bob'], 2) }}</b></span>
            </div>
        </div>

        <!-- 2. Fletes Ganados DEX -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2 interactive-card border-l-4 border-l-[#10B981]">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Fletes Devengados DEX</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-[#10B981] flex items-center justify-center text-xs">
                    <i class="fa-solid fa-route"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#10B981] font-mono" x-text="'Bs ' + formatNumber(kpis.shipping_fees_earned_bob)">
                Bs {{ number_format($kpis['shipping_fees_earned_bob'], 2) }}
            </div>
            <div class="text-slate-500 text-[11px]">Tarifas de transporte por paquetes entregados</div>
        </div>

        <!-- 3. Saldo Neto a Rendir / Liquidar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2 interactive-card border-l-4 border-l-[#0061f2]">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Saldo a Rendir</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0061f2] flex items-center justify-center text-xs">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#0F172A] font-mono" x-text="'Bs ' + formatNumber(kpis.funds_to_settle_bob)">
                Bs {{ number_format($kpis['funds_to_settle_bob'], 2) }}
            </div>
            <div class="text-slate-500 text-[11px]">Total recaudado menos flete del transportista</div>
        </div>

        <!-- 4. Efectividad de Entregas -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2 interactive-card border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Efectividad de Entrega</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#0F172A] font-heading flex items-baseline gap-2">
                <span x-text="kpis.delivery_rate + '%'">{{ $kpis['delivery_rate'] }}%</span>
                <span class="text-xs font-medium text-slate-400" x-text="'(' + kpis.delivered_count + ' de ' + (kpis.delivered_count + kpis.novedades_count) + ')'"></span>
            </div>
            <div class="text-[11px] text-slate-500 flex justify-between">
                <span>En ruta: <b class="text-[#0061f2]" x-text="kpis.in_route_count">{{ $kpis['in_route_count'] }}</b></span>
                <span>Novedades: <b class="text-rose-600" x-text="kpis.novedades_count">{{ $kpis['novedades_count'] }}</b></span>
            </div>
        </div>
    </div>

    <!-- BARRA DE FILTROS REACTIVOS -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                <i class="fa-solid fa-sliders text-[#4F46E5]"></i>
                <span>Filtros Paramétricos y Período</span>
            </div>
            <!-- Botones Rápidos de Período -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <button type="button" @click="setPeriod('today')"
                    class="px-3 py-1 rounded-lg border text-xs font-semibold transition"
                    :class="activePeriod === 'today' ? 'bg-[#0F172A] text-white border-[#0F172A]' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                    Hoy
                </button>
                <button type="button" @click="setPeriod('yesterday')"
                    class="px-3 py-1 rounded-lg border text-xs font-semibold transition"
                    :class="activePeriod === 'yesterday' ? 'bg-[#0F172A] text-white border-[#0F172A]' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                    Ayer
                </button>
                <button type="button" @click="setPeriod('7d')"
                    class="px-3 py-1 rounded-lg border text-xs font-semibold transition"
                    :class="activePeriod === '7d' ? 'bg-[#0F172A] text-white border-[#0F172A]' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                    Últimos 7 días
                </button>
                <button type="button" @click="setPeriod('30d')"
                    class="px-3 py-1 rounded-lg border text-xs font-semibold transition"
                    :class="activePeriod === '30d' ? 'bg-[#0F172A] text-white border-[#0F172A]' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                    Últimos 30 días
                </button>
                <button type="button" @click="setPeriod('month')"
                    class="px-3 py-1 rounded-lg border text-xs font-semibold transition"
                    :class="activePeriod === 'month' ? 'bg-[#0F172A] text-white border-[#0F172A]' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                    Este Mes
                </button>
            </div>
        </div>

        <!-- Controles de Filtro -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- Desde -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Fecha Desde</label>
                <input type="date" x-model="filters.date_from" @change="applyFilters()"
                    class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#4F46E5]">
            </div>

            <!-- Hasta -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Fecha Hasta</label>
                <input type="date" x-model="filters.date_to" @change="applyFilters()"
                    class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#4F46E5]">
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Estado</label>
                <select x-model="filters.status" @change="applyFilters()"
                    class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#4F46E5]">
                    <option value="">Todos los Estados</option>
                    <option value="delivered">Entregados</option>
                    <option value="dispatched">En Ruta / Reparto</option>
                    <option value="failed_delivery">Con Novedad / Fallidos</option>
                    <option value="settled">Liquidados</option>
                </select>
            </div>

            <!-- Método de Pago -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Medio de Pago</label>
                <select x-model="filters.payment_method" @change="applyFilters()"
                    class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#4F46E5]">
                    <option value="">Efectivo & QR</option>
                    <option value="cash">Solo Efectivo COD</option>
                    <option value="qr">Solo QR Interoperable</option>
                </select>
            </div>

            <!-- Proveedor -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Proveedor / Bodega</label>
                <select x-model="filters.provider_id" @change="applyFilters()"
                    class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#4F46E5]">
                    <option value="">Todos los Proveedores</option>
                    @foreach($providersList as $prov)
                        <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Ciudad -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Ciudad Destino</label>
                <select x-model="filters.city" @change="applyFilters()"
                    class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:border-[#4F46E5]">
                    <option value="">Todas las Ciudades</option>
                    @foreach($citiesList as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Buscador Libre y Reset -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
            <div class="relative flex-1 max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" x-model="filters.search" @input.debounce.400ms="applyFilters()"
                    placeholder="Buscar por # guía, código de pedido, cliente o teléfono..."
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 text-slate-700 focus:outline-none focus:border-[#4F46E5]">
            </div>
            <div class="flex items-center gap-2 self-end sm:self-auto">
                <span x-show="loading" class="text-xs text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-notch animate-spin text-[#4F46E5]"></i> Actualizando métricas...
                </span>
                <button type="button" @click="resetFilters()"
                    class="px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Restablecer Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- SECCIÓN INTERACTIVA DE GRÁFICOS (Recaudación Diaria + Mix de Pago) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Curva Diaria de Recaudación (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-[#4F46E5] flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-chart-area"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 font-heading">Tendencia de Recaudación Diaria COD</h4>
                        <p class="text-[11px] text-slate-400">Montos cobrados por jornada en el período seleccionado</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs font-mono font-extrabold text-[#4F46E5]" x-text="'Bs ' + formatNumber(kpis.cod_collected_bob)"></span>
                    <span class="block text-[10px] text-slate-400">Total Período</span>
                </div>
            </div>

            <!-- Gráfico de Barras / Áreas Reactivo con CSS Flexbox -->
            <div class="h-44 flex items-end justify-between gap-2 pt-6 pb-2 px-3 bg-gradient-to-b from-indigo-50/30 to-transparent rounded-xl border border-slate-100">
                <template x-for="(label, idx) in chartData.labels" :key="idx">
                    <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end group cursor-pointer relative"
                        @mouseenter="activeChartIndex = idx" @mouseleave="activeChartIndex = null">
                        <!-- Tooltip flotante al pasar el mouse -->
                        <div x-show="activeChartIndex === idx" x-cloak
                            class="absolute -top-12 z-20 px-2.5 py-1 rounded-lg bg-[#0F172A] text-white text-[10px] font-mono shadow-xl whitespace-nowrap">
                            <div class="font-bold text-emerald-400" x-text="'Bs ' + formatNumber(chartData.recaudacion[idx])"></div>
                            <div class="text-slate-300" x-text="chartData.entregas[idx] + ' entregas'"></div>
                        </div>

                        <!-- Barra de Recaudación -->
                        <div class="w-full max-w-[28px] rounded-t-lg transition-all duration-300"
                            :class="activeChartIndex === idx ? 'bg-[#4F46E5] shadow-md' : 'bg-[#4F46E5]/70 hover:bg-[#4F46E5]'"
                            :style="'height: ' + calculateBarHeight(chartData.recaudacion[idx]) + '%; min-height: 4px;'">
                        </div>

                        <!-- Etiqueta Fecha -->
                        <span class="text-[9px] font-mono text-slate-400 block truncate w-full text-center" x-text="label"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Mix de Medios de Pago & Conciliación (1 col) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-[#10B981] flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 font-heading">Efectivo vs. QR Interoperable</h4>
                        <p class="text-[11px] text-slate-400">Composición de cobranza</p>
                    </div>
                </div>
            </div>

            <!-- Barra Comparativa Proporcional -->
            <div class="space-y-3">
                <div class="h-4 rounded-full bg-slate-100 overflow-hidden flex">
                    <div class="bg-emerald-500 h-full transition-all duration-500"
                        :style="'width: ' + kpis.cash_percentage + '%'"
                        :title="'Efectivo: ' + kpis.cash_percentage + '%'"></div>
                    <div class="bg-[#4F46E5] h-full transition-all duration-500"
                        :style="'width: ' + kpis.qr_percentage + '%'"
                        :title="'QR: ' + kpis.qr_percentage + '%'"></div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <!-- Efectivo -->
                    <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200/60 space-y-1">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                            <span>Efectivo COD</span>
                        </div>
                        <div class="text-base font-extrabold font-mono text-emerald-900" x-text="'Bs ' + formatNumber(kpis.cash_collected_bob)"></div>
                        <div class="text-[10px] text-emerald-700 font-medium" x-text="kpis.cash_percentage + '% del total'"></div>
                    </div>

                    <!-- QR -->
                    <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-200/60 space-y-1">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#4F46E5] inline-block"></span>
                            <span>QR Simple ACH</span>
                        </div>
                        <div class="text-base font-extrabold font-mono text-indigo-900" x-text="'Bs ' + formatNumber(kpis.qr_collected_bob)"></div>
                        <div class="text-[10px] text-indigo-700 font-medium" x-text="kpis.qr_percentage + '% del total'"></div>
                    </div>
                </div>
            </div>

            <!-- Resumen de Conciliación Transportista -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                <div class="flex justify-between items-center text-slate-600">
                    <span>Recaudación Total:</span>
                    <span class="font-mono font-bold text-slate-900" x-text="'Bs ' + formatNumber(kpis.cod_collected_bob)"></span>
                </div>
                <div class="flex justify-between items-center text-slate-600">
                    <span>Retención Fletes DEX:</span>
                    <span class="font-mono font-bold text-[#10B981]" x-text="'- Bs ' + formatNumber(kpis.shipping_fees_earned_bob)"></span>
                </div>
                <div class="flex justify-between items-center pt-1.5 border-t border-slate-200 font-bold text-slate-900">
                    <span>A Rendir a Plataforma:</span>
                    <span class="font-mono text-sm text-[#0061f2]" x-text="'Bs ' + formatNumber(kpis.funds_to_settle_bob)"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- PESTAÑAS PRINCIPALES DEL REPORTE -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Tab Navigation -->
        <div class="flex border-b border-slate-200 bg-slate-50/80 overflow-x-auto px-4 pt-2 gap-2">
            <button type="button" @click="activeTab = 'cierre'"
                :class="activeTab === 'cierre' ? 'border-b-2 border-[#4F46E5] text-[#4F46E5] font-bold bg-white' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                class="px-4 py-2.5 text-xs rounded-t-xl transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-cash-register text-[#4F46E5]"></i>
                <span>Arqueo de Caja / Cierre Diario</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-mono" x-text="arqueoCaja.length"></span>
            </button>

            <button type="button" @click="activeTab = 'movimientos'"
                :class="activeTab === 'movimientos' ? 'border-b-2 border-[#4F46E5] text-[#4F46E5] font-bold bg-white' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                class="px-4 py-2.5 text-xs rounded-t-xl transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-list-check text-sky-500"></i>
                <span>Movimientos &amp; Pedidos</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 font-mono" x-text="ordersList.length"></span>
            </button>

            <button type="button" @click="activeTab = 'proveedores'"
                :class="activeTab === 'proveedores' ? 'border-b-2 border-[#4F46E5] text-[#4F46E5] font-bold bg-white' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                class="px-4 py-2.5 text-xs rounded-t-xl transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-warehouse text-emerald-600"></i>
                <span>Consolidado por Proveedor</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-mono" x-text="consolidadoProveedores.length"></span>
            </button>

            <button type="button" @click="activeTab = 'ciudades'"
                :class="activeTab === 'ciudades' ? 'border-b-2 border-[#4F46E5] text-[#4F46E5] font-bold bg-white' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                class="px-4 py-2.5 text-xs rounded-t-xl transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-map-location-dot text-amber-500"></i>
                <span>Destinos &amp; Cobertura</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-mono" x-text="consolidadoCiudades.length"></span>
            </button>
        </div>

        <div class="p-5">
            <!-- TAB 1: ARQUEO DE CAJA / CIERRE DIARIO -->
            <div x-show="activeTab === 'cierre'" x-cloak class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 font-heading">Planilla de Rendición Diaria de Repartidores</h4>
                        <p class="text-[11px] text-slate-500">Control de billetes físicos recaudados vs. pagos electrónicos QR acreditados</p>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs min-w-[750px]">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Fecha Cierre</th>
                                <th class="py-3 px-4 text-center">Paquetes Entregados</th>
                                <th class="py-3 px-4 text-right">Efectivo Físico (Billetes)</th>
                                <th class="py-3 px-4 text-right">Comprobantes QR</th>
                                <th class="py-3 px-4 text-right">Total Cobrado (BOB)</th>
                                <th class="py-3 px-4 text-right">Flete DEX Retenido</th>
                                <th class="py-3 px-4 text-right font-bold text-[#0061f2]">Neto a Rendir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="arqueoCaja.length === 0">
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400">
                                        <i class="fa-solid fa-receipt text-2xl mb-1 block"></i>
                                        No se registran entregas liquidadas en este rango de fechas.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(dia, idx) in arqueoCaja" :key="idx">
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-4 whitespace-nowrap font-bold text-slate-900 font-mono" x-text="dia.date"></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-check text-[9px]"></i> <span x-text="dia.orders_count + ' paquetes'"></span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-emerald-800 font-semibold" x-text="'Bs ' + formatNumber(dia.cash_amount)"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-indigo-700 font-semibold" x-text="'Bs ' + formatNumber(dia.qr_amount)"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-extrabold text-slate-900" x-text="'Bs ' + formatNumber(dia.total_collected)"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-emerald-600 font-medium" x-text="'Bs ' + formatNumber(dia.shipping_fee)"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-black text-[#0061f2]" x-text="'Bs ' + formatNumber(dia.net_to_settle)"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50/90 font-bold border-t border-slate-200 text-slate-900">
                            <tr>
                                <td class="py-3 px-4 uppercase text-[10px]">TOTALES PERÍODO</td>
                                <td class="py-3 px-4 text-center font-mono" x-text="kpis.delivered_count"></td>
                                <td class="py-3 px-4 text-right font-mono text-emerald-800" x-text="'Bs ' + formatNumber(kpis.cash_collected_bob)"></td>
                                <td class="py-3 px-4 text-right font-mono text-indigo-700" x-text="'Bs ' + formatNumber(kpis.qr_collected_bob)"></td>
                                <td class="py-3 px-4 text-right font-mono text-slate-900 text-sm" x-text="'Bs ' + formatNumber(kpis.cod_collected_bob)"></td>
                                <td class="py-3 px-4 text-right font-mono text-emerald-600" x-text="'Bs ' + formatNumber(kpis.shipping_fees_earned_bob)"></td>
                                <td class="py-3 px-4 text-right font-mono text-[#0061f2] text-sm" x-text="'Bs ' + formatNumber(kpis.funds_to_settle_bob)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- TAB 2: MOVIMIENTOS & DETALLE DE PEDIDOS -->
            <div x-show="activeTab === 'movimientos'" x-cloak class="space-y-4">
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs min-w-[950px]">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4"># Guía / Pedido</th>
                                <th class="py-3 px-4">Fecha</th>
                                <th class="py-3 px-4">Destinatario</th>
                                <th class="py-3 px-4">Ciudad / Destino</th>
                                <th class="py-3 px-4">Medio Pago</th>
                                <th class="py-3 px-4">Estado</th>
                                <th class="py-3 px-4">Proveedor Origen</th>
                                <th class="py-3 px-4 text-right">Recaudo COD</th>
                                <th class="py-3 px-4 text-right">Flete DEX</th>
                                <th class="py-3 px-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="ordersList.length === 0">
                                <tr>
                                    <td colspan="10" class="py-8 text-center text-slate-400">
                                        <i class="fa-solid fa-box-open text-2xl mb-1 block"></i>
                                        No se encontraron pedidos con los filtros aplicados.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="order in ordersList" :key="order.id">
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-bold font-mono text-[#4F46E5]" x-text="order.tracking_number"></div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="order.order_number"></div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-slate-600" x-text="order.created_at_fmt"></td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-900" x-text="order.customer_name"></div>
                                        <div class="text-[11px] text-slate-400 font-mono" x-text="order.customer_phone"></div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-800" x-text="order.shipping_city"></div>
                                        <div class="text-[10px] text-slate-400" x-text="order.shipping_department"></div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="order.is_cash ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-indigo-50 text-indigo-800 border border-indigo-200'">
                                            <i class="fa-solid" :class="order.is_cash ? 'fa-money-bill-wave' : 'fa-qrcode'"></i>
                                            <span x-text="order.payment_method_label"></span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="{
                                                'bg-emerald-50 text-emerald-700 border border-emerald-200': order.status === 'delivered' || order.status === 'settled',
                                                'bg-sky-50 text-sky-700 border border-sky-200': order.status === 'dispatched' || order.status === 'in_transit',
                                                'bg-rose-50 text-rose-700 border border-rose-200': order.status === 'failed_delivery',
                                                'bg-slate-100 text-slate-600': order.status === 'pending_confirmation' || order.status === 'confirmed'
                                            }">
                                            <span class="w-1.5 h-1.5 rounded-full"
                                                :class="{
                                                    'bg-emerald-500': order.status === 'delivered' || order.status === 'settled',
                                                    'bg-sky-500': order.status === 'dispatched' || order.status === 'in_transit',
                                                    'bg-rose-500': order.status === 'failed_delivery',
                                                    'bg-slate-400': order.status === 'pending_confirmation' || order.status === 'confirmed'
                                                }"></span>
                                            <span x-text="order.status === 'delivered' ? 'Entregado' : (order.status === 'dispatched' ? 'En ruta' : (order.status === 'failed_delivery' ? 'Novedad' : order.status))"></span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-slate-700 font-medium" x-text="order.provider_name"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold text-slate-900" x-text="'Bs ' + formatNumber(order.total_amount_bob)"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold text-[#10B981]" x-text="'Bs ' + formatNumber(order.shipping_fee_bob)"></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <button type="button" @click="openModal(order)"
                                            class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer"
                                            title="Ver resumen y productos del pedido">
                                            <i class="fa-solid fa-eye mr-1"></i> Resumen
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: CONSOLIDADO POR PROVEEDOR -->
            <div x-show="activeTab === 'proveedores'" x-cloak class="space-y-4">
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs min-w-[700px]">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Proveedor / Mayorista</th>
                                <th class="py-3 px-4 text-center">Total Paquetes</th>
                                <th class="py-3 px-4 text-center">Entregados</th>
                                <th class="py-3 px-4 text-center">En Ruta</th>
                                <th class="py-3 px-4 text-center">Novedades</th>
                                <th class="py-3 px-4 text-right">Recaudación COD</th>
                                <th class="py-3 px-4 text-right">Costo Mercadería</th>
                                <th class="py-3 px-4 text-right text-[#10B981]">Flete Generado DEX</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="consolidadoProveedores.length === 0">
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-400">
                                        No hay datos de proveedores para el período seleccionado.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(prov, idx) in consolidadoProveedores" :key="idx">
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-4 whitespace-nowrap font-bold text-slate-900" x-text="prov.name"></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap font-mono font-bold" x-text="prov.orders_count"></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-emerald-700 font-bold" x-text="prov.delivered_count"></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-sky-700 font-bold" x-text="prov.in_route_count"></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-rose-700 font-bold" x-text="prov.failed_count"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold text-slate-900" x-text="'Bs ' + formatNumber(prov.total_cod_bob)"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-slate-600" x-text="'Bs ' + formatNumber(prov.provider_cost_bob)"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-extrabold text-[#10B981]" x-text="'Bs ' + formatNumber(prov.shipping_fee_bob)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: CONSOLIDADO POR CIUDAD / DESTINO -->
            <div x-show="activeTab === 'ciudades'" x-cloak class="space-y-4">
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs min-w-[650px]">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Ciudad Destino</th>
                                <th class="py-3 px-4">Departamento</th>
                                <th class="py-3 px-4 text-center">Total Paquetes</th>
                                <th class="py-3 px-4 text-center">Entregados con Éxito</th>
                                <th class="py-3 px-4 text-center">Novedades</th>
                                <th class="py-3 px-4 text-right">Recaudación Cobrada</th>
                                <th class="py-3 px-4 text-right">Tasa Éxito</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="consolidadoCiudades.length === 0">
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400">
                                        No hay datos de ciudades en el período seleccionado.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(city, idx) in consolidadoCiudades" :key="idx">
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-4 whitespace-nowrap font-bold text-slate-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-location-dot text-[#4F46E5] text-[10px]"></i>
                                        <span x-text="city.city"></span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-slate-600 font-medium" x-text="city.department"></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap font-mono font-bold" x-text="city.orders_count"></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-emerald-700 font-bold" x-text="city.delivered_count"></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-rose-700 font-bold" x-text="city.failed_count"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold text-slate-900" x-text="'Bs ' + formatNumber(city.total_collected)"></td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-extrabold text-[#10B981]"
                                        x-text="(city.orders_count > 0 ? ((city.delivered_count / city.orders_count) * 100).toFixed(1) : 0) + '%'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE RESUMEN Y DETALLE DEL PEDIDO -->
    <div x-show="modalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="modalOpen = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in" @click.stop>
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm font-heading" x-text="'Resumen de Pedido: ' + (selectedOrder?.tracking_number || selectedOrder?.order_number)"></h3>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="'Fecha de gestión: ' + selectedOrder?.created_at_fmt"></p>
                </div>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto" x-show="selectedOrder">
                <!-- Destinatario & Ubicación -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 space-y-2 text-xs">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-user text-[#4F46E5]"></i> Destinatario en Bolivia
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Cliente:</span>
                        <span class="font-bold text-slate-900" x-text="selectedOrder?.customer_name"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Teléfono:</span>
                        <span class="font-mono font-semibold text-[#4F46E5]" x-text="selectedOrder?.customer_phone"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Destino:</span>
                        <span class="font-medium text-slate-800" x-text="(selectedOrder?.shipping_city || '') + ', ' + (selectedOrder?.shipping_department || '')"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Dirección:</span>
                        <span class="font-medium text-slate-800 text-right max-w-[250px]" x-text="selectedOrder?.shipping_address"></span>
                    </div>
                </div>

                <!-- Desglose de Productos -->
                <div class="space-y-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-box-open text-[#4F46E5]"></i> Productos en el Paquete
                    </div>
                    <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden text-xs">
                        <template x-for="(item, i) in (selectedOrder?.items || [])" :key="i">
                            <div class="p-3 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-slate-900" x-text="item.product_name"></div>
                                    <div class="text-[10px] text-slate-400" x-text="'Cantidad: ' + item.quantity + ' unidad(es)'"></div>
                                </div>
                                <div class="font-mono font-bold text-slate-900" x-text="'Bs ' + formatNumber(item.subtotal)"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Resumen Financiero COD -->
                <div class="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-200/60 space-y-2 text-xs">
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Total COD Cobrado al Cliente:</span>
                        <span class="font-mono font-extrabold text-slate-900 text-sm" x-text="'Bs ' + formatNumber(selectedOrder?.total_amount_bob)"></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Tarifa / Flete DEX:</span>
                        <span class="font-mono font-bold text-[#10B981]" x-text="'Bs ' + formatNumber(selectedOrder?.shipping_fee_bob)"></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Medio de Pago:</span>
                        <span class="font-bold text-indigo-900" x-text="selectedOrder?.payment_method_label"></span>
                    </div>
                    <div class="flex justify-between items-center pt-1.5 border-t border-indigo-200/80 font-bold text-slate-900">
                        <span>Proveedor Origen:</span>
                        <span class="text-slate-800" x-text="selectedOrder?.provider_name"></span>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-slate-200 flex justify-between items-center bg-slate-50">
                <a :href="'/tracking/' + selectedOrder?.tracking_number" target="_blank"
                    class="text-xs font-bold text-[#4F46E5] hover:underline flex items-center gap-1">
                    <i class="fa-solid fa-route"></i> Ver Tracking Público
                </a>
                <button type="button" @click="modalOpen = false"
                    class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition cursor-pointer">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function carrierReportApp() {
    return {
        activeTab: 'cierre',
        activePeriod: '30d',
        loading: false,
        activeChartIndex: null,

        // Filtros reactivos
        filters: {
            date_from: '{{ $dateFrom }}',
            date_to: '{{ $dateTo }}',
            status: '{{ $statusFilter ?? '' }}',
            payment_method: '{{ $paymentFilter ?? '' }}',
            provider_id: '{{ $providerFilter ?? '' }}',
            city: '{{ $cityFilter ?? '' }}',
            search: '{{ $search ?? '' }}',
        },

        // Datos cargados desde servidor
        kpis: @json($kpis),
        arqueoCaja: @json($arqueoCaja),
        consolidadoProveedores: @json($consolidadoProveedores),
        consolidadoCiudades: @json($consolidadoCiudades),
        chartData: @json($chart),
        ordersList: @json($ordersList),

        // Modal
        modalOpen: false,
        selectedOrder: null,

        // Toast
        toast: { show: false, message: '', type: 'success' },

        get exportUrl() {
            const params = new URLSearchParams(this.filters);
            return '{{ route("carrier.reportes.export") }}?' + params.toString();
        },

        formatNumber(val) {
            const num = parseFloat(val) || 0;
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        calculateBarHeight(val) {
            const max = Math.max(...(this.chartData.recaudacion || [1]), 100);
            const num = parseFloat(val) || 0;
            return Math.min(100, Math.max(4, Math.round((num / max) * 100)));
        },

        setPeriod(type) {
            this.activePeriod = type;
            const now = new Date();
            let from = new Date();
            let to = new Date();

            if (type === 'today') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                to = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            } else if (type === 'yesterday') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
                to = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
            } else if (type === '7d') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6);
            } else if (type === '30d') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 29);
            } else if (type === 'month') {
                from = new Date(now.getFullYear(), now.getMonth(), 1);
            }

            this.filters.date_from = this.formatDate(from);
            this.filters.date_to = this.formatDate(to);
            this.applyFilters();
        },

        formatDate(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        },

        async applyFilters() {
            this.loading = true;
            try {
                const params = new URLSearchParams(this.filters);
                const res = await fetch('{{ route("carrier.reportes.data") }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    this.kpis = json.data.kpis;
                    this.arqueoCaja = json.data.arqueoCaja;
                    this.consolidadoProveedores = json.data.consolidadoProveedores;
                    this.consolidadoCiudades = json.data.consolidadoCiudades;
                    this.chartData = json.data.chart;
                    this.ordersList = json.data.ordersList;
                } else {
                    this.showToast('No se pudieron actualizar los datos.', 'error');
                }
            } catch (err) {
                this.showToast('Error de conexión al cargar métricas.', 'error');
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                date_from: '',
                date_to: '',
                status: '',
                payment_method: '',
                provider_id: '',
                city: '',
                search: '',
            };
            this.setPeriod('30d');
        },

        openModal(order) {
            this.selectedOrder = order;
            this.modalOpen = true;
        },

        showToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        }
    };
}
</script>
@endsection
