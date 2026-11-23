@extends('layouts.app')

@section('title', 'Reporte Comercial & Ventas | Bolidrop Bolivia')

@section('content')
<div class="space-y-8" x-data="commercialReportApp()" x-init="init()">

    {{-- =====================================================================
         HEADER OFICIAL DEL SISTEMA
    ===================================================================== --}}
    <x-page-header
        title="Reporte Comercial & Ventas"
        subtitle="{{ $authUser->isTenantAdmin() ? 'Panel gerencial general • Ventas, costos mayoristas, pedidos y comisiones en Bolivia' : ($authUser->isProvider() ? 'Reporte comercial de proveedor • Ventas de catálogo, costos liquidados y pedidos de dropshippers' : 'Reporte comercial de dropshipper • Mis ventas, pedidos despachados y ganancias netas') }}"
        icon="fa-solid fa-file-invoice-dollar"
        badge="Bolidrop Commercial • Reportes & Métricas en Vivo">
        <x-slot:actions>
            {{-- Filtro de Proveedor para Administradores --}}
            @if($authUser->isTenantAdmin() && $providersList->isNotEmpty())
            <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl px-3 py-1.5 shadow-xs">
                <i class="fa-solid fa-store text-indigo-300 text-xs"></i>
                <label class="text-[11px] font-bold text-white uppercase tracking-wider">Proveedor:</label>
                <select x-model="filterProviderId" @change="fetchData()"
                        class="bg-transparent text-white text-xs font-semibold outline-none cursor-pointer pr-3 focus:ring-0">
                    <option value="" class="text-slate-900">— Todos los proveedores —</option>
                    @foreach($providersList as $p)
                        <option value="{{ $p->id }}" class="text-slate-900">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Botón Exportar CSV --}}
            <a :href="getExportUrl()"
               class="px-4 py-2.5 rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-white font-bold text-xs inline-flex items-center gap-2 shadow-md transition cursor-pointer">
                <i class="fa-solid fa-file-excel text-emerald-200"></i>
                <span>Exportar CSV</span>
            </a>

            {{-- Volver al panel correspondiente --}}
            <a href="{{ $authUser->isProvider() ? url('/panel/proveedor') : ($authUser->isDropshipper() ? url('/panel/dropshipper') : url('/panel/admin')) }}"
               class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-xs inline-flex items-center gap-2 backdrop-blur-sm transition">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Volver a Mi Panel</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- =====================================================================
         FILTROS DE FECHA Y PRESETS (Overlap -mt-12)
    ===================================================================== --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4 !-mt-12 relative z-10">
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
                            :class="activePreset === 1 ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer">Hoy</button>
                    <button @click="setPreset(7)"
                            :class="activePreset === 7 ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer">7 días</button>
                    <button @click="setPreset(30)"
                            :class="activePreset === 30 ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer">30 días</button>
                    <button @click="setPresetMonth()"
                            :class="activePreset === 'month' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer">Este mes</button>
                    <button @click="setPresetYear()"
                            :class="activePreset === 'year' ? 'bg-[#4F46E5] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer">Este año</button>
                </div>
            </div>

            {{-- Botón Aplicar Filtro --}}
            <div class="flex items-center gap-3">
                <button @click="fetchData()" :disabled="loading"
                        class="px-5 py-2.5 rounded-xl bg-[#4F46E5] hover:bg-[#4338CA] disabled:opacity-60 text-white font-bold text-xs inline-flex items-center gap-2 shadow-md transition cursor-pointer">
                    <i class="fa-solid fa-rotate" :class="{'animate-spin': loading}"></i>
                    <span x-text="loading ? 'Actualizando reporte...' : 'Filtrar Reporte'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- =====================================================================
         BLOQUE 1: TARJETAS KPI EJECUTIVAS
    ===================================================================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- KPI 1: Ventas Totales (GMV) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Ventas (BOB)</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0061f2] flex items-center justify-center text-sm">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900 font-mono" x-text="formatBob(kpis.total_sales_bob)"></div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-cart-shopping text-blue-500"></i>
                    <span>Recaudo comercial bruto</span>
                </div>
            </div>
        </div>

        <!-- KPI 2: Pedidos Totales & Entregados -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Pedidos Registrados</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900 font-mono" x-text="kpis.orders_count"></div>
                <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                    <span class="text-emerald-600 font-bold" x-text="kpis.delivered_count + ' entregados'"></span>
                    <span>•</span>
                    <span class="text-amber-600 font-bold" x-text="kpis.pending_count + ' en proceso'"></span>
                </div>
            </div>
        </div>

        <!-- KPI 3: Costo Mayorista / Proveedor -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    {{ $authUser->isProvider() ? 'Mis Ingresos Proveedor' : 'Costo Mayorista' }}
                </span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-store"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-purple-700 font-mono" x-text="formatBob(kpis.provider_cost_bob)"></div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-shield-halved text-purple-500"></i>
                    <span>Liquidación de mercadería</span>
                </div>
            </div>
        </div>

        <!-- KPI 4: Ganancia Neta Dropshippers -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    {{ $authUser->isDropshipper() ? 'Mi Ganancia Neta' : 'Ganancia Dropshippers' }}
                </span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-emerald-600 font-mono" x-text="formatBob(kpis.dropshipper_profit_bob)"></div>
                <div class="text-[11px] text-emerald-600 mt-1 flex items-center gap-1 font-semibold">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>Margen neto liquidable</span>
                </div>
            </div>
        </div>

        <!-- KPI 5: Efectividad de Entrega -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Efectividad Logística</span>
                <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900 font-mono" x-text="kpis.delivery_rate + '%'"></div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-truck-fast text-teal-500"></i>
                    <span>Tasa de entrega exitosa</span>
                </div>
            </div>
        </div>
    </div>

    {{-- =====================================================================
         BLOQUE 2: FLUJO FINANCIERO Y LIQUIDACIÓN COMERCIAL (Cascada COD)
    ===================================================================== --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0061f2] flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm font-heading">Conciliación & Distribución del Recaudo COD</h3>
                    <p class="text-xs text-slate-500">Desglose transparente del dinero cobrado en efectivo o QR interoperable</p>
                </div>
            </div>
            <span class="text-xs font-mono font-bold px-3 py-1 bg-slate-100 text-slate-700 rounded-full">
                Bolivia (BOB)
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-2">
            <!-- Costo Proveedor -->
            <div class="p-4 rounded-xl border border-purple-100 bg-purple-50/50 space-y-1.5">
                <span class="text-[11px] font-bold text-purple-900 uppercase">1. Proveedor (Mercadería)</span>
                <div class="text-xl font-bold font-mono text-purple-700" x-text="formatBob(kpis.provider_cost_bob)"></div>
                <p class="text-[11px] text-purple-600">Liquidado a la billetera del proveedor por el costo de sus productos.</p>
            </div>

            <!-- Ganancia Vendedor -->
            <div class="p-4 rounded-xl border border-emerald-100 bg-emerald-50/50 space-y-1.5">
                <span class="text-[11px] font-bold text-emerald-900 uppercase">2. Vendedor (Dropshipper)</span>
                <div class="text-xl font-bold font-mono text-emerald-700" x-text="formatBob(kpis.dropshipper_profit_bob)"></div>
                <p class="text-[11px] text-emerald-600">Margen comercial neto acumulado tras descontar flete y comisión.</p>
            </div>

            <!-- Flete Courier -->
            <div class="p-4 rounded-xl border border-sky-100 bg-sky-50/50 space-y-1.5">
                <span class="text-[11px] font-bold text-sky-900 uppercase">3. Transporte (DEX Bolivia)</span>
                <div class="text-xl font-bold font-mono text-sky-700" x-text="formatBob(kpis.shipping_fee_bob)"></div>
                <p class="text-[11px] text-sky-600">Flete logístico retenido para cobertura urbana y nacional.</p>
            </div>

            <!-- Comisión Plataforma -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-1.5">
                <span class="text-[11px] font-bold text-slate-800 uppercase">4. Plataforma (4% Fee)</span>
                <div class="text-xl font-bold font-mono text-slate-700" x-text="formatBob(kpis.platform_fee_bob)"></div>
                <p class="text-[11px] text-slate-500">Tarifa tecnológica y operativa regulada por Bolidrop.</p>
            </div>
        </div>
    </div>

    {{-- =====================================================================
         BLOQUE 3: MÉTODOS DE PAGO Y DISTRIBUCIÓN POR CIUDADES
    ===================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Canales de Cobro (Efectivo vs QR) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-qrcode text-sky-500 text-sm"></i>
                    <h3 class="font-bold text-slate-900 text-sm font-heading">Métodos de Cobro al Entregar</h3>
                </div>
                <span class="text-[11px] text-slate-400">Canales COD Bolivia</span>
            </div>

            <div class="space-y-3 pt-1">
                <!-- Efectivo -->
                <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-slate-800">Efectivo contra entrega (COD)</div>
                            <div class="text-[11px] text-slate-500" x-text="paymentDistribution.cash_on_delivery_bob.count + ' pedidos cobrados en billetes'"></div>
                        </div>
                    </div>
                    <div class="text-right font-mono font-bold text-slate-900 text-sm" x-text="formatBob(paymentDistribution.cash_on_delivery_bob.amount)"></div>
                </div>

                <!-- QR Simple Interoperable -->
                <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-slate-800">QR Simple Interoperable (ACH)</div>
                            <div class="text-[11px] text-slate-500" x-text="paymentDistribution.qr_interoperable.count + ' pedidos cobrados vía QR bancario'"></div>
                        </div>
                    </div>
                    <div class="text-right font-mono font-bold text-slate-900 text-sm" x-text="formatBob(paymentDistribution.qr_interoperable.amount)"></div>
                </div>
            </div>
        </div>

        <!-- Cobertura por Ciudades / Departamentos -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-location-dot text-rose-500 text-sm"></i>
                    <h3 class="font-bold text-slate-900 text-sm font-heading">Ventas por Departamentos</h3>
                </div>
                <span class="text-[11px] text-slate-400">Distribución Nacional</span>
            </div>

            <div class="space-y-2.5 pt-1 max-h-56 overflow-y-auto pr-1">
                <template x-for="(d, deptName) in deptDistribution" :key="deptName">
                    <div class="flex items-center justify-between p-2.5 rounded-xl border border-slate-100 bg-white hover:bg-slate-50 transition text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="font-bold text-slate-800" x-text="deptName"></span>
                            <span class="text-[10px] text-slate-400" x-text="'(' + d.count + ' pedidos)'"></span>
                        </div>
                        <span class="font-mono font-bold text-slate-900" x-text="formatBob(d.amount)"></span>
                    </div>
                </template>
                <div x-show="Object.keys(deptDistribution).length === 0" class="p-4 text-center text-xs text-slate-400">
                    No se registran despachos en el rango seleccionado.
                </div>
            </div>
        </div>
    </div>

    {{-- =====================================================================
         BLOQUE 4: TOP PRODUCTOS COMERCIALES
    ===================================================================== --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-boxes-stacked text-[#0061f2] text-sm"></i>
                <h3 class="font-bold text-slate-900 text-sm font-heading">Top Productos Más Vendidos</h3>
            </div>
            <span class="text-xs text-slate-500" x-text="topProducts.length + ' productos en este periodo'"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Producto</th>
                        <th class="px-5 py-3.5">SKU</th>
                        <th class="px-5 py-3.5 text-center">Unidades</th>
                        <th class="px-5 py-3.5 text-right">Costo Total</th>
                        <th class="px-5 py-3.5 text-right">Venta Total</th>
                        <th class="px-5 py-3.5 text-right">Margen Bruto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <template x-for="p in topProducts" :key="p.sku">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3 font-semibold text-slate-800" x-text="p.name"></td>
                            <td class="px-5 py-3 font-mono text-slate-500 text-[11px]" x-text="p.sku"></td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0061f2] font-bold font-mono" x-text="p.units + ' uds'"></span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-slate-600" x-text="formatBob(p.cost_total)"></td>
                            <td class="px-5 py-3 text-right font-mono font-bold text-slate-900" x-text="formatBob(p.sales_total)"></td>
                            <td class="px-5 py-3 text-right font-mono font-bold text-emerald-600" x-text="formatBob(p.profit_total)"></td>
                        </tr>
                    </template>
                    <tr x-show="topProducts.length === 0">
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                            No hay productos vendidos en el rango de fechas seleccionado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- =====================================================================
         BLOQUE 5: DETALLE COMERCIAL DE PEDIDOS
    ===================================================================== --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-receipt text-indigo-500 text-sm"></i>
                <h3 class="font-bold text-slate-900 text-sm font-heading">Detalle de Pedidos en Periodo</h3>
            </div>
            <span class="text-xs text-slate-500">Mostrando hasta 25 pedidos</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5"># Guía / Pedido</th>
                        <th class="px-5 py-3.5">Fecha</th>
                        <th class="px-5 py-3.5">Cliente & Destino</th>
                        <th class="px-5 py-3.5">Método Cobro</th>
                        <th class="px-5 py-3.5">Estado</th>
                        <th class="px-5 py-3.5 text-right">Total Venta</th>
                        <th class="px-5 py-3.5 text-right">Costo Mayorista</th>
                        <th class="px-5 py-3.5 text-right">Ganancia Neta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3 font-mono font-bold text-slate-900">
                                {{ $order->order_number }}
                            </td>
                            <td class="px-5 py-3 text-slate-500 text-[11px]">
                                {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-bold text-slate-800">{{ $order->customer_name }}</div>
                                <div class="text-[11px] text-slate-400">
                                    <i class="fa-solid fa-location-dot text-xs mr-0.5"></i>
                                    {{ $order->shipping_city }} ({{ $order->shipping_department }})
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                @if($order->payment_method === 'qr_interoperable')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        <i class="fa-solid fa-qrcode text-[9px]"></i> QR Simple
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-money-bill-wave text-[9px]"></i> Efectivo COD
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold capitalize
                                    {{ in_array($order->status, ['delivered', 'settled', 'entregado']) ? 'bg-emerald-100 text-emerald-800' : (in_array($order->status, ['novelty', 'en novedad']) ? 'bg-amber-100 text-amber-800' : (in_array($order->status, ['cancelled', 'devolucion']) ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800')) }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono font-bold text-slate-900">
                                Bs {{ number_format((float) $order->total_amount_bob, 2) }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-purple-700 font-semibold">
                                Bs {{ number_format((float) $order->cost_amount_bob, 2) }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-emerald-600 font-bold">
                                Bs {{ number_format((float) $order->dropshipper_profit_bob, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                No se encontraron pedidos registrados en el periodo seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function commercialReportApp() {
    return {
        loading: false,
        dateFrom: '{{ $dateFrom }}',
        dateTo: '{{ $dateTo }}',
        activePreset: 30,
        filterProviderId: '{{ request("provider_id", "") }}',
        kpis: @json($kpis),
        paymentDistribution: @json($paymentDistribution),
        deptDistribution: @json($deptDistribution),
        topProducts: @json($topProducts),

        init() {
            // Inicializado con datos pasados por Blade
        },

        setPreset(days) {
            this.activePreset = days;
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - (days - 1));

            this.dateTo = this.formatDate(end);
            this.dateFrom = this.formatDate(start);
            this.fetchData();
        },

        setPresetMonth() {
            this.activePreset = 'month';
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            this.dateFrom = this.formatDate(start);
            this.dateTo = this.formatDate(now);
            this.fetchData();
        },

        setPresetYear() {
            this.activePreset = 'year';
            const now = new Date();
            const start = new Date(now.getFullYear(), 0, 1);
            this.dateFrom = this.formatDate(start);
            this.dateTo = this.formatDate(now);
            this.fetchData();
        },

        formatDate(d) {
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        },

        formatBob(amount) {
            const num = parseFloat(amount) || 0;
            return 'Bs ' + num.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        getExportUrl() {
            const params = new URLSearchParams();
            if (this.dateFrom) params.append('date_from', this.dateFrom);
            if (this.dateTo) params.append('date_to', this.dateTo);
            if (this.filterProviderId) params.append('provider_id', this.filterProviderId);
            return '{{ route("reporte.export") }}?' + params.toString();
        },

        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.dateFrom) params.append('date_from', this.dateFrom);
                if (this.dateTo) params.append('date_to', this.dateTo);
                if (this.filterProviderId) params.append('provider_id', this.filterProviderId);

                const res = await fetch('{{ route("reporte.data") }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.status === 'success' && json.data) {
                    this.kpis = json.data.kpis;
                    this.paymentDistribution = json.data.paymentDistribution;
                    this.deptDistribution = json.data.deptDistribution;
                    this.topProducts = json.data.topProducts;
                }
            } catch (e) {
                console.error('Error fetching report data:', e);
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection
