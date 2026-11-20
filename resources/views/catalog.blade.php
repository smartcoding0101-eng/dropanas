@extends('layouts.app')

@section('title', 'Megabodega Mayorista | Catálogo de Productos Bolivia')

@section('content')
<div class="space-y-8" x-data="{
    searchQuery: '',
    warehouseFilter: 'all',
    selectedSort: 'featured',
    copiedSku: null,
    matchProduct(el) {
        const q = this.searchQuery.toLowerCase().trim();
        const name = (el.dataset.name || '').toLowerCase();
        const sku  = (el.dataset.sku || '').toLowerCase();
        const cat  = (el.dataset.category || '').toLowerCase();
        const whs  = (el.dataset.warehouses || '').toLowerCase();

        const matchesText = !q || name.includes(q) || sku.includes(q) || cat.includes(q);
        const matchesWh   = this.warehouseFilter === 'all' || whs.includes(this.warehouseFilter);
        return matchesText && matchesWh;
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

    <!-- Barra de Filtros Interactiva en Tiempo Real (Stripe & Linear Executive Overlap) -->
    <div class="bg-white rounded-2xl p-4 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 border border-slate-200/90 shadow-sm !-mt-14 relative z-10">
        <div class="relative flex-grow max-w-md">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" x-model="searchQuery" placeholder="Filtrar al instante por nombre, SKU o categoría..."
                aria-label="Filtrar productos del catálogo"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-8 py-2.5 text-xs text-[#0F172A] placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#4F46E5] focus:ring-2 focus:ring-[#4F46E5]/15 transition">
            <button type="button" x-show="searchQuery.length > 0" x-cloak @click="searchQuery = ''"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                title="Limpiar búsqueda">
                <i class="fa-solid fa-circle-xmark text-xs"></i>
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="text-slate-500 font-semibold mr-1"><i class="fa-solid fa-warehouse text-[#4F46E5] mr-1"></i>Bodega:</span>
            <button type="button" @click="warehouseFilter = 'all'"
                :class="warehouseFilter === 'all' ? 'bg-[#4F46E5] text-white border-[#4F46E5] shadow-xs font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 font-medium'"
                class="px-3 py-1.5 rounded-lg border cursor-pointer transition">
                Todas ({{ $products->count() }})
            </button>
            <button type="button" @click="warehouseFilter = 'santa cruz'"
                :class="warehouseFilter === 'santa cruz' ? 'bg-[#4F46E5] text-white border-[#4F46E5] shadow-xs font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 font-medium'"
                class="px-3 py-1.5 rounded-lg border cursor-pointer transition">
                Santa Cruz
            </button>
            <button type="button" @click="warehouseFilter = 'el alto'"
                :class="warehouseFilter === 'el alto' ? 'bg-[#4F46E5] text-white border-[#4F46E5] shadow-xs font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 font-medium'"
                class="px-3 py-1.5 rounded-lg border cursor-pointer transition">
                La Paz / El Alto
            </button>
            <button type="button" @click="warehouseFilter = 'cochabamba'"
                :class="warehouseFilter === 'cochabamba' ? 'bg-[#4F46E5] text-white border-[#4F46E5] shadow-xs font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 font-medium'"
                class="px-3 py-1.5 rounded-lg border cursor-pointer transition">
                Cochabamba
            </button>
        </div>
    </div>

    <!-- Grid de Productos (#FFFFFF Surface) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($products as $product)
            @php
                $whCities = $product->warehouseInventories->map(fn($inv) => strtolower(($inv->warehouse->city ?? '') . ' ' . ($inv->warehouse->department ?? '')))->implode(' ');
                $netMargin = $product->suggested_price_bob - $product->cost_price_bob;
                $roiPercent = $product->cost_price_bob > 0 ? round(($netMargin / $product->cost_price_bob) * 100) : 0;
            @endphp
            <div x-show="matchProduct($el)"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 data-name="{{ $product->name }}"
                 data-sku="{{ $product->sku }}"
                 data-category="{{ $product->category->name ?? 'Tecnología' }}"
                 data-warehouses="{{ $whCities }}"
                 class="interactive-card bg-[#FFFFFF] rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden flex flex-col hover:border-[#4F46E5] group">
                
                <!-- Showcase Visual del Producto -->
                <div class="relative bg-slate-50 h-60 flex items-center justify-center p-4 border-b border-slate-200 overflow-hidden">
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
                    <button type="button" @click="copySku('{{ $product->sku }}')"
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
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#4F46E5] font-heading">
                                {{ $product->category->name ?? 'Tecnología' }}
                            </span>
                            <h3 class="text-lg font-bold text-[#0F172A] font-heading mt-0.5 group-hover:text-[#4F46E5] transition line-clamp-1">
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

                    <!-- Existencias en Bodegas -->
                    <div class="space-y-1.5 text-xs">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 font-heading">
                            Stock por Bodega:
                        </div>
                        @foreach($product->warehouseInventories as $inventory)
                            <div class="flex justify-between items-center text-slate-700 bg-white px-2.5 py-1.5 rounded-lg border border-slate-200">
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
    </div>

</div>
@endsection



