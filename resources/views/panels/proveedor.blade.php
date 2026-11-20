@extends('layouts.app')

@section('title', 'Panel Proveedor | Megabodega Bolidrop Bolivia')

@section('content')
<div class="space-y-8" x-data="proveedorDashboard()">

    <x-page-header
        :title="$provider?->company_or_store_name ?? $provider?->name ?? 'Mi Megabodega'"
        :subtitle="($provider?->city ?? 'Santa Cruz de la Sierra') . ', ' . ($provider?->department ?? 'Santa Cruz') . ' • NIT: ' . ($provider?->document_id ?? '1029384019')"
        icon="fa-solid fa-warehouse"
        badge="Panel del Proveedor / Mayorista">
        <x-slot:actions>
            <button @click="bulkModalOpen = true"
                class="px-4 py-2.5 rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-white font-bold text-xs inline-flex items-center gap-2 shadow-md transition cursor-pointer">
                <i class="fa-solid fa-file-excel text-amber-200"></i> Carga Masiva Excel
            </button>
            <button @click="productModalOpen = true"
                class="btn-scarlet inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs shadow-md cursor-pointer">
                <i class="fa-solid fa-plus"></i> Publicar Nuevo Producto
            </button>
            <button @click="categoryModalOpen = true"
                class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-xs inline-flex items-center gap-2 backdrop-blur-sm transition cursor-pointer">
                <i class="fa-solid fa-tags text-indigo-300"></i> Categorías (<span x-text="categoriesList.length">{{ $categories->count() }}</span>)
            </button>
            <a href="{{ url('/catalogo') }}"
                class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-xs inline-flex items-center gap-2 backdrop-blur-sm transition">
                <i class="fa-solid fa-boxes-stacked text-emerald-300"></i> Catálogo
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Notificación Toast -->
    <div x-show="toast.show" x-cloak
        x-transition
        class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between shadow-md"
        :class="toast.type === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800'">
        <div class="flex items-center gap-2">
            <i class="fa-solid" :class="toast.type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-600'"></i>
            <span x-text="toast.message"></span>
        </div>
        <button @click="toast.show = false" class="text-slate-400 hover:text-slate-600">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Tarjetas de estadísticas (Stripe & Linear Executive Overlap) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 !-mt-14 relative z-10">
        
        <!-- Ingresos por liquidaciones (#4F46E5 Electric Indigo) -->
        <div class="bg-gradient-to-br from-[#0F172A] via-[#1E1B4B] to-[#4F46E5] rounded-2xl p-5 text-white shadow-sm space-y-2 border border-indigo-400/20 interactive-card">
            <div class="flex items-center gap-2 text-indigo-200 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-sack-dollar text-[#38BDF8]"></i> Ingresos Liquidados
            </div>
            <div class="text-3xl font-extrabold font-mono" data-countup>
                Bs {{ number_format($stats['total_revenue'], 2) }}
            </div>
            <div class="text-indigo-200/90 text-[11px] font-medium">Costos cobrados por ventas COD</div>
        </div>

        <!-- Saldo billetera (#FFFFFF) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2 interactive-card">
            <div class="flex items-center gap-2 text-[#4F46E5] text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-wallet text-[#4F46E5]"></i> Saldo Billetera
            </div>
            <div class="text-2xl font-extrabold font-mono text-[#10B981]" data-countup>
                Bs {{ number_format((float)($wallet?->balance_available ?? 0), 2) }}
            </div>
            <div class="text-slate-500 text-[11px]">Disponible para retiro inmediato</div>
        </div>

        <!-- Stock Total Físico (#FFFFFF) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2 interactive-card">
            <div class="flex items-center gap-2 text-slate-500 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-cubes-stacked text-[#4F46E5]"></i> Stock Físico Disponible
            </div>
            <div class="text-2xl font-extrabold font-mono text-[#0F172A]" data-countup>{{ number_format($stats['total_stock'] ?? 0) }} uds</div>
            <div class="text-slate-500 text-[11px]">{{ $stats['active_products'] }} productos activos en catálogo</div>
        </div>

        <!-- Ventas y Pedidos (#FFFFFF) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2 interactive-card">
            <div class="flex items-center gap-2 text-slate-500 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-cart-flatbed text-[#4F46E5]"></i> Pedidos & Despachos
            </div>
            <div class="text-2xl font-extrabold text-[#0F172A] font-heading" data-countup>{{ $stats['total_sales'] ?? 0 }} pedidos</div>
            <div class="text-slate-500 text-[11px]">{{ $stats['pending_orders'] ?? 0 }} en proceso / {{ $stats['delivered_orders'] ?? 0 }} entregados</div>
        </div>
    </div>

    <!-- Barra de Navegación Rápida entre Secciones del Panel -->
    <nav class="sticky top-14 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 rounded-2xl shadow-sm px-2 py-1.5 flex items-center gap-1 overflow-x-auto">
        <a href="#almacenes" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition whitespace-nowrap">
            <i class="fa-solid fa-warehouse text-indigo-500"></i> Almacenes
        </a>
        <a href="#inventario" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition whitespace-nowrap">
            <i class="fa-solid fa-boxes-stacked text-slate-400"></i> Inventario
        </a>
        <a href="#alertas" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition whitespace-nowrap">
            <i class="fa-solid fa-bell text-slate-400"></i> Alertas
        </a>
        <a href="#kardex" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition whitespace-nowrap">
            <i class="fa-solid fa-clock-rotate-left text-slate-400"></i> Kardex
        </a>
        <a href="#productos" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition whitespace-nowrap">
            <i class="fa-solid fa-tag text-slate-400"></i> Productos
        </a>
        <a href="#ventas" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition whitespace-nowrap">
            <i class="fa-solid fa-chart-line text-slate-400"></i> Ventas
        </a>
    </nav>

    <!-- =========================================================
         MÓDULO ALMACENES — Tab nav de sección
    ========================================================= -->
    <div id="almacenes" class="space-y-6" x-data="almacenModule()">

        <!-- Cabecera del módulo -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/80">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-warehouse text-[#4F46E5]"></i>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm font-heading">Gestión de Almacenes</h3>
                        <p class="text-xs text-slate-500">Crea, edita y administra tus bodegas · Productos · Reservas de Stock · Visibilidad</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button @click="warehouseModalOpen = true; resetWarehouseForm()"
                        class="btn-scarlet px-3.5 py-1.5 rounded-lg font-bold text-xs shadow-sm flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-plus text-amber-200"></i> Nuevo Almacén
                    </button>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-white text-slate-900 border border-slate-200 font-mono"
                        x-text="warehouseList.length + ' bodegas'"></span>
                </div>
            </div>

            <!-- Sub-tabs de navegación -->
            <div class="flex border-b border-slate-200 bg-white overflow-x-auto">
                <button @click="activeTab = 'bodegas'"
                    :class="activeTab === 'bodegas' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-indigo-50/60' : 'text-slate-500 hover:text-slate-700'"
                    class="px-5 py-3 text-xs font-bold whitespace-nowrap flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fa-solid fa-warehouse"></i> Mis Bodegas
                </button>
                <button @click="activeTab = 'productos'; loadProducts()"
                    :class="activeTab === 'productos' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-indigo-50/60' : 'text-slate-500 hover:text-slate-700'"
                    class="px-5 py-3 text-xs font-bold whitespace-nowrap flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fa-solid fa-boxes-stacked"></i> Productos del Almacén
                </button>
                <button @click="activeTab = 'reservas'; loadReserves()"
                    :class="activeTab === 'reservas' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-indigo-50/60' : 'text-slate-500 hover:text-slate-700'"
                    class="px-5 py-3 text-xs font-bold whitespace-nowrap flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fa-solid fa-bookmark"></i> Reservas de Vendedores
                </button>
                <button @click="activeTab = 'traspaso'"
                    :class="activeTab === 'traspaso' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-indigo-50/60' : 'text-slate-500 hover:text-slate-700'"
                    class="px-5 py-3 text-xs font-bold whitespace-nowrap flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fa-solid fa-right-left"></i> Traspaso entre Bodegas
                </button>
            </div>

            <!-- =====================================================
                 TAB 1: MIS BODEGAS — CRUD
            ===================================================== -->
            <div x-show="activeTab === 'bodegas'" x-transition class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <template x-if="loadingWarehouses">
                        <div class="col-span-3 py-10 text-center text-slate-400 text-sm">
                            <i class="fa-solid fa-spinner fa-spin text-indigo-400 text-xl mb-2 block"></i>
                            Cargando almacenes...
                        </div>
                    </template>
                    <template x-for="wh in warehouseList" :key="wh.id">
                        <div class="rounded-xl border p-4 space-y-3 transition hover:shadow-md"
                            :class="wh.is_active ? 'border-slate-200 bg-white' : 'border-slate-200 bg-slate-50 opacity-70'">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                                        :class="wh.is_active ? 'bg-indigo-600' : 'bg-slate-400'">
                                        <i class="fa-solid fa-warehouse"></i>
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-900 text-sm" x-text="wh.name"></p>
                                        <p class="text-[11px] text-slate-400 font-mono" x-text="wh.code"></p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :class="wh.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                                    x-text="wh.is_active ? 'Activa' : 'Inactiva'"></span>
                            </div>
                            <div class="text-xs text-slate-500 space-y-1">
                                <div class="flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-[#4F46E5] w-3"></i> <span x-text="wh.city + ', ' + wh.department"></span></div>
                                <div class="flex items-center gap-1.5" x-show="wh.address"><i class="fa-solid fa-road text-slate-400 w-3"></i> <span x-text="wh.address" class="line-clamp-1"></span></div>
                                <div class="flex items-center gap-1.5" x-show="wh.contact_phone"><i class="fa-solid fa-phone text-slate-400 w-3"></i> <span x-text="wh.contact_phone"></span></div>
                            </div>
                            <!-- KPIs rápidos -->
                            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                                <div class="text-center">
                                    <p class="text-[11px] text-slate-400">Productos</p>
                                    <p class="font-bold text-slate-800 font-mono" x-text="wh.total_products_count ?? 0"></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[11px] text-slate-400">Stock disponible</p>
                                    <p class="font-bold text-emerald-700 font-mono" x-text="(wh.total_stock ?? 0) + ' uds'"></p>
                                </div>
                            </div>
                            <!-- Acciones -->
                            <div class="flex gap-2 pt-1">
                                <button @click="selectWarehouse(wh); activeTab = 'productos'; loadProducts()"
                                    class="flex-1 text-[11px] font-semibold py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition cursor-pointer">
                                    <i class="fa-solid fa-boxes-stacked mr-1"></i> Ver productos
                                </button>
                                <button @click="editWarehouse(wh)"
                                    class="px-3 text-[11px] font-semibold py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 transition cursor-pointer">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click="confirmDeleteWarehouse(wh)"
                                    class="px-3 text-[11px] font-semibold py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 transition cursor-pointer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                    <template x-if="!loadingWarehouses && warehouseList.length === 0">
                        <div class="col-span-3 py-12 text-center text-slate-400">
                            <i class="fa-solid fa-warehouse text-4xl mb-3 block text-slate-200"></i>
                            <p class="font-semibold text-slate-500">Aún no tienes almacenes registrados</p>
                            <p class="text-xs mt-1">Crea tu primer almacén para gestionar tu inventario</p>
                            <button @click="warehouseModalOpen = true; resetWarehouseForm()"
                                class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl cursor-pointer">
                                <i class="fa-solid fa-plus"></i> Crear Almacén
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- =====================================================
                 TAB 2: PRODUCTOS DEL ALMACÉN
            ===================================================== -->
            <div x-show="activeTab === 'productos'" x-transition class="p-4 space-y-4">
                <!-- Selector de bodega + buscador -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <select x-model="selectedWarehouseId" @change="loadProducts()"
                        class="flex-1 text-xs rounded-xl border border-slate-200 px-3 py-2.5 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">— Selecciona un almacén —</option>
                        <template x-for="wh in warehouseList" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name + ' (' + wh.code + ')'"></option>
                        </template>
                    </select>
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" x-model="productSearch" @input.debounce.400ms="loadProducts()"
                            placeholder="Buscar por nombre, SKU o código..."
                            class="w-full pl-8 pr-3 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <!-- Filtros de visibilidad -->
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer">
                            <input type="checkbox" x-model="filterVisiblePublic" @change="loadProducts()" class="rounded accent-indigo-600">
                            <i class="fa-solid fa-eye text-slate-400"></i> Solo públicos
                        </label>
                        <label class="flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer">
                            <input type="checkbox" x-model="filterVisibleSellers" @change="loadProducts()" class="rounded accent-emerald-600">
                            <i class="fa-solid fa-store text-slate-400"></i> Solo vendedores
                        </label>
                    </div>
                </div>

                <!-- Acciones en lote -->
                <div x-show="selectedInventoryIds.length > 0" x-transition
                    class="flex flex-wrap items-center gap-3 bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-2.5">
                    <span class="text-xs font-bold text-indigo-700" x-text="selectedInventoryIds.length + ' productos seleccionados'"></span>
                    <button @click="batchVisibility(true, true)"
                        class="text-[11px] font-bold px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer">
                        <i class="fa-solid fa-eye mr-1"></i> Mostrar todos
                    </button>
                    <button @click="batchVisibility(false, false)"
                        class="text-[11px] font-bold px-3 py-1.5 rounded-lg bg-slate-600 hover:bg-slate-700 text-white cursor-pointer">
                        <i class="fa-solid fa-eye-slash mr-1"></i> Ocultar todos
                    </button>
                    <button @click="batchVisibility(null, true)"
                        class="text-[11px] font-bold px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer">
                        <i class="fa-solid fa-store mr-1"></i> Solo vendedores
                    </button>
                    <button @click="selectedInventoryIds = []"
                        class="text-[11px] font-bold px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">
                        Cancelar
                    </button>
                </div>

                <!-- Tabla de productos -->
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                            <tr>
                                <th class="px-3 py-3 w-8">
                                    <input type="checkbox" @change="toggleSelectAll($event)" class="rounded accent-indigo-600 cursor-pointer">
                                </th>
                                <th class="px-3 py-3">Producto</th>
                                <th class="px-3 py-3 text-right">Stock</th>
                                <th class="px-3 py-3 text-right">Reservado</th>
                                <th class="px-3 py-3 text-center">Público</th>
                                <th class="px-3 py-3 text-center">Vendedores</th>
                                <th class="px-3 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="loadingProducts">
                                <tr><td colspan="7" class="py-10 text-center text-slate-400">
                                    <i class="fa-solid fa-spinner fa-spin text-indigo-400 text-xl mb-2 block"></i>
                                    Cargando productos...
                                </td></tr>
                            </template>
                            <template x-if="!loadingProducts && productList.length === 0">
                                <tr><td colspan="7" class="py-10 text-center text-slate-400">
                                    <i class="fa-solid fa-box-open text-4xl mb-2 block text-slate-200"></i>
                                    <p class="font-semibold text-slate-500">Sin productos en este almacén</p>
                                    <p class="text-xs">Selecciona otro almacén o agrega stock via Carga Masiva</p>
                                </td></tr>
                            </template>
                            <template x-for="inv in productList" :key="inv.id">
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-3 py-2.5">
                                        <input type="checkbox" :value="inv.id"
                                            :checked="selectedInventoryIds.includes(inv.id)"
                                            @change="toggleSelectInventory(inv.id)"
                                            class="rounded accent-indigo-600 cursor-pointer">
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <p class="font-semibold text-slate-800 text-xs" x-text="inv.product?.name"></p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="font-mono text-[10px] text-slate-400" x-text="inv.product?.sku"></span>
                                            <span class="font-mono text-[10px] text-indigo-600 bg-indigo-50 rounded px-1" x-text="inv.product?.internal_code"></span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold"
                                        :class="inv.stock_available > 10 ? 'text-emerald-700' : (inv.stock_available > 0 ? 'text-amber-600' : 'text-red-500')"
                                        x-text="inv.stock_available + ' uds'"></td>
                                    <td class="px-3 py-2.5 text-right font-mono text-slate-500" x-text="inv.stock_reserved + ' uds'"></td>
                                    <!-- Toggle Público -->
                                    <td class="px-3 py-2.5 text-center">
                                        <button @click="toggleSingleVisibility(inv, 'visible_public', !inv.visible_public)"
                                            :title="inv.visible_public ? 'Ocultar del catálogo público' : 'Mostrar en catálogo público'"
                                            class="w-8 h-5 rounded-full transition-colors duration-200 cursor-pointer"
                                            :class="inv.visible_public ? 'bg-emerald-500' : 'bg-slate-300'">
                                            <span class="block w-3 h-3 bg-white rounded-full shadow transition-transform duration-200 mx-auto"
                                                :class="inv.visible_public ? 'translate-x-1.5' : '-translate-x-1.5'"></span>
                                        </button>
                                    </td>
                                    <!-- Toggle Vendedores -->
                                    <td class="px-3 py-2.5 text-center">
                                        <button @click="toggleSingleVisibility(inv, 'visible_sellers', !inv.visible_sellers)"
                                            :title="inv.visible_sellers ? 'Ocultar de vendedores' : 'Mostrar a vendedores'"
                                            class="w-8 h-5 rounded-full transition-colors duration-200 cursor-pointer"
                                            :class="inv.visible_sellers ? 'bg-indigo-500' : 'bg-slate-300'">
                                            <span class="block w-3 h-3 bg-white rounded-full shadow transition-transform duration-200 mx-auto"
                                                :class="inv.visible_sellers ? 'translate-x-1.5' : '-translate-x-1.5'"></span>
                                        </button>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <button @click="openReserveModal(inv)"
                                            class="text-[10px] font-bold px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 cursor-pointer"
                                            title="Reservar stock para un vendedor">
                                            <i class="fa-solid fa-bookmark mr-0.5"></i> Reservar
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <!-- Paginación simple -->
                <div class="flex items-center justify-between text-xs text-slate-500" x-show="productPagination">
                    <span x-text="'Mostrando ' + productList.length + ' de ' + (productPagination?.total ?? 0) + ' productos'"></span>
                    <div class="flex gap-2">
                        <button x-show="productPagination?.prev_page_url" @click="loadProducts(productPagination.current_page - 1)"
                            class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button x-show="productPagination?.next_page_url" @click="loadProducts(productPagination.current_page + 1)"
                            class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- =====================================================
                 TAB 3: RESERVAS DE STOCK PARA VENDEDORES
            ===================================================== -->
            <div x-show="activeTab === 'reservas'" x-transition class="p-4 space-y-4">
                <div class="flex flex-col sm:flex-row gap-3 items-center">
                    <select x-model="selectedWarehouseId" @change="loadReserves()"
                        class="flex-1 text-xs rounded-xl border border-slate-200 px-3 py-2.5 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">— Selecciona un almacén —</option>
                        <template x-for="wh in warehouseList" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name + ' (' + wh.code + ')'"></option>
                        </template>
                    </select>
                    <button @click="reserveModalOpen = true; resetReserveForm()"
                        :disabled="!selectedWarehouseId"
                        class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shadow">
                        <i class="fa-solid fa-bookmark"></i> Nueva Reserva
                    </button>
                </div>

                <!-- Lista de reservas -->
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Producto</th>
                                <th class="px-4 py-3">Vendedor Asignado</th>
                                <th class="px-4 py-3 text-right">Cantidad</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Vencimiento</th>
                                <th class="px-4 py-3">Notas</th>
                                <th class="px-4 py-3 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="loadingReserves">
                                <tr><td colspan="7" class="py-10 text-center text-slate-400">
                                    <i class="fa-solid fa-spinner fa-spin text-amber-400 text-xl mb-2 block"></i>
                                    Cargando reservas...
                                </td></tr>
                            </template>
                            <template x-if="!loadingReserves && reserveList.length === 0">
                                <tr><td colspan="7" class="py-10 text-center text-slate-400">
                                    <i class="fa-solid fa-bookmark text-4xl mb-2 block text-slate-200"></i>
                                    <p class="font-semibold text-slate-500">Sin reservas activas</p>
                                    <p class="text-xs">Selecciona un almacén y crea una reserva para un vendedor</p>
                                </td></tr>
                            </template>
                            <template x-for="res in reserveList" :key="res.id">
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-2.5">
                                        <p class="font-semibold text-slate-800" x-text="res.product?.name"></p>
                                        <span class="font-mono text-[10px] text-indigo-600" x-text="res.product?.internal_code"></span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <p class="font-semibold text-slate-700" x-text="res.dropshipper?.company_or_store_name || res.dropshipper?.name"></p>
                                        <p class="text-slate-400 text-[10px]" x-text="res.dropshipper?.email"></p>
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-mono font-bold text-amber-700" x-text="res.quantity + ' uds'"></td>
                                    <td class="px-4 py-2.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Activa</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-400" x-text="res.expires_at ? new Date(res.expires_at).toLocaleDateString('es-BO') : 'Sin vencimiento'"></td>
                                    <td class="px-4 py-2.5 text-slate-500 max-w-[180px] truncate" x-text="res.notes || '—'"></td>
                                    <td class="px-4 py-2.5 text-center">
                                        <button @click="releaseReserve(res)"
                                            class="text-[10px] font-bold px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 cursor-pointer">
                                            <i class="fa-solid fa-xmark mr-0.5"></i> Liberar
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =====================================================
                 TAB 4: TRASPASO ENTRE BODEGAS
            ===================================================== -->
            <div x-show="activeTab === 'traspaso'" x-transition class="p-4 space-y-4">
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-xs text-indigo-800 flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5 text-indigo-500"></i>
                    <span>El traspaso descuenta stock de la bodega origen y lo acredita en la bodega destino de forma atómica. Se registra en el Kardex de movimientos.</span>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5 max-w-2xl space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bodega Origen *</label>
                            <select x-model="traspasoForm.from_warehouse_id"
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                <option value="">— Seleccionar bodega origen —</option>
                                <template x-for="wh in warehouseList" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.name + ' (' + wh.code + ')'"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bodega Destino *</label>
                            <select x-model="traspasoForm.to_warehouse_id"
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                <option value="">— Seleccionar bodega destino —</option>
                                <template x-for="wh in warehouseList" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.name + ' (' + wh.code + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Producto *</label>
                        <select x-model="traspasoForm.product_id"
                            class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="">— Seleccionar producto —</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} — {{ $p->sku }} / {{ $p->internal_code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cantidad a transferir *</label>
                            <input type="number" x-model.number="traspasoForm.quantity" min="1"
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Notas (opcional)</label>
                            <input type="text" x-model="traspasoForm.notes" placeholder="Motivo del traspaso..."
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                    </div>
                    <button @click="submitTraspaso()"
                        :disabled="traspasoSubmitting || !traspasoForm.from_warehouse_id || !traspasoForm.to_warehouse_id || !traspasoForm.product_id || traspasoForm.quantity < 1"
                        class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shadow">
                        <template x-if="traspasoSubmitting"><span><i class="fa-solid fa-spinner fa-spin"></i> Procesando...</span></template>
                        <template x-if="!traspasoSubmitting"><span><i class="fa-solid fa-right-left"></i> Ejecutar Traspaso</span></template>
                    </button>
                </div>
            </div>
        </div>

        <!-- =====================================================
             MODAL: CREAR / EDITAR ALMACÉN
        ===================================================== -->
        <div x-show="warehouseModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @click.self="warehouseModalOpen = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.stop>
                <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm" x-text="warehouseForm.id ? 'Editar Almacén' : 'Nuevo Almacén'"></h3>
                    <button @click="warehouseModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre del Almacén *</label>
                            <input type="text" x-model="warehouseForm.name" placeholder="Bodega Principal Santa Cruz"
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div x-show="!warehouseForm.id">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Código Único *</label>
                            <input type="text" x-model="warehouseForm.code" placeholder="WH-SCZ-02"
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300 uppercase">
                            <p class="text-[10px] text-slate-400 mt-1">Solo letras, números y guiones. Ej: WH-SCZ-02</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Departamento *</label>
                            <select x-model="warehouseForm.department"
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                <option value="">— Seleccionar —</option>
                                <option>Santa Cruz</option>
                                <option>La Paz</option>
                                <option>Cochabamba</option>
                                <option>Oruro</option>
                                <option>Potosí</option>
                                <option>Sucre</option>
                                <option>Tarija</option>
                                <option>Beni</option>
                                <option>Pando</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Ciudad *</label>
                            <input type="text" x-model="warehouseForm.city" placeholder="Santa Cruz de la Sierra"
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Dirección *</label>
                            <input type="text" x-model="warehouseForm.address" placeholder="Av. San Martín #850, Zona Sur"
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Teléfono de contacto</label>
                            <input type="text" x-model="warehouseForm.contact_phone" placeholder="+591 70000000"
                                class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div x-show="warehouseForm.id" class="flex items-center gap-3 pt-5">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-600">
                                <input type="checkbox" x-model="warehouseForm.is_active" class="rounded accent-emerald-600">
                                Almacén activo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="p-5 border-t border-slate-200 flex justify-end gap-3">
                    <button @click="warehouseModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Cancelar</button>
                    <button @click="saveWarehouse()" :disabled="warehouseSubmitting"
                        class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold cursor-pointer disabled:opacity-50">
                        <template x-if="warehouseSubmitting"><span><i class="fa-solid fa-spinner fa-spin mr-1"></i> Guardando...</span></template>
                        <template x-if="!warehouseSubmitting"><span><i class="fa-solid fa-floppy-disk mr-1"></i> <span x-text="warehouseForm.id ? 'Actualizar' : 'Crear Almacén'"></span></span></template>
                    </button>
                </div>
            </div>
        </div>

        <!-- =====================================================
             MODAL: NUEVA RESERVA DE STOCK
        ===================================================== -->
        <div x-show="reserveModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @click.self="reserveModalOpen = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
                <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Reservar Stock para Vendedor</h3>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="reserveForm.productName ? '📦 ' + reserveForm.productName : 'Selecciona producto y vendedor'"></p>
                    </div>
                    <button @click="reserveModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Stock disponible -->
                    <div x-show="reserveForm.stockAvailable !== null"
                        class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5 text-xs">
                        <i class="fa-solid fa-cubes text-amber-500"></i>
                        <span class="text-amber-700">Stock disponible en bodega: <strong x-text="reserveForm.stockAvailable + ' unidades'"></strong></span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Vendedor / Dropshipper *</label>
                        <select x-model="reserveForm.dropshipper_id"
                            class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-300">
                            <option value="">— Seleccionar vendedor —</option>
                            @foreach($dropshippers as $ds)
                            <option value="{{ $ds->id }}">{{ $ds->company_or_store_name ?: $ds->name }} — {{ $ds->city ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cantidad a reservar *</label>
                        <input type="number" x-model.number="reserveForm.quantity" min="1"
                            :max="reserveForm.stockAvailable"
                            class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-300">
                        <p class="text-[10px] text-slate-400 mt-1">El stock reservado se descuenta del disponible en bodega.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fecha de vencimiento (opcional)</label>
                        <input type="datetime-local" x-model="reserveForm.expires_at"
                            class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Notas internas</label>
                        <textarea x-model="reserveForm.notes" rows="2" placeholder="Motivo de la reserva, campaña, acuerdo especial..."
                            class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-300 resize-none"></textarea>
                    </div>
                </div>
                <div class="p-5 border-t border-slate-200 flex justify-end gap-3">
                    <button @click="reserveModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Cancelar</button>
                    <button @click="submitReserve()" :disabled="reserveSubmitting || !reserveForm.dropshipper_id || reserveForm.quantity < 1"
                        class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold cursor-pointer disabled:opacity-50">
                        <template x-if="reserveSubmitting"><span><i class="fa-solid fa-spinner fa-spin mr-1"></i> Reservando...</span></template>
                        <template x-if="!reserveSubmitting"><span><i class="fa-solid fa-bookmark mr-1"></i> Confirmar Reserva</span></template>
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast de almacenes (independiente del dashboard) -->
        <div x-show="almToast.show" x-cloak x-transition
            class="fixed bottom-6 right-6 z-50 max-w-sm p-4 rounded-2xl text-xs font-semibold flex items-center gap-3 shadow-xl"
            :class="almToast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'">
            <i class="fa-solid" :class="almToast.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
            <span x-text="almToast.message" class="flex-1"></span>
            <button @click="almToast.show = false" class="ml-2 opacity-70 hover:opacity-100 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>
    </div>
    <!-- FIN MÓDULO ALMACENES -->

    <!-- Bodegas con Inventario (#FFFFFF) con ancla #inventario -->
    <div id="inventario" class="space-y-6">
        @if($warehouses->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/80">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-warehouse text-[#0061f2]"></i>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm font-heading">Mis Bodegas de Despacho & Inventario</h3>
                        <p class="text-xs text-slate-500">Distribución física de stock disponible para despacho COD en Bolivia</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button @click="downloadAlertModalOpen = true"
                        class="px-3.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold text-xs shadow-2xs flex items-center gap-1.5 transition cursor-pointer"
                        title="Descargar plantilla oficial de productos e inventario">
                        <i class="fa-solid fa-file-excel text-emerald-600"></i> Plantilla Excel
                    </button>
                    <button @click="bulkModalOpen = true"
                        class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs flex items-center gap-1.5 transition cursor-pointer"
                        title="Carga masiva de inventario y productos">
                        <i class="fa-solid fa-cloud-arrow-up text-amber-200"></i> Carga Masiva
                    </button>
                    <button @click="transferModalOpen = true" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs flex items-center gap-1.5 transition cursor-pointer">
                        <i class="fa-solid fa-dolly text-[#0061f2]"></i> Transferir Stock
                    </button>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-950 border border-emerald-200/80 font-mono">
                        {{ $stats['total_stock'] ?? 0 }} uds totales
                    </span>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-0 divide-y md:divide-y-0 md:divide-x divide-slate-200">
                @foreach($warehouses as $warehouse)
                <div class="p-5 space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="font-bold text-slate-900 text-sm">{{ $warehouse->name }}</div>
                            <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-1">
                                <i class="fa-solid fa-location-dot text-[#0061f2]"></i>
                                {{ $warehouse->address }}, {{ $warehouse->city }}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                <i class="fa-solid fa-phone text-[#0061f2] mr-1"></i> {{ $warehouse->contact_phone ?? '+591 3 3456789' }}
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            {{ $warehouse->code }}
                        </span>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#0061f2] mb-2 flex items-center justify-between">
                            <span>Stock en Bodega:</span>
                            <span class="font-mono text-slate-700">{{ $warehouse->inventories->sum('stock_available') }} uds</span>
                        </div>
                        @forelse($warehouse->inventories as $inv)
                        <div class="flex justify-between items-center text-xs py-2 px-3 rounded-lg bg-slate-50 border border-slate-200/80 mb-1.5">
                            <div class="truncate max-w-[170px]">
                                <span class="text-slate-800 font-semibold block truncate">{{ $inv->product->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">SKU: {{ $inv->product->sku }}</span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-mono font-bold text-slate-900 block">{{ $inv->stock_available }} disp.</span>
                                @if($inv->stock_reserved > 0)
                                <span class="text-[10px] text-amber-600 font-mono">({{ $inv->stock_reserved }} res.)</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-xs text-slate-400 italic py-3 px-1 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <span>Sin inventario registrado aún en esta bodega.</span>
                            <button type="button" @click="downloadAlertModalOpen = true" class="text-emerald-700 hover:text-emerald-800 font-bold underline text-xs cursor-pointer inline-flex items-center gap-1 self-start sm:self-auto">
                                <i class="fa-solid fa-file-excel text-[11px]"></i> Descargar plantilla de ejemplo
                            </button>
                        </div>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Sección de Alertas y Reabastecimiento de Inventario (#alertas) -->
        <div id="alertas" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/80">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-[#0061f2] flex items-center justify-center font-bold text-base">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm font-heading">Alertas de Stock & Reabastecimiento de Inventario</h3>
                        <p class="text-xs text-slate-500">Configura umbrales mínimos por bodega para evitar quiebres de stock en tus ventas COD</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="alertModalOpen = true" class="btn-scarlet px-3.5 py-1.5 rounded-lg font-bold text-xs shadow-xs flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-plus text-amber-200"></i> Nueva Regla de Alerta
                    </button>
                </div>
            </div>

            <!-- Listado de Alertas Configuradas -->
            <div class="p-5 space-y-4">
                @if($inventoryAlerts->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    @foreach($inventoryAlerts as $alert)
                    @php
                        $currentStock = \App\Models\WarehouseInventory::where('warehouse_id', $alert->warehouse_id)
                            ->where('product_id', $alert->product_id)
                            ->value('stock_available') ?? 0;
                        $isLow = $currentStock <= $alert->min_stock_threshold;
                    @endphp
                    <div class="p-4 rounded-2xl border transition {{ $isLow ? 'bg-red-50/40 border-red-200' : 'bg-slate-50/60 border-slate-200' }} space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="truncate">
                                <h4 class="font-bold text-slate-900 text-xs truncate">{{ $alert->product->name ?? 'Producto' }}</h4>
                                <span class="text-[10px] text-slate-400 font-mono">SKU: {{ $alert->product->sku ?? '' }}</span>
                            </div>
                            @if($isLow)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-200 shrink-0">
                                <i class="fa-solid fa-triangle-exclamation"></i> Stock Bajo
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">
                                <i class="fa-solid fa-check"></i> Stock Óptimo
                            </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-200/60 text-center font-mono">
                            <div class="p-1.5 bg-white rounded-lg border border-slate-200/80">
                                <span class="text-[9px] text-slate-400 block font-sans">Disponible</span>
                                <span class="font-bold text-xs {{ $isLow ? 'text-red-600' : 'text-slate-800' }}">{{ $currentStock }} uds</span>
                            </div>
                            <div class="p-1.5 bg-white rounded-lg border border-slate-200/80">
                                <span class="text-[9px] text-slate-400 block font-sans">Umbral Mín.</span>
                                <span class="font-bold text-xs text-amber-600">{{ $alert->min_stock_threshold }}</span>
                            </div>
                            <div class="p-1.5 bg-white rounded-lg border border-slate-200/80">
                                <span class="text-[9px] text-slate-400 block font-sans">Lote Sugerido</span>
                                <span class="font-bold text-xs text-emerald-700">+{{ $alert->reorder_quantity }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                            <span class="flex items-center gap-1">
                                <i class="fa-solid fa-warehouse text-[#0061f2]"></i> {{ $alert->warehouse->name ?? 'Bodega' }}
                            </span>
                            <span class="text-[10px] text-slate-400">
                                Activa
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="py-8 text-center text-slate-400 space-y-2">
                    <i class="fa-solid fa-bell-slash text-2xl text-slate-300"></i>
                    <p class="text-xs">No tienes alertas de stock configuradas aún. Pulsa en "Nueva Regla de Alerta" para definir umbrales.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Historial y Trazabilidad Kardex de Movimientos de Inventario del Proveedor -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50/80">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-clipboard-list text-[#0061f2]"></i>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm font-heading">Trazabilidad Kardex de Mis Productos</h3>
                        <p class="text-xs text-slate-500">Historial inmutable de entradas por saldo inicial, salidas por despacho COD y transferencias</p>
                    </div>
                </div>
                <span class="text-xs font-mono font-semibold px-2.5 py-1 rounded-full bg-white text-slate-600 border border-slate-200">
                    Últimos {{ $recentKardex->count() }} registros
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Fecha / Movimiento</th>
                            <th class="py-3 px-4">Producto & SKU</th>
                            <th class="py-3 px-4">Bodega</th>
                            <th class="py-3 px-4">Cantidad</th>
                            <th class="py-3 px-4">Costo (BOB)</th>
                            <th class="py-3 px-4">Saldo Físico</th>
                            <th class="py-3 px-4">Referencia / Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentKardex as $kardex)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $kardex->created_at->format('d/m/Y H:i') }}</div>
                                <div>
                                    @if(in_array($kardex->movement_type, ['opening_balance', 'purchase_receipt', 'return_optimal', 'transfer_in']))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            <i class="fa-solid fa-arrow-down"></i> {{ ucfirst(str_replace('_', ' ', $kardex->movement_type)) }}
                                        </span>
                                    @elseif(in_array($kardex->movement_type, ['order_dispatch_delivered', 'transfer_out', 'return_damaged']))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-700 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">
                                            <i class="fa-solid fa-arrow-up"></i> {{ ucfirst(str_replace('_', ' ', $kardex->movement_type)) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                            <i class="fa-solid fa-hand"></i> {{ ucfirst(str_replace('_', ' ', $kardex->movement_type)) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800">{{ $kardex->product->name ?? 'Producto' }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $kardex->product->sku ?? '' }}</div>
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-700 whitespace-nowrap">
                                {{ $kardex->warehouse->name ?? 'Bodega' }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold whitespace-nowrap">
                                <span class="{{ $kardex->quantity >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $kardex->quantity > 0 ? '+' : '' }}{{ $kardex->quantity }} uds
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono font-semibold text-slate-700 whitespace-nowrap">
                                Bs {{ number_format((float)$kardex->unit_cost_bob, 2) }}
                            </td>
                            <td class="py-3 px-4 font-mono whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $kardex->stock_physical_after }} uds</div>
                                <div class="text-[10px] text-slate-400">{{ $kardex->stock_available_after }} disponibles</div>
                            </td>
                            <td class="py-3 px-4 max-w-xs">
                                <div class="font-mono text-[11px] font-bold text-slate-800">{{ $kardex->document_number ?? '-' }}</div>
                                <div class="text-[11px] text-slate-500 truncate" title="{{ $kardex->notes }}">{{ $kardex->notes ?? '-' }}</div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 italic">
                                No hay movimientos de kardex registrados para este inventario.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ventas y Pedidos Relacionados con el Proveedor -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50/80">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-cart-shopping text-[#0061f2]"></i>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm font-heading">Ventas y Pedidos COD Despachados</h3>
                        <p class="text-xs text-slate-500">Pedidos que contienen tus productos publicados, con courier y liquidación</p>
                    </div>
                </div>
                <span class="text-xs font-mono font-semibold px-2.5 py-1 rounded-full bg-white text-slate-600 border border-slate-200">
                    {{ $salesOrders->count() }} órdenes
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Pedido / Fecha</th>
                            <th class="py-3 px-4">Productos Despachados</th>
                            <th class="py-3 px-4">Destino / Cliente</th>
                            <th class="py-3 px-4">Courier COD</th>
                            <th class="py-3 px-4">Monto Mayorista</th>
                            <th class="py-3 px-4">Estado Pedido</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($salesOrders as $order)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 font-mono">{{ $order->order_number }}</div>
                                <div class="text-[11px] text-slate-400">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                                <div class="text-[10px] text-[#0061f2] font-semibold">DS: {{ $order->dropshipper->name ?? 'Dropshipper' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                @foreach($order->items as $item)
                                <div class="flex items-center justify-between gap-2 py-0.5">
                                    <span class="font-medium text-slate-800">{{ $item->product->name ?? 'Producto' }}</span>
                                    <span class="font-mono font-bold text-slate-900 shrink-0">x{{ $item->quantity }}</span>
                                </div>
                                @endforeach
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-900">{{ $order->customer_name }}</div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1">
                                    <i class="fa-solid fa-location-dot text-[#0061f2]"></i>
                                    {{ $order->shipping_city }}, {{ $order->shipping_department }}
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-medium text-slate-800">{{ $order->carrier->name ?? 'DEX Bolivia' }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $order->tracking_number ?? 'Guía pendiente' }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold whitespace-nowrap text-emerald-700">
                                @php
                                    $providerSubtotal = $order->items->sum(fn($i) => (float)$i->unit_cost_bob * (int)$i->quantity);
                                @endphp
                                Bs {{ number_format($providerSubtotal, 2) }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($order->status === 'settled')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check"></i> Liquidado
                                    </span>
                                @elseif($order->status === 'delivered')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        <i class="fa-solid fa-box-open"></i> Entregado
                                    </span>
                                @elseif(in_array($order->status, ['in_transit', 'dispatched']))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-truck-fast"></i> En Ruta DEX
                                    </span>
                                @elseif($order->status === 'confirmed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <i class="fa-solid fa-check"></i> Confirmado
                                    </span>
                                @elseif($order->status === 'pending_confirmation')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <i class="fa-solid fa-clock"></i> Pendiente Confirmación
                                    </span>
                                @elseif($order->status === 'cancelled')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fa-solid fa-xmark"></i> Cancelado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 italic">
                                No se registran ventas para tus productos aún.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Productos del Proveedor (#FFFFFF) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked text-[#0061f2]"></i>
                <h3 class="font-bold text-slate-900 text-sm font-heading">Mis Productos Publicados</h3>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button @click="downloadAlertModalOpen = true"
                    class="px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold text-xs shadow-2xs flex items-center gap-1.5 transition cursor-pointer"
                    title="Descargar plantilla de ejemplo">
                    <i class="fa-solid fa-download text-slate-500"></i> Plantilla Excel
                </button>
                <button @click="bulkModalOpen = true"
                    class="px-3.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold text-xs shadow-2xs flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fa-solid fa-file-excel text-emerald-600"></i> Carga Masiva
                </button>
                <button @click="categoryModalOpen = true"
                    class="px-3.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-[#4F46E5] border border-indigo-200/80 font-bold text-xs shadow-2xs flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fa-solid fa-tags"></i> Gestionar Categorías
                </button>
                <button @click="productModalOpen = true"
                    class="btn-scarlet px-3.5 py-1.5 rounded-lg font-bold text-xs shadow-sm flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-plus text-amber-200"></i> Nuevo Producto
                </button>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-white text-slate-900 border border-slate-200 font-mono">
                    {{ $products->count() }} productos
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                    <tr>
                        <th class="py-3 px-5">Producto / Códigos</th>
                        <th class="py-3 px-5">Categoría</th>
                        <th class="py-3 px-5">Costo Mayorista</th>
                        <th class="py-3 px-5">Precio Sugerido</th>
                        <th class="py-3 px-5">Stock Total</th>
                        <th class="py-3 px-5">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($products as $product)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-5">
                            <div class="flex items-center gap-3">
                                @if(!empty($product->images) && count($product->images) > 0)
                                    <img src="{{ $product->images[0] }}" alt="{{ $product->name }}" class="w-11 h-11 object-cover rounded-xl border border-slate-200 shadow-sm shrink-0">
                                @else
                                    <div class="w-11 h-11 rounded-xl bg-emerald-50/60 border border-emerald-100 text-emerald-900 flex items-center justify-center shrink-0 text-base">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="font-bold text-slate-900">{{ $product->name }}</div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="text-[11px] text-[#0061f2] font-mono font-semibold">SKU: {{ $product->sku }}</span>
                                        @if($product->internal_code)
                                            <span class="text-[10px] font-mono font-bold bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200 inline-flex items-center gap-1" title="Código Interno Bolidrop Oficial">
                                                <i class="fa-solid fa-barcode text-[9px] text-slate-400"></i>{{ $product->internal_code }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-5 text-slate-500">
                            {{ $product->category->name ?? 'General' }}
                        </td>
                        <td class="py-3.5 px-5 font-mono font-bold text-slate-900">
                            Bs {{ number_format($product->cost_price_bob, 2) }}
                        </td>
                        <td class="py-3.5 px-5 font-mono font-bold text-[#0061f2]">
                            Bs {{ number_format($product->suggested_price_bob, 2) }}
                        </td>
                        <td class="py-3.5 px-5 font-mono font-bold text-slate-900">
                            {{ $product->warehouseInventories->sum('stock_available') }} uds
                        </td>
                        <td class="py-3.5 px-5">
                            @if($product->status === 'published')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Publicado
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                {{ $product->status }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            No tienes productos registrados aún.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL INTERACTIVO: CONFIGURAR ALERTA DE STOCK MÍNIMO -->
    <div x-show="alertModalOpen" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
        
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 my-auto"
            @click.away="alertModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#0061f2] border border-emerald-200 flex items-center justify-center font-bold text-lg shadow-sm">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 font-heading text-lg leading-tight">Nueva Regla de Alerta</h3>
                        <p class="text-xs text-slate-500">Notifica cuando el stock caiga por debajo del umbral</p>
                    </div>
                </div>
                <button @click="alertModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="submitAlert()" class="text-xs space-y-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Producto:</label>
                    <select x-model="alertForm.product_id" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                        <option value="">Selecciona un producto...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} (SKU: {{ $p->sku }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Bodega / Almacén:</label>
                    <select x-model="alertForm.warehouse_id" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                        <option value="">Seleccionar bodega...</option>
                        @foreach($allWarehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->city }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Stock Mínimo (Umbral):</label>
                        <input type="number" x-model.number="alertForm.min_stock_threshold" min="1" required placeholder="Ej: 15"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs font-mono font-bold focus:outline-none focus:border-emerald-500">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Dispara alerta</span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Lote de Reabastecimiento:</label>
                        <input type="number" x-model.number="alertForm.reorder_quantity" min="1" required placeholder="Ej: 50"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs font-mono font-bold focus:outline-none focus:border-emerald-500">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Unidades sugeridas</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="alertModalOpen = false" class="px-4 py-2 rounded-xl font-semibold text-slate-600 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="alertSubmitting"
                        class="btn-scarlet px-6 py-2 rounded-xl font-bold flex items-center gap-2 shadow-sm">
                        <span x-show="!alertSubmitting" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-save text-amber-200"></i> Guardar Alerta
                        </span>
                        <span x-show="alertSubmitting" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-spinner animate-spin"></i> Guardando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL INTERACTIVO: TRANSFERENCIA DE STOCK ENTRE BODEGAS -->
    <div x-show="transferModalOpen" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
        
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 my-auto"
            @click.away="transferModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#0061f2] border border-emerald-200 flex items-center justify-center font-bold text-lg shadow-sm">
                        <i class="fa-solid fa-dolly"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 font-heading text-lg leading-tight">Transferir Mercadería entre Bodegas</h3>
                        <p class="text-xs text-slate-500">Mueve inventario entre Santa Cruz, La Paz y Cochabamba con Kardex dual</p>
                    </div>
                </div>
                <button @click="transferModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="submitTransfer()" class="text-xs space-y-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Producto a Mover:</label>
                    <select x-model="transferForm.product_id" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                        <option value="">Selecciona un producto...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} (SKU: {{ $p->sku }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Bodega Origen:</label>
                        <select x-model="transferForm.from_warehouse_id" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                            <option value="">Seleccionar origen...</option>
                            @foreach($allWarehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->city }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Bodega Destino:</label>
                        <select x-model="transferForm.to_warehouse_id" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                            <option value="">Seleccionar destino...</option>
                            @foreach($allWarehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->city }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Cantidad de Unidades:</label>
                        <input type="number" x-model.number="transferForm.quantity" min="1" required placeholder="Ej: 25"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs font-mono font-bold focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Motivo / Guía interna:</label>
                        <input type="text" x-model="transferForm.notes" placeholder="Ej: Reabastecimiento eje troncal"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="transferModalOpen = false" class="px-4 py-2 rounded-xl font-semibold text-slate-600 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="transferring"
                        class="btn-scarlet px-6 py-2 rounded-xl font-bold flex items-center gap-2 shadow-sm">
                        <span x-show="!transferring" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-right-arrow-left text-amber-200"></i> Registrar Transferencia
                        </span>
                        <span x-show="transferring" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-spinner animate-spin"></i> Procesando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL INTERACTIVO HORIZONTAL (SIN SCROLL VERTICAL): CREAR Y PUBLICAR NUEVO PRODUCTO MAYORISTA -->
    <div x-show="productModalOpen" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        
        <div class="bg-white rounded-3xl max-w-4xl w-full p-5 sm:p-7 shadow-2xl border border-slate-200 my-auto"
            @click.away="productModalOpen = false">
            
            <!-- Encabezado del Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 flex items-center justify-center font-bold text-lg shadow-sm">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 font-heading text-lg leading-tight">Publicar Producto Mayorista</h3>
                        <p class="text-xs text-slate-500">Disponible para miles de dropshippers en Bolivia</p>
                    </div>
                </div>
                <button @click="productModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="submitProduct()" class="text-xs space-y-4">
                
                <!-- Cuadrícula Horizontal de 2 Columnas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
                    
                    <!-- COLUMNA IZQUIERDA: Datos Básicos y Fotografía -->
                    <div class="space-y-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nombre del Producto:</label>
                            <input type="text" x-model="form.name" required placeholder="Ej: Mini Proyector LED Portátil Full HD"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">SKU / Código Único:</label>
                                <input type="text" x-model="form.sku" required placeholder="PROJ-MINI-01"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs font-mono focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block font-bold text-slate-700">Categoría:</label>
                                    <button type="button" @click="categoryModalOpen = true"
                                        class="text-[10px] font-extrabold text-[#4F46E5] hover:text-[#312E81] flex items-center gap-1 transition cursor-pointer"
                                        title="Crear, editar o eliminar categorías">
                                        <i class="fa-solid fa-gear text-[10px]"></i>
                                        <span>+ Gestionar</span>
                                    </button>
                                </div>
                                <select x-model="form.category_id" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs font-semibold focus:outline-none focus:border-[#4F46E5] focus:bg-white transition">
                                    <option value="">Seleccionar categoría...</option>
                                    <template x-for="cat in categoriesList" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Carga de Imagen del Producto Compacta -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                            <label class="block font-bold text-slate-700 text-[11px]">Foto / Imagen del Producto:</label>
                            <div class="flex items-center gap-3">
                                <!-- Preview Box -->
                                <div class="w-16 h-16 rounded-xl border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden shrink-0 relative group shadow-inner">
                                    <template x-if="imagePreview">
                                        <div class="w-full h-full relative">
                                            <img :src="imagePreview" class="w-full h-full object-cover">
                                            <button type="button" @click="removeImage()"
                                                class="absolute top-0.5 right-0.5 bg-red-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[9px] shadow hover:bg-red-700">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!imagePreview">
                                        <div class="text-center p-1 text-slate-400">
                                            <i class="fa-solid fa-image text-lg text-[#0061f2]"></i>
                                            <div class="text-[8px] font-bold">Sin foto</div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Acciones de Imagen -->
                                <div class="flex-1 space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="$refs.fileInput.click()"
                                            class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-[11px] inline-flex items-center gap-1.5 shadow-sm transition">
                                            <i class="fa-solid fa-upload text-[#0061f2]"></i> Subir Foto
                                        </button>
                                        <span class="text-[10px] text-slate-500 truncate max-w-[140px]" x-text="imageFile ? imageFile.name : 'PNG, JPG (Máx 5MB)'"></span>
                                        <input type="file" x-ref="fileInput" @change="handleFileChange($event)" accept="image/png,image/jpeg,image/webp,image/jpg" class="hidden">
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[9px] text-slate-400 font-bold uppercase shrink-0">O URL:</span>
                                        <input type="url" x-model="form.image_url" @input="handleUrlChange()" placeholder="https://ejemplo.com/foto.jpg"
                                            class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-slate-800 text-[10px] focus:outline-none focus:border-emerald-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: Precios y Bodega -->
                    <div class="space-y-3">
                        <div class="grid grid-cols-3 gap-2.5">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1 text-[11px]">Costo (BOB):</label>
                                <input type="number" step="0.5" x-model.number="form.cost_price_bob" required placeholder="120.00"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 text-slate-800 text-xs font-mono font-bold focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                                <span class="text-[9px] text-slate-400 block mt-0.5">Cobro neto</span>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1 text-[11px]">Piso Mín. (BOB):</label>
                                <input type="number" step="0.5" x-model.number="form.min_price_bob" required placeholder="150.00"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 text-slate-800 text-xs font-mono focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                                <span class="text-[9px] text-slate-400 block mt-0.5">Piso venta</span>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1 text-[11px]">Sugerido (BOB):</label>
                                <input type="number" step="0.5" x-model.number="form.suggested_price_bob" required placeholder="199.00"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 text-[#0061f2] text-xs font-mono font-bold focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                                <span class="text-[9px] text-[#0061f2] block mt-0.5">Venta público</span>
                            </div>
                        </div>

                        <!-- Asignación Inicial de Inventario Físico -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                            <div class="font-bold text-slate-900 flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-[11px]">
                                    <i class="fa-solid fa-warehouse text-[#0061f2]"></i>
                                    Asignación Inicial de Inventario Físico
                                </span>
                                <span class="text-[9px] bg-emerald-100 text-emerald-950 font-bold px-2 py-0.5 rounded-full">Kardex Auto</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-medium text-slate-600 mb-1 text-[10px]">Bodega de Ingreso:</label>
                                    <select x-model="form.initial_warehouse_id"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                                        <option value="">Sin inventario inicial</option>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->city }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-600 mb-1 text-[10px]">Cantidad Inicial (Uds):</label>
                                    <input type="number" x-model.number="form.initial_stock" placeholder="50" min="0"
                                        class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-slate-800 text-xs font-mono focus:outline-none focus:border-emerald-500">
                                </div>
                            </div>
                        </div>

                        <!-- Indicador COD -->
                        <div class="flex items-center gap-2 p-2 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-[11px]">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                            <span class="font-semibold">Habilitado para Cobro Contra Entrega (COD Bolivia)</span>
                        </div>
                    </div>

                </div>

                <!-- Botones Inferiores -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="productModalOpen = false" class="px-4 py-2 rounded-xl font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="submitting"
                        class="btn-scarlet px-6 py-2 rounded-xl font-bold flex items-center gap-2 shadow-sm transition">
                        <span x-show="!submitting" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-cloud-arrow-up text-amber-200"></i> Publicar en Catálogo
                        </span>
                        <span x-show="submitting" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-spinner animate-spin"></i> Guardando...
                        </span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL CRUD COMPLETO DE CATEGORÍAS DE PRODUCTOS (LISTAR, CREAR, EDITAR, ELIMINAR) -->
    <!-- ===================================================================== -->
    <div x-show="categoryModalOpen" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[60] bg-[#0F172A]/65 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 overflow-y-auto">

        <div class="bg-white rounded-3xl max-w-3xl w-full p-5 sm:p-7 shadow-2xl border border-slate-200 my-auto"
            @click.away="categoryModalOpen = false">

            <!-- Encabezado del Modal de Categorías -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-[#4F46E5] border border-indigo-200/80 flex items-center justify-center font-bold text-lg shadow-xs">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#0F172A] font-heading text-lg leading-tight">Gestión de Categorías de Productos</h3>
                        <p class="text-xs text-slate-500">Crea, edita, lista o elimina categorías para la Megabodega Mayorista</p>
                    </div>
                </div>
                <button type="button" @click="categoryModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Formulario Insertar / Editar Categoría -->
            <form @submit.prevent="saveCategory()" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-3 mb-5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="font-extrabold uppercase tracking-wider text-[11px] text-[#4F46E5] flex items-center gap-1.5">
                        <i class="fa-solid" :class="categoryForm.id ? 'fa-pen-to-square' : 'fa-plus-circle'"></i>
                        <span x-text="categoryForm.id ? 'Editar Categoría Seleccionada' : 'Insertar Nueva Categoría'"></span>
                    </span>
                    <button type="button" x-show="categoryForm.id" x-cloak @click="resetCategoryForm()"
                        class="text-[11px] font-bold text-slate-500 hover:text-rose-600 flex items-center gap-1 cursor-pointer">
                        <i class="fa-solid fa-rotate-left"></i> Cancelar edición
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-5">
                        <label class="block font-bold text-slate-700 mb-1">Nombre de Categoría:</label>
                        <input type="text" x-model="categoryForm.name" required placeholder="Ej: Deportes y Fitness"
                            class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-[#0F172A] text-xs font-semibold focus:outline-none focus:border-[#4F46E5] transition">
                    </div>
                    <div class="sm:col-span-5">
                        <label class="block font-bold text-slate-700 mb-1">Descripción:</label>
                        <input type="text" x-model="categoryForm.description" placeholder="Ej: Artículos deportivos, suplementos y gimnasio"
                            class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-[#0F172A] text-xs focus:outline-none focus:border-[#4F46E5] transition">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" :disabled="categorySubmitting"
                            class="w-full btn-scarlet py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                            <i class="fa-solid" :class="categorySubmitting ? 'fa-spinner animate-spin' : (categoryForm.id ? 'fa-check' : 'fa-plus')"></i>
                            <span x-text="categoryForm.id ? 'Actualizar' : 'Guardar'"></span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Buscador y Tabla CRUD de Categorías -->
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="text-xs font-bold text-[#0F172A] flex items-center gap-2">
                        <span>Listado de Categorías Registradas</span>
                        <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-[#4F46E5] border border-indigo-200 font-mono text-[11px]"
                              x-text="categoriesList.length + ' categorías'"></span>
                    </div>
                    <div class="relative w-full sm:w-64">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                        <input type="text" x-model="categorySearch" placeholder="Filtrar categoría..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-[#0F172A] focus:bg-white focus:outline-none focus:border-[#4F46E5]">
                    </div>
                </div>

                <div class="border border-slate-200 rounded-2xl overflow-hidden max-h-64 overflow-y-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-extrabold text-slate-500 border-b border-slate-200 sticky top-0">
                            <tr>
                                <th class="py-2.5 px-4">Categoría / Slug</th>
                                <th class="py-2.5 px-4">Descripción</th>
                                <th class="py-2.5 px-4 text-center">Productos</th>
                                <th class="py-2.5 px-4 text-right">Acciones CRUD</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="cat in filteredCategories()" :key="cat.id">
                                <tr class="hover:bg-indigo-50/40 transition">
                                    <td class="py-2.5 px-4">
                                        <div class="font-bold text-[#0F172A]" x-text="cat.name"></div>
                                        <div class="text-[10px] font-mono text-slate-400" x-text="cat.slug"></div>
                                    </td>
                                    <td class="py-2.5 px-4 text-slate-500 max-w-xs truncate" x-text="cat.description || 'Sin descripción'"></td>
                                    <td class="py-2.5 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200"
                                              x-text="(cat.products_count || 0) + ' uds'"></span>
                                    </td>
                                    <td class="py-2.5 px-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" @click="selectCategoryForProduct(cat)"
                                                class="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold text-[10px] transition cursor-pointer"
                                                title="Elegir esta categoría para el producto">
                                                <i class="fa-solid fa-check mr-0.5"></i> Usar
                                            </button>
                                            <button type="button" @click="editCategory(cat)"
                                                class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-[#4F46E5] border border-indigo-200 transition cursor-pointer"
                                                title="Editar Categoría">
                                                <i class="fa-solid fa-pen text-[10px]"></i>
                                            </button>
                                            <button type="button" @click="deleteCategory(cat)"
                                                class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 transition cursor-pointer"
                                                title="Eliminar Categoría">
                                                <i class="fa-solid fa-trash text-[10px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredCategories().length === 0">
                                <td colspan="4" class="py-6 text-center text-slate-400 italic">
                                    No se encontraron categorías con ese criterio.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 mt-4 border-t border-slate-100">
                <button type="button" @click="categoryModalOpen = false"
                    class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-[#0F172A] font-bold text-xs transition cursor-pointer">
                    Cerrar Gestor de Categorías
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL DE ALERTA TAILWIND: CONFIRMAR DESCARGA DE PLANTILLA      -->
    <!-- ============================================================== -->
    <div x-show="downloadAlertModalOpen" x-cloak
        class="fixed inset-0 z-[60] overflow-y-auto bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-5 text-left transform transition-all relative z-[70]"
            @click.away="downloadAlertModalOpen = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 shadow-xs">
                    <i class="fa-solid fa-file-excel text-2xl"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-900 font-heading">
                        ¿Deseas descargar la plantilla oficial?
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Se descargará la plantilla con los 11 campos requeridos, 3 productos de ejemplo ya configurados y los códigos oficiales de Megabodegas de Bolivia (<span class="font-mono text-indigo-600 font-bold">WH-SCZ-01</span>, <span class="font-mono text-indigo-600 font-bold">WH-LPZ-01</span>, <span class="font-mono text-indigo-600 font-bold">WH-CBB-01</span>).
                    </p>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                <div class="flex items-center justify-between text-slate-700 font-semibold">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-xs"></i>
                        Estructura lista para Excel y Google Sheets
                    </span>
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">Recomendado</span>
                </div>
                <p class="text-[11px] text-slate-500">
                    Al importar, el sistema generará automáticamente en paralelo un <strong>Código Interno Oficial (BLD-PRD-XXXXXX)</strong> para control en bodegas y manifiestos.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-2.5 pt-2">
                <button type="button" @click="downloadAlertModalOpen = false"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                    Cancelar
                </button>
                <a href="{{ route('products.template.download', ['format' => 'csv']) }}"
                    @click="downloadAlertModalOpen = false"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition cursor-pointer inline-flex items-center justify-center gap-1.5 text-decoration-none">
                    <i class="fa-solid fa-file-csv text-slate-500"></i> Descargar CSV
                </a>
                <a href="{{ route('products.template.download', ['format' => 'xlsx']) }}"
                    @click="downloadAlertModalOpen = false"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition cursor-pointer inline-flex items-center justify-center gap-2 text-decoration-none">
                    <i class="fa-solid fa-download"></i> Descargar Excel (.xlsx)
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL INTERACTIVO: CARGA MASIVA DE PRODUCTOS (EXCEL / CSV)     -->
    <!-- ============================================================== -->
    <div x-show="bulkModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl border border-slate-200 space-y-6 text-left transform transition-all my-8"
            @click.away="if (!bulkUploading) bulkModalOpen = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100">

            <!-- Encabezado del Modal -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-xs border border-emerald-100">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-heading">
                            Carga Masiva de Productos en Lote
                        </h3>
                        <p class="text-xs text-slate-500">
                            Importa productos en Excel (.xlsx) o CSV. Se generarán Códigos Internos Bolidrop en paralelo.
                        </p>
                    </div>
                </div>
                <button type="button" @click="bulkModalOpen = false" :disabled="bulkUploading"
                    class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Paso 1: Descargar Plantilla -->
            <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-info text-emerald-600 text-sm mt-0.5"></i>
                    <div>
                        <span class="font-bold text-emerald-950">¿No tienes el formato oficial de importación?</span>
                        <p class="text-[11px] text-emerald-800">Usa la plantilla preconfigurada con validaciones, ejemplos y códigos de megabodega.</p>
                    </div>
                </div>
                <button type="button" @click="downloadAlertModalOpen = true"
                    class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs inline-flex items-center justify-center gap-1.5 shadow-xs transition shrink-0 cursor-pointer">
                    <i class="fa-solid fa-download"></i> Descargar Plantilla
                </button>
            </div>

            <!-- Paso 2: Zona de Carga de Archivo (Dropzone) -->
            <div x-show="!bulkResult">
                <div class="border-2 border-dashed rounded-2xl p-6 text-center transition cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/30"
                    :class="bulkFile ? 'border-emerald-400 bg-emerald-50/20' : 'border-slate-300 bg-slate-50/50'"
                    @dragover.prevent=""
                    @drop.prevent="handleBulkDrop($event)"
                    @click="$refs.bulkFileInput.click()">

                    <input type="file" x-ref="bulkFileInput" @change="handleBulkFileInput($event)"
                        accept=".xlsx,.xls,.csv" class="hidden">

                    <div class="space-y-3">
                        <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center"
                            :class="bulkFile ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">
                            <i class="fa-solid text-2xl" :class="bulkFile ? 'fa-file-excel' : 'fa-cloud-arrow-up'"></i>
                        </div>

                        <template x-if="!bulkFile">
                            <div>
                                <p class="text-sm font-bold text-slate-800">
                                    Haz clic para seleccionar o arrastra tu archivo aquí
                                </p>
                                <p class="text-xs text-slate-400 mt-1">
                                    Formatos permitidos: <span class="font-mono font-semibold text-slate-600">.xlsx, .xls, .csv</span> (Máximo 10 MB)
                                </p>
                            </div>
                        </template>

                        <template x-if="bulkFile">
                            <div class="space-y-1">
                                <p class="text-sm font-bold text-emerald-900" x-text="bulkFile.name"></p>
                                <p class="text-xs text-emerald-700 font-mono" x-text="(bulkFile.size / 1024).toFixed(1) + ' KB'"></p>
                                <button type="button" @click.stop="removeBulkFile()"
                                    class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline pt-1 cursor-pointer">
                                    Cambiar archivo
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 pt-3">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                        Generación automática de Código Interno (BLD-PRD-XXXXXX)
                    </span>
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-boxes-packing text-indigo-600"></i>
                        Registro simultáneo en Kardex e Inventario
                    </span>
                </div>
            </div>

            <!-- Paso 3: Resultados de la Importación -->
            <div x-show="bulkResult" class="space-y-4">
                <!-- Tarjetas de resumen -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                        <div class="text-xs text-slate-500 font-medium">Total Procesados</div>
                        <div class="text-2xl font-extrabold text-slate-900 font-mono mt-0.5" x-text="bulkResult?.total || 0"></div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-center">
                        <div class="text-xs text-emerald-700 font-bold">Nuevos Creados</div>
                        <div class="text-2xl font-extrabold text-emerald-700 font-mono mt-0.5" x-text="bulkResult?.created || 0"></div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-center">
                        <div class="text-xs text-blue-700 font-bold">Actualizados</div>
                        <div class="text-2xl font-extrabold text-blue-700 font-mono mt-0.5" x-text="bulkResult?.updated || 0"></div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-center">
                        <div class="text-xs text-amber-700 font-bold">Con Observaciones</div>
                        <div class="text-2xl font-extrabold text-amber-700 font-mono mt-0.5" x-text="bulkResult?.failed || 0"></div>
                    </div>
                </div>

                <!-- Lista de errores si hubieron -->
                <template x-if="bulkErrors && bulkErrors.length > 0">
                    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 space-y-1.5 text-xs text-rose-800">
                        <div class="font-bold flex items-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                            Observaciones encontradas en algunas filas:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] max-h-24 overflow-y-auto">
                            <template x-for="(err, i) in bulkErrors" :key="i">
                                <li x-text="err"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <!-- Tabla de productos procesados con Doble Código -->
                <div class="border border-slate-200 rounded-xl overflow-hidden max-h-60 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 text-[10px] uppercase font-bold text-slate-600 border-b border-slate-200 sticky top-0">
                            <tr>
                                <th class="py-2.5 px-3">SKU Cliente</th>
                                <th class="py-2.5 px-3">Código Bolidrop</th>
                                <th class="py-2.5 px-3">Producto</th>
                                <th class="py-2.5 px-3">Bodega / Stock</th>
                                <th class="py-2.5 px-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(p, i) in bulkProducts" :key="i">
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2 px-3 font-mono font-bold text-[#0061f2]" x-text="p.sku"></td>
                                    <td class="py-2 px-3">
                                        <span class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 inline-flex items-center gap-1">
                                            <i class="fa-solid fa-barcode text-[9px] text-slate-400"></i>
                                            <span x-text="p.internal_code"></span>
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 font-semibold text-slate-900" x-text="p.name"></td>
                                    <td class="py-2 px-3 text-slate-600">
                                        <span x-text="p.warehouse"></span> • <strong class="text-slate-900" x-text="p.stock + ' uds'"></strong>
                                    </td>
                                    <td class="py-2 px-3 text-right">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="p.action === 'creado' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'"
                                            x-text="p.action === 'creado' ? 'Nuevo' : 'Actualizado'">
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <template x-if="!bulkResult">
                    <div class="flex items-center justify-between w-full">
                        <button type="button" @click="bulkModalOpen = false" :disabled="bulkUploading"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer">
                            Cancelar
                        </button>
                        <button type="button" @click="submitBulkUpload()" :disabled="!bulkFile || bulkUploading"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 inline-flex items-center gap-2 transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="bulkUploading">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                                    <span>Procesando archivo...</span>
                                </span>
                            </template>
                            <template x-if="!bulkUploading">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-upload"></i>
                                    <span>Importar Productos</span>
                                </span>
                            </template>
                        </button>
                    </div>
                </template>

                <template x-if="bulkResult">
                    <div class="flex items-center justify-between w-full">
                        <button type="button" @click="resetBulkModal()"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer">
                            <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Cargar otro archivo
                        </button>
                        <button type="button" @click="window.location.reload()"
                            class="px-5 py-2.5 rounded-xl bg-[#0F172A] hover:bg-slate-800 text-white font-bold text-xs shadow-md inline-flex items-center gap-2 transition cursor-pointer">
                            <i class="fa-solid fa-check text-emerald-400"></i>
                            <span>Listo, Actualizar Panel y Catálogo</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>

<script>
function proveedorDashboard() {
    return {
        productModalOpen: false,
        categoryModalOpen: false,
        transferModalOpen: false,
        alertModalOpen: false,
        downloadAlertModalOpen: false,
        bulkModalOpen: false,
        bulkUploading: false,
        bulkFile: null,
        bulkResult: null,
        bulkProducts: [],
        bulkErrors: [],
        submitting: false,
        categorySubmitting: false,
        transferring: false,
        alertSubmitting: false,
        toast: { show: false, message: '', type: 'success' },
        imageFile: null,
        imagePreview: null,
        categorySearch: '',
        categoriesList: @json($categories),
        categoryForm: {
            id: null,
            name: '',
            description: ''
        },
        form: {
            name: '',
            sku: '',
            category_id: '{{ $categories->first()?->id ?? "" }}',
            cost_price_bob: 120,
            suggested_price_bob: 199,
            min_price_bob: 140,
            initial_warehouse_id: '{{ $warehouses->first()?->id ?? "" }}',
            initial_stock: 50,
            is_cod_available: true,
            image_url: ''
        },
        transferForm: {
            product_id: '{{ $products->first()?->id ?? "" }}',
            from_warehouse_id: '{{ $allWarehouses->first()?->id ?? "" }}',
            to_warehouse_id: '{{ $allWarehouses->count() > 1 ? $allWarehouses->get(1)->id : "" }}',
            quantity: 10,
            notes: 'Transferencia operativa entre bodegas'
        },
        alertForm: {
            product_id: '{{ $products->first()?->id ?? "" }}',
            warehouse_id: '{{ $allWarehouses->first()?->id ?? "" }}',
            min_stock_threshold: 20,
            reorder_quantity: 50
        },

        async submitAlert() {
            this.alertSubmitting = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch('/inventory/alerts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(this.alertForm)
                });
                const result = await response.json();
                if (response.ok && result.status === 'success') {
                    this.showToast(result.message || 'Alerta de inventario configurada correctamente.', 'success');
                    this.alertModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    const errMsg = result.errors ? Object.values(result.errors).flat().join(' ') : (result.message || 'Error al configurar la alerta.');
                    this.showToast(errMsg, 'error');
                }
            } catch (err) {
                this.showToast('Error de comunicación con el servidor.', 'error');
            } finally {
                this.alertSubmitting = false;
            }
        },

        async submitTransfer() {
            if (this.transferForm.from_warehouse_id === this.transferForm.to_warehouse_id) {
                this.showToast('La bodega de origen y destino no pueden ser iguales.', 'error');
                return;
            }
            this.transferring = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch('/inventory/transfer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(this.transferForm)
                });
                const result = await response.json();
                if (response.ok && result.status === 'success') {
                    this.showToast(result.message || 'Transferencia realizada con éxito.', 'success');
                    this.transferModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    const errMsg = result.errors ? Object.values(result.errors).flat().join(' ') : (result.message || 'Error al realizar la transferencia.');
                    this.showToast(errMsg, 'error');
                }
            } catch (err) {
                this.showToast('Error de comunicación con el servidor.', 'error');
            } finally {
                this.transferring = false;
            }
        },

        handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                this.showToast('La imagen no debe superar los 5MB.', 'error');
                return;
            }
            this.imageFile = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        handleUrlChange() {
            if (this.form.image_url && (this.form.image_url.startsWith('http://') || this.form.image_url.startsWith('https://') || this.form.image_url.startsWith('/'))) {
                this.imagePreview = this.form.image_url;
                this.imageFile = null;
            }
        },

        removeImage() {
            this.imageFile = null;
            this.imagePreview = null;
            this.form.image_url = '';
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 5000);
        },

        filteredCategories() {
            const q = this.categorySearch.toLowerCase().trim();
            if (!q) return this.categoriesList;
            return this.categoriesList.filter(c =>
                (c.name || '').toLowerCase().includes(q) ||
                (c.slug || '').toLowerCase().includes(q) ||
                (c.description || '').toLowerCase().includes(q)
            );
        },

        resetCategoryForm() {
            this.categoryForm = { id: null, name: '', description: '' };
        },

        editCategory(cat) {
            this.categoryForm = {
                id: cat.id,
                name: cat.name,
                description: cat.description || ''
            };
        },

        selectCategoryForProduct(cat) {
            this.form.category_id = cat.id;
            this.categoryModalOpen = false;
            this.productModalOpen = true;
            this.showToast(`Categoría "${cat.name}" seleccionada para el producto.`, 'success');
        },

        async saveCategory() {
            if (!this.categoryForm.name.trim()) return;
            this.categorySubmitting = true;
            try {
                const isEdit = Boolean(this.categoryForm.id);
                const url = isEdit ? `/categories/${this.categoryForm.id}` : '/categories';
                const method = isEdit ? 'PUT' : 'POST';
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        name: this.categoryForm.name,
                        description: this.categoryForm.description
                    })
                });
                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    const savedCat = result.data;
                    if (isEdit) {
                        const idx = this.categoriesList.findIndex(c => c.id === savedCat.id);
                        if (idx !== -1) this.categoriesList[idx] = savedCat;
                    } else {
                        this.categoriesList.push(savedCat);
                        this.form.category_id = savedCat.id;
                    }
                    this.resetCategoryForm();
                    this.showToast(result.message || 'Categoría guardada con éxito.', 'success');
                } else {
                    this.showToast(result.message || 'No se pudo guardar la categoría.', 'error');
                }
            } catch (err) {
                this.showToast('Error de comunicación al guardar la categoría.', 'error');
            } finally {
                this.categorySubmitting = false;
            }
        },

        async deleteCategory(cat) {
            if (!confirm(`¿Estás seguro de eliminar la categoría "${cat.name}"?`)) return;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(`/categories/${cat.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }
                });
                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.categoriesList = this.categoriesList.filter(c => c.id !== cat.id);
                    if (this.form.category_id === cat.id) {
                        this.form.category_id = this.categoriesList[0]?.id || '';
                    }
                    this.showToast(result.message || 'Categoría eliminada correctamente.', 'success');
                } else {
                    this.showToast(result.message || 'No se puede eliminar la categoría porque tiene productos asociados.', 'error');
                }
            } catch (err) {
                this.showToast('Error al intentar eliminar la categoría.', 'error');
            }
        },

        async submitProduct() {
            this.submitting = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                // Crear FormData para soportar carga de imagen física y normalizar booleanos
                const formData = new FormData();
                for (const key in this.form) {
                    if (this.form[key] !== null && this.form[key] !== undefined && this.form[key] !== '') {
                        if (typeof this.form[key] === 'boolean') {
                            formData.append(key, this.form[key] ? '1' : '0');
                        } else {
                            formData.append(key, this.form[key]);
                        }
                    }
                }
                if (this.imageFile) {
                    formData.append('image', this.imageFile);
                }

                const response = await fetch('/products', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.showToast(result.message || 'Producto publicado con éxito en Catálogo e Inventarios.', 'success');
                    this.productModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 900);
                } else {
                    const errMsg = result.errors
                        ? Object.values(result.errors).flat().join(' ')
                        : (result.message || 'Error al guardar el producto.');
                    this.showToast(errMsg, 'error');
                }
            } catch (err) {
                this.showToast('Error de comunicación con el servidor.', 'error');
            } finally {
                this.submitting = false;
            }
        },

        triggerDownload(format = 'xlsx') {
            this.downloadAlertModalOpen = false;
            this.showToast('Generando plantilla oficial Bolidrop...', 'success');
            window.location.href = `/products/template/download?format=${format}`;
        },

        handleBulkFileInput(e) {
            const file = e.target.files[0];
            if (file) {
                this.bulkFile = file;
                this.bulkResult = null;
                this.bulkErrors = [];
            }
        },

        handleBulkDrop(e) {
            const file = e.dataTransfer.files[0];
            if (file) {
                this.bulkFile = file;
                this.bulkResult = null;
                this.bulkErrors = [];
            }
        },

        removeBulkFile() {
            this.bulkFile = null;
            this.bulkResult = null;
            this.bulkProducts = [];
            this.bulkErrors = [];
            if (this.$refs.bulkFileInput) {
                this.$refs.bulkFileInput.value = '';
            }
        },

        resetBulkModal() {
            this.bulkFile = null;
            this.bulkResult = null;
            this.bulkProducts = [];
            this.bulkErrors = [];
            if (this.$refs.bulkFileInput) {
                this.$refs.bulkFileInput.value = '';
            }
        },

        async submitBulkUpload() {
            if (!this.bulkFile) {
                this.showToast('Por favor selecciona un archivo Excel o CSV.', 'error');
                return;
            }
            this.bulkUploading = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const formData = new FormData();
                formData.append('file', this.bulkFile);

                const response = await fetch('/products/bulk-upload', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.bulkResult = result.summary;
                    this.bulkProducts = result.products || [];
                    this.bulkErrors = result.errors || [];
                    this.showToast(result.message || 'Carga masiva completada exitosamente.', 'success');
                } else {
                    this.bulkErrors = result.errors || [result.message || 'Error al procesar el archivo.'];
                    this.showToast(result.message || 'Hubo observaciones al procesar el archivo.', 'error');
                }
            } catch (err) {
                this.showToast('Error de conexión al cargar el archivo.', 'error');
            } finally {
                this.bulkUploading = false;
            }
        }
    }
}

// =============================================================================
// MÓDULO ALPINE.JS — GESTIÓN DE ALMACENES
// =============================================================================
function almacenModule() {
    return {
        // Estado UI
        activeTab: 'bodegas',
        almToast: { show: false, message: '', type: 'success' },

        // ---- BODEGAS ----
        warehouseList: [],
        loadingWarehouses: false,
        warehouseModalOpen: false,
        warehouseSubmitting: false,
        selectedWarehouseId: '',
        warehouseForm: {
            id: null, name: '', code: '', department: '', city: '',
            address: '', contact_phone: '', is_active: true
        },

        // ---- PRODUCTOS ----
        productList: [],
        loadingProducts: false,
        productSearch: '',
        filterVisiblePublic: false,
        filterVisibleSellers: false,
        selectedInventoryIds: [],
        productPagination: null,

        // ---- RESERVAS ----
        reserveList: [],
        loadingReserves: false,
        reserveModalOpen: false,
        reserveSubmitting: false,
        reserveForm: {
            product_id: '', productName: '', stockAvailable: null,
            dropshipper_id: '', quantity: 1, notes: '', expires_at: ''
        },

        // ---- TRASPASO ----
        traspasoForm: { from_warehouse_id: '', to_warehouse_id: '', product_id: '', quantity: 1, notes: '' },
        traspasoSubmitting: false,

        // =================================================================
        // INIT
        // =================================================================
        init() {
            this.loadWarehouses();
        },

        showAlmToast(message, type = 'success') {
            this.almToast = { show: true, message, type };
            setTimeout(() => { this.almToast.show = false; }, 5000);
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        // =================================================================
        // CRUD ALMACENES
        // =================================================================
        async loadWarehouses() {
            this.loadingWarehouses = true;
            try {
                const res = await fetch('/warehouses', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.status === 'success') this.warehouseList = data.data;
            } catch { this.showAlmToast('Error al cargar almacenes.', 'error'); }
            finally { this.loadingWarehouses = false; }
        },

        resetWarehouseForm() {
            this.warehouseForm = { id: null, name: '', code: '', department: '', city: '', address: '', contact_phone: '', is_active: true };
        },

        editWarehouse(wh) {
            this.warehouseForm = {
                id: wh.id, name: wh.name, code: wh.code,
                department: wh.department, city: wh.city,
                address: wh.address, contact_phone: wh.contact_phone ?? '',
                is_active: wh.is_active
            };
            this.warehouseModalOpen = true;
        },

        selectWarehouse(wh) {
            this.selectedWarehouseId = wh.id;
        },

        async saveWarehouse() {
            if (!this.warehouseForm.name || !this.warehouseForm.department || !this.warehouseForm.city || !this.warehouseForm.address) {
                this.showAlmToast('Completa los campos obligatorios.', 'error');
                return;
            }
            this.warehouseSubmitting = true;
            const isEdit = Boolean(this.warehouseForm.id);
            const url    = isEdit ? `/warehouses/${this.warehouseForm.id}` : '/warehouses';
            const method = isEdit ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify(this.warehouseForm)
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.showAlmToast(data.message, 'success');
                    this.warehouseModalOpen = false;
                    await this.loadWarehouses();
                } else {
                    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error al guardar.');
                    this.showAlmToast(msg, 'error');
                }
            } catch { this.showAlmToast('Error de comunicación.', 'error'); }
            finally { this.warehouseSubmitting = false; }
        },

        async confirmDeleteWarehouse(wh) {
            if (!confirm(`¿Eliminar el almacén "${wh.name}"?\nSolo se puede eliminar si no tiene stock activo.`)) return;
            try {
                const res = await fetch(`/warehouses/${wh.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() }
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.showAlmToast(data.message, 'success');
                    await this.loadWarehouses();
                } else {
                    this.showAlmToast(data.message || 'No se pudo eliminar.', 'error');
                }
            } catch { this.showAlmToast('Error al eliminar.', 'error'); }
        },

        // =================================================================
        // PRODUCTOS DEL ALMACÉN
        // =================================================================
        async loadProducts(page = 1) {
            if (!this.selectedWarehouseId) { this.productList = []; return; }
            this.loadingProducts = true;
            const params = new URLSearchParams({ page });
            if (this.productSearch) params.set('search', this.productSearch);
            if (this.filterVisiblePublic)  params.set('visible_public', '1');
            if (this.filterVisibleSellers) params.set('visible_sellers', '1');
            try {
                const res = await fetch(`/warehouses/${this.selectedWarehouseId}/products?${params}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.productList      = data.data.data ?? data.data;
                    this.productPagination = data.data.meta ? { ...data.data.meta, prev_page_url: data.data.prev_page_url, next_page_url: data.data.next_page_url } : null;
                }
            } catch { this.showAlmToast('Error al cargar productos.', 'error'); }
            finally { this.loadingProducts = false; }
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectedInventoryIds = this.productList.map(i => i.id);
            } else {
                this.selectedInventoryIds = [];
            }
        },

        toggleSelectInventory(id) {
            const idx = this.selectedInventoryIds.indexOf(id);
            if (idx === -1) this.selectedInventoryIds.push(id);
            else this.selectedInventoryIds.splice(idx, 1);
        },

        // Toggle individual de visibilidad (switch)
        async toggleSingleVisibility(inv, field, value) {
            const payload = {};
            payload[field] = value;
            try {
                const res = await fetch(`/warehouses/${this.selectedWarehouseId}/products/${inv.id}/visibility`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    inv[field] = value;
                    this.showAlmToast(data.message, 'success');
                } else {
                    this.showAlmToast(data.message || 'Error al cambiar visibilidad.', 'error');
                }
            } catch { this.showAlmToast('Error de comunicación.', 'error'); }
        },

        // Toggle en lote (null = no cambiar ese campo)
        async batchVisibility(visiblePublic, visibleSellers) {
            if (this.selectedInventoryIds.length === 0) return;
            const payload = { inventory_ids: this.selectedInventoryIds };
            if (visiblePublic  !== null) payload.visible_public  = visiblePublic;
            if (visibleSellers !== null) payload.visible_sellers = visibleSellers;
            try {
                const res = await fetch(`/warehouses/${this.selectedWarehouseId}/products/batch-visibility`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.showAlmToast(data.message, 'success');
                    this.selectedInventoryIds = [];
                    await this.loadProducts();
                } else {
                    this.showAlmToast(data.message || 'Error en la operación por lote.', 'error');
                }
            } catch { this.showAlmToast('Error de comunicación.', 'error'); }
        },

        // =================================================================
        // RESERVAS
        // =================================================================
        async loadReserves(page = 1) {
            if (!this.selectedWarehouseId) { this.reserveList = []; return; }
            this.loadingReserves = true;
            try {
                const res = await fetch(`/warehouses/${this.selectedWarehouseId}/reserves?page=${page}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.reserveList = data.data.data ?? data.data;
                }
            } catch { this.showAlmToast('Error al cargar reservas.', 'error'); }
            finally { this.loadingReserves = false; }
        },

        resetReserveForm() {
            this.reserveForm = { product_id: '', productName: '', stockAvailable: null, dropshipper_id: '', quantity: 1, notes: '', expires_at: '' };
        },

        openReserveModal(inv) {
            this.resetReserveForm();
            this.reserveForm.product_id      = inv.product?.id ?? '';
            this.reserveForm.productName     = inv.product?.name ?? '';
            this.reserveForm.stockAvailable  = inv.stock_available;
            this.reserveForm.quantity        = Math.min(1, inv.stock_available);
            this.reserveModalOpen = true;
        },

        async submitReserve() {
            if (!this.reserveForm.dropshipper_id || this.reserveForm.quantity < 1) {
                this.showAlmToast('Selecciona un vendedor y una cantidad válida.', 'error');
                return;
            }
            this.reserveSubmitting = true;
            try {
                const payload = {
                    product_id:     this.reserveForm.product_id,
                    dropshipper_id: this.reserveForm.dropshipper_id,
                    quantity:       this.reserveForm.quantity,
                    notes:          this.reserveForm.notes || null,
                    expires_at:     this.reserveForm.expires_at || null,
                };
                const res = await fetch(`/warehouses/${this.selectedWarehouseId}/reserves`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.showAlmToast(data.message, 'success');
                    this.reserveModalOpen = false;
                    await this.loadProducts();
                    await this.loadReserves();
                } else {
                    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error al crear reserva.');
                    this.showAlmToast(msg, 'error');
                }
            } catch { this.showAlmToast('Error de comunicación.', 'error'); }
            finally { this.reserveSubmitting = false; }
        },

        async releaseReserve(reserve) {
            if (!confirm(`¿Liberar la reserva de ${reserve.quantity} uds para "${reserve.dropshipper?.name ?? 'este vendedor'}"? El stock volverá a estar disponible.`)) return;
            try {
                const res = await fetch(`/warehouses/${reserve.warehouse_id}/reserves/${reserve.id}/release`, {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() }
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.showAlmToast(data.message, 'success');
                    await this.loadReserves();
                    await this.loadProducts();
                } else {
                    this.showAlmToast(data.message || 'Error al liberar reserva.', 'error');
                }
            } catch { this.showAlmToast('Error de comunicación.', 'error'); }
        },

        // =================================================================
        // TRASPASO (reutiliza endpoint de InventoryController)
        // =================================================================
        async submitTraspaso() {
            if (this.traspasoForm.from_warehouse_id === this.traspasoForm.to_warehouse_id) {
                this.showAlmToast('La bodega origen y destino no pueden ser la misma.', 'error');
                return;
            }
            this.traspasoSubmitting = true;
            try {
                const res = await fetch('/inventory/transfer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify(this.traspasoForm)
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.showAlmToast(data.message || 'Traspaso ejecutado exitosamente.', 'success');
                    this.traspasoForm = { from_warehouse_id: '', to_warehouse_id: '', product_id: '', quantity: 1, notes: '' };
                    await this.loadWarehouses();
                } else {
                    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error en el traspaso.');
                    this.showAlmToast(msg, 'error');
                }
            } catch { this.showAlmToast('Error de comunicación.', 'error'); }
            finally { this.traspasoSubmitting = false; }
        },
    }
}
</script>
@endsection



