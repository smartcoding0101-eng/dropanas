@extends('layouts.app')

@section('title', 'Megabodega Mayorista | Catálogo de Productos Bolivia')

@section('content')
@php
    $modalProductsJson = $catalogModalProducts ?? collect([]);
    $catalogProductsJson = $modalProductsJson->map(function($p) {
        return [
            'id' => (string) $p['id'],
            'name' => strtolower($p['name'] ?? ''),
            'sku' => strtolower($p['sku'] ?? ''),
            'internal_code' => strtolower($p['internal_code'] ?? ''),
            'category_id' => (string) ($p['category_id'] ?? ''),
            'category_name' => strtolower($p['category_name'] ?? 'tecnología'),
            'warehouses_str' => $p['warehouses_str'] ?? '',
            'stock_by_wh' => $p['stock_by_wh'] ?? [],
        ];
    })->values();
@endphp

<div class="space-y-8" x-data="{
    searchQuery: '',
    warehouseFilter: 'all',
    categoryFilter: 'all',
    copiedSku: null,
    copiedModalSku: false,
    catalogItems: {{ Js::from($catalogProductsJson) }},
    productsDetailMap: {{ Js::from($modalProductsJson->keyBy('id')) }},
    
    // Modal state
    modalOpen: false,
    selectedProduct: null,
    activeImageIdx: 0,
    selectedWarehouseId: '',

    openProductModal(id) {
        const prod = this.productsDetailMap[String(id)];
        if (!prod) return;
        this.selectedProduct = prod;
        this.activeImageIdx = 0;
        this.copiedModalSku = false;
        // Preseleccionar bodega con mayor stock disponible
        if (prod.stock_by_wh && prod.stock_by_wh.length > 0) {
            this.selectedWarehouseId = prod.stock_by_wh[0].warehouse_id;
        } else {
            this.selectedWarehouseId = '';
        }
        this.modalOpen = true;
    },

    closeProductModal() {
        this.modalOpen = false;
        this.selectedProduct = null;
    },

    getCheckoutUrl() {
        if (!this.selectedProduct) return '{{ url("/checkout-demo") }}';
        let url = '{{ url("/checkout-demo") }}?product_id=' + encodeURIComponent(this.selectedProduct.id);
        if (this.selectedWarehouseId) {
            url += '&warehouse_id=' + encodeURIComponent(this.selectedWarehouseId);
        }
        return url;
    },

    isProductVisible(id) {
        const item = this.catalogItems.find(i => i.id === String(id));
        if (!item) return true;

        const q = this.searchQuery.toLowerCase().trim();
        const matchesText = !q || item.name.includes(q) || item.sku.includes(q) || item.internal_code.includes(q) || item.category_name.includes(q);
        const matchesCat  = this.categoryFilter === 'all' || item.category_id === String(this.categoryFilter);
        const matchesWh   = this.warehouseFilter === 'all' || item.warehouses_str.includes(this.warehouseFilter);

        return matchesText && matchesCat && matchesWh;
    },

    visibleCount() {
        return this.catalogItems.filter(i => this.isProductVisible(i.id)).length;
    },

    countByWarehouse(whKey) {
        if (whKey === 'all') return this.catalogItems.length;
        return this.catalogItems.filter(i => i.warehouses_str.includes(whKey)).length;
    },

    resetFilters() {
        this.searchQuery = '';
        this.warehouseFilter = 'all';
        this.categoryFilter = 'all';
    },

    copySku(sku) {
        navigator.clipboard.writeText(sku);
        this.copiedSku = sku;
        setTimeout(() => { if (this.copiedSku === sku) this.copiedSku = null; }, 1800);
    },

    copyModalSkuText(sku) {
        navigator.clipboard.writeText(sku);
        this.copiedModalSku = true;
        setTimeout(() => { this.copiedModalSku = false; }, 2000);
    }
}" @keydown.escape.window="closeProductModal()">

    <x-page-header
        title="Catálogo Mayorista de Productos COD"
        subtitle="Productos verificados con inventario físico en Santa Cruz, La Paz y Cochabamba para despachar hoy con recaudación contra entrega."
        icon="fa-solid fa-boxes-stacked"
        badge="Megabodega Central Sincronizada">
        <x-slot:actions>
            <a href="{{ url('/checkout-demo') }}" class="btn-scarlet px-5 py-2.5 rounded-xl font-bold text-xs shadow-md flex items-center gap-2 transition">
                <i class="fa-solid fa-bolt"></i> Crear Pedido COD
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Barra de Filtros Interactiva en Tiempo Real (Búsqueda + Categoría + Bodega) -->
    <div class="bg-white rounded-2xl p-4 space-y-3 border border-slate-200/90 shadow-sm !-mt-14 relative z-10">
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <!-- Buscador Instantáneo + Selector de Categoría -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-grow max-w-2xl">
                <div class="relative flex-grow">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" x-model="searchQuery" placeholder="Buscar por producto, SKU o categoría..."
                        aria-label="Filtrar productos del catálogo"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-8 py-2 text-xs text-[#0F172A] placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#4F46E5] focus:ring-2 focus:ring-[#4F46E5]/15 transition">
                    <button type="button" x-show="searchQuery.length > 0" x-cloak @click="searchQuery = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                        title="Limpiar búsqueda">
                        <i class="fa-solid fa-circle-xmark text-xs"></i>
                    </button>
                </div>

                @if(isset($categories) && $categories->isNotEmpty())
                <div class="sm:w-56 shrink-0">
                    <select x-model="categoryFilter" aria-label="Filtrar por categoría"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-[#0F172A] focus:bg-white focus:outline-none focus:border-[#4F46E5] transition cursor-pointer">
                        <option value="all">Todas las Categorías ({{ $categories->count() }})</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <!-- Botones de Filtro por Bodega Validados con Conteo Real -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <span class="text-slate-500 font-semibold mr-1"><i class="fa-solid fa-warehouse text-[#4F46E5] mr-1"></i>Filtrar por Bodega:</span>
                <button type="button" @click="warehouseFilter = 'all'"
                    :class="warehouseFilter === 'all' ? 'bg-[#4F46E5] text-white border-[#4F46E5] shadow-xs font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 font-medium'"
                    class="px-3 py-1.5 rounded-lg border cursor-pointer transition flex items-center gap-1.5">
                    <span>Todas</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                          :class="warehouseFilter === 'all' ? 'bg-white/20 text-white' : 'bg-white text-slate-600'"
                          x-text="countByWarehouse('all')"></span>
                </button>
                <button type="button" @click="warehouseFilter = 'santa cruz'"
                    :class="warehouseFilter === 'santa cruz' ? 'bg-[#4F46E5] text-white border-[#4F46E5] shadow-xs font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 font-medium'"
                    class="px-3 py-1.5 rounded-lg border cursor-pointer transition flex items-center gap-1.5">
                    <span>Santa Cruz</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                          :class="warehouseFilter === 'santa cruz' ? 'bg-white/20 text-white' : 'bg-white text-slate-600'"
                          x-text="countByWarehouse('santa cruz')"></span>
                </button>
                <button type="button" @click="warehouseFilter = 'el alto'"
                    :class="warehouseFilter === 'el alto' ? 'bg-[#4F46E5] text-white border-[#4F46E5] shadow-xs font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 font-medium'"
                    class="px-3 py-1.5 rounded-lg border cursor-pointer transition flex items-center gap-1.5">
                    <span>El Alto / La Paz</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                          :class="warehouseFilter === 'el alto' ? 'bg-white/20 text-white' : 'bg-white text-slate-600'"
                          x-text="countByWarehouse('el alto')"></span>
                </button>
                <button type="button" @click="warehouseFilter = 'cochabamba'"
                    :class="warehouseFilter === 'cochabamba' ? 'bg-[#4F46E5] text-white border-[#4F46E5] shadow-xs font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 font-medium'"
                    class="px-3 py-1.5 rounded-lg border cursor-pointer transition flex items-center gap-1.5">
                    <span>Cochabamba</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                          :class="warehouseFilter === 'cochabamba' ? 'bg-white/20 text-white' : 'bg-white text-slate-600'"
                          x-text="countByWarehouse('cochabamba')"></span>
                </button>
            </div>
        </div>

        <!-- Barra de Estado de Filtros Activos -->
        <div x-show="searchQuery !== '' || warehouseFilter !== 'all' || categoryFilter !== 'all'" x-cloak
             class="pt-2.5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2 text-slate-600">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-50 text-[#4F46E5] font-bold text-[11px] border border-indigo-200/80">
                    <i class="fa-solid fa-filter text-[10px]"></i>
                    <span x-text="visibleCount() + ' producto(s) encontrado(s)'"></span>
                </span>
                <span x-show="warehouseFilter !== 'all'" class="text-[11px] text-slate-500">
                    en bodega <strong class="text-[#0F172A] uppercase" x-text="warehouseFilter"></strong>
                </span>
            </div>
            <button type="button" @click="resetFilters()"
                class="text-[11px] font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 cursor-pointer">
                <i class="fa-solid fa-rotate-left text-[10px]"></i> Restablecer filtros
            </button>
        </div>
    </div>

    <!-- Grid de Productos (#FFFFFF Surface) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($products as $product)
            @php
                $netMargin = $product->suggested_price_bob - $product->cost_price_bob;
                $roiPercent = $product->cost_price_bob > 0 ? round(($netMargin / $product->cost_price_bob) * 100) : 0;
            @endphp
            <div x-show="isProductVisible('{{ $product->id }}')"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="interactive-card bg-[#FFFFFF] rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden flex flex-col hover:border-[#4F46E5] group">
                
                <!-- Showcase Visual del Producto con Quick-View Trigger -->
                <div class="relative bg-slate-50 h-60 flex items-center justify-center p-4 border-b border-slate-200 overflow-hidden cursor-pointer group/img"
                     @click="openProductModal('{{ $product->id }}')">
                    @if(!empty($product->images) && count($product->images) > 0)
                        <img src="{{ $product->images[0] }}" alt="{{ $product->name }}" loading="lazy" class="w-full h-full object-contain p-2 group-hover/img:scale-105 transition duration-300">
                    @else
                        <div class="w-24 h-24 rounded-2xl bg-indigo-50 border border-indigo-200/80 text-[#4F46E5] flex items-center justify-center text-4xl shadow-inner group-hover/img:scale-105 transition duration-300">
                            @if(str_contains($product->slug, 'audifono'))
                                <i class="fa-solid fa-headphones"></i>
                            @elseif(str_contains($product->slug, 'smartwatch'))
                                <i class="fa-solid fa-stopwatch"></i>
                            @elseif(str_contains($product->slug, 'depiladora') || str_contains($product->slug, 'cepillo'))
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            @elseif(str_contains($product->slug, 'licuadora'))
                                <i class="fa-solid fa-blender"></i>
                            @elseif(str_contains($product->slug, 'camara') || str_contains($product->slug, 'foco'))
                                <i class="fa-solid fa-video"></i>
                            @else
                                <i class="fa-solid fa-box-open"></i>
                            @endif
                        </div>
                    @endif

                    <!-- Overlay sutil "Ver Detalles" al pasar el cursor -->
                    <div class="absolute inset-0 bg-slate-900/20 backdrop-blur-[1px] opacity-0 group-hover/img:opacity-100 transition duration-200 flex items-center justify-center">
                        <span class="px-3.5 py-1.5 rounded-xl bg-white/95 text-[#0F172A] font-bold text-xs shadow-md border border-slate-200 inline-flex items-center gap-1.5 transform translate-y-2 group-hover/img:translate-y-0 transition duration-200">
                            <i class="fa-solid fa-eye text-[#4F46E5]"></i>
                            <span>Ver Detalle Completo</span>
                        </span>
                    </div>
                    
                    <span class="absolute top-3.5 left-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider z-10">
                        <i class="fa-solid fa-truck-fast mr-1"></i> Contra Entrega
                    </span>
                    <button type="button" @click.stop="copySku('{{ $product->sku }}')"
                        title="Clic para copiar SKU"
                        class="absolute top-3.5 right-3.5 bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 text-[#0F172A] font-mono font-bold text-[11px] px-2.5 py-0.5 rounded-lg shadow-2xs transition cursor-pointer flex items-center gap-1 z-10">
                        <span x-text="copiedSku === '{{ $product->sku }}' ? '¡Copiado!' : 'SKU: {{ $product->sku }}'">SKU: {{ $product->sku }}</span>
                        <i class="fa-regular text-[10px] text-[#4F46E5]" :class="copiedSku === '{{ $product->sku }}' ? 'fa-circle-check text-emerald-600' : 'fa-copy'"></i>
                    </button>
                </div>

                <!-- Detalles del Producto -->
                <div class="p-6 flex-grow space-y-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#4F46E5] font-heading">
                                    {{ $product->category->name ?? 'Tecnología' }}
                                </span>
                                @if($product->internal_code)
                                    <span class="text-[9px] font-mono font-bold bg-slate-100 text-slate-500 px-1.5 py-0.2 rounded border border-slate-200" title="Código Interno Bolidrop">
                                        {{ $product->internal_code }}
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-[#0F172A] font-heading mt-0.5 group-hover:text-[#4F46E5] transition line-clamp-1 cursor-pointer"
                                @click="openProductModal('{{ $product->id }}')">
                                {{ $product->name }}
                            </h3>
                        </div>
                                {{ $product->name }}
                            </h3>
                        </div>
                        <span class="shrink-0 px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 font-mono font-extrabold text-[10px]" title="Retorno estimado sobre costo mayorista">
                            +{{ $roiPercent }}% ROI
                        </span>
                    </div>

                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                        {{ $product->description }}
                    </p>

                    <!-- Precios & Desglose en BOB -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-2 text-xs">
                        <div class="flex justify-between items-center text-slate-500">
                            <span>Costo Mayorista:</span>
                            <span class="font-bold text-[#0F172A] font-mono">{{ number_format($product->cost_price_bob, 2) }} BOB</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-500">
                            <span>Precio Mínimo de Venta:</span>
                            <span class="font-bold text-slate-700 font-mono">{{ number_format($product->min_price_bob, 2) }} BOB</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-slate-200 text-[#4F46E5] font-bold">
                            <span>Precio Sugerido Tienda:</span>
                            <span class="text-base font-extrabold font-mono text-[#0F172A]">{{ number_format($product->suggested_price_bob, 2) }} BOB</span>
                        </div>
                    </div>

                    <!-- Existencias en Bodegas (Se resalta la bodega filtrada activamente) -->
                    <div class="space-y-1.5 text-xs">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 font-heading">
                            Stock por Bodega:
                        </div>
                        @foreach($product->warehouseInventories as $inventory)
                            @php
                                $invWhKey = strtolower(($inventory->warehouse->city ?? '') . ' ' . ($inventory->warehouse->department ?? ''));
                            @endphp
                            <div x-show="warehouseFilter === 'all' || '{{ $invWhKey }}'.includes(warehouseFilter)"
                                 :class="warehouseFilter !== 'all' && '{{ $invWhKey }}'.includes(warehouseFilter) ? 'bg-indigo-50/80 border-[#4F46E5]/40 ring-1 ring-[#4F46E5]/20' : 'bg-white border-slate-200'"
                                 class="flex justify-between items-center text-slate-700 px-2.5 py-1.5 rounded-lg border transition">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span class="text-xs font-medium">{{ $inventory->warehouse->city }} ({{ $inventory->warehouse->department }})</span>
                                </span>
                                <span class="font-bold font-mono text-[#0F172A] text-xs">{{ $inventory->stock_available }} uds</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Footer de Acción Directa con Deep-Link product_id -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-2">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-bold">Ganancia Estimada</div>
                        <div class="text-sm font-extrabold font-mono text-emerald-600">
                            +{{ number_format($netMargin, 2) }} BOB
                        </div>
                    </div>

                    <a href="{{ url('/checkout-demo?product_id=' . $product->id) }}"
                       class="btn-scarlet px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-cart-arrow-down"></i>
                        <span>Vender Producto</span>
                    </a>
                </div>

            </div>
        @empty
            <div class="col-span-3 text-center py-16 text-slate-400 bg-white rounded-2xl border border-slate-200">
                <i class="fa-solid fa-box-open text-4xl mb-3 text-slate-400"></i>
                <p>No hay productos disponibles en el catálogo en este momento.</p>
            </div>
        @endforelse

        <!-- Estado Vacío cuando el filtro de búsqueda/bodega/categoría no tiene coincidencias -->
        <div x-show="visibleCount() === 0" x-cloak
             class="col-span-1 sm:col-span-2 lg:col-span-3 text-center py-14 px-6 bg-white rounded-2xl border border-slate-200/90 shadow-xs space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-[#4F46E5] border border-indigo-200/80 flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-extrabold text-[#0F172A] font-heading">No encontramos productos con ese filtro</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    No hay productos con stock disponible que coincidan con la bodega, categoría o texto seleccionado.
                </p>
            </div>
            <button type="button" @click="resetFilters()"
                class="px-4 py-2 rounded-xl bg-[#4F46E5] hover:bg-[#312E81] text-white font-bold text-xs shadow-xs transition cursor-pointer inline-flex items-center gap-1.5">
                <i class="fa-solid fa-rotate-left text-[10px]"></i>
                <span>Ver todos los productos</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL DE DETALLE COMPLETO DEL PRODUCTO (Stripe/Linear Executive Quick-View) -->
    <!-- ========================================================================= -->
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true" aria-labelledby="modal-product-title">
        
        <!-- Backdrop translúcido con desenfoque de fondo -->
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
             @click="closeProductModal()"></div>

        <!-- Contenedor centrado para pantalla completa en móviles y caja amplia en desktop -->
        <div class="flex min-h-full items-center justify-center p-3 sm:p-6 text-center">
            
            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all w-full max-w-4xl border border-slate-200/90 flex flex-col max-h-[90vh]">

                <!-- Barra Superior Ejecutiva del Modal con Badges & Botón Cerrar -->
                <div class="px-5 sm:px-6 py-4 bg-slate-50/90 border-b border-slate-200 flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2 flex-wrap min-w-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-[10px] uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <i class="fa-solid fa-truck-fast text-[9px]"></i>
                            <span>Despacho COD Inmediato</span>
                        </span>
                        <template x-if="selectedProduct?.internal_code">
                            <span class="font-mono font-bold text-[10px] bg-slate-200/80 text-slate-700 px-2 py-0.5 rounded border border-slate-300"
                                  x-text="selectedProduct.internal_code"></span>
                        </template>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider font-heading"
                              x-text="selectedProduct?.category_name"></span>
                    </div>

                    <button type="button" @click="closeProductModal()"
                        class="w-8 h-8 rounded-full bg-white hover:bg-slate-200 text-slate-400 hover:text-slate-700 border border-slate-200/80 flex items-center justify-center transition cursor-pointer shrink-0"
                        title="Cerrar ventana (Esc)">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Cuerpo con scroll interno dividido en 2 columnas (Visual & Datos Comerciales) -->
                <div class="p-5 sm:p-7 overflow-y-auto overflow-x-hidden flex-grow space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                        
                        <!-- COLUMNA IZQUIERDA: Galería Visual y Fotos (5 cols) -->
                        <div class="lg:col-span-5 space-y-3.5">
                            <!-- Visualizador de Imagen Principal -->
                            <div class="relative bg-slate-50 rounded-2xl border border-slate-200/90 p-4 h-72 sm:h-80 flex items-center justify-center overflow-hidden shadow-inner group">
                                <template x-if="selectedProduct && selectedProduct.images && selectedProduct.images.length > 0">
                                    <img :src="selectedProduct.images[activeImageIdx] || selectedProduct.images[0]"
                                         :alt="selectedProduct.name"
                                         class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105">
                                </template>
                                <template x-if="!selectedProduct || !selectedProduct.images || selectedProduct.images.length === 0">
                                    <div class="w-24 h-24 rounded-2xl bg-indigo-50 border border-indigo-200 text-[#4F46E5] flex items-center justify-center text-5xl shadow-inner">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>
                                </template>
                            </div>

                            <!-- Selector de Miniaturas (si hay más de 1 imagen) -->
                            <template x-if="selectedProduct && selectedProduct.images && selectedProduct.images.length > 1">
                                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                    <template x-for="(img, idx) in selectedProduct.images" :key="idx">
                                        <button type="button" @click="activeImageIdx = idx"
                                            class="w-14 h-14 rounded-xl border-2 p-1 bg-slate-50 shrink-0 transition overflow-hidden cursor-pointer"
                                            :class="activeImageIdx === idx ? 'border-[#4F46E5] ring-2 ring-[#4F46E5]/20 scale-105' : 'border-slate-200 hover:border-slate-300 opacity-70 hover:opacity-100'">
                                            <img :src="img" class="w-full h-full object-contain">
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <!-- Proveedor Mayorista & Garantía de Origen -->
                            <div class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-2">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-shield-halved text-[#4F46E5]"></i>
                                    <span>Proveedor Mayorista Verificado</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-extrabold text-[#0F172A]" x-text="selectedProduct?.provider_name"></span>
                                    <span class="text-slate-500 font-medium" x-text="selectedProduct?.provider_city"></span>
                                </div>
                                <template x-if="selectedProduct?.provider_phone">
                                    <div class="text-[11px] text-slate-500 flex items-center gap-1 pt-1 border-t border-indigo-100/60 font-mono">
                                        <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                                        <span x-text="selectedProduct.provider_phone"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Ficha Técnica, Finanzas, Bodegas y Venta (7 cols) -->
                        <div class="lg:col-span-7 space-y-5">
                            
                            <!-- Título, SKU con Copiado y Peso -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="text-xs font-mono font-bold bg-slate-100 text-[#0F172A] px-2.5 py-1 rounded-lg border border-slate-200">
                                            SKU: <strong x-text="selectedProduct?.sku"></strong>
                                        </span>
                                        <button type="button" @click="copyModalSkuText(selectedProduct?.sku)"
                                            class="px-2 py-1 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-xs font-medium text-slate-600 hover:text-[#4F46E5] transition cursor-pointer flex items-center gap-1">
                                            <i class="fa-regular" :class="copiedModalSku ? 'fa-circle-check text-emerald-600' : 'fa-copy'"></i>
                                            <span x-text="copiedModalSku ? '¡Copiado!' : 'Copiar'"></span>
                                        </button>
                                    </div>
                                    <div class="text-xs text-slate-500 font-medium">
                                        <i class="fa-solid fa-scale-balanced text-slate-400 mr-1"></i>Peso:
                                        <strong class="text-[#0F172A] font-mono" x-text="(selectedProduct?.weight_kg || 0.5) + ' kg'"></strong>
                                    </div>
                                </div>

                                <h2 id="modal-product-title"
                                    class="text-xl sm:text-2xl font-extrabold text-[#0F172A] font-heading leading-tight"
                                    x-text="selectedProduct?.name"></h2>
                                
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed"
                                   x-text="selectedProduct?.description"></p>
                            </div>

                            <!-- Matriz Financiera & Ganancia del Vendedor (Stripe Surface) -->
                            <div class="rounded-2xl bg-gradient-to-br from-slate-50 via-indigo-50/30 to-emerald-50/30 p-4 border border-slate-200 space-y-3">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
                                    <span>Estructura de Precios Oficiales (BOB)</span>
                                    <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-mono font-extrabold text-[10px]"
                                          x-text="'+' + (selectedProduct?.roi_percent || 0) + '% ROI'"></span>
                                </div>

                                <div class="grid grid-cols-3 gap-2.5 text-center">
                                    <div class="bg-white rounded-xl p-2.5 border border-slate-200/80 shadow-2xs">
                                        <div class="text-[10px] text-slate-400 font-semibold uppercase">Costo Proveedor</div>
                                        <div class="text-xs sm:text-sm font-extrabold font-mono text-[#0F172A] mt-0.5"
                                             x-text="(Number(selectedProduct?.cost_price_bob || 0)).toFixed(2) + ' BOB'"></div>
                                    </div>
                                    <div class="bg-white rounded-xl p-2.5 border border-slate-200/80 shadow-2xs">
                                        <div class="text-[10px] text-slate-400 font-semibold uppercase">Mínimo Venta</div>
                                        <div class="text-xs sm:text-sm font-extrabold font-mono text-slate-700 mt-0.5"
                                             x-text="(Number(selectedProduct?.min_price_bob || 0)).toFixed(2) + ' BOB'"></div>
                                    </div>
                                    <div class="bg-white rounded-xl p-2.5 border border-indigo-200 shadow-2xs">
                                        <div class="text-[10px] text-[#4F46E5] font-semibold uppercase">Sugerido Tienda</div>
                                        <div class="text-xs sm:text-sm font-extrabold font-mono text-[#4F46E5] mt-0.5"
                                             x-text="(Number(selectedProduct?.suggested_price_bob || 0)).toFixed(2) + ' BOB'"></div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-slate-200/80 text-xs">
                                    <span class="font-semibold text-slate-600 flex items-center gap-1.5">
                                        <i class="fa-solid fa-coins text-amber-500"></i>
                                        <span>Tu Ganancia Neta Estimada:</span>
                                    </span>
                                    <span class="text-sm font-extrabold font-mono text-emerald-600"
                                          x-text="'+' + (Number(selectedProduct?.margin_bob || 0)).toFixed(2) + ' BOB'"></span>
                                </div>
                            </div>

                            <!-- Selector de Bodega para Despacho Directo -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
                                    <span><i class="fa-solid fa-warehouse text-[#4F46E5] mr-1"></i>Seleccionar Bodega de Origen:</span>
                                    <span class="font-mono text-[11px] text-slate-500 font-normal">
                                        Total Disponible: <strong class="text-[#0F172A] font-bold" x-text="(selectedProduct?.total_stock || 0) + ' uds'"></strong>
                                    </span>
                                </label>

                                <div class="space-y-1.5">
                                    <template x-for="wh in (selectedProduct?.stock_by_wh || [])" :key="wh.warehouse_id">
                                        <label class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition"
                                               :class="selectedWarehouseId === wh.warehouse_id ? 'border-[#4F46E5] bg-indigo-50/70 ring-2 ring-[#4F46E5]/20' : 'border-slate-200 hover:bg-slate-50 bg-white'">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <input type="radio" name="modal_warehouse_select" :value="wh.warehouse_id"
                                                       x-model="selectedWarehouseId"
                                                       class="w-4 h-4 text-[#4F46E5] focus:ring-[#4F46E5] border-slate-300">
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-[#0F172A] flex items-center gap-1.5 truncate">
                                                        <span x-text="wh.name"></span>
                                                        <span class="text-[10px] text-slate-400 font-mono" x-text="'(' + wh.code + ')'"></span>
                                                    </div>
                                                    <div class="text-[10px] text-slate-500 truncate" x-text="wh.city + ', ' + wh.department + ' • ' + (wh.address || '')"></div>
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0 pl-2">
                                                <div class="text-xs font-mono font-bold"
                                                     :class="wh.stock_available > 0 ? 'text-emerald-600' : 'text-slate-400'"
                                                     x-text="wh.stock_available + ' uds'"></div>
                                                <div class="text-[9px] text-slate-400 uppercase font-semibold">disponibles</div>
                                            </div>
                                        </label>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Footer del Modal: Acciones de Conversión -->
                <div class="px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                    <div class="text-xs text-slate-500 text-center sm:text-left">
                        Al pulsar vender, se precargará el producto y la bodega elegida en el generador de pedidos COD.
                    </div>

                    <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                        <button type="button" @click="closeProductModal()"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer">
                            Cerrar
                        </button>
                        <a :href="getCheckoutUrl()"
                           class="btn-scarlet px-5 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                            <i class="fa-solid fa-cart-arrow-down"></i>
                            <span>Vender Producto / Crear Pedido COD</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection



