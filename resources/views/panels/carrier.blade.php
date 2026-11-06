@extends('layouts.app')

@section('title', 'Panel DEX Bolivia Express | Ruta & Novedades COD')

@section('content')
<div class="space-y-6" x-data="carrierPanelApp()">

    <!-- Toast Notification -->
    <div x-show="toast.show" x-cloak
        class="fixed bottom-6 right-6 z-50 px-5 py-3.5 rounded-2xl shadow-xl text-sm font-semibold flex items-center gap-3 transition"
        :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-[#960016] text-white'">
        <i class="fa-solid" :class="toast.type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- Header Panel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-[#E88A38] uppercase tracking-wider">Módulo de Última Milla & Call Center</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#960016]">DEX Bolivia Express — Ruta COD</h1>
            <p class="text-slate-500 text-xs sm:text-sm">
                Gestión de paquetes en calle, confirmación de entregas en efectivo/QR y resolución inmediata de novedades.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="px-3.5 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold flex items-center gap-2">
                <i class="fa-solid fa-motorcycle text-[#E88A38]"></i>
                <span>Agente: {{ $carrierUser->name ?? 'Repartidor Central' }}</span>
            </span>
        </div>
    </div>

    <!-- 4 Métricas de Despacho (Fireworks 60/30/10) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="white-card p-5 rounded-2xl border-l-4 border-[#960016]">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">En Ruta Hoy</div>
            <div class="text-2xl font-black text-[#960016] mt-1">{{ $stats['in_route'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Paquetes para entregar</div>
        </div>

        <div class="white-card p-5 rounded-2xl border-l-4 border-emerald-500">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Entregados</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['delivered_today'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Con recaudación efectiva</div>
        </div>

        <div class="white-card p-5 rounded-2xl border-l-4 border-[#E88A38]">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Novedades Activas</div>
            <div class="text-2xl font-black text-[#E88A38] mt-1">{{ $stats['novedades_active'] }}</div>
            <div class="text-[11px] text-amber-700 font-semibold mt-1">Requieren gestión WhatsApp</div>
        </div>

        <div class="white-card p-5 rounded-2xl border-l-4 border-[#ED2100]">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Por Recaudar COD</div>
            <div class="text-2xl font-black text-[#ED2100] mt-1 font-mono">
                Bs. {{ number_format($stats['cod_to_collect_bob'], 2) }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1 font-mono">Total asignado en calle</div>
        </div>
    </div>

    <!-- Tabla de Envíos en Ruta / Novedades -->
    <div class="white-card rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-[#960016]">Hoja de Ruta y Gestión de Entregas</h2>
                <p class="text-xs text-slate-500">Haz clic en "Entregado" para recaudar o en "Reportar Novedad" si el cliente no responde.</p>
            </div>
            <span class="text-xs font-bold px-3 py-1 bg-slate-100 text-slate-700 rounded-lg">
                {{ $ordersInRoute->count() }} pedidos en gestión
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Guía / Pedido</th>
                        <th class="py-3 px-4">Destinatario</th>
                        <th class="py-3 px-4">Dirección & Depto</th>
                        <th class="py-3 px-4">Total COD</th>
                        <th class="py-3 px-4">Estado</th>
                        <th class="py-3 px-4">Intentos</th>
                        <th class="py-3 px-4 text-center">Acciones en Campo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($ordersInRoute as $order)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-[#960016]">{{ $order->order_number }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $order->tracking_number ?? 'DEX-PENDIENTE' }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800">{{ $order->customer_name }}</div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                    <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                                    <span>{{ $order->customer_phone }}</span>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}?text=Hola%20{{ urlencode($order->customer_name) }},%20te%20escribimos%20de%20DEX%20Bolivia%20Express.%20Tenemos%20tu%20pedido%20{{ $order->order_number }}%20en%20camino.%20¿Estás%20disponible%20para%20recibirlo?"
                                        target="_blank" class="text-emerald-700 hover:underline font-bold text-[10px] ml-1">
                                        Escribir
                                    </a>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-semibold text-slate-800 truncate">{{ $order->shipping_address }}</div>
                                <div class="text-[10px] text-slate-500">{{ $order->shipping_city }}, <strong class="text-[#960016]">{{ $order->shipping_department }}</strong></div>
                                @if($order->shipping_reference)
                                    <div class="text-[10px] text-amber-700 italic truncate">Ref: {{ $order->shipping_reference }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-[#ED2100] text-sm">
                                Bs. {{ number_format($order->total_amount_bob, 2) }}
                            </td>

                            <td class="py-3.5 px-4">
                                @if($order->status === 'failed_delivery')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <span>Novedad: {{ str_replace('_', ' ', $order->novedad_reason ?? 'Pendiente') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        <i class="fa-solid fa-truck-fast"></i>
                                        <span>En Reparto</span>
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ ($order->delivery_attempts ?? 0) > 1 ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $order->delivery_attempts ?? 0 }} / 3
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="markAsDelivered('{{ $order->id }}', '{{ $order->order_number }}')"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm flex items-center gap-1.5 transition">
                                        <i class="fa-solid fa-check"></i>
                                        <span>Entregado</span>
                                    </button>

                                    <button @click="openNovedadModal('{{ $order->id }}', '{{ $order->order_number }}', '{{ $order->customer_name }}', '{{ $order->customer_phone }}')"
                                        class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-sm flex items-center gap-1.5 transition">
                                        <i class="fa-solid fa-headset"></i>
                                        <span>Novedad</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-truck-ramp-box text-3xl mb-2 text-slate-300"></i>
                                <div>No hay pedidos pendientes en la ruta actual.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Registro de Novedades (DEX Call Center) -->
    <div x-show="modalNovedad.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="white-card w-full max-w-lg rounded-2xl p-6 shadow-2xl border border-slate-200" @click.away="modalNovedad.show = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-phone-volume"></i>
                    </span>
                    <div>
                        <h3 class="font-bold text-[#960016] text-base">Registrar Novedad de Entrega</h3>
                        <p class="text-xs text-slate-500" x-text="'Pedido: ' + modalNovedad.orderNumber"></p>
                    </div>
                </div>
                <button @click="modalNovedad.show = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="submitNovedad()" class="space-y-4 pt-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Motivo de la Novedad:</label>
                    <select x-model="modalNovedad.reason" required
                        class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs focus:bg-white focus:outline-none focus:border-[#E88A38]">
                        <option value="cliente_ausente">Cliente ausente en domicilio</option>
                        <option value="telefono_apagado">Teléfono apagado / Sin señal</option>
                        <option value="direccion_incompleta">Dirección o referencia incompleta</option>
                        <option value="rechazo_sin_dinero">Cliente solicita reprogramar (Sin efectivo hoy)</option>
                        <option value="rechazo_definitivo">Cliente canceló / No reconoce el pedido</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Reprogramar Fecha de Entrega (opcional):</label>
                    <input type="date" x-model="modalNovedad.rescheduledDate"
                        class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Observaciones / Notas del Courier:</label>
                    <textarea x-model="modalNovedad.notes" rows="3" placeholder="Ej: Se tocó el timbre, atendió familiar indicando que vuelve a las 18:00..."
                        class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs focus:bg-white focus:outline-none focus:border-[#E88A38]"></textarea>
                </div>

                <!-- Botón WhatsApp Rápido -->
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-800 flex items-center justify-between">
                    <div>
                        <div class="font-bold">Contactar al Cliente de Inmediato</div>
                        <div class="text-[11px] text-emerald-700" x-text="modalNovedad.customerName + ' (' + modalNovedad.customerPhone + ')'"></div>
                    </div>
                    <a :href="'https://wa.me/' + modalNovedad.customerPhone.replace(/[^0-9]/g, '') + '?text=Hola%20' + encodeURIComponent(modalNovedad.customerName) + ',%20el%20repartidor%20de%20DEX%20Bolivia%20estuvo%20en%20tu%20dirección.%20¿Cuándo%20podemos%20reprogramar%20tu%20entrega?'"
                        target="_blank" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm hover:bg-emerald-700">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </a>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="modalNovedad.show = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="modalNovedad.submitting"
                        class="btn-scarlet px-5 py-2 rounded-xl font-bold text-xs shadow-md">
                        <span x-show="!modalNovedad.submitting">Guardar Novedad</span>
                        <span x-show="modalNovedad.submitting">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function carrierPanelApp() {
    return {
        toast: { show: false, message: '', type: 'success' },
        modalNovedad: {
            show: false,
            orderId: '',
            orderNumber: '',
            customerName: '',
            customerPhone: '',
            reason: 'cliente_ausente',
            rescheduledDate: '',
            notes: '',
            submitting: false
        },

        openNovedadModal(id, number, name, phone) {
            this.modalNovedad.orderId = id;
            this.modalNovedad.orderNumber = number;
            this.modalNovedad.customerName = name;
            this.modalNovedad.customerPhone = phone;
            this.modalNovedad.reason = 'cliente_ausente';
            this.modalNovedad.notes = '';
            this.modalNovedad.rescheduledDate = '';
            this.modalNovedad.show = true;
        },

        async markAsDelivered(orderId, orderNumber) {
            if (!confirm(`¿Confirmas que el pedido ${orderNumber} fue entregado y se recaudó el monto COD?`)) return;

            try {
                const response = await fetch(`/api/v1/orders/${orderId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ status: 'delivered' })
                });

                const data = await response.json();
                if (response.ok && data.status === 'success') {
                    this.showToast(`¡Pedido ${orderNumber} entregado y liquidado con éxito!`, 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(data.message || 'Error al actualizar pedido.', 'error');
                }
            } catch (err) {
                this.showToast('Error de comunicación con el servidor.', 'error');
            }
        },

        async submitNovedad() {
            this.modalNovedad.submitting = true;
            try {
                const response = await fetch(`/api/v1/orders/${this.modalNovedad.orderId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        status: 'failed_delivery',
                        novedad_reason: this.modalNovedad.reason,
                        novedad_notes: this.modalNovedad.notes,
                        rescheduled_delivery_at: this.modalNovedad.rescheduledDate ? this.modalNovedad.rescheduledDate + ' 10:00:00' : null
                    })
                });

                const data = await response.json();
                if (response.ok && data.status === 'success') {
                    this.showToast(`Novedad registrada para el pedido ${this.modalNovedad.orderNumber}.`, 'success');
                    this.modalNovedad.show = false;
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(data.message || 'Error al registrar novedad.', 'error');
                }
            } catch (err) {
                this.showToast('Error de comunicación con el servidor.', 'error');
            } finally {
                this.modalNovedad.submitting = false;
            }
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 4000);
        }
    }
}
</script>
@endsection
