@extends('layouts.app')

@section('title', 'Gestión de Transportadoras & Coberturas | Bolidrop Bolivia')

@section('content')
<div class="space-y-6" x-data="carrierManager()">

    <!-- Header Principal -->
    <x-page-header
        title="Transportadoras & Coberturas COD Bolivia"
        subtitle="Administración de empresas de courier, configuración de tarifas de flete por departamento y activación de servicio contra entrega para dropshippers."
        icon="fa-solid fa-truck-fast"
        badge="Dropana Logística • Ecosistema Nacional">
        <x-slot:actions>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" @click="openCreateModal()"
                    class="btn-scarlet px-4 py-2 rounded-xl text-xs font-bold shadow-md flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-plus"></i>
                    <span>Nueva Transportadora</span>
                </button>
                <a href="{{ url('/panel/admin') }}"
                    class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-semibold text-white transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left"></i> Panel Central
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Alertas Flash -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-bold space-y-1 shadow-xs">
            @foreach($errors->all() as $error)
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Tarjetas KPI Superiores -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Couriers -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-slate-500 text-xs font-bold uppercase tracking-wider">
                <span>Total Transportadoras</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-truck"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 font-heading">
                {{ $kpis['total_carriers'] }}
            </div>
            <div class="text-[11px] text-slate-500">Operadores registrados en plataforma</div>
        </div>

        <!-- Couriers Activos -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-emerald-600 text-xs font-bold uppercase tracking-wider">
                <span>Couriers Operativos</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-600 font-heading">
                {{ $kpis['active_carriers'] }}
            </div>
            <div class="text-[11px] text-slate-500">Disponibles en el selector de pedidos</div>
        </div>

        <!-- Couriers con Servicio COD -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-sky-600 text-xs font-bold uppercase tracking-wider">
                <span>Soporte Contra Entrega (COD)</span>
                <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-sky-700 font-heading">
                {{ $kpis['cod_carriers'] }}
            </div>
            <div class="text-[11px] text-slate-500">Cobranza de efectivo / QR en puerta</div>
        </div>

        <!-- Coberturas & Tarifas -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-slate-500 text-xs font-bold uppercase tracking-wider">
                <span>Tarifas de Cobertura</span>
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-purple-700 font-heading">
                {{ $kpis['total_coverages'] }}
            </div>
            <div class="text-[11px] text-slate-500">Precios de flete por departamento</div>
        </div>

    </div>

    <!-- Lista de Transportadoras -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Barra de Búsqueda -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm font-heading">Empresas de Transporte Logístico</h3>
                <p class="text-xs text-slate-500">Las empresas activas aparecen inmediatamente en el formulario de creación de pedidos de los dropshippers.</p>
            </div>

            <form action="{{ route('admin.carriers.index') }}" method="GET" class="flex items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre, código o teléfono..."
                        class="bg-white border border-slate-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 w-56 sm:w-72">
                </div>
                <button type="submit" class="px-3 py-1.5 bg-[#0F172A] hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition">
                    Buscar
                </button>
                @if(!empty($search))
                    <a href="{{ route('admin.carriers.index') }}" class="text-xs text-slate-500 hover:text-rose-600 font-semibold" title="Limpiar búsqueda">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </form>
        </div>

        <!-- Cards / Grilla de Couriers -->
        <div class="divide-y divide-slate-100">
            @forelse($carriers as $carrier)
                <div class="p-5 sm:p-6 hover:bg-slate-50/60 transition space-y-4">
                    
                    <!-- Fila Superior: Nombre, Estado, Badges y Acciones -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl {{ $carrier->is_active ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center text-xl font-bold shrink-0">
                                <i class="fa-solid fa-truck-ramp-box"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-extrabold text-slate-900 text-base font-heading">{{ $carrier->name }}</h4>
                                    <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $carrier->code }}
                                    </span>
                                    @if($carrier->is_active)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                            Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-slate-100 text-slate-600 border border-slate-200">
                                            Inactivo
                                        </span>
                                    @endif

                                    @if($carrier->has_cod_service)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                            <i class="fa-solid fa-hand-holding-dollar text-[9px]"></i> Contra Entrega COD
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-3 flex-wrap">
                                    @if($carrier->contact_name)
                                        <span><i class="fa-solid fa-user text-slate-400 text-[10px] mr-1"></i> {{ $carrier->contact_name }}</span>
                                    @endif
                                    @if($carrier->contact_phone)
                                        <span><i class="fa-solid fa-phone text-slate-400 text-[10px] mr-1"></i> <strong class="text-slate-700">{{ $carrier->contact_phone }}</strong></span>
                                    @endif
                                    @if($carrier->contact_email)
                                        <span><i class="fa-solid fa-envelope text-slate-400 text-[10px] mr-1"></i> {{ $carrier->contact_email }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center gap-2 self-start sm:self-center flex-wrap">
                            
                            <!-- Añadir / Gestionar Cobertura -->
                            <button type="button"
                                @click="openCoveragesModal({{ json_encode($carrier) }})"
                                class="px-3 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-map-location-dot"></i>
                                <span>Tarifas & Coberturas ({{ $carrier->coverages->count() }})</span>
                            </button>

                            <!-- Editar -->
                            <button type="button"
                                @click="openEditModal({{ json_encode($carrier) }})"
                                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                                <i class="fa-solid fa-pen"></i>
                                <span>Editar</span>
                            </button>

                            <!-- Toggle Activar/Desactivar -->
                            <form action="{{ route('admin.carriers.toggle_status', $carrier->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold border transition cursor-pointer {{ $carrier->is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200' }}"
                                    title="{{ $carrier->is_active ? 'Desactivar transportadora' : 'Activar transportadora' }}">
                                    @if($carrier->is_active)
                                        <i class="fa-solid fa-pause"></i> Desactivar
                                    @else
                                        <i class="fa-solid fa-play"></i> Activar
                                    @endif
                                </button>
                            </form>

                        </div>
                    </div>

                    <!-- Coberturas Departamentales de esta Transportadora -->
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200/80">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                                Coberturas y Tarifas de Flete Habilitadas:
                            </span>
                            <button type="button" @click="openAddCoverageSingle({{ json_encode($carrier) }})"
                                class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 cursor-pointer">
                                <i class="fa-solid fa-plus text-[10px]"></i> Añadir Departamento
                            </button>
                        </div>

                        @if($carrier->coverages->isEmpty())
                            <div class="text-xs text-slate-400 italic py-1 flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                                <span>No tiene tarifas de cobertura registradas. Usa el botón "+ Añadir Departamento" para fijar precios de flete.</span>
                            </div>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach($carrier->coverages as $cov)
                                    <div class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 flex items-center gap-2 shadow-2xs text-xs">
                                        <i class="fa-solid fa-location-dot text-indigo-500 text-[10px]"></i>
                                        <span class="font-bold text-slate-800">{{ $cov->department }}</span>
                                        <span class="text-slate-400 font-normal">({{ $cov->city }})</span>
                                        <span class="font-mono font-extrabold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                                            Bs. {{ number_format((float) $cov->base_shipping_bob, 2) }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            {{ $cov->estimated_delivery_days }} {{ $cov->estimated_delivery_days === 1 ? 'día' : 'días' }}
                                        </span>
                                        
                                        <!-- Eliminar Cobertura -->
                                        <form action="{{ route('admin.carriers.coverages.delete', $cov->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar tarifa de flete para {{ $cov->department }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-slate-300 hover:text-rose-600 ml-1 cursor-pointer" title="Eliminar cobertura">
                                                <i class="fa-solid fa-xmark text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
            @empty
                <div class="py-12 text-center text-slate-400">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <div class="font-bold text-slate-600">No se encontraron transportadoras.</div>
                    <p class="text-xs text-slate-400 mt-0.5">Haz clic en "+ Nueva Transportadora" para agregar tu primer operador logístico.</p>
                </div>
            @endforelse
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: NUEVA TRANSPORTADORA                                             -->
    <!-- ========================================================================= -->
    <div x-show="createModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-start sm:items-center justify-center p-3 sm:p-5 md:p-6 py-6 sm:py-8">
        <div class="bg-white rounded-3xl max-w-xl w-full p-5 sm:p-7 shadow-2xl border border-slate-200 space-y-4 my-auto relative"
             @click.away="createModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 font-heading text-base">Registrar Nueva Transportadora</h3>
                        <p class="text-xs text-slate-500">Aparecerá en el selector de pedidos COD para todos los dropshippers.</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.carriers.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                
                <!-- Nombre y Código -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-800 mb-1">Nombre Comercial de la Empresa: <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Ej: DEX Bolivia, Servicourier, TotalExpress..."
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-bold focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Código Identificador (Opcional):</label>
                        <input type="text" name="code" placeholder="Ej: dex_bolivia (Auto si queda vacío)"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-mono focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <!-- Contacto -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Persona / Responsable de Operaciones:</label>
                        <input type="text" name="contact_name" placeholder="Ej: Lic. Carlos Mendizábal"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Teléfono / WhatsApp de Contacto:</label>
                        <input type="text" name="contact_phone" placeholder="Ej: +591 71234567"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-mono focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Correo Electrónico:</label>
                    <input type="email" name="contact_email" placeholder="operaciones@courier.bo"
                        class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-indigo-500">
                </div>

                <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <input type="checkbox" name="has_cod_service" value="1" id="has_cod_create" checked
                        class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <label for="has_cod_create" class="text-xs font-bold text-slate-800 cursor-pointer">
                        Habilitar Cobro Contra Entrega (Cash on Delivery - COD Bolivia)
                    </label>
                </div>

                <!-- Tarifas Base Iniciales Eje Troncal (Opcionales para ahorrar tiempo) -->
                <div class="p-3.5 bg-indigo-50/50 rounded-2xl border border-indigo-100 space-y-2.5">
                    <div class="font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-bolt text-amber-500"></i>
                        <span>Tarifas de Flete Iniciales Eje Troncal (Opcional):</span>
                    </div>
                    <p class="text-[11px] text-slate-500">Fija el costo de envío estándar en BOB que se cobrará al dropshipper al generar órdenes.</p>
                    
                    <div class="grid grid-cols-3 gap-2.5 pt-1">
                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1">Santa Cruz (Bs):</label>
                            <input type="number" step="0.5" name="default_shipping_scz_bob" placeholder="18.00" value="18.00"
                                class="w-full p-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold text-center focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1">La Paz (Bs):</label>
                            <input type="number" step="0.5" name="default_shipping_lpz_bob" placeholder="25.00" value="25.00"
                                class="w-full p-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold text-center focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1">Cochabamba (Bs):</label>
                            <input type="number" step="0.5" name="default_shipping_cbb_bob" placeholder="22.00" value="22.00"
                                class="w-full p-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold text-center focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-scarlet px-5 py-2.5 rounded-xl font-bold shadow-md cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Transportadora
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: EDITAR TRANSPORTADORA                                            -->
    <!-- ========================================================================= -->
    <div x-show="editModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-start sm:items-center justify-center p-3 sm:p-5 md:p-6 py-6 sm:py-8">
        <div class="bg-white rounded-3xl max-w-xl w-full p-5 sm:p-7 shadow-2xl border border-slate-200 space-y-4 my-auto relative"
             @click.away="editModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 font-heading text-base">Editar Transportadora</h3>
                        <p class="text-xs text-slate-500" x-text="editCarrier.name"></p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form :action="'{{ url('/admin/carriers') }}/' + editCarrier.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block font-bold text-slate-800 mb-1">Nombre Comercial de la Empresa: <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editCarrier.name" required
                        class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-bold focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Persona de Contacto:</label>
                        <input type="text" name="contact_name" x-model="editCarrier.contact_name"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Teléfono / WhatsApp:</label>
                        <input type="text" name="contact_phone" x-model="editCarrier.contact_phone"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-mono focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Correo Electrónico:</label>
                    <input type="email" name="contact_email" x-model="editCarrier.contact_email"
                        class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-indigo-500">
                </div>

                <div class="flex items-center gap-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_cod_service" value="1" :checked="editCarrier.has_cod_service"
                            class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-slate-800">Soporte Contra Entrega (COD)</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer ml-4">
                        <input type="checkbox" name="is_active" value="1" :checked="editCarrier.is_active"
                            class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-slate-800">Activo en Plataforma</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-scarlet px-5 py-2.5 rounded-xl font-bold shadow-md cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-check"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: GESTIONAR O AÑADIR COBERTURA POR DEPARTAMENTO                    -->
    <!-- ========================================================================= -->
    <div x-show="coverageModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-start sm:items-center justify-center p-3 sm:p-5 md:p-6 py-6 sm:py-8">
        <div class="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl border border-slate-200 space-y-4 my-auto relative"
             @click.away="coverageModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 font-heading text-base">Fijar Tarifa de Flete Departamental</h3>
                        <p class="text-xs text-slate-500" x-text="'Para: ' + activeCarrierForCoverage.name"></p>
                    </div>
                </div>
                <button type="button" @click="coverageModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form :action="'{{ url('/admin/carriers') }}/' + activeCarrierForCoverage.id + '/coverages'" method="POST" class="space-y-4 text-xs">
                @csrf
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-800 mb-1">Departamento de Bolivia: <span class="text-rose-500">*</span></label>
                        <select name="department" required x-model="coverageForm.department" @change="onDepartmentChange()"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-bold focus:outline-none focus:border-purple-500">
                            @foreach($boliviaDepartments as $dep)
                                <option value="{{ $dep }}">{{ $dep }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-800 mb-1">Ciudad / Municipio: <span class="text-rose-500">*</span></label>
                        <input type="text" name="city" required x-model="coverageForm.city" placeholder="Ej: Santa Cruz de la Sierra"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-purple-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-800 mb-1">Costo del Flete Base (BOB): <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-mono font-bold">Bs.</span>
                            <input type="number" step="0.5" name="base_shipping_bob" required placeholder="20.00"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-purple-500">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Días Estimados de Entrega:</label>
                        <input type="number" name="estimated_delivery_days" value="2" min="1" max="15"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 text-center font-bold focus:outline-none focus:border-purple-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tipo de Zona:</label>
                    <select name="zone_type" class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-purple-500">
                        <option value="urban">Urbana (Capital / Radio Central)</option>
                        <option value="suburban">Suburbana / Conurbación</option>
                        <option value="provincial">Provincial</option>
                        <option value="remote">Remota</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="coverageModalOpen = false" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold shadow-md cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Tarifa
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function carrierManager() {
    return {
        createModalOpen: false,
        editModalOpen: false,
        coverageModalOpen: false,
        editCarrier: {
            id: '',
            name: '',
            contact_name: '',
            contact_phone: '',
            contact_email: '',
            has_cod_service: true,
            is_active: true
        },
        activeCarrierForCoverage: {
            id: '',
            name: ''
        },
        coverageForm: {
            department: 'Santa Cruz',
            city: 'Santa Cruz de la Sierra'
        },

        openCreateModal() {
            this.createModalOpen = true;
        },
        openEditModal(carrier) {
            this.editCarrier = { ...carrier };
            this.editModalOpen = true;
        },
        openCoveragesModal(carrier) {
            this.activeCarrierForCoverage = { ...carrier };
            this.coverageForm.department = 'Santa Cruz';
            this.coverageForm.city = 'Santa Cruz de la Sierra';
            this.coverageModalOpen = true;
        },
        openAddCoverageSingle(carrier) {
            this.openCoveragesModal(carrier);
        },
        onDepartmentChange() {
            const defaults = {
                'Santa Cruz': 'Santa Cruz de la Sierra',
                'La Paz': 'La Paz / El Alto',
                'Cochabamba': 'Cochabamba',
                'Oruro': 'Oruro',
                'Potosí': 'Potosí',
                'Chuquisaca': 'Sucre',
                'Tarija': 'Tarija',
                'Beni': 'Trinidad',
                'Pando': 'Cobija'
            };
            this.coverageForm.city = defaults[this.coverageForm.department] || this.coverageForm.department;
        }
    };
}
</script>
@endpush
@endsection
