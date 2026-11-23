@extends('layouts.app')

@section('title', 'Centro de Reportes & Métricas — Bolidrop')

@section('content')
<div x-data="reportesHubPage()" x-init="init()" class="min-h-screen bg-slate-50 pb-16">

    {{-- =====================================================================
         HEADER HERO CON MENÚ SUPERIOR Y ACCIONES
    ===================================================================== --}}
    <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-950 text-white px-4 md:px-8 py-8 border-b border-indigo-900/50 shadow-lg">
        <div class="max-w-7xl mx-auto space-y-6">
            
            {{-- Barra superior de navegación y contexto --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-violet-300 text-xs font-bold uppercase tracking-wider mb-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-violet-500/20 text-violet-400">
                            <i class="fa-solid fa-chart-pie text-xs"></i>
                        </span>
                        <span>Plataforma Bolidrop</span>
                        <span class="text-violet-500">/</span>
                        <span class="text-white font-extrabold">Centro de Reportes & Métricas</span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                        Métricas de Ventas & Inventario Valorado
                    </h1>
                    <p class="text-slate-300 text-sm mt-1 max-w-2xl leading-relaxed">
                        Control analítico integral: rotación ABC, cobertura geográfica de Bolivia, embudo del flujo COD y liquidación de costos de mercadería.
                    </p>
                </div>

                {{-- Menú de acciones rápidas --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    {{-- Selector de Proveedor (exclusivo Administrador) --}}
                    @if(Auth::user()->isTenantAdmin() && $providers->count() > 1)
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-3 py-2 shadow-inner">
                        <i class="fa-solid fa-building text-violet-300 text-xs"></i>
                        <label class="text-[11px] font-bold text-violet-200">Proveedor:</label>
                        <select x-model="selectedProviderId" @change="loadReportData()"
                                class="bg-transparent text-white text-xs font-semibold outline-none appearance-none cursor-pointer pr-4 focus:ring-0">
                            @foreach($providers as $prov)
                                <option value="{{ $prov->id }}" class="text-slate-900">{{ $prov->name }}</option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down text-violet-300 text-[10px]"></i>
                    </div>
                    @endif

                    {{-- Exportación Directa a CSV con UTF-8 BOM para Excel --}}
                    <a :href="getExportUrl()"
                       class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-950/40 transition">
                        <i class="fa-solid fa-file-csv text-sm"></i>
                        <span>Exportar CSV</span>
                    </a>

                    {{-- Acceso Rápido al Panel del Proveedor --}}
                    <a href="{{ url('/panel/proveedor') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/20 backdrop-blur-sm transition">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Panel Proveedor</span>
                    </a>
                </div>
            </div>

            {{-- BARRA DE FILTROS TEMPORALES (PRESETS Y SELECTORES DE FECHA) --}}
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 flex flex-wrap items-end gap-3 shadow-inner">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-violet-200 mb-1.5">Fecha Inicial</label>
                    <input type="date" x-model="dateFrom"
                           class="bg-white/10 border border-white/25 text-white rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-violet-400 outline-none [color-scheme:dark]">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-violet-200 mb-1.5">Fecha Final</label>
                    <input type="date" x-model="dateTo"
                           class="bg-white/10 border border-white/25 text-white rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-violet-400 outline-none [color-scheme:dark]">
                </div>
                
                {{-- Botones de Acceso Rápido (Presets) --}}
                <div class="flex flex-wrap gap-1.5">
                    <button @click="setPreset(1)"        class="px-3 py-2 text-xs font-bold bg-white/10 hover:bg-violet-600 text-white rounded-xl transition border border-white/15">Hoy</button>
                    <button @click="setPreset(7)"        class="px-3 py-2 text-xs font-bold bg-white/10 hover:bg-violet-600 text-white rounded-xl transition border border-white/15">7 días</button>
                    <button @click="setPreset(30)"       class="px-3 py-2 text-xs font-bold bg-white/10 hover:bg-violet-600 text-white rounded-xl transition border border-white/15">30 días</button>
                    <button @click="setPreset(90)"       class="px-3 py-2 text-xs font-bold bg-white/10 hover:bg-violet-600 text-white rounded-xl transition border border-white/15">90 días</button>
                    <button @click="setPresetMonth()"    class="px-3 py-2 text-xs font-bold bg-white/10 hover:bg-violet-600 text-white rounded-xl transition border border-white/15">Este mes</button>
                    <button @click="setPresetYear()"     class="px-3 py-2 text-xs font-bold bg-white/10 hover:bg-violet-600 text-white rounded-xl transition border border-white/15">Este año</button>
                </div>

                {{-- Botón de Aplicar Filtro con Spinner Reactivo --}}
                <button @click="loadReportData()" :disabled="loading"
                        class="ml-auto px-6 py-2.5 bg-violet-600 hover:bg-violet-500 disabled:opacity-60 text-white text-xs font-extrabold rounded-xl shadow-lg shadow-violet-900/50 transition flex items-center gap-2">
                    <i class="fa-solid fa-rotate" :class="{'animate-spin': loading}"></i>
                    <span x-text="loading ? 'Consultando...' : 'Aplicar Filtro'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- =====================================================================
         MENÚ DE NAVEGACIÓN MODULAR (STICKY TABS BAR)
    ===================================================================== --}}
    <div class="sticky top-14 z-30 bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 md:px-8">
            <nav class="flex items-center gap-1 overflow-x-auto scrollbar-none py-1">
                <button @click="activeTab = 'kpis'"
                        :class="activeTab === 'kpis' ? 'border-violet-600 text-violet-700 font-extrabold bg-violet-50/80 shadow-sm' : 'border-transparent text-slate-600 hover:text-violet-600 hover:bg-slate-50 font-semibold'"
                        class="flex items-center gap-2 px-4 py-2.5 text-xs rounded-xl transition whitespace-nowrap border">
                    <i class="fa-solid fa-gauge-high text-xs" :class="activeTab === 'kpis' ? 'text-violet-600' : 'text-slate-400'"></i>
                    <span>Resumen KPIs</span>
                </button>

                <button @click="activeTab = 'embudo'"
                        :class="activeTab === 'embudo' ? 'border-violet-600 text-violet-700 font-extrabold bg-violet-50/80 shadow-sm' : 'border-transparent text-slate-600 hover:text-violet-600 hover:bg-slate-50 font-semibold'"
                        class="flex items-center gap-2 px-4 py-2.5 text-xs rounded-xl transition whitespace-nowrap border">
                    <i class="fa-solid fa-filter text-xs" :class="activeTab === 'embudo' ? 'text-violet-600' : 'text-slate-400'"></i>
                    <span>Embudo COD</span>
                </button>

                <button @click="activeTab = 'rotacion'"
                        :class="activeTab === 'rotacion' ? 'border-violet-600 text-violet-700 font-extrabold bg-violet-50/80 shadow-sm' : 'border-transparent text-slate-600 hover:text-violet-600 hover:bg-slate-50 font-semibold'"
                        class="flex items-center gap-2 px-4 py-2.5 text-xs rounded-xl transition whitespace-nowrap border">
                    <i class="fa-solid fa-rotate text-xs" :class="activeTab === 'rotacion' ? 'text-violet-600' : 'text-slate-400'"></i>
                    <span>Rotación ABC</span>
                </button>

                <button @click="activeTab = 'valorado'"
                        :class="activeTab === 'valorado' ? 'border-violet-600 text-violet-700 font-extrabold bg-violet-50/80 shadow-sm' : 'border-transparent text-slate-600 hover:text-violet-600 hover:bg-slate-50 font-semibold'"
                        class="flex items-center gap-2 px-4 py-2.5 text-xs rounded-xl transition whitespace-nowrap border">
                    <i class="fa-solid fa-warehouse text-xs" :class="activeTab === 'valorado' ? 'text-violet-600' : 'text-slate-400'"></i>
                    <span>Valorado Inventario</span>
                </button>

                <button @click="activeTab = 'geo'"
                        :class="activeTab === 'geo' ? 'border-violet-600 text-violet-700 font-extrabold bg-violet-50/80 shadow-sm' : 'border-transparent text-slate-600 hover:text-violet-600 hover:bg-slate-50 font-semibold'"
                        class="flex items-center gap-2 px-4 py-2.5 text-xs rounded-xl transition whitespace-nowrap border">
                    <i class="fa-solid fa-map-location-dot text-xs" :class="activeTab === 'geo' ? 'text-violet-600' : 'text-slate-400'"></i>
                    <span>Geografía Bolivia</span>
                </button>

                <button @click="activeTab = 'sellers'"
                        :class="activeTab === 'sellers' ? 'border-violet-600 text-violet-700 font-extrabold bg-violet-50/80 shadow-sm' : 'border-transparent text-slate-600 hover:text-violet-600 hover:bg-slate-50 font-semibold'"
                        class="flex items-center gap-2 px-4 py-2.5 text-xs rounded-xl transition whitespace-nowrap border">
                    <i class="fa-solid fa-trophy text-xs" :class="activeTab === 'sellers' ? 'text-violet-600' : 'text-slate-400'"></i>
                    <span>Top Vendedores</span>
                </button>

                <button @click="activeTab = 'movimientos'"
                        :class="activeTab === 'movimientos' ? 'border-violet-600 text-violet-700 font-extrabold bg-violet-50/80 shadow-sm' : 'border-transparent text-slate-600 hover:text-violet-600 hover:bg-slate-50 font-semibold'"
                        class="flex items-center gap-2 px-4 py-2.5 text-xs rounded-xl transition whitespace-nowrap border">
                    <i class="fa-solid fa-list-check text-xs" :class="activeTab === 'movimientos' ? 'text-violet-600' : 'text-slate-400'"></i>
                    <span>Movimientos Detallados</span>
                </button>
            </nav>
        </div>
    </div>

    {{-- =====================================================================
         CONTENIDO PRINCIPAL DINÁMICO
    ===================================================================== --}}
    <div class="max-w-7xl mx-auto px-4 md:px-8 pt-6 space-y-6">

        {{-- ESTADO DE CARGA --}}
        <div x-show="loading" class="flex flex-col items-center justify-center py-28 gap-4">
            <div class="w-12 h-12 border-4 border-violet-200 border-t-violet-600 rounded-full animate-spin"></div>
            <p class="text-slate-500 text-sm font-semibold">Calculando métricas y consolidando datos en vivo...</p>
        </div>

        {{-- ESTADO VACÍO --}}
        <div x-show="!loading && !report" class="text-center py-24 bg-white rounded-3xl border border-slate-200 shadow-sm p-8">
            <div class="w-16 h-16 rounded-full bg-violet-50 text-violet-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800">No hay datos disponibles para este rango</h3>
            <p class="text-slate-500 text-sm mt-1 max-w-md mx-auto">Selecciona otro período de fechas o presiona el botón "Aplicar Filtro" para recargar.</p>
        </div>

        {{-- ================================================================
             MÓDULO 1: RESUMEN EJECUTIVO (KPIS)
        ================================================================ --}}
        <div x-show="!loading && report && activeTab === 'kpis'" class="space-y-6">
            
            {{-- 6 KPI Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                {{-- Uds Vendidas --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Uds Vendidas</span>
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-box"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-indigo-700" x-text="report?.summary_kpis?.total_units_sold ?? '0'"></div>
                    <div class="text-[11px] text-slate-400 mt-1">unidades en el período</div>
                </div>

                {{-- Ingresos Mayoristas --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Ingresos Proveedor</span>
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-700" x-text="'Bs ' + (report?.funnel?.total_provider_cost_bob ?? '0')"></div>
                    <div class="text-[11px] text-slate-400 mt-1">costo mayorista acreditado</div>
                </div>

                {{-- Tasa de Entrega --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tasa de Entrega</span>
                        <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-teal-700" x-text="(report?.funnel?.delivery_rate_percent ?? '0') + '%'"></div>
                    <div class="text-[11px] text-slate-400 mt-1">efectividad courier COD</div>
                </div>

                {{-- Valorado de Inventario --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Valorado Costo</span>
                        <div class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-cubes"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-violet-700" x-text="'Bs ' + (report?.valuation?.total_valuation_cost_bob ?? '0')"></div>
                    <div class="text-[11px] text-slate-400 mt-1">capital en almacenes</div>
                </div>

                {{-- Saldo Billetera --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Saldo Disponible</span>
                        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-amber-700" x-text="'Bs ' + (report?.wallet?.available_bob ?? '0')"></div>
                    <div class="text-[11px] text-slate-400 mt-1">liquidación inmediata</div>
                </div>

                {{-- Producto Estrella --}}
                <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-4 flex flex-col justify-between ring-2 ring-indigo-50">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600">Top Producto</span>
                        <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs">
                            <i class="fa-solid fa-crown"></i>
                        </div>
                    </div>
                    <div class="text-xs font-black text-slate-800 line-clamp-1" :title="report?.summary_kpis?.top_mover_product?.name" x-text="report?.summary_kpis?.top_mover_product?.name ?? '—'"></div>
                    <div class="text-[11px] font-bold text-indigo-600 mt-1" x-text="(report?.summary_kpis?.top_mover_product?.sold ?? 0) + ' uds vendidas'"></div>
                </div>
            </div>

            {{-- Ficha Ejecutiva Consolidada --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800">Balance Operativo del Período</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Rango activo: <span class="font-bold text-violet-700" x-text="dateFrom + ' al ' + dateTo"></span></p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold border border-emerald-200">
                        <i class="fa-solid fa-shield-halved text-xs"></i> Datos Verificados en DB
                    </span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-6">
                    <div class="bg-slate-50/80 rounded-2xl p-4 text-center">
                        <div class="text-2xl font-black text-slate-800" x-text="report?.funnel?.total_orders ?? 0"></div>
                        <div class="text-xs font-bold text-slate-400 mt-1">Total Pedidos COD</div>
                    </div>
                    <div class="bg-slate-50/80 rounded-2xl p-4 text-center">
                        <div class="text-2xl font-black text-indigo-700" x-text="report?.summary_kpis?.total_units_sold ?? 0"></div>
                        <div class="text-xs font-bold text-slate-400 mt-1">Unidades Despachadas</div>
                    </div>
                    <div class="bg-slate-50/80 rounded-2xl p-4 text-center">
                        <div class="text-2xl font-black text-emerald-700" x-text="report?.funnel?.phase_3_delivered?.count ?? 0"></div>
                        <div class="text-xs font-bold text-slate-400 mt-1">Entregas Efectivas</div>
                    </div>
                    <div class="bg-slate-50/80 rounded-2xl p-4 text-center">
                        <div class="text-2xl font-black text-rose-600" x-text="report?.funnel?.phase_4_novedades?.count ?? 0"></div>
                        <div class="text-xs font-bold text-slate-400 mt-1">Devoluciones / Novedades</div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4 text-xs">
                    <div class="flex items-center gap-6">
                        <div>
                            <span class="text-slate-400 font-semibold">Valorado Costo:</span>
                            <span class="font-extrabold text-violet-700 ml-1.5" x-text="'Bs ' + (report?.valuation?.total_valuation_cost_bob ?? 0)"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold">Valorado Venta Sugerida:</span>
                            <span class="font-extrabold text-indigo-700 ml-1.5" x-text="'Bs ' + (report?.valuation?.total_valuation_suggested_bob ?? 0)"></span>
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold">Saldo Liquidable:</span>
                        <span class="font-extrabold text-amber-700 ml-1.5" x-text="'Bs ' + (report?.wallet?.available_bob ?? 0)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================================
             MÓDULO 2: EMBUDO DEL FLUJO COD
        ================================================================ --}}
        <div x-show="!loading && report && activeTab === 'embudo'" class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 md:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-filter text-violet-600"></i> Embudo de Ventas Contra Entrega (COD)
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Seguimiento de las 4 fases de ciclo de vida del pedido con transportadora DEX.</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tasa de Conversión / Entrega</span>
                        <div class="text-2xl font-black text-emerald-700" x-text="(report?.funnel?.delivery_rate_percent ?? 0) + '%'"></div>
                    </div>
                </div>

                {{-- Tarjetas del Embudo --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Fase 1 --}}
                    <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-5 relative overflow-hidden">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </span>
                            <span class="text-xs font-extrabold text-indigo-900 uppercase tracking-wider">Fase 1: Preparación</span>
                        </div>
                        <div class="text-3xl font-black text-indigo-900" x-text="report?.funnel?.phase_1_prep?.count ?? 0"></div>
                        <div class="text-xs text-indigo-600 font-semibold mt-1">Confirmados & En Bodega</div>
                        <div class="mt-4 pt-3 border-t border-indigo-200/60 text-xs font-bold text-indigo-950" x-text="'Bs ' + (report?.funnel?.phase_1_prep?.total_bob ?? 0)"></div>
                    </div>

                    {{-- Fase 2 --}}
                    <div class="bg-amber-50/70 border border-amber-100 rounded-2xl p-5 relative overflow-hidden">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-truck-fast"></i>
                            </span>
                            <span class="text-xs font-extrabold text-amber-900 uppercase tracking-wider">Fase 2: En Ruta DEX</span>
                        </div>
                        <div class="text-3xl font-black text-amber-900" x-text="report?.funnel?.phase_2_transit?.count ?? 0"></div>
                        <div class="text-xs text-amber-600 font-semibold mt-1">Despachados / En Tránsito</div>
                        <div class="mt-4 pt-3 border-t border-amber-200/60 text-xs font-bold text-amber-950" x-text="'Bs ' + (report?.funnel?.phase_2_transit?.total_bob ?? 0)"></div>
                    </div>

                    {{-- Fase 3 --}}
                    <div class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-5 relative overflow-hidden">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-circle-check"></i>
                            </span>
                            <span class="text-xs font-extrabold text-emerald-900 uppercase tracking-wider">Fase 3: Entregados</span>
                        </div>
                        <div class="text-3xl font-black text-emerald-900" x-text="report?.funnel?.phase_3_delivered?.count ?? 0"></div>
                        <div class="text-xs text-emerald-600 font-semibold mt-1">Entregados & Cobrados</div>
                        <div class="mt-4 pt-3 border-t border-emerald-200/60 text-xs font-bold text-emerald-950" x-text="'Bs ' + (report?.funnel?.phase_3_delivered?.total_bob ?? 0)"></div>
                    </div>

                    {{-- Fase 4 --}}
                    <div class="bg-rose-50/70 border border-rose-100 rounded-2xl p-5 relative overflow-hidden">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-7 h-7 rounded-lg bg-rose-600 text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </span>
                            <span class="text-xs font-extrabold text-rose-900 uppercase tracking-wider">Fase 4: Novedades</span>
                        </div>
                        <div class="text-3xl font-black text-rose-900" x-text="report?.funnel?.phase_4_novedades?.count ?? 0"></div>
                        <div class="text-xs text-rose-600 font-semibold mt-1">Devoluciones / Fallidos</div>
                        <div class="mt-4 pt-3 border-t border-rose-200/60 text-xs font-bold text-rose-950" x-text="'Bs ' + (report?.funnel?.phase_4_novedades?.total_bob ?? 0)"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================================
             MÓDULO 3: ROTACIÓN ABC & RUNWAY
        ================================================================ --}}
        <div x-show="!loading && report && activeTab === 'rotacion'" class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 md:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-rotate text-indigo-600"></i> Matriz de Rotación ABC & Runway de Stock
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Clasificación de productos según velocidad diaria de venta y días restantes de stock.</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full font-extrabold">A = Alta Rotación</span>
                        <span class="px-2.5 py-1 bg-indigo-100 text-indigo-800 rounded-full font-extrabold">B = Media</span>
                        <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full font-extrabold">C = Baja</span>
                    </div>
                </div>

                {{-- Controles de Ordenamiento --}}
                <div class="flex flex-wrap items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                    <span class="text-xs font-bold text-slate-400 mr-2">Ordenar por:</span>
                    <button @click="toggleSort('units_sold')"
                            :class="sortField === 'units_sold' ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition">
                        Uds Vendidas <span x-show="sortField === 'units_sold'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                    </button>
                    <button @click="toggleSort('runway_days')"
                            :class="sortField === 'runway_days' ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition">
                        Días de Runway <span x-show="sortField === 'runway_days'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                    </button>
                    <button @click="toggleSort('velocity_per_day')"
                            :class="sortField === 'velocity_per_day' ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition">
                        Velocidad Diaria <span x-show="sortField === 'velocity_per_day'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                    </button>
                    <button @click="toggleSort('stock_available')"
                            :class="sortField === 'stock_available' ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition">
                        Stock Disponible <span x-show="sortField === 'stock_available'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                    </button>
                </div>

                {{-- Tabla de Rotación --}}
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="px-4 py-3 text-left">Producto</th>
                                <th class="px-4 py-3 text-left">SKU</th>
                                <th class="px-4 py-3 text-center">Stock Disp.</th>
                                <th class="px-4 py-3 text-center">Uds Vendidas</th>
                                <th class="px-4 py-3 text-center">Velocidad/Día</th>
                                <th class="px-4 py-3 text-center">Runway (Días)</th>
                                <th class="px-4 py-3 text-center">Tasa Rotación</th>
                                <th class="px-4 py-3 text-center">Categoría ABC</th>
                                <th class="px-4 py-3 text-left">Recomendación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!report?.rotation?.items?.length">
                                <tr><td colspan="9" class="text-center text-slate-400 py-10 italic">No hay productos registrados en este período.</td></tr>
                            </template>
                            <template x-for="item in sortedRotation()" :key="item.product_id">
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 font-bold text-slate-800 max-w-[200px] truncate" x-text="item.product_name"></td>
                                    <td class="px-4 py-3 font-mono text-slate-400" x-text="item.sku ?? '—'"></td>
                                    <td class="px-4 py-3 text-center font-bold text-slate-700" x-text="item.stock_available"></td>
                                    <td class="px-4 py-3 text-center font-extrabold text-indigo-600" x-text="item.units_sold"></td>
                                    <td class="px-4 py-3 text-center text-slate-600" x-text="item.velocity_per_day"></td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="{
                                            'text-emerald-700 font-black': item.runway_days > 60,
                                            'text-amber-700 font-black': item.runway_days > 15 && item.runway_days <= 60,
                                            'text-rose-600 font-black': item.runway_days !== 9999 && item.runway_days <= 15
                                        }" x-text="item.runway_days === 9999 ? '∞' : item.runway_days"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-600" x-text="item.rotation_rate + '%'"></td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="{
                                            'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300': item.abc_class === 'A',
                                            'bg-indigo-100 text-indigo-800 ring-1 ring-indigo-300': item.abc_class === 'B',
                                            'bg-amber-100 text-amber-800 ring-1 ring-amber-300': item.abc_class === 'C'
                                        }" class="px-2.5 py-1 rounded-full font-black text-xs" x-text="item.abc_class"></span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 italic text-xs" x-text="item.recommendation"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================================================================
             MÓDULO 4: VALORADO DE INVENTARIO
        ================================================================ --}}
        <div x-show="!loading && report && activeTab === 'valorado'" class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 md:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-warehouse text-violet-600"></i> Valorado de Inventario por Bodega
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Capital físico invertido en inventario valuado al costo proveedor y sugerido de venta.</p>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-violet-50/70 border border-violet-100 rounded-2xl p-4">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-violet-600 mb-1">Valorado al Costo Total</div>
                        <div class="text-2xl font-black text-violet-900" x-text="'Bs ' + (report?.valuation?.total_valuation_cost_bob ?? 0)"></div>
                    </div>
                    <div class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-4">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 mb-1">Valorado a Precio Sugerido</div>
                        <div class="text-2xl font-black text-emerald-900" x-text="'Bs ' + (report?.valuation?.total_valuation_suggested_bob ?? 0)"></div>
                    </div>
                    <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 mb-1">Margen Comercial Proyectado</div>
                        <div class="text-2xl font-black text-indigo-900" x-text="'Bs ' + ((parseFloat(report?.valuation?.total_valuation_suggested_bob ?? 0) - parseFloat(report?.valuation?.total_valuation_cost_bob ?? 0)).toFixed(2))"></div>
                    </div>
                </div>

                {{-- Tabla de Bodegas --}}
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="px-4 py-3 text-left">Bodega</th>
                                <th class="px-4 py-3 text-left">Departamento</th>
                                <th class="px-4 py-3 text-center">Stock Disponible</th>
                                <th class="px-4 py-3 text-center">Stock Físico</th>
                                <th class="px-4 py-3 text-right">Valorado Costo BOB</th>
                                <th class="px-4 py-3 text-right">Valorado Sugerido BOB</th>
                                <th class="px-4 py-3 text-center">Participación %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!report?.valuation?.warehouses?.length">
                                <tr><td colspan="7" class="text-center text-slate-400 py-10 italic">No hay inventario registrado en bodegas.</td></tr>
                            </template>
                            <template x-for="wh in report?.valuation?.warehouses ?? []" :key="wh.warehouse_id">
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 font-extrabold text-slate-800" x-text="wh.warehouse_name"></td>
                                    <td class="px-4 py-3 text-slate-500" x-text="wh.department"></td>
                                    <td class="px-4 py-3 text-center font-bold text-indigo-700" x-text="wh.stock_available"></td>
                                    <td class="px-4 py-3 text-center text-slate-600" x-text="wh.stock_physical"></td>
                                    <td class="px-4 py-3 text-right font-black text-slate-800" x-text="'Bs ' + wh.valuation_cost_bob"></td>
                                    <td class="px-4 py-3 text-right font-black text-emerald-700" x-text="'Bs ' + wh.valuation_suggested_bob"></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center gap-2 justify-center">
                                            <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-violet-600 h-2 rounded-full" :style="'width:' + wh.participation_pct + '%'"></div>
                                            </div>
                                            <span class="font-bold text-violet-700" x-text="wh.participation_pct + '%'"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr class="border-t-2 border-slate-300 bg-slate-50 font-black text-xs">
                                <td class="px-4 py-3 text-slate-800" colspan="4">TOTAL INVENTARIO</td>
                                <td class="px-4 py-3 text-right text-violet-700" x-text="'Bs ' + (report?.valuation?.total_valuation_cost_bob ?? 0)"></td>
                                <td class="px-4 py-3 text-right text-emerald-700" x-text="'Bs ' + (report?.valuation?.total_valuation_suggested_bob ?? 0)"></td>
                                <td class="px-4 py-3 text-center text-slate-500">100%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================================================================
             MÓDULO 5: DISTRIBUCIÓN GEOGRÁFICA
        ================================================================ --}}
        <div x-show="!loading && report && activeTab === 'geo'" class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 md:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-map-location-dot text-teal-600"></i> Distribución Geográfica de Ventas en Bolivia
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Penetración de mercado por departamento (Santa Cruz, La Paz, Cochabamba, etc.).</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="dept in report?.geo ?? []" :key="dept.department">
                        <div class="flex items-center gap-4 p-2 rounded-xl hover:bg-slate-50 transition">
                            <div class="w-32 text-xs font-bold text-slate-700 truncate" x-text="dept.department"></div>
                            <div class="flex-1 bg-slate-100 rounded-full h-4 overflow-hidden relative">
                                <div class="h-4 bg-gradient-to-r from-teal-500 to-indigo-600 rounded-full transition-all duration-700"
                                     :style="'width:' + dept.participation_pct + '%'"></div>
                            </div>
                            <span class="w-12 text-xs text-right font-black text-teal-700" x-text="dept.participation_pct + '%'"></span>
                            <span class="w-16 text-xs text-right text-slate-500" x-text="dept.orders + ' ped.'"></span>
                            <span class="w-24 text-xs text-right font-black text-slate-800" x-text="'Bs ' + dept.total_cost_bob"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ================================================================
             MÓDULO 6: TOP VENDEDORES
        ================================================================ --}}
        <div x-show="!loading && report && activeTab === 'sellers'" class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 md:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-trophy text-amber-500"></i> Ranking Top Dropshippers & Vendedores
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Vendedores con mayor volumen de unidades y ventas en el período.</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="(seller, idx) in report?.top_sellers ?? []" :key="seller.seller_id">
                        <div class="flex items-center gap-4 p-3.5 rounded-2xl border border-slate-100 hover:bg-slate-50/80 transition">
                            <div class="w-8 text-center">
                                <span x-show="idx === 0" class="text-2xl">🥇</span>
                                <span x-show="idx === 1" class="text-2xl">🥈</span>
                                <span x-show="idx === 2" class="text-2xl">🥉</span>
                                <span x-show="idx >= 3" class="text-xs font-black text-slate-400" x-text="idx + 1"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-extrabold text-slate-800 truncate" x-text="seller.seller_name"></div>
                                <div class="text-[11px] text-slate-400 truncate" x-text="(seller.store_name ?? 'Tienda') + (seller.city ? ' · ' + seller.city : '')"></div>
                            </div>
                            <div class="text-center hidden sm:block">
                                <div class="font-bold text-indigo-600" x-text="seller.orders"></div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">pedidos</div>
                            </div>
                            <div class="text-center hidden sm:block">
                                <div class="font-bold text-slate-700" x-text="seller.units"></div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">unidades</div>
                            </div>
                            <div class="text-right">
                                <div class="font-black text-emerald-700 text-sm" x-text="'Bs ' + seller.total_cost_bob"></div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">volumen mayorista</div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ================================================================
             MÓDULO 7: MOVIMIENTOS DETALLADOS DE VENTAS
        ================================================================ --}}
        <div x-show="!loading && report && activeTab === 'movimientos'" class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 md:p-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-indigo-600"></i> Movimientos Detallados de Ventas
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Auditoría ítem por ítem con estado de pedido, guía y conciliación.</p>
                    </div>
                    
                    {{-- Filtros y Búsqueda --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" x-model="movementSearch" placeholder="Buscar pedido, producto, SKU, cliente..."
                               class="border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-300 outline-none w-64">
                        <select x-model="movementStatusFilter"
                                class="border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-300 outline-none">
                            <option value="all">Todos los estados</option>
                            <option value="pending">Pendiente</option>
                            <option value="confirmed">Confirmado</option>
                            <option value="dispatched">Despachado</option>
                            <option value="delivered">Entregado</option>
                            <option value="settled">Liquidado</option>
                            <option value="cancelled">Cancelado</option>
                            <option value="returned">Devuelto</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400 mb-3">
                    <div>
                        Mostrando <span class="font-extrabold text-slate-700" x-text="filteredMovements().length"></span>
                        de <span x-text="report?.movements?.length ?? 0"></span> registros
                    </div>
                    <a :href="getExportUrl()" class="text-emerald-700 font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-download"></i> Descargar listado completo en CSV
                    </a>
                </div>

                {{-- Tabla Virtualizada --}}
                <div class="overflow-x-auto rounded-2xl border border-slate-100 max-h-[550px] overflow-y-auto">
                    <table class="w-full text-xs">
                        <thead class="sticky top-0 bg-white z-10 shadow-sm">
                            <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="px-3 py-3 text-left">Fecha</th>
                                <th class="px-3 py-3 text-left">Pedido</th>
                                <th class="px-3 py-3 text-left">Guía DEX</th>
                                <th class="px-3 py-3 text-left">Producto</th>
                                <th class="px-3 py-3 text-center">Cant.</th>
                                <th class="px-3 py-3 text-right">Costo Unit.</th>
                                <th class="px-3 py-3 text-right">Subtotal</th>
                                <th class="px-3 py-3 text-left">Cliente</th>
                                <th class="px-3 py-3 text-left">Ciudad</th>
                                <th class="px-3 py-3 text-center">Estado Pedido</th>
                                <th class="px-3 py-3 text-center">Liquidación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!filteredMovements().length">
                                <tr><td colspan="11" class="text-center text-slate-400 py-10 italic">No se encontraron movimientos que coincidan con la búsqueda.</td></tr>
                            </template>
                            <template x-for="mv in filteredMovements()" :key="mv.order_item_id">
                                <tr class="border-b border-slate-100 hover:bg-indigo-50/20 transition">
                                    <td class="px-3 py-2.5 font-mono text-slate-400 whitespace-nowrap" x-text="mv.created_at"></td>
                                    <td class="px-3 py-2.5 font-mono font-bold text-indigo-600" x-text="mv.order_number"></td>
                                    <td class="px-3 py-2.5 font-mono text-slate-500" x-text="mv.tracking_guide ?? '—'"></td>
                                    <td class="px-3 py-2.5 max-w-[160px] truncate font-semibold text-slate-800" :title="mv.product_name" x-text="mv.product_name"></td>
                                    <td class="px-3 py-2.5 text-center font-extrabold text-slate-700" x-text="mv.quantity"></td>
                                    <td class="px-3 py-2.5 text-right text-slate-600" x-text="'Bs ' + mv.unit_cost_bob"></td>
                                    <td class="px-3 py-2.5 text-right font-extrabold text-emerald-700" x-text="'Bs ' + mv.subtotal_cost_bob"></td>
                                    <td class="px-3 py-2.5 text-slate-600 max-w-[120px] truncate" :title="mv.customer_name" x-text="mv.customer_name"></td>
                                    <td class="px-3 py-2.5 text-slate-500 whitespace-nowrap" x-text="mv.customer_city ?? '—'"></td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span :class="{
                                            'bg-emerald-100 text-emerald-700': mv.order_status === 'delivered' || mv.order_status === 'settled',
                                            'bg-amber-100 text-amber-700': mv.order_status === 'dispatched',
                                            'bg-indigo-100 text-indigo-700': mv.order_status === 'confirmed',
                                            'bg-slate-100 text-slate-500': mv.order_status === 'pending',
                                            'bg-rose-100 text-rose-700': mv.order_status === 'cancelled' || mv.order_status === 'returned'
                                        }" class="px-2 py-0.5 rounded-full font-bold text-[11px] whitespace-nowrap" x-text="mv.order_status_label ?? mv.order_status"></span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span :class="'px-2 py-0.5 rounded-full font-bold text-[11px] ' + (mv.settlement_badge ?? 'bg-slate-100 text-slate-400')"
                                              x-text="mv.settlement_status_label ?? '—'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- SCRIPT REACTIVO ALPINE.JS --}}
<script>
function reportesHubPage() {
    return {
        activeTab: 'kpis',
        loading:   false,
        report:    null,
        dateFrom:  '',
        dateTo:    '',
        movementSearch: '',
        movementStatusFilter: 'all',
        sortField: 'units_sold',
        sortDir:   'desc',
        selectedProviderId: '{{ $provider?->id ?? "" }}',

        getExportUrl() {
            const params = new URLSearchParams();
            if (this.dateFrom) params.set('date_from', this.dateFrom);
            if (this.dateTo)   params.set('date_to',   this.dateTo);
            if (this.selectedProviderId) params.set('provider_id', this.selectedProviderId);
            return '/reportes/exportar?' + params.toString();
        },

        async init() {
            const to   = new Date();
            const from = new Date();
            from.setDate(from.getDate() - 29);
            this.dateTo   = to.toISOString().split('T')[0];
            this.dateFrom = from.toISOString().split('T')[0];
            await this.loadReportData();
        },

        setPreset(days) {
            const to   = new Date();
            const from = new Date();
            from.setDate(from.getDate() - (days - 1));
            this.dateTo   = to.toISOString().split('T')[0];
            this.dateFrom = from.toISOString().split('T')[0];
            this.loadReportData();
        },

        setPresetMonth() {
            const now  = new Date();
            const from = new Date(now.getFullYear(), now.getMonth(), 1);
            this.dateTo   = now.toISOString().split('T')[0];
            this.dateFrom = from.toISOString().split('T')[0];
            this.loadReportData();
        },

        setPresetYear() {
            const now  = new Date();
            const from = new Date(now.getFullYear(), 0, 1);
            this.dateTo   = now.toISOString().split('T')[0];
            this.dateFrom = from.toISOString().split('T')[0];
            this.loadReportData();
        },

        async loadReportData() {
            this.loading = true;
            this.report  = null;
            try {
                const params = new URLSearchParams();
                if (this.dateFrom) params.set('date_from', this.dateFrom);
                if (this.dateTo)   params.set('date_to',   this.dateTo);
                if (this.selectedProviderId) params.set('provider_id', this.selectedProviderId);
                const res  = await fetch('/reportes/metricas?' + params, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.report = data.data;
                } else {
                    console.warn('Error al cargar reporte:', data.message);
                }
            } catch (e) {
                console.error('Error cargando métricas:', e);
            } finally {
                this.loading = false;
            }
        },

        filteredMovements() {
            if (!this.report?.movements) return [];
            return this.report.movements.filter(m => {
                const matchStatus = this.movementStatusFilter === 'all'
                    || m.order_status === this.movementStatusFilter;
                const q = this.movementSearch.toLowerCase().trim();
                const matchSearch = !q
                    || (m.product_name   ?? '').toLowerCase().includes(q)
                    || (m.order_number   ?? '').toLowerCase().includes(q)
                    || (m.customer_name  ?? '').toLowerCase().includes(q)
                    || (m.product_sku    ?? '').toLowerCase().includes(q)
                    || (m.tracking_guide ?? '').toLowerCase().includes(q);
                return matchStatus && matchSearch;
            });
        },

        sortedRotation() {
            if (!this.report?.rotation?.items) return [];
            const items = [...this.report.rotation.items];
            const dir   = this.sortDir === 'asc' ? 1 : -1;
            return items.sort((a, b) => {
                const av = a[this.sortField] ?? 0;
                const bv = b[this.sortField] ?? 0;
                return dir * (av > bv ? 1 : av < bv ? -1 : 0);
            });
        },

        toggleSort(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir   = 'desc';
            }
        }
    };
}
</script>
@endsection
