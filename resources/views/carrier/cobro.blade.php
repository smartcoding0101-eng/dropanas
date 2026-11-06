@extends('layouts.app')

@section('title', 'Cobro COD – Dropana Bolivia | Repartidor')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6 space-y-5"
     x-data="carrieroCobroPanel()">

    <!-- Header del repartidor -->
    <div class="bg-gradient-to-r from-[#960016] to-[#ED2100] rounded-2xl p-5 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center text-xl">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
            <div>
                <h1 class="text-base font-black font-heading">Cobro COD en Campo</h1>
                <p class="text-white/70 text-xs">Confirma entregas y registra pagos</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-2xl font-black">{{ now()->format('d/m') }}</div>
            <div class="text-white/60 text-xs">{{ now()->format('D') }}</div>
        </div>
    </div>

    <!-- Resumen de hoja de ruta del día -->
    <div class="grid grid-cols-3 gap-3">
        @foreach([
            ['label'=>'Por Entregar','value'=>'8','color'=>'amber'],
            ['label'=>'Entregados','value'=>'5','color'=>'emerald'],
            ['label'=>'Novedades','value'=>'1','color'=>'red'],
        ] as $stat)
        <div class="bg-white border border-slate-200 rounded-2xl p-4 text-center shadow-sm">
            <div class="text-2xl font-black {{ $stat['color'] === 'amber' ? 'text-amber-500' : ($stat['color'] === 'emerald' ? 'text-emerald-600' : 'text-[#960016]') }} font-heading">{{ $stat['value'] }}</div>
            <div class="text-[11px] text-slate-500 font-medium mt-0.5">{{ $stat['label'] }}</div>
        </div>
        @endforeach
    </div>

    <!-- Buscar pedido por guía -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
        <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-magnifying-glass text-[#E88A38]"></i>
            Buscar Pedido
        </h2>
        <div class="flex gap-2">
            <input type="text" x-model="searchGuide" placeholder="Ej: DRP-2026-001234"
                   class="flex-1 border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38] font-mono"
                   @keydown.enter="loadOrder()">
            <button @click="loadOrder()" class="btn-scarlet px-4 py-3 rounded-xl text-white font-bold text-sm">
                <i class="fa-solid fa-search"></i>
            </button>
        </div>
    </div>

    <!-- Pedido cargado -->
    <div x-show="currentOrder" x-cloak class="bg-white border-2 border-[#ED2100]/20 rounded-2xl shadow-sm overflow-hidden">

        <!-- Datos del pedido -->
        <div class="p-5 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wide">Guía</div>
                    <div class="font-mono font-black text-[#960016] text-base" x-text="currentOrder?.guide_number"></div>
                </div>
                <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase">
                    Pendiente de Entrega
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Cliente</div>
                    <div class="font-bold text-slate-800" x-text="currentOrder?.customer_name"></div>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">WhatsApp</div>
                    <a :href="'https://wa.me/' + (currentOrder?.customer_phone || '')"
                       class="font-semibold text-emerald-600 flex items-center gap-1 hover:underline text-sm">
                        <i class="fa-brands fa-whatsapp text-xs"></i>
                        <span x-text="currentOrder?.customer_phone"></span>
                    </a>
                </div>
                <div class="col-span-2">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Dirección</div>
                    <div class="font-semibold text-slate-700 text-sm" x-text="currentOrder?.delivery_address"></div>
                </div>
            </div>

            <!-- Monto a cobrar (destacado) -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Monto a Cobrar (COD)</div>
                    <div class="text-3xl font-black text-[#960016] font-heading">
                        Bs. <span x-text="currentOrder?.cod_amount"></span>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-500 space-y-0.5">
                    <div>≈ <strong class="text-slate-700">$<span x-text="(currentOrder?.cod_amount / {{ $bcbOfficialRate ?? 12.06 }}).toFixed(2)"></span> USD</strong></div>
                    <div class="text-[10px] text-slate-400">Tasa BCB: Bs. {{ number_format($bcbOfficialRate ?? 12.06, 2) }}</div>
                </div>
            </div>

            <!-- Método de pago -->
            <div>
                <div class="text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Método de Cobro</div>
                <div class="grid grid-cols-3 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" x-model="paymentMethod" value="cash" class="sr-only">
                        <div class="border-2 rounded-xl p-3 flex flex-col sm:flex-row items-center gap-2 transition"
                             :class="paymentMethod === 'cash' ? 'border-[#ED2100] bg-red-50' : 'border-slate-200'">
                            <i class="fa-solid fa-money-bill-wave text-sm" :class="paymentMethod === 'cash' ? 'text-[#960016]' : 'text-slate-400'"></i>
                            <div>
                                <div class="text-xs font-bold text-slate-800">Efectivo</div>
                                <div class="text-[10px] text-slate-400">BOB / USD</div>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" x-model="paymentMethod" value="qr" class="sr-only">
                        <div class="border-2 rounded-xl p-3 flex flex-col sm:flex-row items-center gap-2 transition"
                             :class="paymentMethod === 'qr' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200'">
                            <i class="fa-solid fa-qrcode text-sm" :class="paymentMethod === 'qr' ? 'text-emerald-600' : 'text-slate-400'"></i>
                            <div>
                                <div class="text-xs font-bold text-slate-800">QR Simple</div>
                                <div class="text-[10px] text-slate-400">Bancos / Tigo</div>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" x-model="paymentMethod" value="binance" class="sr-only">
                        <div class="border-2 rounded-xl p-3 flex flex-col sm:flex-row items-center gap-2 transition"
                             :class="paymentMethod === 'binance' ? 'border-amber-400 bg-amber-50' : 'border-slate-200'">
                            <i class="fa-brands fa-bitcoin text-sm" :class="paymentMethod === 'binance' ? 'text-amber-500' : 'text-slate-400'"></i>
                            <div>
                                <div class="text-xs font-bold text-slate-800">Binance Pay</div>
                                <div class="text-[10px] text-slate-400">Cripto USDT</div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- QR Simple (mockup) -->
            <div x-show="paymentMethod === 'qr'" x-cloak
                 class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 text-center space-y-3">
                <div class="text-xs font-bold text-emerald-700 uppercase tracking-wide">QR Simple Bolivia</div>
                <div class="w-40 h-40 bg-white border-4 border-emerald-200 rounded-2xl flex items-center justify-center mx-auto">
                    <i class="fa-solid fa-qrcode text-6xl text-slate-300"></i>
                </div>
                <div class="text-sm font-bold text-emerald-800">Bs. <span x-text="currentOrder?.cod_amount"></span></div>
                <div class="text-[11px] text-emerald-600">El cliente escanea este QR con su banco o billetera Tigo Money</div>
            </div>

            <!-- Binance Pay Cripto QR -->
            <div x-show="paymentMethod === 'binance'" x-cloak
                 class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-center space-y-3">
                <div class="text-xs font-bold text-amber-800 uppercase tracking-wide flex items-center justify-center gap-2">
                    <i class="fa-brands fa-bitcoin text-amber-500"></i>
                    <span>Binance Pay (Cripto USDT)</span>
                </div>
                <div class="w-40 h-40 bg-white border-4 border-amber-300 rounded-2xl flex flex-col items-center justify-center mx-auto shadow-sm p-2">
                    <i class="fa-solid fa-qrcode text-5xl text-amber-500 mb-1"></i>
                    <span class="text-[9px] font-mono text-slate-600 font-bold">BINANCE PAY QR</span>
                </div>
                <div>
                    <div class="text-base font-black text-amber-900 font-mono">
                        <span x-text="(currentOrder?.cod_amount / {{ $bcbOfficialRate ?? 12.06 }}).toFixed(2)"></span> USDT
                    </div>
                    <div class="text-xs text-slate-600 font-medium">Equivalente a Bs. <span x-text="currentOrder?.cod_amount"></span> (Tasa BCB: {{ number_format($bcbOfficialRate ?? 12.06, 2) }})</div>
                </div>
                <div class="text-[11px] text-amber-800 bg-amber-100/70 p-2 rounded-lg font-medium">
                    El cliente escanea el QR con su app de Binance o transfiere al Pay ID Dropana. La acreditación es inmediata con 0% de comisión.
                </div>
            </div>

            <!-- Campos de evidencia -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">
                    Foto de Evidencia de Entrega
                </label>
                <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-[#E88A38] transition cursor-pointer"
                     @click="$refs.photoInput.click()">
                    <input type="file" accept="image/*" capture="environment" x-ref="photoInput"
                           @change="previewPhoto($event)" class="hidden">
                    <template x-if="!photoPreview">
                        <div class="space-y-2">
                            <i class="fa-solid fa-camera text-3xl text-slate-300"></i>
                            <div class="text-xs text-slate-400">Tomar foto de comprobante o señar de entrega</div>
                        </div>
                    </template>
                    <template x-if="photoPreview">
                        <img :src="photoPreview" class="w-full max-h-32 object-cover rounded-xl mx-auto">
                    </template>
                </div>
            </div>

            <!-- Notas opcionales -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Observaciones (opcional)</label>
                <input type="text" x-model="notes" placeholder="Ej: Entregado a familiar, dejado en portería..."
                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38]">
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="border-t border-slate-100 p-4 grid grid-cols-2 gap-3">
            <button @click="confirmDelivery()" :disabled="confirming"
                    class="btn-scarlet py-3.5 rounded-xl text-white text-sm font-bold flex items-center justify-center gap-2 disabled:opacity-60">
                <i class="fa-solid fa-circle-check"></i>
                <span x-show="!confirming">Entrega Exitosa</span>
                <span x-show="confirming" x-cloak>Guardando...</span>
            </button>
            <button @click="reportNovedad()" class="bg-amber-50 border border-amber-200 text-amber-700 py-3.5 rounded-xl text-sm font-bold flex items-center justify-center gap-2 hover:bg-amber-100 transition">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Reportar Novedad
            </button>
        </div>

    </div>

    <!-- Placeholder si no hay pedido cargado -->
    <div x-show="!currentOrder" class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center space-y-3">
        <i class="fa-solid fa-barcode text-4xl text-slate-300"></i>
        <div class="text-slate-500 text-sm font-semibold">Ingresa un número de guía para cargar el pedido</div>
        <button @click="loadDemoOrder()" class="text-xs text-[#960016] font-bold hover:underline">
            Cargar pedido de demo →
        </button>
    </div>

    <!-- Lista mini de hoja de ruta -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i class="fa-solid fa-route text-[#E88A38]"></i>
                Hoja de Ruta de Hoy
            </h3>
            <span class="text-xs font-bold text-slate-400">8 pedidos</span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach([
                ['guide'=>'DRP-2026-0150','name'=>'Ana Chávez','address'=>'Av. Beni #1234, SC','amount'=>'Bs. 350','status'=>'pending'],
                ['guide'=>'DRP-2026-0151','name'=>'Marco Gutiérrez','address'=>'Calle 21 de Mayo, LP','amount'=>'Bs. 220','status'=>'delivered'],
                ['guide'=>'DRP-2026-0152','name'=>'Rosa Lima','address'=>'Barrio Sur #456, EA','amount'=>'Bs. 480','status'=>'pending'],
                ['guide'=>'DRP-2026-0153','name'=>'Luis Chura','address'=>'Av. Germán Busch, CB','amount'=>'Bs. 180','status'=>'novedad'],
            ] as $route)
            <div class="px-5 py-3 flex items-center gap-3 hover:bg-slate-50 transition cursor-pointer"
                 @click="searchGuide = '{{ $route['guide'] }}'; loadOrder()">
                <div class="w-2 h-2 rounded-full shrink-0
                    {{ $route['status'] === 'delivered' ? 'bg-emerald-500' : ($route['status'] === 'novedad' ? 'bg-amber-500' : 'bg-slate-300') }}">
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-bold text-slate-800 truncate">{{ $route['name'] }}</div>
                    <div class="text-[10px] text-slate-400 truncate">{{ $route['address'] }}</div>
                </div>
                <div class="text-right shrink-0">
                    <div class="font-black text-sm text-slate-900">{{ $route['amount'] }}</div>
                    <div class="font-mono text-[10px] text-slate-400">{{ $route['guide'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

<script>
function carrieroCobroPanel() {
    return {
        searchGuide: '',
        currentOrder: null,
        paymentMethod: 'cash',
        photoPreview: null,
        notes: '',
        confirming: false,

        loadOrder() {
            if (!this.searchGuide.trim()) return;
            // Simulación: en producción esto hace GET /api/v1/orders?guide={searchGuide}
            this.currentOrder = {
                guide_number: this.searchGuide || 'DRP-2026-0150',
                customer_name: 'Ana Chávez López',
                customer_phone: '59175123456',
                delivery_address: 'Av. Beni #1234, Barrio Las Palmas, Santa Cruz de la Sierra',
                cod_amount: 350,
            };
            this.paymentMethod = 'cash';
            this.photoPreview = null;
            this.notes = '';
        },

        loadDemoOrder() {
            this.searchGuide = 'DRP-2026-0150';
            this.loadOrder();
        },

        previewPhoto(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => { this.photoPreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        async confirmDelivery() {
            if (!this.paymentMethod) { alert('Selecciona el método de cobro.'); return; }
            this.confirming = true;
            await new Promise(r => setTimeout(r, 1500));
            alert('✅ Entrega confirmada. La guía ' + this.currentOrder.guide_number + ' ha sido marcada como ENTREGADA. Las ganancias del dropshipper se han acreditado a su billetera.');
            this.currentOrder = null;
            this.searchGuide = '';
            this.confirming = false;
        },

        reportNovedad() {
            const reason = prompt('Motivo de la novedad:\n1. Cliente ausente\n2. Dirección incorrecta\n3. Rechazo del cliente\n4. Zona de difícil acceso\n\nEscribe el motivo:');
            if (reason) {
                alert('⚠️ Novedad registrada: "' + reason + '"\nSe programará un 2do intento de entrega mañana.');
                this.currentOrder = null;
                this.searchGuide = '';
            }
        }
    }
}
</script>
@endsection
