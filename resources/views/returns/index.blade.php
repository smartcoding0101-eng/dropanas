@extends('layouts.app')

@section('title', 'Gestión de Novedades & Devoluciones COD | Dropana Bolivia')

@section('content')
<div class="space-y-6">

    <!-- Header con Breadcrumb y Badges de Bolivia -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-[#960016] bg-red-50 border border-red-200 px-2.5 py-0.5 rounded-full">
                    Logística Inversa • Reverse Logistics Bolivia
                </span>
                <span class="text-xs text-slate-500 font-medium">Control de Novedades DEX y Reingresos a Bodega</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#960016] font-heading">
                Gestión de Devoluciones & Novedades COD
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">
                Resuelve pedidos no entregados, programa nuevos intentos o reintegra productos al inventario mayorista con deducción de flete.
            </p>
        </div>

        <!-- Filtros Rápidos -->
        <div class="flex items-center gap-2">
            <a href="{{ route('returns.index', ['status' => 'novedades']) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm {{ $status === 'novedades' ? 'bg-[#ED2100] text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Novedades Activas
            </a>
            <a href="{{ route('returns.index', ['status' => 'returned']) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm {{ $status === 'returned' ? 'bg-[#960016] text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                <i class="fa-solid fa-rotate-left mr-1.5"></i> Devueltos a Bodega
            </a>
        </div>
    </div>

    <!-- Alertas Flash -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Tabla de Pedidos en Novedad / Devolución -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                <i class="fa-solid fa-boxes-packing text-[#E88A38]"></i>
                <span>Registros en Logística Inversa ({{ $orders->total() }})</span>
            </div>
        </div>

        @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100/75 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                            <th class="p-4">Pedido / Tracking</th>
                            <th class="p-4">Cliente & Destino</th>
                            <th class="p-4">Monto COD</th>
                            <th class="p-4">Intentos / Motivo</th>
                            <th class="p-4">Estado</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="p-4 font-mono">
                                    <div class="font-bold text-[#960016]">#{{ $order->order_number }}</div>
                                    <div class="text-[11px] text-slate-400">Trk: {{ $order->tracking_number ?? 'S/T' }}</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Carrier: {{ $order->carrier->name ?? 'DEX Express' }}</div>
                                </td>

                                <td class="p-4">
                                    <div class="font-semibold text-slate-800">{{ $order->customer_name }}</div>
                                    <div class="text-slate-500 text-[11px]"><i class="fa-solid fa-phone text-[10px] mr-1 text-[#E88A38]"></i>{{ $order->customer_phone }}</div>
                                    <div class="text-slate-500 text-[11px]"><i class="fa-solid fa-location-dot text-[10px] mr-1 text-slate-400"></i>{{ $order->shipping_department }} - {{ $order->shipping_city }}</div>
                                </td>

                                <td class="p-4 font-mono">
                                    <div class="font-bold text-slate-900">Bs {{ number_format($order->total_amount_bob, 2) }}</div>
                                    <div class="text-[10px] text-slate-500">Flete: Bs {{ number_format($order->shipping_fee_bob, 2) }}</div>
                                </td>

                                <td class="p-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $order->delivery_attempts >= 3 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $order->delivery_attempts }} intento(s)
                                        </span>
                                    </div>
                                    <div class="text-[11px] font-semibold text-slate-700 mt-1">
                                        {{ $reasons[$order->novedad_reason] ?? ($order->novedad_reason ?? 'Sin motivo') }}
                                    </div>
                                    @if($order->novedad_notes)
                                        <div class="text-[10px] text-slate-500 italic mt-0.5 line-clamp-1" title="{{ $order->novedad_notes }}">
                                            "{{ $order->novedad_notes }}"
                                        </div>
                                    @endif
                                </td>

                                <td class="p-4">
                                    @if($order->status === 'returned')
                                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            <i class="fa-solid fa-rotate-left mr-1"></i> Devuelto a Bodega
                                        </span>
                                    @elseif($order->status === 'failed_delivery')
                                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                            <i class="fa-solid fa-circle-exclamation mr-1"></i> Entrega Fallida
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <i class="fa-solid fa-clock-rotate-left mr-1"></i> En Novedad
                                        </span>
                                    @endif
                                </td>

                                <td class="p-4 text-right">
                                    @if($order->status !== 'returned')
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Reprogramar -->
                                            <button type="button" 
                                                    onclick="document.getElementById('modal-reschedule-{{ $order->id }}').classList.remove('hidden')"
                                                    class="px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-[#E88A38] font-bold border border-amber-200 text-[11px] transition">
                                                <i class="fa-solid fa-calendar-days mr-1"></i> Reprogramar
                                            </button>

                                            <!-- Confirmar Retorno -->
                                            <form action="{{ route('returns.process', $order->id) }}" method="POST" onsubmit="return confirm('¿Confirmas el reingreso físico a bodega y cobro de flete al dropshipper?')">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-[#960016] font-bold border border-red-200 text-[11px] transition">
                                                    <i class="fa-solid fa-arrow-right-to-bracket mr-1"></i> Reingresar a Bodega
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Modal de Reprogramación Simple -->
                                        <div id="modal-reschedule-{{ $order->id }}" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                                            <div class="bg-white rounded-2xl max-w-md w-full p-6 text-left space-y-4 shadow-xl border border-slate-200">
                                                <div class="flex items-center justify-between border-b pb-3">
                                                    <h3 class="font-bold text-slate-800 text-sm">Reprogramar Entrega #{{ $order->order_number }}</h3>
                                                    <button type="button" onclick="document.getElementById('modal-reschedule-{{ $order->id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                                <form action="{{ route('returns.reschedule', $order->id) }}" method="POST" class="space-y-3">
                                                    @csrf
                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nueva Fecha y Hora:</label>
                                                        <input type="datetime-local" name="rescheduled_date" required class="w-full text-xs rounded-xl border-slate-200 px-3 py-2 border focus:ring-[#960016]">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Instrucciones o Notas:</label>
                                                        <textarea name="notes" rows="2" placeholder="Ej: Llamar antes de llegar, timbre blanco" class="w-full text-xs rounded-xl border-slate-200 px-3 py-2 border focus:ring-[#960016]"></textarea>
                                                    </div>
                                                    <div class="flex justify-end gap-2 pt-2">
                                                        <button type="button" onclick="document.getElementById('modal-reschedule-{{ $order->id }}').classList.add('hidden')" class="px-3 py-1.5 rounded-xl border text-xs text-slate-600 font-semibold">Cancelar</button>
                                                        <button type="submit" class="px-4 py-1.5 rounded-xl bg-[#ED2100] text-white text-xs font-bold hover:bg-red-700">Guardar Reprogramación</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs text-emerald-600 font-semibold flex items-center justify-end gap-1">
                                            <i class="fa-solid fa-check-double text-[11px]"></i> Stock Restablecido
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $orders->links() }}
            </div>
        @else
            <div class="p-12 text-center text-slate-400 space-y-2">
                <i class="fa-solid fa-clipboard-check text-4xl text-slate-300"></i>
                <p class="text-sm font-semibold text-slate-600">No hay órdenes en este estado de logística inversa.</p>
                <p class="text-xs text-slate-400">Todos los despachos se encuentran en tránsito normal o entregados exitosamente.</p>
            </div>
        @endif
    </div>

</div>
@endsection
