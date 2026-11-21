@extends('layouts.app')

@section('title', 'Megabodega Mayorista | Catálogo de Productos Bolivia')

@section('content')
@php
    $catalogProductsJson = $products->map(function($p) {
        $stockByWh = [];
        $whTags = [];
        foreach ($p->warehouseInventories as $inv) {
            if ((int) $inv->stock_available > 0) {
                $city = strtolower($inv->warehouse->city ?? '');
                $dept = strtolower($inv->warehouse->department ?? '');
                $whTags[] = $city . ' ' . $dept;
                $stockByWh[] = [
                    'warehouse_id' => (string) $inv->warehouse_id,
                    'name' => $inv->warehouse->name ?? 'Bodega',
                    'city' => $inv->warehouse->city ?? '',
                    'department' => $inv->warehouse->department ?? '',
                    'stock' => (int) $inv->stock_available,
                ];
            }
        }
        $cost = (float) $p->cost_price_bob;
        $sug  = (float) $p->suggested_price_bob;
        $min  = (float) $p->min_price_bob;
        $margin = $sug - $cost;
        $roi = $cost > 0 ? round(($margin / $cost) * 100) : 0;

        return [
            'id' => (string) $p->id,
            'name' => $p->name ?? '',
            'name_lower' => strtolower($p->name ?? ''),
            'sku' => $p->sku ?? '',
            'sku_lower' => strtolower($p->sku ?? ''),
            'internal_code' => $p->internal_code ?? '',
            'internal_code_lower' => strtolower($p->internal_code ?? ''),
            'description' => $p->description ?? 'Producto verificado para distribución nacional con garantía y cobro contra entrega (COD).',
            'cost_price_bob' => $cost,
            'suggested_price_bob' => $sug,
            'min_price_bob' => $min,
            'margin_bob' => $margin,
            'roi_percent' => $roi,
            'weight_kg' => (float) ($p->weight_kg ?? 0.5),
            'is_cod_available' => (bool) $p->is_cod_available,
            'images' => !empty($p->images) && is_array($p->images) ? array_values($p->images) : [],
            'category_id' => (string) ($p->category_id ?? ''),
            'category_name' => $p->category->name ?? 'Tecnología',
            'category_name_lower' => strtolower($p->category->name ?? 'tecnología'),
            'provider_name' => $p->provider?->company_or_store_name ?? $p->provider?->name ?? 'Megabodega Bolidrop',
            'provider_city' => $p->provider?->city ?? 'Santa Cruz',
            'provider_phone' => $p->provider?->phone ?? '',
            'warehouses_str' => implode(' | ', $whTags),
            'stock_by_wh' => $stockByWh,
            'total_stock' => array_sum(array_column($stockByWh, 'stock')),
        ];
    })->values();
@endphp

<script>
    window.__bolidropCatalog = {{ Js::from($catalogProductsJson) }};
</script>

<div class="space-y-8" x-data="{
    searchQuery: '',
    warehouseFilter: 'all',
    categoryFilter: 'all',
    copiedSku: null,
    catalogItems: {{ Js::from($catalogProductsJson) }},

    isProductVisible(id) {
        const item = this.catalogItems.find(i => i.id === String(id));
        if (!item) return true;

        const q = this.searchQuery.toLowerCase().trim();
        const matchesText = !q || item.name_lower.includes(q) || item.sku_lower.includes(q) || item.internal_code_lower.includes(q) || item.category_name_lower.includes(q);
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
    }
}">

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
                
                <!-- Showcase Visual del Producto -->
                <div class="relative bg-slate-50 h-60 flex items-center justify-center p-4 border-b border-slate-200 overflow-hidden cursor-pointer"
                     @click="$dispatch('open-product-modal', { id: '{{ $product->id }}' })">
                    @if(!empty($product->images) && count($product->images) > 0)
                        <img src="{{ $product->images[0] }}" alt="{{ $product->name }}" loading="lazy" class="w-full h-full object-contain p-2 group-hover:scale-105 transition duration-300">
                    @else
                        <div class="w-24 h-24 rounded-2xl bg-indigo-50 border border-indigo-200/80 text-[#4F46E5] flex items-center justify-center text-4xl shadow-inner group-hover:scale-105 transition duration-300">
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
                    
                    <span class="absolute top-3.5 left-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                        <i class="fa-solid fa-truck-fast mr-1"></i> Contra Entrega
                    </span>
                    <button type="button" @click.stop="copySku('{{ $product->sku }}')"
                        title="Clic para copiar SKU"
                        class="absolute top-3.5 right-3.5 bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 text-[#0F172A] font-mono font-bold text-[11px] px-2.5 py-0.5 rounded-lg shadow-2xs transition cursor-pointer flex items-center gap-1">
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
                                @click="$dispatch('open-product-modal', { id: '{{ $product->id }}' })">
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

</div>
@endsection



