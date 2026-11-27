@extends('layouts.app')

@section('title', 'Solicitudes de Recarga & Acreditación | Bolidrop Bolivia')

@section('content')
<div class="space-y-6" x-data="topupManager()">

    <!-- Header Principal -->
    <x-page-header
        title="Historial de Solicitudes de Recarga"
        subtitle="Verificación de transferencias bancarias, validación de comprobantes (Imagen o PDF) y acreditación de saldo en tiempo real."
        icon="fa-solid fa-money-bill-transfer"
        badge="Dropana FinTech • Mesa de Control de Saldos">
        <x-slot:actions>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" @click="settingsModalOpen = true"
                    class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-bold text-white transition flex items-center gap-2 shadow-sm cursor-pointer">
                    <i class="fa-solid fa-qrcode text-emerald-300"></i>
                    <span>Configurar QR & Correo Admin</span>
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
    @if(session('status'))
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-amber-600 text-base"></i>
                <span>{{ session('status') }}</span>
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
        
        <!-- Total Solicitudes -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-slate-500 text-xs font-bold uppercase tracking-wider">
                <span>Total Solicitudes</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 font-heading">
                {{ $kpis['total_count'] }}
            </div>
            <div class="text-[11px] text-slate-500">Historial histórico registrado</div>
        </div>

        <!-- Pendientes de Validación -->
        <div class="bg-white rounded-2xl p-5 border {{ $kpis['pending_count'] > 0 ? 'border-amber-300 ring-2 ring-amber-100' : 'border-slate-200/80' }} shadow-xs space-y-2">
            <div class="flex items-center justify-between text-amber-600 text-xs font-bold uppercase tracking-wider">
                <span>Pendientes por Validar</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-clock animate-pulse"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <div class="text-2xl font-black text-amber-600 font-heading">
                    {{ $kpis['pending_count'] }}
                </div>
                <span class="text-xs font-mono font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                    Bs. {{ number_format($kpis['pending_amount_bob'], 2) }}
                </span>
            </div>
            <div class="text-[11px] text-amber-800 font-medium">Requieren contrastar con cuenta bancaria</div>
        </div>

        <!-- Aprobadas & Acreditadas -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-emerald-600 text-xs font-bold uppercase tracking-wider">
                <span>Aprobadas & Acreditadas</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <div class="text-2xl font-black text-emerald-600 font-heading">
                    {{ $kpis['approved_count'] }}
                </div>
                <span class="text-xs font-mono font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                    Bs. {{ number_format($kpis['total_approved_bob'], 2) }}
                </span>
            </div>
            <div class="text-[11px] text-slate-500">Fondos ya inyectados al saldo de vendedores</div>
        </div>

        <!-- Rechazadas -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-slate-500 text-xs font-bold uppercase tracking-wider">
                <span>Rechazadas</span>
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-ban"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-700 font-heading">
                {{ $kpis['rejected_count'] }}
            </div>
            <div class="text-[11px] text-slate-500">No validadas o comprobantes no legítimos</div>
        </div>

    </div>

    <!-- Banner Informativo del Correo de Recepción de Solicitudes -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-4 text-white flex flex-col sm:flex-row items-center justify-between gap-3 shadow-md">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-sm">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-200 flex items-center gap-2">
                    <span>Bandeja de Notificaciones Oficial:</span>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-mono text-[11px] font-bold border border-emerald-500/30">
                        {{ $financialSettings->getEffectiveAdminEmail() }}
                    </span>
                </div>
                <p class="text-[11px] text-slate-400">Cada vez que un dropshipper envía comprobante (imagen o PDF), el sistema notifica inmediatamente a esta dirección.</p>
            </div>
        </div>
        <button type="button" @click="settingsModalOpen = true" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-semibold text-white transition cursor-pointer shrink-0">
            <i class="fa-solid fa-gear mr-1"></i> Modificar Correo o Datos
        </button>
    </div>

    <!-- Tabla y Filtros -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Barra de Filtros y Búsqueda -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/50">
            
            <!-- Pestañas de Estado -->
            <div class="inline-flex items-center bg-slate-200/70 p-1 rounded-xl text-xs gap-1 flex-wrap">
                <a href="{{ route('admin.recargas.index', ['status' => 'all', 'search' => $search]) }}"
                   class="px-3 py-1.5 rounded-lg transition font-bold {{ $statusFilter === 'all' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Todas ({{ $kpis['total_count'] }})
                </a>
                <a href="{{ route('admin.recargas.index', ['status' => 'pending', 'search' => $search]) }}"
                   class="px-3 py-1.5 rounded-lg transition font-bold flex items-center gap-1.5 {{ $statusFilter === 'pending' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    <span>Pendientes ({{ $kpis['pending_count'] }})</span>
                </a>
                <a href="{{ route('admin.recargas.index', ['status' => 'approved', 'search' => $search]) }}"
                   class="px-3 py-1.5 rounded-lg transition font-bold {{ $statusFilter === 'approved' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Aprobadas ({{ $kpis['approved_count'] }})
                </a>
                <a href="{{ route('admin.recargas.index', ['status' => 'rejected', 'search' => $search]) }}"
                   class="px-3 py-1.5 rounded-lg transition font-bold {{ $statusFilter === 'rejected' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Rechazadas ({{ $kpis['rejected_count'] }})
                </a>
            </div>

            <!-- Buscador -->
            <form action="{{ route('admin.recargas.index') }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por código, dropshipper o ref..."
                        class="bg-white border border-slate-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 w-56 sm:w-72">
                </div>
                <button type="submit" class="px-3 py-1.5 bg-[#0F172A] hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition">
                    Buscar
                </button>
                @if(!empty($search))
                    <a href="{{ route('admin.recargas.index', ['status' => $statusFilter]) }}" class="text-xs text-slate-500 hover:text-rose-600 font-semibold" title="Limpiar búsqueda">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabla de Solicitudes -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold border-b border-slate-200 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">Código</th>
                        <th class="py-3.5 px-4">Vendedor Dropshipper</th>
                        <th class="py-3.5 px-4 text-right">Monto (BOB)</th>
                        <th class="py-3.5 px-4">Método &amp; Referencia</th>
                        <th class="py-3.5 px-4">Comprobante</th>
                        <th class="py-3.5 px-4">Fecha y Hora</th>
                        <th class="py-3.5 px-4">Estado</th>
                        <th class="py-3.5 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium bg-white">
                    @forelse($topups as $topup)
                        @php
                            $isPdf = $topup->receipt_path && str_ends_with(strtolower($topup->receipt_path), '.pdf');
                            $voucherUrl = $topup->receipt_path ? asset('storage/' . $topup->receipt_path) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Código -->
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-1 rounded-md">
                                    {{ $topup->topup_number }}
                                </span>
                            </td>

                            <!-- Vendedor -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $topup->user?->name ?? 'Usuario Desconocido' }}</div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
                                    <i class="fa-solid fa-envelope text-[10px] text-slate-400"></i>
                                    <span>{{ $topup->user?->email }}</span>
                                </div>
                            </td>

                            <!-- Monto BOB -->
                            <td class="py-3.5 px-4 text-right">
                                <span class="font-mono font-black text-emerald-600 text-sm">
                                    Bs. {{ number_format((float) $topup->amount_bob, 2) }}
                                </span>
                            </td>

                            <!-- Método & Ref -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                    @if(in_array($topup->payment_method, ['qr_simple', 'qr_bolivia']))
                                        <i class="fa-solid fa-qrcode text-sky-600"></i>
                                        <span>QR Simple</span>
                                    @else
                                        <i class="fa-solid fa-building-columns text-indigo-600"></i>
                                        <span>Transferencia Bancaria</span>
                                    @endif
                                </div>
                                <div class="text-[11px] font-mono text-slate-500">
                                    Ref: <strong class="text-slate-800">{{ $topup->bank_reference ?? 'S/R' }}</strong>
                                </div>
                            </td>

                            <!-- Comprobante / Voucher -->
                            <td class="py-3.5 px-4">
                                @if($voucherUrl)
                                    <button type="button"
                                        @click="viewReceipt('{{ $voucherUrl }}', '{{ $isPdf ? 'pdf' : 'image' }}', '{{ $topup->topup_number }}', '{{ number_format($topup->amount_bob, 2) }}', '{{ $topup->user?->name }}', '{{ $topup->bank_reference }}')"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $isPdf ? 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' : 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100' }}">
                                        @if($isPdf)
                                            <i class="fa-solid fa-file-pdf text-red-600"></i>
                                            <span>Ver PDF</span>
                                        @else
                                            <i class="fa-solid fa-image text-indigo-600"></i>
                                            <span>Ver Voucher</span>
                                        @endif
                                    </button>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Sin archivo</span>
                                @endif
                            </td>

                            <!-- Fecha -->
                            <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                <div>{{ $topup->created_at->format('d/m/Y') }}</div>
                                <div class="font-mono text-slate-400">{{ $topup->created_at->format('H:i') }}</div>
                            </td>

                            <!-- Estado -->
                            <td class="py-3.5 px-4">
                                @if($topup->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-amber-100 text-amber-800 border border-amber-200">
                                        <i class="fa-solid fa-clock text-[9px]"></i> Pendiente
                                    </span>
                                @elseif($topup->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <i class="fa-solid fa-check text-[9px]"></i> Aprobada
                                    </span>
                                @elseif($topup->status === 'rejected')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-rose-100 text-rose-800 border border-rose-200" title="{{ $topup->admin_notes }}">
                                        <i class="fa-solid fa-xmark text-[9px]"></i> Rechazada
                                    </span>
                                @endif
                            </td>

                            <!-- Acciones -->
                            <td class="py-3.5 px-4 text-center">
                                @if($topup->status === 'pending')
                                    <div class="flex items-center justify-center gap-1.5">
                                        
                                        <!-- Botón Aprobar -->
                                        <form action="{{ route('admin.recargas.approve', $topup->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" onclick="return confirm('¿Confirmas que verificaste el abono de Bs. {{ number_format($topup->amount_bob, 2) }} en la cuenta bancaria de Dropana?')"
                                                class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs flex items-center gap-1 transition cursor-pointer"
                                                title="Acreditar saldo inmediatamente a la billetera">
                                                <i class="fa-solid fa-check"></i> Aprobar
                                            </button>
                                        </form>

                                        <!-- Botón Rechazar -->
                                        <button type="button"
                                            @click="openRejectModal('{{ $topup->id }}', '{{ $topup->topup_number }}', '{{ $topup->user?->name }}', '{{ number_format($topup->amount_bob, 2) }}')"
                                            class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs flex items-center gap-1 transition cursor-pointer"
                                            title="Rechazar solicitud con motivo">
                                            <i class="fa-solid fa-xmark"></i> Rechazar
                                        </button>
                                    </div>
                                @elseif($topup->status === 'approved')
                                    <div class="text-[11px] text-slate-500">
                                        <div class="text-emerald-700 font-semibold">Acreditado</div>
                                        <div class="text-[10px] text-slate-400">{{ $topup->approved_at?->format('d/m H:i') }}</div>
                                    </div>
                                @elseif($topup->status === 'rejected')
                                    <div class="text-[11px] text-slate-500 max-w-[150px] truncate" title="{{ $topup->admin_notes }}">
                                        <span class="text-rose-600 font-semibold">Motivo:</span> {{ $topup->admin_notes ?? 'No especificado' }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl">
                                    <i class="fa-solid fa-inbox"></i>
                                </div>
                                <div class="font-bold text-slate-600">No se encontraron solicitudes de recarga.</div>
                                <p class="text-xs text-slate-400 mt-0.5">Las solicitudes enviadas por los dropshippers aparecerán aquí para su validación.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($topups->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $topups->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: VISOR DE COMPROBANTE / VOUCHER (IMAGEN O PDF)                    -->
    <!-- ========================================================================= -->
    <div x-show="receiptModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-start sm:items-center justify-center p-3 sm:p-5 md:p-6 py-6 sm:py-8">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-5 sm:p-6 shadow-2xl border border-slate-200 space-y-4 my-auto relative"
             @click.away="receiptModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-extrabold text-slate-900 font-heading text-base flex items-center gap-2">
                        <span>Comprobante de Recarga</span>
                        <span class="font-mono text-emerald-600" x-text="'#' + activeTopup.number"></span>
                    </h3>
                    <p class="text-xs text-slate-500">
                        Vendedor: <strong class="text-slate-800" x-text="activeTopup.seller"></strong> • 
                        Monto: <strong class="text-emerald-600 font-mono" x-text="'Bs. ' + activeTopup.amount"></strong> • 
                        Ref: <strong class="font-mono text-slate-700" x-text="activeTopup.reference"></strong>
                    </p>
                </div>
                <button type="button" @click="receiptModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Contenedor del comprobante -->
            <div class="bg-slate-50 rounded-2xl p-2 flex items-center justify-center border border-slate-200 overflow-hidden min-h-[300px] max-h-[550px]">
                <!-- Imagen -->
                <template x-if="activeTopup.type === 'image'">
                    <img :src="activeTopup.url" alt="Voucher de transferencia" class="max-h-[520px] max-w-full object-contain rounded-xl shadow-xs">
                </template>

                <!-- PDF -->
                <template x-if="activeTopup.type === 'pdf'">
                    <div class="w-full h-[500px] flex flex-col items-center justify-center space-y-3">
                        <iframe :src="activeTopup.url" class="w-full h-full rounded-xl border border-slate-200"></iframe>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <a :href="activeTopup.url" target="_blank" download class="text-xs font-bold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir archivo original en nueva pestaña
                </a>
                <button type="button" @click="receiptModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer">
                    Cerrar Visor
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: RECHAZAR SOLICITUD DE RECARGA CON MOTIVO                         -->
    <!-- ========================================================================= -->
    <div x-show="rejectModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-start sm:items-center justify-center p-3 sm:p-5 md:p-6 py-6 sm:py-8">
        <div class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-rose-200 space-y-4 my-auto relative"
             @click.away="rejectModalOpen = false">
            
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 font-heading text-base">Rechazar Solicitud de Recarga</h3>
                    <p class="text-xs text-slate-500" x-text="'Solicitud #' + rejectData.number + ' de ' + rejectData.seller"></p>
                </div>
            </div>

            <form :action="'{{ url('/admin/recargas') }}/' + rejectData.id + '/reject'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Motivo del rechazo (Se notificará al vendedor):</label>
                    <textarea name="reason" rows="3" required
                        placeholder="Ej: Número de referencia no encontrado en el extracto bancario de Dropana."
                        class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-xs text-slate-800 focus:outline-none focus:border-rose-500"></textarea>
                </div>

                <!-- Motivos rápidos -->
                <div class="space-y-1.5">
                    <span class="text-[11px] font-bold text-slate-500">Motivos frecuentes:</span>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" @click="$el.closest('form').querySelector('textarea').value = 'El número de referencia bancaria no figura en el extracto oficial.'"
                            class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium cursor-pointer">
                            Referencia no encontrada
                        </button>
                        <button type="button" @click="$el.closest('form').querySelector('textarea').value = 'El comprobante adjunto es ilegible o está cortado. Por favor sube un nuevo archivo.'"
                            class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium cursor-pointer">
                            Comprobante ilegible
                        </button>
                        <button type="button" @click="$el.closest('form').querySelector('textarea').value = 'El monto transferido no coincide con el importe declarado en la solicitud.'"
                            class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium cursor-pointer">
                            Monto no coincide
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-sm cursor-pointer">
                        Confirmar Rechazo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: AJUSTES DE ACREDITACIÓN, CORREO OFICIAL & CÓDIGO QR ADMIN        -->
    <!-- ========================================================================= -->
    <div x-show="settingsModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-start sm:items-center justify-center p-3 sm:p-5 md:p-6 py-6 sm:py-8">
        <div class="bg-white rounded-3xl max-w-xl w-full p-5 sm:p-7 shadow-2xl border border-slate-200 space-y-4 my-auto relative"
             @click.away="settingsModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 font-heading text-base">Ajustes de Acreditación Bancaria &amp; QR</h3>
                        <p class="text-xs text-slate-500">Configura la cuenta y el código QR que verán los dropshippers al recargar.</p>
                    </div>
                </div>
                <button type="button" @click="settingsModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.recargas.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                
                <!-- Correo del Administrador -->
                <div>
                    <label class="block font-bold text-slate-800 mb-1">Correo Electrónico para Recibir Solicitudes (Admin):</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" name="admin_notification_email" required
                            value="{{ $financialSettings->getEffectiveAdminEmail() }}"
                            placeholder="admin@dropana.bo"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-medium focus:outline-none focus:border-indigo-500">
                    </div>
                    <p class="text-[10px] text-slate-500 mt-1">El sistema enviará el voucher y detalles de cada recarga a este correo para su validación.</p>
                </div>

                <!-- Datos de la Cuenta Oficial -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Banco Oficial:</label>
                        <input type="text" name="bank_name" required value="{{ $financialSettings->bank_name ?? 'Banco Nacional de Bolivia / BCP' }}"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Número de Cuenta:</label>
                        <input type="text" name="bank_account_number" required value="{{ $financialSettings->bank_account_number ?? '1000-482910-BOB' }}"
                            class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-mono focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Titular de la Cuenta:</label>
                    <input type="text" name="bank_account_name" required value="{{ $financialSettings->bank_account_name ?? 'Dropana FinTech Logística Bolivia S.R.L.' }}"
                        class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 focus:outline-none focus:border-indigo-500">
                </div>

                <!-- Subir Imagen del Código QR -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                    <label class="block font-bold text-slate-800">Código QR Oficial para Acreditaciones:</label>
                    <div class="flex items-center gap-4">
                        <img src="{{ $financialSettings->getEffectiveQrImageUrl() }}" alt="Código QR Actual"
                            class="w-20 h-20 rounded-xl border border-slate-200 bg-white object-contain p-1 shadow-xs">
                        <div class="flex-1 space-y-1">
                            <input type="file" name="qr_image" accept="image/*"
                                class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                            <p class="text-[10px] text-slate-500">Sube la captura de tu QR Simple (BCP, BNB, Banco Unión o BNB) en formato PNG o JPG.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Instrucciones para el Dropshipper:</label>
                    <textarea name="recharge_instructions" rows="2"
                        class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 text-xs focus:outline-none focus:border-indigo-500">{{ $financialSettings->recharge_instructions }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="settingsModalOpen = false" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Ajustes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function topupManager() {
    return {
        receiptModalOpen: false,
        rejectModalOpen: false,
        settingsModalOpen: false,
        activeTopup: {
            url: '',
            type: 'image',
            number: '',
            amount: '',
            seller: '',
            reference: ''
        },
        rejectData: {
            id: '',
            number: '',
            seller: '',
            amount: ''
        },
        viewReceipt(url, type, number, amount, seller, reference) {
            this.activeTopup = { url, type, number, amount, seller, reference };
            this.receiptModalOpen = true;
        },
        openRejectModal(id, number, seller, amount) {
            this.rejectData = { id, number, seller, amount };
            this.rejectModalOpen = true;
        }
    };
}
</script>
@endpush
@endsection
