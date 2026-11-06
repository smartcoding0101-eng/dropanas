@extends('layouts.app')

@section('title', 'Contáctenos – Dropana Bolivia | Únete al Ecosistema')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12"
     x-data="{
         submitting: false,
         sent: false,
         toast: { show: false, message: '', type: 'success' },
         form: {
             full_name: '',
             phone: '+591 ',
             email: '',
             interest_role: 'dropshipper',
             estimated_monthly_volume: '1-50 envíos mes',
             department: 'Santa Cruz',
             city: 'Santa Cruz de la Sierra',
             message: ''
         },
         async submit() {
             this.submitting = true;
             try {
                 const res = await fetch('/api/v1/leads', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                     body: JSON.stringify(this.form)
                 });
                 const data = await res.json();
                 if (res.ok && data.status === 'success') {
                     this.sent = true;
                     this.showToast('¡Gracias! Nos pondremos en contacto pronto.', 'success');
                 } else {
                     this.showToast(data.message || 'Error al enviar el formulario.', 'error');
                 }
             } catch(e) {
                 this.showToast('Error de conexión. Intenta de nuevo.', 'error');
             } finally {
                 this.submitting = false;
             }
         },
         showToast(msg, type) {
             this.toast = { show: true, message: msg, type };
             setTimeout(() => this.toast.show = false, 5000);
         }
     }">

    <!-- Toast -->
    <div x-show="toast.show" x-cloak
         class="fixed top-20 right-6 z-50 p-4 rounded-2xl shadow-xl text-xs font-semibold flex items-center gap-3 border"
         :class="toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
        <i class="fa-solid" :class="toast.type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-600'"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- Header -->
    <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-50 border border-red-200 text-xs font-bold text-[#960016]">
            <span class="flex h-2 w-2 rounded-full bg-[#ED2100] animate-pulse"></span>
            <span>Únete al Ecosistema Dropana Bolivia</span>
        </div>
        <h1 class="text-4xl font-black text-[#960016] font-heading">Contáctenos</h1>
        <p class="text-slate-500 text-base max-w-lg mx-auto">
            Cuéntanos cómo quieres usar Dropana y un asesor te contactará en menos de <strong>2 horas hábiles</strong>.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

        <!-- Formulario (col 3) -->
        <div class="lg:col-span-3">
            <!-- Estado: enviado correctamente -->
            <div x-show="sent" x-cloak class="bg-emerald-50 border border-emerald-200 rounded-2xl p-10 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto text-3xl text-emerald-600">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h3 class="font-black text-slate-800 text-xl font-heading">¡Solicitud Recibida!</h3>
                <p class="text-slate-500 text-sm">Un asesor de Dropana Bolivia se pondrá en contacto contigo por WhatsApp en menos de 2 horas hábiles.</p>
                <button @click="sent = false" class="btn-scarlet px-6 py-2.5 rounded-xl text-white text-sm font-bold">
                    Enviar otra solicitud
                </button>
            </div>

            <!-- Formulario -->
            <form x-show="!sent" @submit.prevent="submit"
                  class="bg-white border border-slate-200 rounded-2xl p-7 shadow-sm space-y-5">
                <h2 class="font-bold text-slate-800 text-lg font-heading flex items-center gap-2">
                    <i class="fa-solid fa-file-pen text-[#E88A38]"></i>
                    Formulario de Registro de Interés
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Nombre Completo *</label>
                        <input type="text" x-model="form.full_name" required placeholder="Juan Pérez"
                               class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38] focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">WhatsApp (Bolivia) *</label>
                        <input type="tel" x-model="form.phone" required placeholder="+591 7X-XXX-XXXX"
                               class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38] focus:bg-white transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Correo Electrónico</label>
                    <input type="email" x-model="form.email" placeholder="correo@ejemplo.com"
                           class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38] focus:bg-white transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">¿Cómo quieres usar Dropana? *</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([
                            ['value'=>'dropshipper','icon'=>'fa-store','label'=>'Dropshipper','sub'=>'Venta sin stock'],
                            ['value'=>'provider','icon'=>'fa-warehouse','label'=>'Proveedor','sub'=>'Abastecimiento'],
                            ['value'=>'fulfillment_client','icon'=>'fa-box-open','label'=>'Fulfillment','sub'=>'Tienda online'],
                            ['value'=>'warehouse_operator','icon'=>'fa-industry','label'=>'Almacén Industrial','sub'=>'Grandes volúmenes'],
                        ] as $role)
                        <label class="cursor-pointer">
                            <input type="radio" x-model="form.interest_role" value="{{ $role['value'] }}" class="sr-only">
                            <div class="border rounded-xl p-3 flex items-center gap-3 transition"
                                 :class="form.interest_role === '{{ $role['value'] }}' ? 'border-[#ED2100] bg-red-50' : 'border-slate-200 hover:border-slate-300'">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0"
                                     :class="form.interest_role === '{{ $role['value'] }}' ? 'bg-[#960016] text-white' : 'bg-slate-100 text-slate-500'">
                                    <i class="fa-solid {{ $role['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">{{ $role['label'] }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $role['sub'] }}</div>
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Volumen Estimado</label>
                        <select x-model="form.estimated_monthly_volume"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38] transition">
                            <option value="1-50 envíos mes">1–50 envíos/mes (Empezando)</option>
                            <option value="50-200 envíos mes">50–200 envíos/mes (Creciendo)</option>
                            <option value="+200 envios mes">+200 envíos/mes (Escalado)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Departamento</label>
                        <select x-model="form.department"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38] transition">
                            @foreach(['Santa Cruz','La Paz','El Alto','Cochabamba','Oruro','Potosí','Chuquisaca','Tarija','Beni','Pando'] as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Mensaje (opcional)</label>
                    <textarea x-model="form.message" rows="3" placeholder="Cuéntanos más sobre tu negocio o tus preguntas..."
                              class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm bg-slate-50 focus:outline-none focus:border-[#E88A38] focus:bg-white transition resize-none"></textarea>
                </div>

                <button type="submit" :disabled="submitting"
                        class="w-full btn-scarlet py-3.5 rounded-xl text-white text-sm font-bold flex items-center justify-center gap-2 disabled:opacity-60">
                    <span x-show="!submitting">Enviar Solicitud y Recibir Asesoría</span>
                    <span x-show="submitting" x-cloak>Enviando...</span>
                    <i class="fa-solid fa-arrow-right text-xs" x-show="!submitting"></i>
                </button>

                <p class="text-[10px] text-slate-400 text-center">
                    Al enviar, aceptas que Dropana Bolivia te contacte por WhatsApp. Sin spam, prometido.
                </p>
            </form>
        </div>

        <!-- Info lateral (col 2) -->
        <div class="lg:col-span-2 space-y-5">

            <!-- Por qué unirte -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h3 class="font-bold text-slate-800 font-heading">¿Por qué unirte a Dropana?</h3>
                @foreach([
                    ['icon'=>'fa-ban','color'=>'red','text'=>'Sin inversión inicial ni membresías'],
                    ['icon'=>'fa-infinity','color'=>'blue','text'=>'Inventario infinito en la Megabodega'],
                    ['icon'=>'fa-money-bill-wave','color'=>'green','text'=>'Retiros a tu cuenta en 24 horas'],
                    ['icon'=>'fa-map-location-dot','color'=>'amber','text'=>'Cobertura en los 9 departamentos'],
                    ['icon'=>'fa-qrcode','color'=>'slate','text'=>'Cobro en efectivo o QR Simple'],
                    ['icon'=>'fa-rotate-left','color'=>'orange','text'=>'2 intentos de entrega incluidos'],
                ] as $point)
                <div class="flex items-center gap-3 text-sm text-slate-700">
                    <div class="w-7 h-7 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center shrink-0">
                        <i class="fa-solid {{ $point['icon'] }} text-[11px] text-slate-500"></i>
                    </div>
                    {{ $point['text'] }}
                </div>
                @endforeach
            </div>

            <!-- Oficinas y ubicación -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-3">
                <h3 class="font-bold text-slate-800 font-heading flex items-center gap-2">
                    <i class="fa-solid fa-location-dot text-[#960016]"></i>
                    Almacenes Dropana Bolivia
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex gap-3 items-start">
                        <div class="w-2 h-2 rounded-full bg-[#ED2100] mt-1.5 shrink-0"></div>
                        <div>
                            <div class="font-semibold text-slate-800">Almacén Santa Cruz</div>
                            <div class="text-slate-500 text-xs">Parque Industrial Latinoamericano, Santa Cruz de la Sierra</div>
                        </div>
                    </div>
                    <div class="flex gap-3 items-start">
                        <div class="w-2 h-2 rounded-full bg-[#E88A38] mt-1.5 shrink-0"></div>
                        <div>
                            <div class="font-semibold text-slate-800">Almacén El Alto</div>
                            <div class="text-slate-500 text-xs">Ciudad Satélite, El Alto – La Paz</div>
                        </div>
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-500">
                    <i class="fa-solid fa-clock text-slate-400"></i>
                    Lun–Sáb 8:00 AM – 6:00 PM (hora Bolivia)
                </div>
            </div>

            <!-- Métricas clave -->
            <div class="bg-[#960016] rounded-2xl p-5 text-white space-y-3">
                <div class="text-xs font-bold uppercase tracking-widest text-white/60">Red Activa Dropana</div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <div class="text-2xl font-black">9/9</div>
                        <div class="text-[10px] text-white/70">Departamentos</div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-amber-300">2</div>
                        <div class="text-[10px] text-white/70">Intentos COD</div>
                    </div>
                    <div>
                        <div class="text-2xl font-black">24h</div>
                        <div class="text-[10px] text-white/70">Retiro Wallet</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
