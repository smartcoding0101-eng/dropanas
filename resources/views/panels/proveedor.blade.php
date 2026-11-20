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
                <div class="flex items-center gap-2">
                    <button @click="transferModalOpen = true" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs flex items-center gap-1.5 transition">
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
                        <div class="text-xs text-slate-400 italic py-2">Sin inventario registrado</div>
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
                        <th class="py-3 px-5">Producto / SKU</th>
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
                                    <div class="text-[11px] text-[#0061f2] font-mono font-semibold">SKU: {{ $product->sku }}</div>
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

</div>

<script>
function proveedorDashboard() {
    return {
        productModalOpen: false,
        categoryModalOpen: false,
        transferModalOpen: false,
        alertModalOpen: false,
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

        async getAuthToken() {
            const authRes = await fetch('/api/v1/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ email: 'proveedor@bolidrop.bo', password: 'password123' })
            });
            const authData = await authRes.json();
            return authData.data?.token;
        },

        async submitAlert() {
            this.alertSubmitting = true;
            try {
                const token = await this.getAuthToken();
                const response = await fetch('/api/v1/inventory/alerts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(this.alertForm)
                });
                const result = await response.json();
                if (response.ok && result.status === 'success') {
                    this.showToast(result.message || 'Alerta de inventario configurada correctamente.', 'success');
                    this.alertModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    this.showToast(result.message || 'Error al configurar la alerta.', 'error');
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
                const token = await this.getAuthToken();
                const response = await fetch('/api/v1/inventory/transfer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(this.transferForm)
                });
                const result = await response.json();
                if (response.ok && result.status === 'success') {
                    this.showToast(result.message || 'Transferencia realizada con éxito.', 'success');
                    this.transferModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    this.showToast(result.message || 'Error al realizar la transferencia.', 'error');
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
                // Obtener token con el usuario proveedor
                const authRes = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: 'proveedor@bolidrop.bo', password: 'password123' })
                });
                const authData = await authRes.json();
                const token = authData.data?.token;

                // Crear FormData para soportar carga de imagen física
                const formData = new FormData();
                for (const key in this.form) {
                    if (this.form[key] !== null && this.form[key] !== undefined && this.form[key] !== '') {
                        formData.append(key, this.form[key]);
                    }
                }
                if (this.imageFile) {
                    formData.append('image', this.imageFile);
                }

                const response = await fetch('/api/v1/products', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.showToast(result.message || 'Producto publicado con éxito.', 'success');
                    this.productModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    this.showToast(result.message || 'Error al guardar el producto.', 'error');
                }
            } catch (err) {
                this.showToast('Error de comunicación con el servidor.', 'error');
            } finally {
                this.submitting = false;
            }
        }
    }
}
</script>
@endsection



