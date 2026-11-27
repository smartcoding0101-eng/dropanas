@extends('layouts.app')

@section('title', 'Panel Dropshipper | Bolidrop Bolivia')

@section('content')
<div class="space-y-8" x-data="dropshipperDashboard()">

    @if($isAdmin ?? false)
    <!-- BARRA DE AUDITORÍA & CONTROL ADMINISTRATIVO GLOBAL -->
    <div class="bg-gradient-to-r from-emerald-950 via-slate-900 to-emerald-950 border border-emerald-500/40 rounded-2xl p-4 text-white shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div class="text-xs font-bold text-emerald-300 uppercase tracking-wider flex items-center gap-2">
                    <span>Modo Supervisión y Control Administrativo</span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] bg-emerald-400 text-slate-950 font-extrabold">SUPER ADMIN</span>
                </div>
                <div class="text-xs text-slate-200">
                    Inspeccionando tienda y billetera de: <strong class="text-white">{{ $dropshipper?->name }}</strong> ({{ $dropshipper?->email }})
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            @if(isset($allDropshippers) && $allDropshippers->count() > 1)
            <div class="flex items-center gap-2">
                <label class="text-[11px] text-slate-300 font-semibold">Cambiar Vendedor:</label>
                <select onchange="window.location.href='{{ url('/panel/dropshipper') }}?dropshipper_id=' + this.value"
                    class="bg-slate-800 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl px-3 py-1.5 focus:outline-none">
                    @foreach($allDropshippers as $ad)
                        <option value="{{ $ad->id }}" {{ ($dropshipper && $dropshipper->id === $ad->id) ? 'selected' : '' }}>
                            {{ $ad->name }} ({{ $ad->company_or_store_name ?? 'Tienda' }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <a href="{{ url('/panel/admin') }}" class="px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-bold text-white transition inline-flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i> Volver a Panel Admin
            </a>
        </div>
    </div>
    @endif

    <x-page-header
        title="Panel del Dropshipper"
        subtitle="Monitorea tus pedidos contra entrega en Bolivia, ganancias líquidas y estado de recaudación de DEX."
        icon="fa-solid fa-store"
        :badge="'Vendedor Dropshipper • ID: ' . substr($dropshipper->id ?? 'DEMO', 0, 8)">
        <x-slot:actions>
            <button @click="withdrawModalOpen = true"
                class="px-4 py-2.5 rounded-xl font-bold text-xs bg-white/10 hover:bg-white/20 border border-white/20 text-white backdrop-blur-sm flex items-center gap-2 transition">
                <i class="fa-solid fa-hand-holding-dollar text-emerald-300"></i> Retirar Fondos
            </button>
            <a href="{{ url('/checkout-demo') }}" class="btn-scarlet px-5 py-2.5 rounded-xl font-bold text-xs shadow-md flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Nuevo Pedido COD
            </a>
            <a href="{{ url('/catalogo') }}" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-xs transition flex items-center gap-2 backdrop-blur-sm">
                <i class="fa-solid fa-boxes-stacked text-emerald-300"></i> Catálogo
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Mensaje de Notificación Toast -->
    <div x-show="toast.show" x-cloak
        x-transition
        class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between shadow-md"
        :class="toast.type === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800'">
        <div class="flex items-center gap-2">
            <i class="fa-solid" :class="toast.type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-600'"></i>
            <span x-text="toast.message"></span>
        </div>
        <button @click="toast.show = false" class="text-slate-400 hover:text-slate-600">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Tarjetas de Métricas & Rendimiento (SB Admin Pro Overlap) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 !-mt-14 relative z-10">
        
        <!-- Total Pedidos -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3 hover:border-emerald-400 hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-slate-900 font-bold uppercase tracking-wider">Total Pedidos</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 flex items-center justify-center">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 font-heading" data-countup>
                {{ $stats['total_orders'] }}
            </div>
            <div class="text-[11px] text-slate-500">Histórico de órdenes registradas</div>
        </div>

        <!-- Pedidos En Camino -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3 interactive-card">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-[#4F46E5] font-bold uppercase tracking-wider">En Camino / Pendientes</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-[#4F46E5] border border-indigo-200 flex items-center justify-center">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#4F46E5] font-heading font-mono" data-countup>
                {{ $stats['pending_orders'] }}
            </div>
            <div class="text-[11px] text-amber-600 font-medium">Por recaudar en destino COD</div>
        </div>

        <!-- Pedidos Liquidados -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3 interactive-card">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-emerald-700 font-bold uppercase tracking-wider">Entregados & Liquidados</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center">
                    <i class="fa-solid fa-check-double"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-emerald-600 font-heading" data-countup>
                {{ $stats['settled_orders'] }}
            </div>
            <div class="text-[11px] text-emerald-600 font-medium">Fondos acreditados con éxito</div>
        </div>

        <!-- Ganancia Acumulada -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3 interactive-card">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-[#4F46E5] font-bold uppercase tracking-wider">Ganancia Neta Total</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-[#4F46E5] border border-indigo-200 flex items-center justify-center">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 font-heading font-mono" data-countup>
                Bs {{ number_format($stats['total_profit'], 2) }}
            </div>
            <div class="text-[11px] text-slate-500">Tu beneficio líquido en BOB</div>
        </div>

    </div>

    <!-- Billetera Digital & Saldo Retirable (#FFFFFF) -->
    @if($wallet)
    <div class="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-slate-200 pb-6">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-900 bg-red-50 border border-red-200 px-2.5 py-1 rounded-full">
                    Billetera Virtual Bolidrop
                </span>
                <h3 class="text-xl font-bold text-slate-900 font-heading mt-2">
                    Saldo y Fondos Disponibles
                </h3>
                <p class="text-xs text-slate-500">
                    Retira tus utilidades al instante mediante QR interoperable bancario o Binance USDT.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <button @click="topupModalOpen = true"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-sm flex items-center gap-2 transition cursor-pointer">
                    <i class="fa-solid fa-qrcode text-amber-200"></i>
                    <span>Recargar Saldo (QR / Banco)</span>
                </button>
                <button @click="withdrawModalOpen = true"
                    class="px-4 py-2.5 rounded-xl bg-[#0F172A] hover:bg-slate-800 text-white text-xs font-bold shadow-sm flex items-center gap-2 transition cursor-pointer">
                    <i class="fa-solid fa-arrow-up-from-bracket text-sky-300"></i>
                    <span>Solicitar Retiro</span>
                </button>
                <span class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 font-mono font-bold text-slate-900 text-xs">
                    BOB (Bolivianos)
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-1">
                <div class="text-xs text-slate-500 font-medium">Saldo Disponible para Retiro</div>
                <div class="text-3xl font-extrabold text-emerald-600 font-mono">
                    Bs {{ number_format($wallet->balance_available, 2) }}
                </div>
                <div class="text-[11px] text-slate-400">Listo para transferir a tu cuenta bancaria</div>
            </div>

            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-1">
                <div class="text-xs text-slate-500 font-medium">Saldo Retenido (En Camino COD)</div>
                <div class="text-3xl font-extrabold text-[#0061f2] font-mono">
                    Bs {{ number_format($wallet->balance_pending, 2) }}
                </div>
                <div class="text-[11px] text-slate-400">Se libera cuando el courier confirme entrega</div>
            </div>

            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-1">
                <div class="text-xs text-slate-500 font-medium">Total Histórico Retirado</div>
                <div class="text-3xl font-extrabold text-slate-800 font-mono">
                    Bs {{ number_format($wallet->balance_withdrawn, 2) }}
                </div>
                <div class="text-[11px] text-slate-400">Transferencias exitosas realizadas</div>
            </div>
        </div>

        <!-- Historial de Recargas Solicitadas -->
        @if($walletTopups->isNotEmpty())
        <div class="border-t border-slate-100 pt-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-indigo-600"></i>
                    <span>Tus Solicitudes de Recarga Recientes</span>
                </span>
                <span class="text-[11px] text-slate-400">Verificadas por el Administrador</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                        <tr>
                            <th class="py-2.5 px-3">Código</th>
                            <th class="py-2.5 px-3">Fecha</th>
                            <th class="py-2.5 px-3">Método</th>
                            <th class="py-2.5 px-3 text-right">Monto</th>
                            <th class="py-2.5 px-3">Referencia</th>
                            <th class="py-2.5 px-3">Comprobante</th>
                            <th class="py-2.5 px-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($walletTopups as $topup)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-mono font-bold text-slate-900">{{ $topup->topup_number }}</td>
                            <td class="py-2 px-3 text-slate-500">{{ $topup->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-2 px-3 font-semibold">{{ $topup->payment_method === 'qr_bolivia' ? 'QR Simple' : 'Transferencia Bancaria' }}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">+Bs. {{ number_format($topup->amount_bob, 2) }}</td>
                            <td class="py-2 px-3 font-mono text-slate-600">{{ $topup->bank_reference ?? 'N/A' }}</td>
                            <td class="py-2 px-3">
                                @if($topup->receipt_path)
                                    <a href="{{ asset('storage/' . $topup->receipt_path) }}" target="_blank" class="text-indigo-600 hover:underline font-bold">
                                        <i class="fa-solid fa-file-invoice"></i> Ver
                                    </a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-center">
                                @if($topup->status === 'approved')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Aprobada</span>
                                @elseif($topup->status === 'rejected')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">Rechazada</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 animate-pulse">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Tabla de Pedidos Recientes (#FFFFFF) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50/60">
            <div>
                <h3 class="text-lg font-bold text-slate-900 font-heading">
                    Tus Pedidos Recientes
                </h3>
                <p class="text-xs text-slate-500">Flujo completo con número de guía y estado en tiempo real</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-white border border-slate-200 text-slate-900 font-mono">
                {{ $recentOrders->count() }} órdenes registradas
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-5">Pedido</th>
                        <th class="py-3.5 px-5">Cliente</th>
                        <th class="py-3.5 px-5">Destino</th>
                        <th class="py-3.5 px-5">Courier / Guía</th>
                        <th class="py-3.5 px-5">Total COD</th>
                        <th class="py-3.5 px-5">Tu Ganancia</th>
                        <th class="py-3.5 px-5">Estado</th>
                        <th class="py-3.5 px-5 text-right">Rótulo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-5 font-mono text-xs font-bold text-slate-900">
                                #{{ $order->order_number }}
                                <div class="text-[10px] text-slate-400 font-normal">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-900">{{ $order->customer_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $order->customer_phone }}</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="text-xs text-slate-700 font-medium">{{ $order->shipping_city }}</div>
                                <div class="text-[11px] text-slate-400">{{ $order->shipping_department }}</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="text-xs font-bold text-[#0061f2]">{{ $order->carrier->name ?? 'DEX Bolivia' }}</div>
                                <div class="text-[10px] font-mono text-slate-400">{{ $order->tracking_number ?? 'Por generar' }}</div>
                            </td>
                            <td class="py-4 px-5 font-mono font-bold text-slate-900 text-xs">
                                Bs {{ number_format($order->total_amount_bob, 2) }}
                            </td>
                            <td class="py-4 px-5 font-mono font-extrabold text-emerald-600 text-xs">
                                +Bs {{ number_format($order->dropshipper_profit_bob, 2) }}
                            </td>
                            <td class="py-4 px-5">
                                @if($order->status === 'settled')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 border border-emerald-200 text-emerald-700">
                                        <i class="fa-solid fa-check-double mr-1.5"></i> Liquidado
                                    </span>
                                @elseif($order->status === 'delivered')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 border border-sky-200 text-sky-700">
                                        <i class="fa-solid fa-box mr-1.5"></i> Entregado
                                    </span>
                                @elseif(in_array($order->status, ['dispatched', 'in_transit']))
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 border border-amber-200 text-amber-800">
                                        <i class="fa-solid fa-truck-fast mr-1.5 text-amber-600"></i> En Tránsito DEX
                                    </span>
                                @elseif($order->status === 'confirmed')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 border border-indigo-200 text-indigo-800">
                                        <i class="fa-solid fa-check mr-1.5 text-[#4F46E5]"></i> Confirmado
                                    </span>
                                @elseif($order->status === 'pending_confirmation')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 border border-amber-200 text-amber-800">
                                        <i class="fa-solid fa-clock mr-1.5 text-amber-600"></i> Pendiente Confirmación
                                    </span>
                                @elseif($order->status === 'failed_delivery')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-800">
                                        <i class="fa-solid fa-triangle-exclamation mr-1.5 text-rose-600"></i> Entrega Fallida
                                    </span>
                                @elseif($order->status === 'cancelled')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 border border-slate-200 text-slate-600">
                                        <i class="fa-solid fa-ban mr-1.5"></i> Cancelado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-5 text-right">
                                <a href="{{ route('orders.label', $order->id) }}" target="_blank"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 inline-flex items-center gap-1 transition"
                                    title="Imprimir Rótulo Térmico">
                                    <i class="fa-solid fa-print text-[#0061f2]"></i>
                                    <span>Imprimir</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                                Aún no has registrado pedidos. Usa el botón "Nuevo Pedido COD" para simular el primero.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECCIÓN: INTEGRACIONES CON SHOPIFY & WOOCOMMERCE -->
    <div class="white-card rounded-2xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h2 class="text-lg font-bold text-slate-900">Integración de Tiendas Online (Sincronización Automática)</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Conecta tu tienda Shopify o WooCommerce. Los pedidos que ingresen en tu web se importarán a Bolidrop Bolivia listos para despacho COD.
                </p>
            </div>
            <button @click="integrationModalOpen = true"
                class="btn-scarlet px-4 py-2 rounded-xl text-xs font-bold shadow-sm flex items-center gap-2 shrink-0">
                <i class="fa-solid fa-plug text-amber-200"></i>
                <span>Conectar Nueva Tienda</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($storeIntegrations as $store)
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-emerald-500 transition space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm {{ $store->platform === 'shopify' ? 'bg-[#95BF47]' : 'bg-[#96588A]' }}">
                                <i class="{{ $store->platform === 'shopify' ? 'fa-brands fa-shopify' : 'fa-brands fa-wordpress' }}"></i>
                            </span>
                            <div>
                                <div class="font-bold text-slate-900 text-xs">{{ $store->store_name }}</div>
                                <div class="text-[10px] text-slate-400 truncate max-w-[150px]">{{ $store->store_url }}</div>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Activo
                        </span>
                    </div>

                    <div class="pt-2 border-t border-slate-200/60 text-[11px] space-y-1">
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Despacho DEX Auto:</span>
                            <strong class="{{ $store->auto_dispatch ? 'text-emerald-600' : 'text-slate-600' }}">
                                {{ $store->auto_dispatch ? 'Habilitado' : 'Manual' }}
                            </strong>
                        </div>
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Última Sincronización:</span>
                            <span class="font-mono">{{ $store->last_synced_at ? $store->last_synced_at->format('d/m/Y H:i') : 'En espera' }}</span>
                        </div>
                    </div>

                    <div class="bg-white p-2 rounded-lg border border-slate-200 text-[10px] space-y-1 font-mono">
                        <div class="text-slate-400 font-sans font-semibold">URL de Webhook:</div>
                        <div class="text-slate-700 truncate select-all">{{ url("/api/v1/webhooks/{$store->platform}/{$store->webhook_secret}") }}</div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-2xl">
                    <i class="fa-solid fa-cloud-arrow-down text-3xl text-slate-300 mb-2 block"></i>
                    <p class="text-xs">No tienes tiendas conectadas aún. Haz clic en "Conectar Nueva Tienda" para configurar tu webhook.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL INTERACTIVO: SOLICITAR RECARGA DE SALDO CON QR / BANCO (DISEÑO HORIZONTAL RESPONSIVO) -->
    <div x-show="topupModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-start sm:items-center justify-center p-3 sm:p-5 md:p-6 py-6 sm:py-8">
        
        <div class="bg-white rounded-3xl max-w-4xl w-full p-5 sm:p-7 shadow-2xl border border-slate-200 space-y-4 my-auto relative"
            @click.away="topupModalOpen = false">
            
            <!-- Cabecera del Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg shadow-xs">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 font-heading text-base leading-tight">Recargar Billetera Dropana</h3>
                        <p class="text-xs text-slate-500">Saldo disponible actual: <strong class="text-emerald-600 font-mono font-bold">Bs {{ number_format($wallet?->balance_available ?? 0, 2) }}</strong></p>
                    </div>
                </div>
                <button @click="topupModalOpen = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Contenido Horizontal en 2 Columnas -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
                
                <!-- COLUMNA 1: Cuentas Oficiales y Código QR Simple (lg:col-span-5) -->
                <div class="lg:col-span-5 bg-slate-50/90 border border-slate-200/90 rounded-2xl p-4 flex flex-col justify-between space-y-3.5 text-xs">
                    <div>
                        <div class="font-extrabold text-slate-900 flex items-center justify-between pb-2 border-b border-slate-200/70">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                                <span>1. Acreditación Oficial</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wide">
                                QR Simple ACH
                            </span>
                        </div>

                        <!-- Tarjeta del Código QR Centrado -->
                        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-xs text-center space-y-1.5 mt-3">
                            <img src="{{ $financialSettings?->getEffectiveQrImageUrl() }}" alt="QR Oficial Dropana"
                                class="w-32 h-32 sm:w-36 sm:h-36 mx-auto rounded-xl border border-slate-100 p-1 bg-white object-contain shadow-xs">
                            <div class="text-[11px] font-bold text-slate-800">Escanea desde tu app bancaria</div>
                            <div class="text-[10px] text-slate-500">Banco Unión, BCP, BNB, Bisa, Ganadero o GanaMóvil</div>
                        </div>

                        <!-- Datos Bancarios & Correo Admin -->
                        <div class="space-y-1.5 mt-3 text-[11px] text-slate-700">
                            <div class="flex justify-between items-center py-0.5 border-b border-slate-200/50">
                                <span class="text-slate-500">Banco:</span>
                                <strong class="text-slate-900">{{ $financialSettings->bank_name ?? 'Banco Nacional de Bolivia / BCP' }}</strong>
                            </div>
                            <div class="flex justify-between items-center py-0.5 border-b border-slate-200/50">
                                <span class="text-slate-500">Nro. Cuenta:</span>
                                <strong class="font-mono text-slate-900">{{ $financialSettings->bank_account_number ?? '1000-482910-BOB' }}</strong>
                            </div>
                            <div class="flex justify-between items-center py-0.5 border-b border-slate-200/50">
                                <span class="text-slate-500">Titular:</span>
                                <strong class="text-slate-900 text-right">{{ $financialSettings->bank_account_name ?? 'Dropana Bolivia S.R.L.' }}</strong>
                            </div>
                            <div class="flex justify-between items-center pt-1">
                                <span class="text-slate-500">Correo Admin:</span>
                                <span class="font-mono font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100 text-[10px]">
                                    {{ $financialSettings?->getEffectiveAdminEmail() ?? 'admin@dropana.bo' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="text-[10px] text-slate-500 pt-2 border-t border-slate-200/80 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-emerald-600 mr-1"></i>
                        Paga vía QR o transferencia y registra el comprobante a la derecha para su validación inmediata.
                    </p>
                </div>

                <!-- COLUMNA 2: Formulario de Recarga & Voucher (lg:col-span-7) -->
                <div class="lg:col-span-7 flex flex-col justify-between">
                    <form action="{{ route('wallet.topup.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3.5 flex flex-col justify-between h-full text-xs"
                          x-data="{ selectedFileName: '', isPdf: false }">
                        @csrf
                        <input type="hidden" name="amount" :value="$el.closest('form').querySelector('input[name=amount_bob]')?.value">

                        <div class="space-y-3">
                            <div class="font-extrabold text-slate-900 pb-2 border-b border-slate-100 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-file-invoice text-indigo-600"></i>
                                    <span>2. Datos del Depósito &amp; Comprobante</span>
                                </span>
                                <span class="text-[11px] text-slate-400 font-normal">Paso final</span>
                            </div>

                            <!-- Monto a Recargar -->
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Monto a Recargar (BOB):</label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-bold font-mono">Bs</span>
                                    <input type="number" step="0.01" min="1" name="amount_bob" required placeholder="500.00"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-slate-900 font-mono font-bold text-sm focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                                </div>
                            </div>

                            <!-- Método y Referencia en 2 Columnas -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Método Utilizado:</label>
                                    <select name="payment_method" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 text-xs font-semibold focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                                        <option value="qr_simple">Pago QR Simple (Cualquier Banco)</option>
                                        <option value="bank_transfer">Transferencia Bancaria Nacional</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Nro de Referencia Bancaria:</label>
                                    <input type="text" name="bank_reference" required placeholder="Ej: OP-984321 o Autorización"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 text-xs font-mono focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                                </div>
                            </div>

                            <!-- Adjuntar Comprobante (Imagen o PDF) -->
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Adjuntar Comprobante de Pago (Imagen o PDF):</label>
                                <input type="file" name="receipt" accept="image/jpeg,image/png,image/jpg,image/webp,application/pdf" required
                                    @change="
                                        if ($event.target.files.length > 0) {
                                            selectedFileName = $event.target.files[0].name;
                                            isPdf = selectedFileName.toLowerCase().endsWith('.pdf');
                                        } else {
                                            selectedFileName = '';
                                        }
                                    "
                                    class="w-full p-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-600 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition">
                                
                                <!-- Feedback reactivo de archivo adjunto -->
                                <template x-if="selectedFileName">
                                    <div class="mt-1.5 p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold flex items-center justify-between">
                                        <span class="flex items-center gap-1.5 truncate">
                                            <i :class="isPdf ? 'fa-solid fa-file-pdf text-red-600' : 'fa-solid fa-file-image text-emerald-600'"></i>
                                            <span x-text="selectedFileName"></span>
                                        </span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-white text-emerald-700 border border-emerald-300 font-mono" x-text="isPdf ? 'DOCUMENTO PDF' : 'IMAGEN'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                            <button type="button" @click="topupModalOpen = false" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md flex items-center gap-2 cursor-pointer transition">
                                <i class="fa-solid fa-paper-plane text-xs"></i>
                                <span>Enviar Solicitud de Recarga</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL INTERACTIVO: SOLICITUD DE RETIRO DE SALDO DISPONIBLE -->
    <div x-show="withdrawModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-6"
            @click.away="withdrawModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 font-heading text-base">Retirar Fondos de Billetera</h3>
                        <p class="text-xs text-slate-500">Saldo disponible: <strong class="text-emerald-600 font-mono">Bs {{ number_format($wallet?->balance_available ?? 0, 2) }}</strong></p>
                    </div>
                </div>
                <button @click="withdrawModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Monto a Retirar (BOB):</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-bold font-mono">Bs</span>
                        <input type="number" x-model.number="form.amount" min="10" max="{{ $wallet?->balance_available ?? 0 }}" step="1"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-slate-900 font-mono font-bold text-sm focus:outline-none focus:border-emerald-500">
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Mínimo de retiro: 10 BOB</div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Método de Transferencia:</label>
                    <select x-model="form.payout_method" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 text-xs font-semibold focus:outline-none focus:border-emerald-500">
                        <option value="qr_simple">Transferencia QR Simple (Cualquier Banco Boliviano)</option>
                        <option value="bank_transfer">Cuenta Bancaria Tradicional (BNB, BCP, Unión, Mercantil)</option>
                        <option value="binance_pay">Binance Pay (Cripto USDT)</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nombre del Titular:</label>
                        <input type="text" x-model="form.account_details.beneficiary_name" placeholder="Ej: Juan Carlos Pérez"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Carnet de Identidad (CI):</label>
                        <input type="text" x-model="form.account_details.document_id" placeholder="Ej: 7891234 SCZ"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div x-show="form.payout_method === 'bank_transfer' || form.payout_method === 'qr_simple'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Banco Destino:</label>
                        <input type="text" x-model="form.account_details.bank_name" placeholder="Banco Nacional de Bolivia (BNB)"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Número de Cuenta / Teléfono QR:</label>
                        <input type="text" x-model="form.account_details.account_number" placeholder="150-1234567 o 71234567"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div x-show="form.payout_method === 'binance_pay'">
                    <label class="block font-bold text-slate-700 mb-1">Binance Pay ID (o correo de cuenta Binance):</label>
                    <input type="text" x-model="form.account_details.binance_pay_id" placeholder="Ej: 123456789 o correo@ejemplo.com"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button @click="withdrawModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                    Cancelar
                </button>
                <button @click="submitWithdraw()" :disabled="submitting || form.amount <= 0"
                    class="btn-scarlet px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm">
                    <span x-show="!submitting"><i class="fa-solid fa-paper-plane mr-1"></i> Confirmar Retiro</span>
                    <span x-show="submitting" class="flex items-center gap-1.5">
                        <i class="fa-solid fa-spinner animate-spin"></i> Procesando...
                    </span>
                </button>
            </div>

        </div>

    <!-- MODAL INTERACTIVO: CONECTAR TIENDA SHOPIFY O WOOCOMMERCE -->
    <div x-show="integrationModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5"
            @click.away="integrationModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-900 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-plug-circle-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Conectar Tienda Online</h3>
                        <p class="text-xs text-slate-500">Shopify o WooCommerce</p>
                    </div>
                </div>
                <button @click="integrationModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="submitIntegration()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Plataforma</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="p-3 rounded-xl border-2 cursor-pointer transition text-center"
                            :class="integrationForm.platform === 'shopify' ? 'border-[#0061f2] bg-emerald-50/50 text-emerald-950 font-bold' : 'border-slate-200 text-slate-600'">
                            <input type="radio" value="shopify" x-model="integrationForm.platform" class="sr-only">
                            <i class="fa-brands fa-shopify text-lg block mb-1"></i>
                            <span class="text-xs">Shopify</span>
                        </label>
                        <label class="p-3 rounded-xl border-2 cursor-pointer transition text-center"
                            :class="integrationForm.platform === 'woocommerce' ? 'border-[#0061f2] bg-emerald-50/50 text-emerald-950 font-bold' : 'border-slate-200 text-slate-600'">
                            <input type="radio" value="woocommerce" x-model="integrationForm.platform" class="sr-only">
                            <i class="fa-brands fa-wordpress text-lg block mb-1"></i>
                            <span class="text-xs">WooCommerce</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre Identificativo de tu Tienda</label>
                    <input type="text" x-model="integrationForm.store_name" required placeholder="Ej: Bolivia Trends Shop"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs focus:bg-white focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">URL de tu Tienda</label>
                    <input type="url" x-model="integrationForm.store_url" required placeholder="https://tutienda.myshopify.com"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs focus:bg-white focus:outline-none focus:border-emerald-500">
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="auto_dispatch" x-model="integrationForm.auto_dispatch"
                        class="rounded border-slate-300 text-slate-900 focus:ring-[#0061f2]">
                    <label for="auto_dispatch" class="text-xs text-slate-700 cursor-pointer">
                        Despachar automáticamente con DEX Bolivia Express
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="integrationModalOpen = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="integrationSubmitting"
                        class="btn-scarlet px-5 py-2 rounded-xl text-xs font-bold shadow-md">
                        <span x-show="!integrationSubmitting">Crear Integración & Webhook</span>
                        <span x-show="integrationSubmitting">Guardando...</span>
                    </button>
                </div>
            </form>

        </div>

    </div>

</div>

<script>
function dropshipperDashboard() {
    return {
        topupModalOpen: false,
        withdrawModalOpen: false,
        integrationModalOpen: false,
        submitting: false,
        integrationSubmitting: false,
        toast: { show: false, message: '', type: 'success' },
        integrationForm: {
            platform: 'shopify',
            store_name: '',
            store_url: '',
            auto_dispatch: false
        },
        form: {
            amount: 50,
            payout_method: 'qr_simple',
            account_details: {
                beneficiary_name: 'Alejandro Soliz',
                document_id: '7891234 SCZ',
                bank_name: 'Banco Nacional de Bolivia (BNB)',
                account_number: '150-098765-01',
                binance_pay_id: ''
            }
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 5000);
        },

        async submitIntegration() {
            this.integrationSubmitting = true;
            try {
                const token = '{{ $apiToken ?? "" }}';

                const response = await fetch('/api/v1/store-integrations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(this.integrationForm)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.showToast('¡Tienda conectada! Usa la URL del Webhook generada.', 'success');
                    this.integrationModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    this.showToast(result.message || 'Error al conectar la tienda.', 'error');
                }
            } catch (err) {
                this.showToast('Error de conexión con el servidor.', 'error');
            } finally {
                this.integrationSubmitting = false;
            }
        },

        async submitWithdraw() {
            this.submitting = true;

            try {
                const token = '{{ $apiToken ?? "" }}';

                const response = await fetch('/api/v1/wallet/withdraw', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(this.form)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.showToast(result.message || 'Solicitud de retiro procesada correctamente.', 'success');
                    this.withdrawModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1500);
                } else {
                    this.showToast(result.message || 'Error al procesar retiro.', 'error');
                }
            } catch (err) {
                this.showToast('Error de conexión con el servidor.', 'error');
            } finally {
                this.submitting = false;
            }
        }
    }
}
</script>
@endsection



