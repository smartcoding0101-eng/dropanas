@extends('layouts.app')

@section('title', 'Panel Dropshipper | Dropana Bolivia')

@section('content')
<div class="space-y-8 bg-[#FFFFFF]" x-data="dropshipperDashboard()">

    <!-- Header del panel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-slate-200 pb-6">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-[#960016] border border-red-200 uppercase tracking-wider">
                    <i class="fa-solid fa-store mr-1.5 text-[#ED2100]"></i> Vendedor Dropshipper
                </span>
                <span class="text-xs text-slate-400 font-mono">ID: {{ substr($dropshipper->id ?? 'DEMO', 0, 8) }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#960016] font-heading">
                Panel del Dropshipper
            </h1>
            <p class="text-slate-500 text-sm mt-1">
                Monitorea tus pedidos contra entrega en Bolivia, ganancias líquidas y estado de recaudación de DEX.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button @click="withdrawModalOpen = true"
                class="px-4 py-2.5 rounded-xl font-bold text-xs bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center gap-2 transition">
                <i class="fa-solid fa-hand-holding-dollar"></i> Retirar Fondos
            </button>
            <a href="{{ url('/checkout-demo') }}" class="btn-scarlet px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-plus text-amber-200"></i> Nuevo Pedido COD
            </a>
            <a href="{{ url('/catalogo') }}" class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-semibold text-xs transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-boxes-stacked text-[#E88A38]"></i> Catálogo
            </a>
        </div>
    </div>

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

    <!-- Tarjetas de Métricas & Rendimiento (#FFFFFF) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Total Pedidos -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3 hover:border-red-400 hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-[#960016] font-bold uppercase tracking-wider">Total Pedidos</span>
                <div class="w-9 h-9 rounded-xl bg-red-50 text-[#ED2100] border border-red-200 flex items-center justify-center">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#960016] font-heading">
                {{ $stats['total_orders'] }}
            </div>
            <div class="text-[11px] text-slate-500">Histórico de órdenes registradas</div>
        </div>

        <!-- Pedidos En Camino -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3 hover:border-amber-400 hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-[#E88A38] font-bold uppercase tracking-wider">En Camino / Pendientes</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-[#E88A38] border border-amber-200 flex items-center justify-center">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#E88A38] font-heading font-mono">
                {{ $stats['pending_orders'] }}
            </div>
            <div class="text-[11px] text-amber-600 font-medium">Por recaudar en destino COD</div>
        </div>

        <!-- Pedidos Liquidados -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3 hover:border-emerald-400 hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-emerald-700 font-bold uppercase tracking-wider">Entregados & Liquidados</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center">
                    <i class="fa-solid fa-check-double"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-emerald-600 font-heading">
                {{ $stats['settled_orders'] }}
            </div>
            <div class="text-[11px] text-emerald-600 font-medium">Fondos acreditados con éxito</div>
        </div>

        <!-- Ganancia Acumulada -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3 hover:border-amber-400 hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                <span class="text-[#E88A38] font-bold uppercase tracking-wider">Ganancia Neta Total</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-[#E88A38] border border-amber-200 flex items-center justify-center">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#960016] font-heading font-mono">
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
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#960016] bg-red-50 border border-red-200 px-2.5 py-1 rounded-full">
                    Billetera Virtual Dropana
                </span>
                <h3 class="text-xl font-bold text-[#960016] font-heading mt-2">
                    Saldo y Fondos Disponibles
                </h3>
                <p class="text-xs text-slate-500">
                    Retira tus utilidades al instante mediante QR interoperable bancario o Binance USDT.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button @click="withdrawModalOpen = true"
                    class="btn-scarlet px-4 py-2 rounded-xl text-xs font-bold shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Solicitar Retiro
                </button>
                <span class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 font-mono font-bold text-[#960016] text-xs">
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
                <div class="text-3xl font-extrabold text-[#E88A38] font-mono">
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
    </div>
    @endif

    <!-- Tabla de Pedidos Recientes (#FFFFFF) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50/60">
            <div>
                <h3 class="text-lg font-bold text-[#960016] font-heading">
                    Tus Pedidos Recientes
                </h3>
                <p class="text-xs text-slate-500">Flujo completo con número de guía y estado en tiempo real</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-white border border-slate-200 text-[#960016] font-mono">
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
                            <td class="py-4 px-5 font-mono text-xs font-bold text-[#960016]">
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
                                <div class="text-xs font-bold text-[#E88A38]">{{ $order->carrier->name ?? 'DEX Bolivia' }}</div>
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
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 border border-blue-200 text-blue-700">
                                        <i class="fa-solid fa-box mr-1.5"></i> Entregado
                                    </span>
                                @elseif($order->status === 'dispatched')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 border border-amber-200 text-amber-800">
                                        <i class="fa-solid fa-truck-fast mr-1.5 text-[#E88A38]"></i> Despachado
                                    </span>
                                @elseif($order->status === 'confirmed')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 border border-red-200 text-[#960016]">
                                        <i class="fa-solid fa-check mr-1.5 text-[#ED2100]"></i> Confirmado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ $order->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-5 text-right">
                                <a href="{{ route('orders.label', $order->id) }}" target="_blank"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 inline-flex items-center gap-1 transition"
                                    title="Imprimir Rótulo Térmico">
                                    <i class="fa-solid fa-print text-[#ED2100]"></i>
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
                    <h2 class="text-lg font-bold text-[#960016]">Integración de Tiendas Online (Sincronización Automática)</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Conecta tu tienda Shopify o WooCommerce. Los pedidos que ingresen en tu web se importarán a Dropana Bolivia listos para despacho COD.
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
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-[#E88A38] transition space-y-3">
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
                        <h3 class="font-bold text-[#960016] font-heading text-base">Retirar Fondos de Billetera</h3>
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
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-slate-900 font-mono font-bold text-sm focus:outline-none focus:border-[#E88A38]">
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Mínimo de retiro: 10 BOB</div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Método de Transferencia:</label>
                    <select x-model="form.payout_method" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 text-xs font-semibold focus:outline-none focus:border-[#E88A38]">
                        <option value="qr_simple">Transferencia QR Simple (Cualquier Banco Boliviano)</option>
                        <option value="bank_transfer">Cuenta Bancaria Tradicional (BNB, BCP, Unión, Mercantil)</option>
                        <option value="binance_pay">Binance Pay (Cripto USDT)</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nombre del Titular:</label>
                        <input type="text" x-model="form.account_details.beneficiary_name" placeholder="Ej: Juan Carlos Pérez"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-[#E88A38]">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Carnet de Identidad (CI):</label>
                        <input type="text" x-model="form.account_details.document_id" placeholder="Ej: 7891234 SCZ"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-[#E88A38]">
                    </div>
                </div>

                <div x-show="form.payout_method === 'bank_transfer' || form.payout_method === 'qr_simple'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Banco Destino:</label>
                        <input type="text" x-model="form.account_details.bank_name" placeholder="Banco Nacional de Bolivia (BNB)"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-[#E88A38]">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Número de Cuenta / Teléfono QR:</label>
                        <input type="text" x-model="form.account_details.account_number" placeholder="150-1234567 o 71234567"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-[#E88A38]">
                    </div>
                </div>

                <div x-show="form.payout_method === 'binance_pay'">
                    <label class="block font-bold text-slate-700 mb-1">Binance Pay ID (o correo de cuenta Binance):</label>
                    <input type="text" x-model="form.account_details.binance_pay_id" placeholder="Ej: 123456789 o correo@ejemplo.com"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-[#E88A38]">
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
                    <div class="w-10 h-10 rounded-2xl bg-red-50 text-[#960016] flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-plug-circle-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-[#960016]">Conectar Tienda Online</h3>
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
                            :class="integrationForm.platform === 'shopify' ? 'border-[#960016] bg-red-50/40 text-[#960016] font-bold' : 'border-slate-200 text-slate-600'">
                            <input type="radio" value="shopify" x-model="integrationForm.platform" class="sr-only">
                            <i class="fa-brands fa-shopify text-lg block mb-1"></i>
                            <span class="text-xs">Shopify</span>
                        </label>
                        <label class="p-3 rounded-xl border-2 cursor-pointer transition text-center"
                            :class="integrationForm.platform === 'woocommerce' ? 'border-[#960016] bg-red-50/40 text-[#960016] font-bold' : 'border-slate-200 text-slate-600'">
                            <input type="radio" value="woocommerce" x-model="integrationForm.platform" class="sr-only">
                            <i class="fa-brands fa-wordpress text-lg block mb-1"></i>
                            <span class="text-xs">WooCommerce</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre Identificativo de tu Tienda</label>
                    <input type="text" x-model="integrationForm.store_name" required placeholder="Ej: Bolivia Trends Shop"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">URL de tu Tienda</label>
                    <input type="url" x-model="integrationForm.store_url" required placeholder="https://tutienda.myshopify.com"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="auto_dispatch" x-model="integrationForm.auto_dispatch"
                        class="rounded border-slate-300 text-[#960016] focus:ring-[#960016]">
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
                const authRes = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: 'dropshipper@dropana.bo', password: 'password123' })
                });
                const authData = await authRes.json();
                const token = authData.data?.token;

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
                // Obtener token demo del dropshipper
                const authRes = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: 'dropshipper@dropana.bo', password: 'password123' })
                });
                const authData = await authRes.json();
                const token = authData.data?.token;

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
