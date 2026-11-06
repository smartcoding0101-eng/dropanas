@extends('layouts.app')

@section('title', 'Notificaciones Transaccionales WhatsApp | Dropana Bolivia')

@section('content')
<div class="space-y-6">

    <!-- Header con Badges Bolivia -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                    <i class="fa-brands fa-whatsapp text-emerald-600 mr-1"></i> WhatsApp Business API Bolivia (+591)
                </span>
                <span class="text-xs text-slate-500 font-medium">Automatización Transaccional COD</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#960016] font-heading">
                Centro de Notificaciones WhatsApp
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">
                Auditoría en tiempo real de mensajes de confirmación de pedido, aviso de despacho DEX y rescate ante novedades.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1.5 animate-pulse"></span> Gateway Activo: Meta Cloud API (+591)
            </span>
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

    <!-- Tarjetas de Plantillas Activas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($templates as $key => $tpl)
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-2 hover:border-emerald-400 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">
                        <i class="fa-solid fa-message mr-1"></i> {{ $tpl['name'] }}
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        {{ $key }}
                    </span>
                </div>
                <div class="text-[11px] text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100 whitespace-pre-line line-clamp-4 font-mono text-[10px]">
                    {{ $tpl['body'] }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- Historial y Auditoría de Mensajes -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                <span>Bitácora de Envíos Realizados ({{ $logs->total() }})</span>
            </div>
        </div>

        @if($logs->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100/75 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                            <th class="p-4">Destinatario</th>
                            <th class="p-4">Plantilla / Evento</th>
                            <th class="p-4">Contenido del Mensaje</th>
                            <th class="p-4">Gateway / Fecha</th>
                            <th class="p-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($logs as $log)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="p-4 font-mono">
                                    <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                        <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                                        <span>{{ $log->recipient }}</span>
                                    </div>
                                    @if($log->reference_id)
                                        <div class="text-[10px] text-slate-400 mt-0.5">Ref: #{{ $log->metadata['order_number'] ?? substr($log->reference_id, 0, 8) }}</div>
                                    @endif
                                </td>

                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        {{ $templates[$log->event_type]['name'] ?? $log->event_type }}
                                    </span>
                                </td>

                                <td class="p-4 max-w-md">
                                    <div class="text-[11px] text-slate-700 whitespace-pre-line line-clamp-2" title="{{ $log->body }}">
                                        {{ $log->body }}
                                    </div>
                                </td>

                                <td class="p-4 text-slate-500 text-[11px]">
                                    <div class="font-semibold text-slate-700">{{ $log->metadata['provider_gateway'] ?? 'WhatsApp Gateway' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                </td>

                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-check-double mr-1"></i> Enviado
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @else
            <div class="p-12 text-center text-slate-400 space-y-2">
                <i class="fa-brands fa-whatsapp text-5xl text-emerald-300"></i>
                <p class="text-sm font-semibold text-slate-600">No hay notificaciones de WhatsApp registradas recientemente.</p>
                <p class="text-xs text-slate-400">Los mensajes se enviarán y auditarán automáticamente cuando se generen pedidos, despachos o novedades.</p>
            </div>
        @endif
    </div>

</div>
@endsection
