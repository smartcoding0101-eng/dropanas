@extends('layouts.app')

@section('title', 'Panel Fulfillment | Dropana Bolivia – Tu Tienda Online')

@section('content')
<div class="space-y-6 pb-10" x-data="fulfillmentPanel()">

    <!-- Header -->
    <div class="bg-gradient-to-r from-[#960016] to-[#ED2100] rounded-2xl p-6 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center text-2xl">
                <i class="fa-solid fa-store"></i>
            </div>
            <div>
                <h1 class="text-xl font-black font-heading">Panel de Fulfillment</h1>
                <p class="text-white/70 text-sm">Tú vendes — Dropana almacena, empaca y entrega</p>
            </div>
        </div>
        <div class="flex items-center gap-2 bg-white/10 border border-white/20 rounded-xl px-4 py-2 text-sm">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="font-semibold">Almacenaje $0 activo</span>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            ['label'=>'Pedidos Este Mes','value'=>'142','icon'=>'fa-boxes-stacked','color'=>'red','sub'=>'↑ 23% vs mes anterior'],
            ['label'=>'Entregados','value'=>'121','icon'=>'fa-circle-check','color'=>'emerald','sub'=>'85.2% tasa de entrega'],
            ['label'=>'En Tránsito','value'=>'18','icon'=>'fa-truck-fast','color'=>'amber','sub'=>'3 ciudades activas'],
            ['label'=>'Stock Disponible','value'=>'1,240','icon'=>'fa-cubes','color'=>'blue','sub'=>'Unidades en bodega'],
        ] as $kpi)
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm interactive-card">
            <div class="flex items-start justify-between mb-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide leading-tight">{{ $kpi['label'] }}</div>
                <div class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center">
                    <i class="fa-solid {{ $kpi['icon'] }} text-sm text-slate-400"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 font-heading">{{ $kpi['value'] }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">{{ $kpi['sub'] }}</div>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- Enviar un pedido manual -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4 lg:col-span-1">
            <h2 class="font-bold text-slate-800 font-heading flex items-center gap-2">
                <i class="fa-solid fa-plus text-[#ED2100]"></i>
                Enviar Nuevo Pedido
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-bold text-slate-600 block mb-1">Nombre del Cliente</label>
                    <input type="text" placeholder="Ej: María López" x-model="order.customer_name"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600 block mb-1">Teléfono (WhatsApp)</label>
                    <input type="tel" placeholder="+591 7X-XXX-XXXX" x-model="order.customer_phone"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600 block mb-1">Dirección de Entrega</label>
                    <textarea rows="2" placeholder="Calle, barrio, ciudad..." x-model="order.address"
                              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38] resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-bold text-slate-600 block mb-1">Monto COD (Bs.)</label>
                        <input type="number" placeholder="0.00" x-model="order.cod_amount"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38]">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 block mb-1">Departamento</label>
                        <select x-model="order.department"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38]">
                            @foreach(['Santa Cruz','La Paz','El Alto','Cochabamba','Oruro','Potosí','Chuquisaca','Tarija','Beni','Pando'] as $dept)
                            <option>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button @click="createOrder()" :disabled="creating"
                        class="w-full btn-scarlet py-3 rounded-xl text-white text-sm font-bold flex items-center justify-center gap-2 disabled:opacity-60">
                    <span x-show="!creating">Crear Envío COD</span>
                    <span x-show="creating" x-cloak>Procesando...</span>
                    <i class="fa-solid fa-truck-fast text-xs" x-show="!creating"></i>
                </button>
            </div>

            <!-- Tarifa estimada -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm">
                <div class="text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Tarifa de Envío Estimada</div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-600">Destino: <span class="font-semibold text-slate-800" x-text="order.department"></span></span>
                    <span class="font-black text-[#960016]">Bs. <span x-text="shippingRate"></span></span>
                </div>
                <div class="text-[10px] text-slate-400 mt-1">Tarifa plana hasta 3 kg • Almacenaje $0</div>
            </div>
        </div>

        <!-- Historial de pedidos -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-800 font-heading flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-[#E88A38]"></i>
                    Mis Pedidos Recientes
                </h2>
                <a href="{{ url('/tracking') }}" class="text-xs text-[#960016] font-bold hover:underline flex items-center gap-1">
                    Rastrear <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wide">Guía</th>
                            <th class="text-left px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="text-left px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wide">Ciudad</th>
                            <th class="text-left px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wide">Monto</th>
                            <th class="text-left px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wide">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach([
                            ['guide'=>'DRP-2026-0142','name'=>'Carlos Mamani','city'=>'Santa Cruz','amount'=>'Bs. 350','status'=>'delivered'],
                            ['guide'=>'DRP-2026-0141','name'=>'Ana Quispe','city'=>'La Paz','amount'=>'Bs. 280','status'=>'in_transit'],
                            ['guide'=>'DRP-2026-0140','name'=>'Pedro Flores','city'=>'Cochabamba','amount'=>'Bs. 420','status'=>'in_transit'],
                            ['guide'=>'DRP-2026-0139','name'=>'Luisa Condori','city'=>'El Alto','amount'=>'Bs. 190','status'=>'returned'],
                            ['guide'=>'DRP-2026-0138','name'=>'Jorge Salinas','city'=>'Santa Cruz','amount'=>'Bs. 520','status'=>'delivered'],
                        ] as $order)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-mono text-xs text-[#960016] font-bold">{{ $order['guide'] }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800 text-xs">{{ $order['name'] }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $order['city'] }}</td>
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $order['amount'] }}</td>
                            <td class="px-4 py-3">
                                @if($order['status'] === 'delivered')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check text-[8px]"></i> Entregado
                                    </span>
                                @elseif($order['status'] === 'in_transit')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-truck-fast text-[8px]"></i> En Tránsito
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                                        <i class="fa-solid fa-rotate-left text-[8px]"></i> Devuelto
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Inventario en bodega Dropana -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <h2 class="font-bold text-slate-800 font-heading flex items-center gap-2">
            <i class="fa-solid fa-warehouse text-[#E88A38]"></i>
            Mi Inventario en Bodega Dropana
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach([
                ['sku'=>'SKU-001','name'=>'Auriculares Bluetooth Pro','stock'=>245,'bodega'=>'Santa Cruz','low'=>false],
                ['sku'=>'SKU-002','name'=>'Cargador USB-C 65W','stock'=>12,'bodega'=>'El Alto','low'=>true],
                ['sku'=>'SKU-003','name'=>'Funda Protectora iPhone 14','stock'=>380,'bodega'=>'Santa Cruz','low'=>false],
            ] as $item)
            <div class="bg-slate-50 border {{ $item['low'] ? 'border-red-200 bg-red-50' : 'border-slate-200' }} rounded-xl p-4 space-y-2">
                @if($item['low'])
                <div class="flex items-center gap-1.5 text-[10px] font-bold text-red-600">
                    <i class="fa-solid fa-triangle-exclamation"></i> Stock bajo — Reabastece pronto
                </div>
                @endif
                <div class="font-mono text-[10px] text-slate-400">{{ $item['sku'] }}</div>
                <div class="font-bold text-slate-800 text-sm">{{ $item['name'] }}</div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500 text-xs">📍 {{ $item['bodega'] }}</span>
                    <span class="font-black text-lg {{ $item['low'] ? 'text-red-600' : 'text-slate-900' }}">{{ $item['stock'] }}</span>
                </div>
                <div class="text-[10px] text-slate-400">unidades disponibles</div>
            </div>
            @endforeach
        </div>
        <div class="text-center pt-2">
            <a href="{{ url('/contactenos') }}" class="text-xs text-[#960016] font-bold hover:underline">
                ¿Quieres ingresar más stock? Contacta al equipo logístico →
            </a>
        </div>
    </div>

</div>

<script>
function fulfillmentPanel() {
    return {
        creating: false,
        order: {
            customer_name: '',
            customer_phone: '+591 ',
            address: '',
            cod_amount: '',
            department: 'Santa Cruz'
        },
        shippingRates: {
            'Santa Cruz': 55, 'La Paz': 75, 'El Alto': 75, 'Cochabamba': 65,
            'Oruro': 90, 'Potosí': 100, 'Chuquisaca': 100, 'Tarija': 110,
            'Beni': 130, 'Pando': 150
        },
        get shippingRate() {
            return this.shippingRates[this.order.department] || 55;
        },
        async createOrder() {
            if (!this.order.customer_name || !this.order.customer_phone || !this.order.address) {
                alert('Completa los campos obligatorios.');
                return;
            }
            this.creating = true;
            await new Promise(r => setTimeout(r, 1200));
            alert('✅ Pedido COD creado. Guía asignada: DRP-2026-' + Math.floor(Math.random()*9000+1000));
            this.order = { customer_name: '', customer_phone: '+591 ', address: '', cod_amount: '', department: 'Santa Cruz' };
            this.creating = false;
        }
    }
}
</script>
@endsection
