@extends('layouts.app')

@section('title', 'Rastrear Pedido – Bolidrop Bolivia')

@section('content')
<div class="space-y-6 pb-10" x-data="trackingPanel()">

    <x-page-header
        title="Rastrear Pedido COD"
        subtitle="Consulta en tiempo real el estado de tu envío con DEX Bolivia Express sin necesidad de crear una cuenta."
        icon="fa-solid fa-magnifying-glass-location"
        badge="Rastreo Público en Vivo">
    </x-page-header>

    <!-- Formulario búsqueda (SB Admin Pro Overlap) -->
    <div class="max-w-2xl mx-auto space-y-6 !-mt-14 relative z-10">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" x-model="guideNumber"
                       placeholder="Ej: DRP-2026-001234"
                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3.5 text-sm font-mono bg-slate-50 focus:outline-none focus:border-emerald-500 focus:bg-white transition"
                       @keydown.enter="search()">
            </div>
            <button @click="search()" :disabled="loading"
                    class="btn-scarlet px-5 py-3.5 rounded-xl text-white font-bold text-sm flex items-center gap-2 disabled:opacity-60">
                <i class="fa-solid fa-search" x-show="!loading"></i>
                <i class="fa-solid fa-circle-notch fa-spin" x-show="loading" x-cloak></i>
                <span class="hidden sm:inline">Rastrear</span>
            </button>
        </div>
        <div class="text-center">
            <button @click="loadDemo()" class="text-xs text-slate-400 hover:text-[#212832] hover:underline transition">
                Ver ejemplo con guía de demo →
            </button>
        </div>
    </div>

    <!-- Error -->
    <div x-show="error" x-cloak
         class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-center gap-3 text-sm text-red-700">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        <span x-text="error"></span>
    </div>

    <!-- Resultado del tracking -->
    <div x-show="result" x-cloak class="space-y-4">

        <!-- Header del pedido -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Número de Guía</div>
                    <div class="font-mono font-black text-[#212832] text-lg" x-text="result?.guide_number"></div>
                </div>
                <div class="px-3 py-1.5 rounded-full text-xs font-bold border"
                     :class="{
                         'bg-emerald-50 text-emerald-700 border-emerald-200': result?.status === 'delivered',
                         'bg-amber-50 text-amber-700 border-amber-200': result?.status === 'in_transit',
                         'bg-blue-50 text-blue-700 border-blue-200': result?.status === 'processing',
                         'bg-red-50 text-red-700 border-red-200': result?.status === 'returned',
                     }"
                     x-text="statusLabel(result?.status)">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm border-t border-slate-100 pt-4">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Destinatario</div>
                    <div class="font-semibold text-slate-800" x-text="result?.customer_name"></div>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Ciudad de Destino</div>
                    <div class="font-semibold text-slate-800" x-text="result?.shipping_city || result?.city"></div>
                </div>
                <div class="col-span-2">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Dirección de Entrega</div>
                    <div class="font-semibold text-slate-700" x-text="result?.shipping_address || result?.address"></div>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Monto COD</div>
                    <div class="font-black text-[#212832] text-base">Bs. <span x-text="result?.cod_amount"></span></div>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Intentos de Entrega</div>
                    <div class="font-bold text-slate-800">
                        <span x-text="result?.delivery_attempts"></span> / 2
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline de estados -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i class="fa-solid fa-timeline text-[#0061f2]"></i>
                Historial del Pedido
            </h3>
            <div class="space-y-0">
                <template x-for="(event, idx) in result?.events" :key="idx">
                    <div class="flex gap-4">
                        <!-- Línea y punto -->
                        <div class="flex flex-col items-center">
                            <div class="w-3.5 h-3.5 rounded-full shrink-0 mt-0.5 border-2"
                                 :class="idx === 0 ? 'bg-[#0061f2] border-slate-800' : 'bg-white border-slate-300'">
                            </div>
                            <div class="w-0.5 flex-1 mt-1" :class="idx < result.events.length - 1 ? 'bg-slate-200' : 'bg-transparent'"></div>
                        </div>
                        <!-- Contenido -->
                        <div class="pb-5 flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="font-bold text-slate-800 text-sm" x-text="event.label"></div>
                                    <div class="text-xs text-slate-400" x-text="event.description"></div>
                                </div>
                                <div class="text-[10px] text-slate-400 shrink-0 text-right" x-text="event.date"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- CTA ayuda -->
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
            <div class="flex-1">
                <div class="font-bold text-slate-800 text-sm">¿Tienes algún problema con tu pedido?</div>
                <div class="text-slate-400 text-xs mt-0.5">Contáctanos por WhatsApp con tu número de guía</div>
            </div>
            <a href="https://wa.me/59170000000?text=Hola%20Bolidrop%2C%20tengo%20una%20consulta%20sobre%20la%20guía%20" target="_blank"
               class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition whitespace-nowrap">
                <i class="fa-brands fa-whatsapp"></i>
                Contactar Soporte
            </a>
        </div>

    </div>
    </div>

</div>

<script>
function trackingPanel() {
    return {
        guideNumber: '{{ $prefillGuide ?? '' }}',
        loading: false,
        error: null,
        result: null,

        init() {
            if (this.guideNumber) {
                this.search();
            }
        },

        async search() {
            const guide = this.guideNumber.trim();
            if (!guide) return;
            this.loading = true;
            this.error = null;
            this.result = null;

            try {
                const response = await fetch('/api/v1/tracking/' + encodeURIComponent(guide), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    this.result = data.data;
                } else {
                    this.error = data.message || 'No se encontró ningún pedido con la guía "' + guide + '".';
                }
            } catch (e) {
                this.error = 'Error de conexión al consultar el rastreo. Por favor intenta nuevamente.';
            } finally {
                this.loading = false;
            }
        },

        loadDemo() {
            this.guideNumber = 'DRP-2026-001234';
            this.search();
        },

        statusLabel(status) {
            const labels = {
                'pending_confirmation': '⏳ Pendiente de Confirmación',
                'confirmed':  '📦 Confirmado – En Preparación',
                'processing': '📦 En Preparación',
                'dispatched': '🚚 Despachado – En Reparto',
                'in_transit': '🚚 En Camino',
                'delivered':  '✅ Entregado',
                'returned':   '↩️ Devuelto',
                'cancelled':  '❌ Cancelado',
                'settled':    '💰 Pagado / Liquidado',
            };
            return labels[status] || status;
        }
    }
}
</script>
@endsection



