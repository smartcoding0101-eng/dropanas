@extends('layouts.app')

@section('title', 'Megabodega Mayorista | Catálogo de Productos Bolivia')

@section('content')
<div class="space-y-8 bg-[#FFFFFF]">

    <!-- Header del Catálogo -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-slate-200 pb-6">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#960016] bg-red-50 border border-red-200 px-2.5 py-0.5 rounded-full">
                    Megabodega Central
                </span>
                <span class="text-xs text-slate-500 flex items-center gap-1.5 font-medium">
                    <i class="fa-solid fa-location-dot text-[#ED2100]"></i> Stock físico en Santa Cruz & El Alto
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#960016] font-heading">
                Catálogo Mayorista de Productos COD
            </h1>
            <p class="text-slate-500 text-sm mt-1 max-w-2xl">
                Productos verificados con inventario físico para promocionar y despachar con recaudación contra entrega.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ url('/checkout-demo') }}" class="btn-scarlet px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-bolt text-amber-200"></i> Simular Pedido COD
            </a>
        </div>
    </div>

    <!-- Barra de Filtros (#FFFFFF con Borde Sutil) -->
    <div class="bg-[#FFFFFF] rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4 border border-slate-200 shadow-sm">
        <div class="relative w-full sm:w-96">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" placeholder="Buscar por producto, SKU o categoría..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#E88A38] focus:ring-1 focus:ring-[#E88A38] transition">
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto text-xs text-slate-500">
            <span class="hidden sm:inline font-medium">Filtrar por Bodega:</span>
            <span class="px-3 py-1.5 rounded-lg bg-[#960016] text-white border border-[#960016] cursor-pointer font-bold shadow-sm">Todas</span>
            <span class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 cursor-pointer transition font-medium">Santa Cruz</span>
            <span class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 cursor-pointer transition font-medium">El Alto</span>
        </div>
    </div>

    <!-- Grid de Productos (#FFFFFF Surface) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($products as $product)
            <div class="interactive-card bg-[#FFFFFF] rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col hover:border-amber-400 group">
                
                <!-- Showcase Visual del Producto -->
                <div class="relative bg-slate-50 h-60 flex items-center justify-center p-6 border-b border-slate-200">
                    <div class="w-24 h-24 rounded-2xl bg-red-50 border border-red-200 text-[#960016] flex items-center justify-center text-4xl shadow-inner group-hover:scale-105 transition duration-300">
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
                    
                    <span class="absolute top-3.5 left-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                        <i class="fa-solid fa-truck-fast mr-1"></i> Contra Entrega
                    </span>
                    <span class="absolute top-3.5 right-3.5 bg-white border border-slate-200 text-[#960016] font-mono font-bold text-[11px] px-2 py-0.5 rounded shadow-sm">
                        SKU: {{ $product->sku }}
                    </span>
                </div>

                <!-- Detalles del Producto -->
                <div class="p-6 flex-grow space-y-4">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#E88A38] font-heading">
                            {{ $product->category->name ?? 'Tecnología' }}
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 font-heading mt-1 group-hover:text-[#960016] transition line-clamp-1">
                            {{ $product->name }}
                        </h3>
                    </div>

                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                        {{ $product->description }}
                    </p>

                    <!-- Precios & Desglose en BOB -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-2 text-xs">
                        <div class="flex justify-between items-center text-slate-500">
                            <span>Costo Mayorista:</span>
                            <span class="font-bold text-slate-900 font-mono">{{ number_format($product->cost_price_bob, 2) }} BOB</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-500">
                            <span>Precio Mínimo de Venta:</span>
                            <span class="font-bold text-slate-700 font-mono">{{ number_format($product->min_price_bob, 2) }} BOB</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-slate-200 text-[#E88A38] font-bold">
                            <span>Precio Sugerido Tienda:</span>
                            <span class="text-base font-extrabold font-mono text-[#960016]">{{ number_format($product->suggested_price_bob, 2) }} BOB</span>
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
                                <span class="font-bold font-mono text-[#960016] text-xs">{{ $inventory->stock_available }} uds</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Footer de Acción con botón Scarlet #ED2100 -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-bold">Ganancia Estimada</div>
                        <div class="text-sm font-extrabold font-mono text-emerald-600">
                            +{{ number_format($product->suggested_price_bob - $product->cost_price_bob, 2) }} BOB
                        </div>
                    </div>

                    <a href="{{ url('/checkout-demo') }}" class="btn-scarlet px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-cart-arrow-down"></i> Vender Producto
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
