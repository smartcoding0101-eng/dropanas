@extends('layouts.app')

@section('title', 'Kardex Global de Inventario y Trazabilidad | Bolidrop Admin')

@section('content')
<div class="space-y-6 pb-12" x-data="{ adjustModal: false }">

    <x-page-header
        title="Kardex Global y Trazabilidad de Inventario"
        subtitle="Auditoría histórica inmutable de entradas, salidas, reservas, devoluciones y transferencias en todas las bodegas de Bolivia."
        icon="fa-solid fa-clipboard-list"
        badge="Exclusivo Administrador del Sistema">
        <x-slot:actions>
            <a href="{{ route('admin.kardex.export', request()->query()) }}"
               class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-file-csv text-emerald-300 text-sm"></i>
                <span>Exportar CSV</span>
            </a>
            <button @click="adjustModal = true"
                    class="px-4 py-2.5 rounded-xl bg-[#00ac69] hover:bg-[#008f57] text-white text-xs font-bold shadow-md flex items-center gap-2 transition">
                <i class="fa-solid fa-sliders"></i>
                <span>Ajuste de Auditoría</span>
            </button>
        </x-slot:actions>
    </x-page-header>

    <!-- Alertas Flash -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-emerald-800 text-xs font-semibold shadow-xs">
        <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 rounded-2xl flex items-center gap-3 text-red-800 text-xs font-semibold shadow-xs">
        <i class="fa-solid fa-triangle-exclamation text-red-600 text-base"></i>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    <!-- 1. Tarjetas de Métricas Globales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 !-mt-14 relative z-10">
        <!-- Stock Físico -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span>Stock Físico Total</span>
                <i class="fa-solid fa-boxes-stacked text-[#212832]"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-[#212832] font-mono">
                {{ number_format($metrics['total_physical']) }} <span class="text-xs font-normal text-slate-400">uds</span>
            </div>
            <div class="text-[11px] text-slate-500 flex justify-between border-t border-slate-100 pt-2 font-medium">
                <span>Disp: <strong class="text-emerald-700">{{ number_format($metrics['total_available']) }}</strong></span>
                <span>Res: <strong class="text-amber-700">{{ number_format($metrics['total_reserved']) }}</strong></span>
            </div>
        </div>

        <!-- Valorización Total -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span>Valorización (Costo Fijo)</span>
                <i class="fa-solid fa-vault text-[#0061f2]"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 font-mono">
                Bs. {{ number_format($metrics['total_valuation'], 2) }}
            </div>
            <div class="text-[11px] text-slate-400 border-t border-slate-100 pt-2">
                Capital inmovilizado en inventario
            </div>
        </div>

        <!-- Flujo Mes (Entradas vs Salidas) -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span>Movimientos del Mes</span>
                <i class="fa-solid fa-arrow-right-arrow-left text-sky-600"></i>
            </div>
            <div class="flex items-center gap-3 text-lg sm:text-xl font-bold font-mono">
                <span class="text-emerald-600">+{{ number_format($metrics['inbound_month']) }}</span>
                <span class="text-slate-300">/</span>
                <span class="text-red-600">-{{ number_format($metrics['outbound_month']) }}</span>
            </div>
            <div class="text-[11px] text-slate-500 border-t border-slate-100 pt-2 flex justify-between">
                <span>Entradas vs Salidas COD</span>
            </div>
        </div>

        <!-- Auditoría / Mermas -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span>Mermas / Dañados</span>
                <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-rose-700 font-mono">
                {{ number_format($metrics['damaged_count']) }}
            </div>
            <div class="text-[11px] text-slate-400 border-t border-slate-100 pt-2">
                Total transacciones: <strong>{{ number_format($metrics['total_transactions']) }}</strong>
            </div>
        </div>
    </div>

    <!-- 2. Filtros de Búsqueda y Navegación -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.kardex.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
            <!-- Bodega -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Bodega / Almacén</label>
                <select name="warehouse_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-slate-50 focus:bg-white outline-none">
                    <option value="">— Todas las Bodegas —</option>
                    @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                        {{ $wh->name }} ({{ $wh->city }})
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Producto -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Producto</label>
                <select name="product_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-slate-50 focus:bg-white outline-none">
                    <option value="">— Todos los Productos —</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} [{{ $p->sku }}]
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Tipo de Movimiento -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Tipo de Movimiento</label>
                <select name="movement_type" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-slate-50 focus:bg-white outline-none">
                    <option value="">— Todos los Flujos —</option>
                    <option value="opening_balance" {{ request('movement_type') == 'opening_balance' ? 'selected' : '' }}>Saldo de Apertura</option>
                    <option value="purchase_receipt" {{ request('movement_type') == 'purchase_receipt' ? 'selected' : '' }}>Ingreso por Compra (PO)</option>
                    <option value="sale_reservation" {{ request('movement_type') == 'sale_reservation' ? 'selected' : '' }}>Reserva por Venta COD</option>
                    <option value="sale_dispatch" {{ request('movement_type') == 'sale_dispatch' ? 'selected' : '' }}>Salida Pedido Entregado</option>
                    <option value="order_cancellation" {{ request('movement_type') == 'order_cancellation' ? 'selected' : '' }}>Cancelación de Pedido</option>
                    <option value="order_annulment" {{ request('movement_type') == 'order_annulment' ? 'selected' : '' }}>Anulación por Administrador</option>
                    <option value="return_restock" {{ request('movement_type') == 'return_restock' ? 'selected' : '' }}>Devolución Apta (Reingreso)</option>
                    <option value="return_damaged" {{ request('movement_type') == 'return_damaged' ? 'selected' : '' }}>Devolución Dañada (Merma)</option>
                    <option value="transfer_out" {{ request('movement_type') == 'transfer_out' ? 'selected' : '' }}>Transferencia (Salida Origen)</option>
                    <option value="transfer_in" {{ request('movement_type') == 'transfer_in' ? 'selected' : '' }}>Transferencia (Entrada Destino)</option>
                    <option value="audit_adjustment_in" {{ request('movement_type') == 'audit_adjustment_in' ? 'selected' : '' }}>Ajuste Positivo Auditoría</option>
                    <option value="audit_adjustment_out" {{ request('movement_type') == 'audit_adjustment_out' ? 'selected' : '' }}>Ajuste Negativo Auditoría</option>
                </select>
            </div>

            <!-- Rango Desde -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Desde Fecha</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-slate-50 focus:bg-white outline-none">
            </div>

            <!-- Rango Hasta -->
            <div>
                <label class="block font-bold text-slate-600 mb-1">Hasta Fecha</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-slate-50 focus:bg-white outline-none">
            </div>

            <input type="hidden" name="per_page" value="{{ $perPage ?? 30 }}">

            <!-- Botones -->
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-scarlet px-4 py-2 rounded-xl text-white font-bold flex-1 shadow-xs">
                    <i class="fa-solid fa-filter mr-1"></i> Filtrar
                </button>
                <a href="{{ route('admin.kardex.index') }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold" title="Limpiar Filtros">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- 3. Tabla Principal del Kardex Global -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-[#212832] text-sm flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-indigo-600"></i>
                    <span>Movimientos Históricos Inmutables ({{ number_format($records->total()) }} registros)</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Trazabilidad física y financiera en tiempo real para todas las megabodegas.</p>
            </div>
            <div class="flex items-center gap-3 self-end sm:self-auto">
                <!-- Selector de Registros por Página -->
                <div class="flex items-center gap-2 text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 shadow-2xs">
                    <i class="fa-solid fa-table-list text-slate-400"></i>
                    <label for="per_page_select" class="text-slate-600 font-bold text-[11px] whitespace-nowrap">Mostrar:</label>
                    <select id="per_page_select"
                            onchange="const u = new URL(window.location.href); u.searchParams.set('per_page', this.value); u.searchParams.delete('page'); window.location.href = u.toString();"
                            class="bg-transparent font-extrabold text-indigo-700 text-xs outline-none cursor-pointer">
                        <option value="15" {{ ($perPage ?? 30) == 15 ? 'selected' : '' }}>15 por pág.</option>
                        <option value="30" {{ ($perPage ?? 30) == 30 ? 'selected' : '' }}>30 por pág. (Predeterminado)</option>
                        <option value="50" {{ ($perPage ?? 30) == 50 ? 'selected' : '' }}>50 por pág.</option>
                        <option value="100" {{ ($perPage ?? 30) == 100 ? 'selected' : '' }}>100 por pág.</option>
                    </select>
                </div>
                <div class="text-xs text-slate-400 font-mono hidden md:block">
                    Orden cronológico
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4">Fecha / Hora</th>
                        <th class="py-3.5 px-4">Bodega</th>
                        <th class="py-3.5 px-4">Producto & SKU</th>
                        <th class="py-3.5 px-4">Flujo / Tipo</th>
                        <th class="py-3.5 px-4">Documento Ref.</th>
                        <th class="py-3.5 px-4 text-center">Variación</th>
                        <th class="py-3.5 px-4 text-right">Costo Unit.</th>
                        <th class="py-3.5 px-4 text-right">Total Costo</th>
                        <th class="py-3.5 px-4 text-center">Saldo Físico</th>
                        <th class="py-3.5 px-4 text-center">Saldo Disp.</th>
                        <th class="py-3.5 px-4">Operador / Glosa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($records as $row)
                    <tr class="hover:bg-slate-50/80 transition">
                        <!-- Fecha -->
                        <td class="py-3 px-4 font-mono text-[11px] whitespace-nowrap text-slate-500">
                            {{ $row->created_at->format('d/m/Y H:i') }}
                        </td>

                        <!-- Bodega -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-semibold text-slate-900 block">{{ $row->warehouse->name ?? 'Bodega Central' }}</span>
                            <span class="text-[10px] text-slate-400">{{ $row->warehouse->city ?? 'Bolivia' }}</span>
                        </td>

                        <!-- Producto -->
                        <td class="py-3 px-4 max-w-xs truncate">
                            <span class="font-bold text-slate-800 block truncate" title="{{ $row->product->name ?? '' }}">
                                {{ $row->product->name ?? 'Producto' }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ $row->product->sku ?? '' }}</span>
                        </td>

                        <!-- Tipo de Movimiento con Badges Específicos -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            @php
                                $badgeClass = match($row->movement_type) {
                                    'opening_balance'    => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'purchase_receipt'   => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'sale_reservation'   => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'sale_dispatch'      => 'bg-red-50 text-red-800 border-red-200',
                                    'order_cancellation' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                    'order_annulment'    => 'bg-purple-50 text-purple-800 border-purple-200',
                                    'return_restock'     => 'bg-teal-50 text-teal-800 border-teal-200',
                                    'return_damaged'     => 'bg-rose-100 text-rose-800 border-rose-300 font-bold',
                                    'transfer_in'        => 'bg-sky-50 text-sky-800 border-sky-200',
                                    'transfer_out'       => 'bg-orange-50 text-orange-800 border-orange-200',
                                    'audit_adjustment_in'=> 'bg-emerald-100 text-emerald-900 border-emerald-300',
                                    'audit_adjustment_out'=> 'bg-red-100 text-red-900 border-red-300',
                                    default              => 'bg-slate-100 text-slate-800 border-slate-200',
                                };
                                $label = match($row->movement_type) {
                                    'opening_balance'    => 'Saldo Inicial',
                                    'purchase_receipt'   => 'Ingreso Compra',
                                    'sale_reservation'   => 'Reserva Venta',
                                    'sale_dispatch'      => 'Salida Entregado',
                                    'order_cancellation' => 'Cancelación',
                                    'order_annulment'    => 'Anulación',
                                    'return_restock'     => 'Devolución Apta',
                                    'return_damaged'     => 'Devolución Dañada',
                                    'transfer_in'        => 'Transfer. Entrada',
                                    'transfer_out'       => 'Transfer. Salida',
                                    'audit_adjustment_in'=> 'Ajuste Sobrante',
                                    'audit_adjustment_out'=> 'Ajuste Merma',
                                    default              => $row->movement_type,
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $badgeClass }}">
                                {{ $label }}
                            </span>
                            @if($row->condition === 'damaged')
                                <span class="ml-1 inline-block w-2 h-2 rounded-full bg-rose-500" title="Condición: Dañado"></span>
                            @endif
                        </td>

                        <!-- Documento Ref -->
                        <td class="py-3 px-4 font-mono text-[11px] whitespace-nowrap text-slate-600">
                            {{ $row->document_number ?? 'S/D' }}
                        </td>

                        <!-- Variación -->
                        <td class="py-3 px-4 text-center font-mono font-bold whitespace-nowrap">
                            @if($row->quantity > 0)
                                <span class="text-emerald-600">+{{ $row->quantity }}</span>
                            @elseif($row->quantity < 0)
                                <span class="text-red-600">{{ $row->quantity }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>

                        <!-- Costo Unit -->
                        <td class="py-3 px-4 text-right font-mono text-slate-500 whitespace-nowrap">
                            Bs. {{ number_format($row->unit_cost_bob, 2) }}
                        </td>

                        <!-- Costo Total -->
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                            Bs. {{ number_format($row->total_cost_bob, 2) }}
                        </td>

                        <!-- Saldo Físico (Antes -> Después) -->
                        <td class="py-3 px-4 text-center font-mono text-[11px] whitespace-nowrap">
                            <span class="text-slate-400">{{ $row->stock_physical_before }}</span>
                            <i class="fa-solid fa-arrow-right text-[9px] text-slate-300 mx-1"></i>
                            <strong class="text-slate-900">{{ $row->stock_physical_after }}</strong>
                        </td>

                        <!-- Saldo Disponible (Antes -> Después) -->
                        <td class="py-3 px-4 text-center font-mono text-[11px] whitespace-nowrap">
                            <span class="text-slate-400">{{ $row->stock_available_before }}</span>
                            <i class="fa-solid fa-arrow-right text-[9px] text-slate-300 mx-1"></i>
                            <strong class="text-emerald-700">{{ $row->stock_available_after }}</strong>
                        </td>

                        <!-- Usuario & Notas -->
                        <td class="py-3 px-4 max-w-xs">
                            <div class="text-[11px] font-semibold text-slate-800 truncate" title="{{ $row->notes }}">
                                {{ $row->notes ?? 'Sin observaciones adicionales' }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                {{ $row->user ? $row->user->name : 'Sistema Automático' }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-clipboard-list text-3xl mb-2 text-slate-300 block"></i>
                            No se encontraron registros de Kardex con los filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginador Estilo Carrusel con Botones « Inicio y Final » -->
        <div class="p-4 sm:px-6 border-t border-slate-100 bg-slate-50/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
            
            <!-- Resumen de registros -->
            <div class="text-xs text-slate-500 font-medium flex items-center gap-2">
                <i class="fa-solid fa-database text-slate-400 text-xs"></i>
                <span>
                    Mostrando <strong class="text-slate-800 font-mono font-bold">{{ $records->firstItem() ?? 0 }}</strong>
                    a <strong class="text-slate-800 font-mono font-bold">{{ $records->lastItem() ?? 0 }}</strong>
                    de <strong class="text-slate-900 font-mono font-bold">{{ number_format($records->total()) }}</strong> movimientos
                </span>
                <span class="text-slate-300 hidden sm:inline">|</span>
                <span class="text-[11px] text-slate-400 font-mono hidden sm:inline">
                    Página <strong>{{ $records->currentPage() }}</strong> de <strong>{{ $records->lastPage() }}</strong>
                </span>
            </div>

            <!-- Controles del Carrusel de Paginación -->
            <nav aria-label="Navegación de Kardex" class="flex items-center justify-center sm:justify-end gap-1 flex-wrap">
                
                <!-- Botón: INICIO (Primera Página) -->
                @if($records->onFirstPage())
                    <span class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed flex items-center gap-1.5 select-none opacity-60"
                          title="Ya estás en la primera página">
                        <i class="fa-solid fa-angles-left text-[10px]"></i>
                        <span>Inicio</span>
                    </span>
                @else
                    <a href="{{ $records->url(1) }}"
                       class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-700 hover:text-indigo-700 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                       title="Ir a la primera página">
                        <i class="fa-solid fa-angles-left text-[10px]"></i>
                        <span>Inicio</span>
                    </a>
                @endif

                <!-- Botón: ANTERIOR -->
                @if($records->onFirstPage())
                    <span class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed flex items-center gap-1 select-none opacity-60">
                        <i class="fa-solid fa-angle-left text-xs"></i>
                        <span class="hidden sm:inline">Anterior</span>
                    </span>
                @else
                    <a href="{{ $records->previousPageUrl() }}"
                       class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-700 hover:text-indigo-700 text-xs font-bold transition flex items-center gap-1 shadow-2xs">
                        <i class="fa-solid fa-angle-left text-xs"></i>
                        <span class="hidden sm:inline">Anterior</span>
                    </a>
                @endif

                <!-- Carrusel de Números de Página (Ventana Dinámica Deslizante) -->
                @php
                    $cur = $records->currentPage();
                    $last = max(1, $records->lastPage());
                    $start = max(1, $cur - 2);
                    $end = min($last, $cur + 2);

                    if ($end - $start < 4) {
                        if ($start === 1) {
                            $end = min($last, $start + 4);
                        } elseif ($end === $last) {
                            $start = max(1, $end - 4);
                        }
                    }
                @endphp

                @if($start > 1)
                    <a href="{{ $records->url(1) }}"
                       class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-700 hover:text-indigo-700 font-bold text-xs flex items-center justify-center transition shadow-2xs">
                        1
                    </a>
                    @if($start > 2)
                        <span class="px-1 text-slate-400 font-bold">...</span>
                    @endif
                @endif

                @for($p = $start; $p <= $end; $p++)
                    @if($p == $cur)
                        <span class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white font-extrabold text-xs flex items-center justify-center shadow-xs border border-indigo-700">
                            {{ $p }}
                        </span>
                    @else
                        <a href="{{ $records->url($p) }}"
                           class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-700 hover:text-indigo-700 font-bold text-xs flex items-center justify-center transition shadow-2xs">
                            {{ $p }}
                        </a>
                    @endif
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                        <span class="px-1 text-slate-400 font-bold">...</span>
                    @endif
                    <a href="{{ $records->url($last) }}"
                       class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-700 hover:text-indigo-700 font-bold text-xs flex items-center justify-center transition shadow-2xs">
                        {{ $last }}
                    </a>
                @endif

                <!-- Botón: SIGUIENTE -->
                @if($records->hasMorePages())
                    <a href="{{ $records->nextPageUrl() }}"
                       class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-700 hover:text-indigo-700 text-xs font-bold transition flex items-center gap-1 shadow-2xs">
                        <span class="hidden sm:inline">Siguiente</span>
                        <i class="fa-solid fa-angle-right text-xs"></i>
                    </a>
                @else
                    <span class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed flex items-center gap-1 select-none opacity-60">
                        <span class="hidden sm:inline">Siguiente</span>
                        <i class="fa-solid fa-angle-right text-xs"></i>
                    </span>
                @endif

                <!-- Botón: FINAL (Última Página) -->
                @if($cur >= $last)
                    <span class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed flex items-center gap-1.5 select-none opacity-60"
                          title="Ya estás en la última página">
                        <span>Final</span>
                        <i class="fa-solid fa-angles-right text-[10px]"></i>
                    </span>
                @else
                    <a href="{{ $records->url($last) }}"
                       class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-700 hover:text-indigo-700 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                       title="Ir a la última página">
                        <span>Final</span>
                        <i class="fa-solid fa-angles-right text-[10px]"></i>
                    </a>
                @endif

            </nav>
        </div>
    </div>

    <!-- 4. Modal para Ajuste Manual de Auditoría (Exclusivo Admin) -->
    <div x-show="adjustModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div @click.away="adjustModal = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-5 animate-scale-in">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2 text-[#212832] font-bold text-base font-heading">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Ajuste Manual de Inventario (Auditoría)</span>
                </div>
                <button @click="adjustModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.kardex.adjust') }}" class="space-y-4 text-xs">
                @csrf

                <!-- Bodega -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Bodega a Ajustar *</label>
                    <select name="warehouse_id" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-slate-50 focus:bg-white outline-none">
                        <option value="">— Seleccionar Bodega —</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->city }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Producto -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Producto a Ajustar *</label>
                    <select name="product_id" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-slate-50 focus:bg-white outline-none">
                        <option value="">— Seleccionar Producto —</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} [SKU: {{ $p->sku }}]</option>
                        @endforeach
                    </select>
                </div>

                <!-- Cantidad Variación -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Variación Cantidad *</label>
                        <input type="number" name="quantity_delta" required step="1" placeholder="Ej: 5 o -3"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-slate-50 focus:bg-white outline-none font-mono">
                        <span class="text-[10px] text-slate-400">Positivo (+) suma stock, Negativo (-) resta</span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Condición *</label>
                        <select name="condition" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-slate-50 focus:bg-white outline-none">
                            <option value="optimal">Apto para Reventa</option>
                            <option value="damaged">Dañado / Defectuoso</option>
                        </select>
                    </div>
                </div>

                <!-- Motivo / Glosa -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Motivo / Glosa de Auditoría *</label>
                    <textarea name="reason" rows="3" required placeholder="Ej: Conteo físico mensual arroja sobrante de 2 unidades en pasillo B..."
                              class="w-full border border-slate-200 rounded-xl p-3 bg-slate-50 focus:bg-white outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" @click="adjustModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-scarlet px-5 py-2.5 rounded-xl text-white font-bold shadow-xs">
                        Guardar Ajuste en Kardex
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection




