@extends('layouts.app')

@section('title', 'Centro de Reportes & Métricas | Bolidrop Bolivia')

@section('content')
<div class="space-y-8" x-data="reportesHubPage()" x-init="init()">

    {{-- =====================================================================
         HEADER OFICIAL DEL SISTEMA (<x-page-header>)
    ===================================================================== --}}
    <x-page-header
        title="Centro de Reportes & Métricas"
        :subtitle="'Auditoría analítica integral • ' . ($provider?->name ?? 'Megabodega Bolidrop') . ' • Actualizado en vivo: ' . now()->format('H:i:s')"
        icon="fa-solid fa-chart-pie"
        badge="Bolidrop Analytics • Inteligencia de Ventas & Inventario">
        <x-slot:actions>
            {{-- Selector de Proveedor para Administradores --}}
            @if(Auth::user()->isTenantAdmin() && $providers->count() > 1)
            <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl px-3 py-2 shadow-xs">
                <i class="fa-solid fa-building text-sky-300 text-xs"></i>
                <label class="text-[11px] font-bold text-white uppercase tracking-wider">Proveedor:</label>
                <select x-model="selectedProviderId" @change="loadReportData()"
                        class="bg-transparent text-white text-xs font-semibold outline-none appearance-none cursor-pointer pr-4 focus:ring-0">
                    @foreach($providers as $prov)
                        <option value="{{ $prov->id }}" class="text-slate-900">{{ $prov->name }}</option>
                    @endforeach
                </select>
                <i class="fa-solid fa-chevron-down text-white/60 text-[10px]"></i>
            </div>
            @endif

            {{-- Botón Exportar CSV --}}
            <a :href="getExportUrl()"
               class="px-4 py-2.5 rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-white font-bold text-xs inline-flex items-center gap-2 shadow-md transition cursor-pointer">
                <i class="fa-solid fa-file-excel text-amber-200"></i>
                <span>Exportar CSV</span>
            </a>

            {{-- Botón Volver al Panel --}}
            <a href="{{ Auth::user()->isProvider() ? url('/panel/proveedor') : url('/panel/admin') }}"
               class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-xs inline-flex items-center gap-2 backdrop-blur-sm transition">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Volver al Panel</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- =====================================================================
         FILTROS DE FECHA (Stripe & Linear Executive Overlap !-mt-14)
    ===================================================================== --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4 !-mt-14 relative z-10 interactive-card">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            
            {{-- Rango de Fechas & Presets --}}
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-regular fa-calendar text-[#4F46E5]"></i> Desde
                    </label>
                    <input type="date" x-model="dateFrom"
                           class="border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono text-slate-800 focus:ring-2 focus:ring-[#4F46E5] outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-regular fa-calendar-check text-[#4F46E5]"></i> Hasta
                    </label>
                    <input type="date" x-model="dateTo"
                           class="border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono text-slate-800 focus:ring-2 focus:ring-[#4F46E5] outline-none">
                </div>

                {{-- Presets de acceso rápido --}}
                <div class="flex flex-wrap items-center gap-1.5 pt-2 sm:pt-0">
                    <button @click="setPreset(1)"
                            :class="activePreset === 1 ? 'bg-[#4F46E5] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition">Hoy</button>
                    <button @click="setPreset(7)"
                            :class="activePreset === 7 ? 'bg-[#4F46E5] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition">7 días</button>
                    <button @click="setPreset(30)"
                            :class="activePreset === 30 ? 'bg-[#4F46E5] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition">30 días</button>
                    <button @click="setPreset(90)"
                            :class="activePreset === 90 ? 'bg-[#4F46E5] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition">90 días</button>
                    <button @click="setPresetMonth()"
                            :class="activePreset === 'month' ? 'bg-[#4F46E5] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition">Este mes</button>
                    <button @click="setPresetYear()"
                            :class="activePreset === 'year' ? 'bg-[#4F46E5] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition">Este año</button>
                </div>
            </div>

            {{-- Botón Aplicar Filtro --}}
            <div class="flex items-center gap-3">
                <button @click="loadReportData()" :disabled="loading"
                        class="px-5 py-2.5 rounded-xl bg-[#4F46E5] hover:bg-[#4338CA] disabled:opacity-60 text-white font-bold text-xs inline-flex items-center gap-2 shadow-md transition cursor-pointer">
                    <i class="fa-solid fa-rotate" :class="{'animate-spin': loading}"></i>
                    <span x-text="loading ? 'Actualizando datos...' : 'Aplicar Filtro'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- =====================================================================
         STICKY NAVIGATION TABS (Bolidrop Standard)
    ===================================================================== --}}
    <nav class="sticky top-14 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 rounded-2xl shadow-sm px-2 py-1.5 flex items-center gap-1 overflow-x-auto">
        <button @click="activeTab = 'kpis'"
                :class="activeTab === 'kpis' ? 'text-[#4F46E5] bg-indigo-50/80 font-bold border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-[#4F46E5] hover:bg-slate-100 font-semibold'"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs transition whitespace-nowrap">
            <i class="fa-solid fa-gauge-high text-xs" :class="activeTab === 'kpis' ? 'text-[#4F46E5]' : 'text-slate-400'"></i>
            <span>Resumen KPIs</span>
        </button>

        <button @click="activeTab = 'embudo'"
                :class="activeTab === 'embudo' ? 'text-[#4F46E5] bg-indigo-50/80 font-bold border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-[#4F46E5] hover:bg-slate-100 font-semibold'"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs transition whitespace-nowrap">
            <i class="fa-solid fa-filter text-xs" :class="activeTab === 'embudo' ? 'text-[#4F46E5]' : 'text-slate-400'"></i>
            <span>Embudo COD</span>
        </button>

        <button @click="activeTab = 'rotacion'"
                :class="activeTab === 'rotacion' ? 'text-[#4F46E5] bg-indigo-50/80 font-bold border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-[#4F46E5] hover:bg-slate-100 font-semibold'"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs transition whitespace-nowrap">
            <i class="fa-solid fa-rotate text-xs" :class="activeTab === 'rotacion' ? 'text-[#4F46E5]' : 'text-slate-400'"></i>
            <span>Rotación ABC & Runway</span>
        </button>

        <button @click="activeTab = 'valorado'"
                :class="activeTab === 'valorado' ? 'text-[#4F46E5] bg-indigo-50/80 font-bold border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-[#4F46E5] hover:bg-slate-100 font-semibold'"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs transition whitespace-nowrap">
            <i class="fa-solid fa-warehouse text-xs" :class="activeTab === 'valorado' ? 'text-[#4F46E5]' : 'text-slate-400'"></i>
            <span>Valorado Inventario</span>
        </button>

        <button @click="activeTab = 'geo'"
                :class="activeTab === 'geo' ? 'text-[#4F46E5] bg-indigo-50/80 font-bold border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-[#4F46E5] hover:bg-slate-100 font-semibold'"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs transition whitespace-nowrap">
            <i class="fa-solid fa-map-location-dot text-xs" :class="activeTab === 'geo' ? 'text-[#4F46E5]' : 'text-slate-400'"></i>
            <span>Geografía Bolivia</span>
        </button>

        <button @click="activeTab = 'sellers'"
                :class="activeTab === 'sellers' ? 'text-[#4F46E5] bg-indigo-50/80 font-bold border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-[#4F46E5] hover:bg-slate-100 font-semibold'"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs transition whitespace-nowrap">
            <i class="fa-solid fa-trophy text-xs" :class="activeTab === 'sellers' ? 'text-[#4F46E5]' : 'text-slate-400'"></i>
            <span>Top Dropshippers</span>
        </button>

        <button @click="activeTab = 'movimientos'"
                :class="activeTab === 'movimientos' ? 'text-[#4F46E5] bg-indigo-50/80 font-bold border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-[#4F46E5] hover:bg-slate-100 font-semibold'"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs transition whitespace-nowrap">
            <i class="fa-solid fa-list-check text-xs" :class="activeTab === 'movimientos' ? 'text-[#4F46E5]' : 'text-slate-400'"></i>
            <span>Movimientos de Ventas</span>
        </button>
    </nav>

    {{-- =====================================================================
         SPINNER DE CARGA
    ===================================================================== --}}
    <div x-show="loading" class="flex flex-col items-center justify-center py-20 gap-3">
        <div class="w-10 h-10 border-4 border-indigo-200 border-t-[#4F46E5] rounded-full animate-spin"></div>
        <p class="text-xs font-semibold text-slate-500">Consolidando métricas e inventarios en vivo...</p>
    </div>

    {{-- =====================================================================
         ESTADO VACÍO
    ===================================================================== --}}
    <div x-show="!loading && !report" class="text-center py-16 bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-[#4F46E5] flex items-center justify-center mx-auto mb-3 text-xl">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <h3 class="text-sm font-extrabold text-[#0F172A]">Sin información en el período seleccionado</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Selecciona otro rango de fechas o presiona "Aplicar Filtro" para sincronizar.</p>
    </div>

    {{-- =====================================================================
         CONTENIDO POR PESTAÑAS
    ===================================================================== --}}
    <div x-show="!loading && report" class="space-y-8">

        {{-- ============================================================
             TAB 1: RESUMEN KPIS (TARJETAS OFICIALES BOLIDROP)
        ============================================================ --}}
        <div x-show="activeTab === 'kpis'" class="space-y-6">
            
            {{-- Grid de 6 Tarjetas con diseño del sistema --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                
                {{-- Tarjeta 1: Ingresos Mayoristas (Signature Gradient #0F172A -> #1E1B4B -> #4F46E5) --}}
                <div class="bg-gradient-to-br from-[#0F172A] via-[#1E1B4B] to-[#4F46E5] rounded-2xl p-5 text-white shadow-sm space-y-2 border border-indigo-400/20 interactive-card">
                    <div class="flex items-center gap-2 text-indigo-200 text-[11px] font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-sack-dollar text-[#38BDF8]"></i> Ingresos Proveedor
                    </div>
                    <div class="text-2xl font-extrabold font-mono" x-text="'Bs ' + (report?.funnel?.total_provider_cost_bob ?? '0.00')"></div>
                    <div class="text-indigo-200/90 text-[11px] font-medium">Costo acreditado total</div>
                </div>

                {{-- Tarjeta 2: Unidades Vendidas --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2 interactive-card">
                    <div class="flex items-center gap-2 text-[#4F46E5] text-[11px] font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-boxes-stacked text-[#4F46E5]"></i> Uds Vendidas
                    </div>
                    <div class="text-2xl font-extrabold font-mono text-[#0F172A]" x-text="(report?.summary_kpis?.total_units_sold ?? 0) + ' uds'"></div>
                    <div class="text-slate-500 text-[11px]">En órdenes del período</div>
                </div>

                {{-- Tarjeta 3: Tasa de Entrega COD --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2 interactive-card">
                    <div class="flex items-center gap-2 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-circle-check text-[#10B981]"></i> Tasa de Entrega
                    </div>
                    <div class="text-2xl font-extrabold font-mono text-[#10B981]" x-text="(report?.funnel?.delivery_rate_percent ?? 0) + '%'"></div>
                    <div class="text-slate-500 text-[11px]">Efectividad logística DEX</div>
                </div>

                {{-- Tarjeta 4: Valorado de Inventario al Costo --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2 interactive-card">
                    <div class="flex items-center gap-2 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-cubes text-[#4F46E5]"></i> Valorado Costo
                    </div>
                    <div class="text-2xl font-extrabold font-mono text-[#0F172A]" x-text="'Bs ' + (report?.valuation?.total_valuation_cost_bob ?? '0.00')"></div>
                    <div class="text-slate-500 text-[11px]">Capital físico en bodegas</div>
                </div>

                {{-- Tarjeta 5: Saldo Disponible Billetera --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2 interactive-card">
                    <div class="flex items-center gap-2 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-wallet text-[#F59E0B]"></i> Saldo Billetera
                    </div>
                    <div class="text-2xl font-extrabold font-mono text-[#F59E0B]" x-text="'Bs ' + (report?.wallet?.available_bob ?? '0.00')"></div>
                    <div class="text-slate-500 text-[11px]">Disponible para retiro</div>
                </div>

                {{-- Tarjeta 6: Top Producto Estrella --}}
                <div class="bg-white rounded-2xl border border-indigo-200 p-5 shadow-sm space-y-2 interactive-card">
                    <div class="flex items-center gap-2 text-[#4F46E5] text-[11px] font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-crown text-amber-500"></i> Producto Top
                    </div>
                    <div class="text-xs font-black text-[#0F172A] line-clamp-1" :title="report?.summary_kpis?.top_mover_product?.name" x-text="report?.summary_kpis?.top_mover_product?.name ?? '—'"></div>
                    <div class="text-[11px] font-bold text-[#4F46E5]" x-text="(report?.summary_kpis?.top_mover_product?.sold ?? 0) + ' uds vendidas'"></div>
                </div>
            </div>

            {{-- Ficha de Balance Operativo --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-extrabold text-[#0F172A] flex items-center gap-2">
                            <i class="fa-solid fa-chart-line text-[#4F46E5]"></i> Balance Operativo del Período
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Rango analizado: <span class="font-bold text-[#4F46E5] font-mono" x-text="dateFrom + ' al ' + dateTo"></span></p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold border border-emerald-200">
                        <i class="fa-solid fa-shield-halved text-xs"></i> Trazabilidad Verificada
                    </span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-2">
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-black font-mono text-[#0F172A]" x-text="report?.funnel?.total_orders ?? 0"></div>
                        <div class="text-[11px] font-bold text-slate-500 mt-1">Total Pedidos COD</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-black font-mono text-[#4F46E5]" x-text="report?.summary_kpis?.total_units_sold ?? 0"></div>
                        <div class="text-[11px] font-bold text-slate-500 mt-1">Unidades Vendidas</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-black font-mono text-[#10B981]" x-text="report?.funnel?.phase_3_delivered?.count ?? 0"></div>
                        <div class="text-[11px] font-bold text-slate-500 mt-1">Entregas Exitosas</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-black font-mono text-rose-600" x-text="report?.funnel?.phase_4_novedades?.count ?? 0"></div>
                        <div class="text-[11px] font-bold text-slate-500 mt-1">Devoluciones / Novedades</div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4 text-xs">
                    <div class="flex items-center gap-6">
                        <div>
                            <span class="text-slate-500">Valorado al Costo:</span>
                            <span class="font-bold font-mono text-[#0F172A] ml-1.5" x-text="'Bs ' + (report?.valuation?.total_valuation_cost_bob ?? '0.00')"></span>
                        </div>
                        <div>
                            <span class="text-slate-500">Valorado Precio Sugerido:</span>
                            <span class="font-bold font-mono text-[#10B981] ml-1.5" x-text="'Bs ' + (report?.valuation?.total_valuation_suggested_bob ?? '0.00')"></span>
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-500">Saldo Disponible en Billetera:</span>
                        <span class="font-bold font-mono text-[#F59E0B] ml-1.5" x-text="'Bs ' + (report?.wallet?.available_bob ?? '0.00')"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             TAB 2: EMBUDO COD
        ============================================================ --}}
        <div x-show="activeTab === 'embudo'" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-extrabold text-[#0F172A] flex items-center gap-2">
                            <i class="fa-solid fa-filter text-[#4F46E5]"></i> Embudo de Conversión & Logística Contra Entrega (COD)
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Seguimiento de las 4 etapas del pedido desde la confirmación hasta la entrega y cobro.</p>
                    </div>
                    <div class="text-right">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Efectividad Global</span>
                        <div class="text-2xl font-black font-mono text-[#10B981]" x-text="(report?.funnel?.delivery_rate_percent ?? 0) + '%'"></div>
                    </div>
                </div>

                {{-- 4 Fases del Embudo COD --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Fase 1 --}}
                    <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-5 relative overflow-hidden interactive-card">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-7 h-7 rounded-lg bg-[#4F46E5] text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </span>
                            <span class="text-xs font-extrabold text-indigo-950 uppercase tracking-wider">1. En Bodega</span>
                        </div>
                        <div class="text-3xl font-black font-mono text-indigo-900" x-text="report?.funnel?.phase_1_prep?.count ?? 0"></div>
                        <div class="text-xs text-indigo-700 font-semibold mt-1">Confirmados / Preparación</div>
                        <div class="mt-4 pt-3 border-t border-indigo-200/60 text-xs font-bold font-mono text-indigo-950" x-text="'Bs ' + (report?.funnel?.phase_1_prep?.total_bob ?? '0.00')"></div>
                    </div>

                    {{-- Fase 2 --}}
                    <div class="bg-amber-50/70 border border-amber-100 rounded-2xl p-5 relative overflow-hidden interactive-card">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-7 h-7 rounded-lg bg-[#F59E0B] text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-truck-fast"></i>
                            </span>
                            <span class="text-xs font-extrabold text-amber-950 uppercase tracking-wider">2. En Ruta DEX</span>
                        </div>
                        <div class="text-3xl font-black font-mono text-amber-900" x-text="report?.funnel?.phase_2_transit?.count ?? 0"></div>
                        <div class="text-xs text-amber-700 font-semibold mt-1">Despachados / Con Transportadora</div>
                        <div class="mt-4 pt-3 border-t border-amber-200/60 text-xs font-bold font-mono text-amber-950" x-text="'Bs ' + (report?.funnel?.phase_2_transit?.total_bob ?? '0.00')"></div>
                    </div>

                    {{-- Fase 3 --}}
                    <div class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-5 relative overflow-hidden interactive-card">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-7 h-7 rounded-lg bg-[#10B981] text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-circle-check"></i>
                            </span>
                            <span class="text-xs font-extrabold text-emerald-950 uppercase tracking-wider">3. Entregados</span>
                        </div>
                        <div class="text-3xl font-black font-mono text-emerald-900" x-text="report?.funnel?.phase_3_delivered?.count ?? 0"></div>
                        <div class="text-xs text-emerald-700 font-semibold mt-1">Entregados & Cobrados</div>
                        <div class="mt-4 pt-3 border-t border-emerald-200/60 text-xs font-bold font-mono text-emerald-950" x-text="'Bs ' + (report?.funnel?.phase_3_delivered?.total_bob ?? '0.00')"></div>
                    </div>

                    {{-- Fase 4 --}}
                    <div class="bg-rose-50/70 border border-rose-100 rounded-2xl p-5 relative overflow-hidden interactive-card">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-7 h-7 rounded-lg bg-[#F43F5E] text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </span>
                            <span class="text-xs font-extrabold text-rose-950 uppercase tracking-wider">4. Novedades</span>
                        </div>
                        <div class="text-3xl font-black font-mono text-rose-900" x-text="report?.funnel?.phase_4_novedades?.count ?? 0"></div>
                        <div class="text-xs text-rose-700 font-semibold mt-1">Devoluciones / Fallidos</div>
                        <div class="mt-4 pt-3 border-t border-rose-200/60 text-xs font-bold font-mono text-rose-950" x-text="'Bs ' + (report?.funnel?.phase_4_novedades?.total_bob ?? '0.00')"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             TAB 3: ROTACIÓN ABC & RUNWAY
        ============================================================ --}}
        <div x-show="activeTab === 'rotacion'" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-extrabold text-[#0F172A] flex items-center gap-2">
                            <i class="fa-solid fa-rotate text-[#4F46E5]"></i> Matriz de Rotación ABC & Días de Runway
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Clasificación estratégica según volumen de ventas y proyección de agotamiento de inventario.</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold">A = Alta Rotación</span>
                        <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full font-bold">B = Rotación Media</span>
                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full font-bold">C = Baja Rotación</span>
                    </div>
                </div>

                {{-- Controles de Ordenamiento --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-400 mr-1">Ordenar por:</span>
                    <button @click="toggleSort('units_sold')"
                            :class="sortField === 'units_sold' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition">
                        Uds Vendidas <span x-show="sortField === 'units_sold'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                    </button>
                    <button @click="toggleSort('runway_days')"
                            :class="sortField === 'runway_days' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition">
                        Días de Runway <span x-show="sortField === 'runway_days'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                    </button>
                    <button @click="toggleSort('velocity_per_day')"
                            :class="sortField === 'velocity_per_day' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition">
                        Velocidad Diaria <span x-show="sortField === 'velocity_per_day'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                    </button>
                    <button @click="toggleSort('stock_available')"
                            :class="sortField === 'stock_available' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition">
                        Stock Disponible <span x-show="sortField === 'stock_available'" x-text="sortDir === 'desc' ? '↓' : '↑'"></span>
                    </button>
                </div>

                {{-- Tabla de Rotación --}}
                <div class="overflow-x-auto rounded-xl border border-slate-100">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="bg-slate-50/90 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                                <th class="px-4 py-3">Producto</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3 text-center">Stock Disp.</th>
                                <th class="px-4 py-3 text-center">Uds Vendidas</th>
                                <th class="px-4 py-3 text-center">Velocidad/Día</th>
                                <th class="px-4 py-3 text-center">Runway (Días)</th>
                                <th class="px-4 py-3 text-center">Tasa Rotación</th>
                                <th class="px-4 py-3 text-center">Clase ABC</th>
                                <th class="px-4 py-3">Recomendación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!report?.rotation?.items?.length">
                                <tr><td colspan="9" class="text-center text-slate-400 py-8 italic">No hay productos con movimientos en este período.</td></tr>
                            </template>
                            <template x-for="item in sortedRotation()" :key="item.product_id">
                                <tr class="border-b border-slate-100 hover:bg-indigo-50/20 transition">
                                    <td class="px-4 py-3 font-bold text-[#0F172A] max-w-[200px] truncate" x-text="item.product_name"></td>
                                    <td class="px-4 py-3 font-mono text-slate-400" x-text="item.sku ?? '—'"></td>
                                    <td class="px-4 py-3 text-center font-bold font-mono text-slate-700" x-text="item.stock_available"></td>
                                    <td class="px-4 py-3 text-center font-extrabold font-mono text-[#4F46E5]" x-text="item.units_sold"></td>
                                    <td class="px-4 py-3 text-center font-mono text-slate-600" x-text="item.velocity_per_day"></td>
                                    <td class="px-4 py-3 text-center font-mono font-bold">
                                        <span :class="{
                                            'text-emerald-700': item.runway_days > 60,
                                            'text-amber-700': item.runway_days > 15 && item.runway_days <= 60,
                                            'text-rose-600 font-black': item.runway_days !== 9999 && item.runway_days <= 15
                                        }" x-text="item.runway_days === 9999 ? '∞' : item.runway_days"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-slate-600" x-text="item.rotation_rate + '%'"></td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': item.abc_class === 'A',
                                            'bg-indigo-50 text-indigo-700 border border-indigo-200': item.abc_class === 'B',
                                            'bg-amber-50 text-amber-700 border border-amber-200': item.abc_class === 'C'
                                        }" class="px-2.5 py-0.5 rounded-full font-black text-[10px]" x-text="item.abc_class"></span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 italic text-xs" x-text="item.recommendation"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============================================================
             TAB 4: VALORADO DE INVENTARIO
        ============================================================ --}}
        <div x-show="activeTab === 'valorado'" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
                <div class="pb-3 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-[#0F172A] flex items-center gap-2">
                        <i class="fa-solid fa-warehouse text-[#4F46E5]"></i> Valorado de Inventario Físico por Bodega
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Control de capital en stock valuado al costo mayorista y valor de venta sugerido.</p>
                </div>

                {{-- 3 Cajas de Resumen --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-5 space-y-1">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Valorado al Costo Total</div>
                        <div class="text-2xl font-black font-mono text-[#0F172A]" x-text="'Bs ' + (report?.valuation?.total_valuation_cost_bob ?? '0.00')"></div>
                        <div class="text-[11px] text-slate-400">Capital real inmovilizado</div>
                    </div>
                    <div class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-5 space-y-1">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Valorado Precio Sugerido</div>
                        <div class="text-2xl font-black font-mono text-emerald-900" x-text="'Bs ' + (report?.valuation?.total_valuation_suggested_bob ?? '0.00')"></div>
                        <div class="text-[11px] text-emerald-600">Valor proyectado en catálogo</div>
                    </div>
                    <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-5 space-y-1">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Margen Comercial Potencial</div>
                        <div class="text-2xl font-black font-mono text-[#4F46E5]" x-text="'Bs ' + ((parseFloat(report?.valuation?.total_valuation_suggested_bob ?? 0) - parseFloat(report?.valuation?.total_valuation_cost_bob ?? 0)).toFixed(2))"></div>
                        <div class="text-[11px] text-indigo-600">Margen para la red comercial</div>
                    </div>
                </div>

                {{-- Tabla de Bodegas --}}
                <div class="overflow-x-auto rounded-xl border border-slate-100">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="bg-slate-50/90 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                                <th class="px-4 py-3">Bodega</th>
                                <th class="px-4 py-3">Departamento</th>
                                <th class="px-4 py-3 text-center">Stock Disp.</th>
                                <th class="px-4 py-3 text-center">Stock Físico</th>
                                <th class="px-4 py-3 text-right">Valorado Costo BOB</th>
                                <th class="px-4 py-3 text-right">Valorado Sugerido BOB</th>
                                <th class="px-4 py-3 text-center">Participación %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!report?.valuation?.warehouses?.length">
                                <tr><td colspan="7" class="text-center text-slate-400 py-8 italic">No hay inventario en bodegas actualmente.</td></tr>
                            </template>
                            <template x-for="wh in report?.valuation?.warehouses ?? []" :key="wh.warehouse_id">
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 font-bold text-[#0F172A]" x-text="wh.warehouse_name"></td>
                                    <td class="px-4 py-3 text-slate-500" x-text="wh.department"></td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-[#4F46E5]" x-text="wh.stock_available"></td>
                                    <td class="px-4 py-3 text-center font-mono text-slate-600" x-text="wh.stock_physical"></td>
                                    <td class="px-4 py-3 text-right font-black font-mono text-[#0F172A]" x-text="'Bs ' + wh.valuation_cost_bob"></td>
                                    <td class="px-4 py-3 text-right font-black font-mono text-emerald-700" x-text="'Bs ' + wh.valuation_suggested_bob"></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center gap-2 justify-center">
                                            <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-[#4F46E5] h-2 rounded-full" :style="'width:' + wh.participation_pct + '%'"></div>
                                            </div>
                                            <span class="font-bold font-mono text-[#4F46E5]" x-text="wh.participation_pct + '%'"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr class="border-t-2 border-slate-300 bg-slate-50 font-black text-xs">
                                <td class="px-4 py-3 text-[#0F172A]" colspan="4">TOTAL INVENTARIO</td>
                                <td class="px-4 py-3 text-right font-mono text-[#4F46E5]" x-text="'Bs ' + (report?.valuation?.total_valuation_cost_bob ?? '0.00')"></td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-700" x-text="'Bs ' + (report?.valuation?.total_valuation_suggested_bob ?? '0.00')"></td>
                                <td class="px-4 py-3 text-center font-mono text-slate-500">100%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============================================================
             TAB 5: DISTRIBUCIÓN GEOGRÁFICA
        ============================================================ --}}
        <div x-show="activeTab === 'geo'" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="pb-3 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-[#0F172A] flex items-center gap-2">
                        <i class="fa-solid fa-map-location-dot text-[#4F46E5]"></i> Cobertura Geográfica de Entregas en Bolivia
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Penetración por departamento y concentración de recaudación contra entrega.</p>
                </div>

                <div class="space-y-3">
                    <template x-for="dept in report?.geo ?? []" :key="dept.department">
                        <div class="flex items-center gap-4 p-2.5 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-100">
                            <div class="w-32 text-xs font-bold text-[#0F172A] truncate" x-text="dept.department"></div>
                            <div class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden relative">
                                <div class="h-3 bg-gradient-to-r from-[#4F46E5] to-[#38BDF8] rounded-full transition-all duration-700"
                                     :style="'width:' + dept.participation_pct + '%'"></div>
                            </div>
                            <span class="w-14 text-xs text-right font-black font-mono text-[#4F46E5]" x-text="dept.participation_pct + '%'"></span>
                            <span class="w-18 text-xs text-right text-slate-500 font-mono" x-text="dept.orders + ' ped.'"></span>
                            <span class="w-28 text-xs text-right font-black font-mono text-[#0F172A]" x-text="'Bs ' + dept.total_cost_bob"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ============================================================
             TAB 6: TOP DROPSHIPPERS
        ============================================================ --}}
        <div x-show="activeTab === 'sellers'" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="pb-3 border-b border-slate-100">
                    <h3 class="text-base font-extrabold text-[#0F172A] flex items-center gap-2">
                        <i class="fa-solid fa-trophy text-amber-500"></i> Ranking Top Dropshippers & Vendedores
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Vendedores con mayor desempeño de ventas del catálogo del proveedor.</p>
                </div>

                <div class="space-y-3">
                    <template x-for="(seller, idx) in report?.top_sellers ?? []" :key="seller.seller_id">
                        <div class="flex items-center gap-4 p-3.5 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                            <div class="w-8 text-center">
                                <span x-show="idx === 0" class="text-xl">🥇</span>
                                <span x-show="idx === 1" class="text-xl">🥈</span>
                                <span x-show="idx === 2" class="text-xl">🥉</span>
                                <span x-show="idx >= 3" class="text-xs font-black text-slate-400 font-mono" x-text="'#' + (idx + 1)"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-extrabold text-[#0F172A] truncate" x-text="seller.seller_name"></div>
                                <div class="text-[11px] text-slate-400 font-mono truncate" x-text="(seller.store_name ?? 'Tienda e-Commerce') + (seller.city ? ' • ' + seller.city : '')"></div>
                            </div>
                            <div class="text-center hidden sm:block">
                                <div class="font-bold font-mono text-[#4F46E5]" x-text="seller.orders"></div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">pedidos</div>
                            </div>
                            <div class="text-center hidden sm:block">
                                <div class="font-bold font-mono text-[#0F172A]" x-text="seller.units"></div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">unidades</div>
                            </div>
                            <div class="text-right">
                                <div class="font-black font-mono text-emerald-700 text-sm" x-text="'Bs ' + seller.total_cost_bob"></div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">volumen mayorista</div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ============================================================
             TAB 7: MOVIMIENTOS DETALLADOS DE VENTAS
        ============================================================ --}}
        <div x-show="activeTab === 'movimientos'" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-extrabold text-[#0F172A] flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-[#4F46E5]"></i> Trazabilidad Detallada de Movimientos
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Auditoría pedido a pedido con estatus logístico DEX y conciliación contable.</p>
                    </div>

                    {{-- Buscador y Filtro de Estado --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" x-model="movementSearch" placeholder="Buscar pedido, producto, SKU..."
                                   class="border border-slate-200 rounded-xl pl-8 pr-3 py-2 text-xs focus:ring-2 focus:ring-[#4F46E5] outline-none w-64">
                        </div>
                        <select x-model="movementStatusFilter"
                                class="border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-[#4F46E5] outline-none">
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

                <div class="flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Mostrando <span class="font-extrabold text-[#0F172A]" x-text="filteredMovements().length"></span>
                        de <span x-text="report?.movements?.length ?? 0"></span> registros
                    </div>
                    <a :href="getExportUrl()" class="text-emerald-700 font-bold hover:underline flex items-center gap-1.5">
                        <i class="fa-solid fa-file-excel"></i> Descargar listado completo en CSV
                    </a>
                </div>

                {{-- Tabla de Movimientos --}}
                <div class="overflow-x-auto rounded-xl border border-slate-100 max-h-[500px] overflow-y-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="sticky top-0 bg-white z-10 shadow-xs">
                            <tr class="bg-slate-50/90 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                                <th class="px-3 py-3">Fecha</th>
                                <th class="px-3 py-3">Pedido</th>
                                <th class="px-3 py-3">Guía DEX</th>
                                <th class="px-3 py-3">Producto</th>
                                <th class="px-3 py-3 text-center">Cant.</th>
                                <th class="px-3 py-3 text-right">Costo Unit.</th>
                                <th class="px-3 py-3 text-right">Subtotal</th>
                                <th class="px-3 py-3">Cliente</th>
                                <th class="px-3 py-3">Ciudad</th>
                                <th class="px-3 py-3 text-center">Estado Pedido</th>
                                <th class="px-3 py-3 text-center">Liquidación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!filteredMovements().length">
                                <tr><td colspan="11" class="text-center text-slate-400 py-8 italic">No hay movimientos que coincidan con los filtros aplicados.</td></tr>
                            </template>
                            <template x-for="mv in filteredMovements()" :key="mv.order_item_id">
                                <tr class="border-b border-slate-100 hover:bg-indigo-50/20 transition">
                                    <td class="px-3 py-2.5 font-mono text-slate-400 whitespace-nowrap" x-text="mv.created_at"></td>
                                    <td class="px-3 py-2.5 font-mono font-bold text-[#4F46E5]" x-text="mv.order_number"></td>
                                    <td class="px-3 py-2.5 font-mono text-slate-500" x-text="mv.tracking_guide ?? '—'"></td>
                                    <td class="px-3 py-2.5 max-w-[160px] truncate font-semibold text-[#0F172A]" :title="mv.product_name" x-text="mv.product_name"></td>
                                    <td class="px-3 py-2.5 text-center font-black font-mono text-slate-700" x-text="mv.quantity"></td>
                                    <td class="px-3 py-2.5 text-right font-mono text-slate-600" x-text="'Bs ' + mv.unit_cost_bob"></td>
                                    <td class="px-3 py-2.5 text-right font-black font-mono text-emerald-700" x-text="'Bs ' + mv.subtotal_cost_bob"></td>
                                    <td class="px-3 py-2.5 text-slate-600 max-w-[120px] truncate" :title="mv.customer_name" x-text="mv.customer_name"></td>
                                    <td class="px-3 py-2.5 text-slate-500 whitespace-nowrap" x-text="mv.customer_city ?? '—'"></td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': mv.order_status === 'delivered' || mv.order_status === 'settled',
                                            'bg-amber-50 text-amber-700 border border-amber-200': mv.order_status === 'dispatched',
                                            'bg-indigo-50 text-indigo-700 border border-indigo-200': mv.order_status === 'confirmed',
                                            'bg-slate-100 text-slate-600 border border-slate-200': mv.order_status === 'pending',
                                            'bg-rose-50 text-rose-700 border border-rose-200': mv.order_status === 'cancelled' || mv.order_status === 'returned'
                                        }" class="px-2 py-0.5 rounded-full font-bold text-[10px] whitespace-nowrap" x-text="mv.order_status_label ?? mv.order_status"></span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span :class="'px-2 py-0.5 rounded-full font-bold text-[10px] ' + (mv.settlement_badge ?? 'bg-slate-100 text-slate-400')"
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
        activePreset: 30,
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
            this.activePreset = 30;
            await this.loadReportData();
        },

        setPreset(days) {
            this.activePreset = days;
            const to   = new Date();
            const from = new Date();
            from.setDate(from.getDate() - (days - 1));
            this.dateTo   = to.toISOString().split('T')[0];
            this.dateFrom = from.toISOString().split('T')[0];
            this.loadReportData();
        },

        setPresetMonth() {
            this.activePreset = 'month';
            const now  = new Date();
            const from = new Date(now.getFullYear(), now.getMonth(), 1);
            this.dateTo   = now.toISOString().split('T')[0];
            this.dateFrom = from.toISOString().split('T')[0];
            this.loadReportData();
        },

        setPresetYear() {
            this.activePreset = 'year';
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
