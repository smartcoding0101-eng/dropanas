@extends('layouts.app')

@section('title', 'Dashboard Principal | Dropana Bolivia')

@section('content')
<div class="space-y-8 bg-[#FFFFFF]" x-data="adminDashboard()">

    <!-- Header con Bienvenida y Selector de Período -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#960016] bg-red-50 border border-red-200/80 px-2.5 py-0.5 rounded-full">
                    Tenant Admin • Panel de Control de la Plataforma
                </span>
                <span class="text-xs text-slate-500 font-medium">Actualizado en vivo</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#960016] font-heading">
                Bienvenido de nuevo, Alejandro!
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">
                Monitoreo en vivo de recaudación contra entrega, despachos con DEX y comisiones.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 hidden sm:inline font-medium">Período:</span>
            <span class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-sm">
                <i class="fa-regular fa-calendar mr-1.5 text-[#E88A38]"></i> Hoy: {{ date('d M, Y') }}
            </span>
        </div>
    </div>

    <!-- Mensaje de Notificación Reactiva -->
    <div x-show="toast.show" x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
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

    <!-- TARJETAS DE MÉTRICAS (Fondo #FFFFFF con acentos Ámbar #E88A38 y Carmín #960016) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Total Recaudación BOB -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4 hover:border-amber-400 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#E88A38] uppercase tracking-wider">Volumen Total (BOB)</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-[#E88A38] border border-amber-200 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-3xl font-extrabold text-[#960016] font-heading">
                    Bs {{ number_format($stats['total_volume_bob'] ?? 32450.00, 2) }}
                </div>
                <div class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>+8.5% respecto a la semana pasada</span>
                </div>
            </div>
            <!-- Gráfica simulada en ámbar -->
            <div class="h-9 w-full bg-slate-50 rounded-lg flex items-end px-1 gap-1 border border-slate-100">
                <div class="w-1/6 bg-[#E88A38]/40 rounded-t h-4"></div>
                <div class="w-1/6 bg-[#E88A38]/60 rounded-t h-6"></div>
                <div class="w-1/6 bg-[#E88A38]/50 rounded-t h-5"></div>
                <div class="w-1/6 bg-[#E88A38]/80 rounded-t h-7"></div>
                <div class="w-1/6 bg-[#E88A38] rounded-t h-8"></div>
                <div class="w-1/6 bg-[#ED2100] rounded-t h-9"></div>
            </div>
        </div>

        <!-- Pedidos Nuevos COD -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4 hover:border-red-400 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#960016] uppercase tracking-wider">Total Pedidos</span>
                <div class="w-8 h-8 rounded-xl bg-red-50 text-[#ED2100] border border-red-200 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-3xl font-extrabold text-[#960016] font-heading">
                    {{ $stats['total_orders'] ?? 157 }}
                </div>
                <div class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>+12% en conversión contra entrega</span>
                </div>
            </div>
            <div class="text-xs text-slate-500 pt-2 border-t border-slate-100 flex justify-between items-center">
                <span>Despachos activos:</span>
                <strong class="text-slate-800">42 órdenes en ruta</strong>
            </div>
        </div>

        <!-- Pagos Pendientes por Conciliar -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4 hover:border-amber-400 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[#E88A38] uppercase tracking-wider">Recaudación Pendiente</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-[#E88A38] border border-amber-200 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-3xl font-extrabold text-[#E88A38] font-heading font-mono">
                    Bs 18,310.00
                </div>
                <div class="text-[11px] text-slate-500">
                    En poder de transportadoras DEX / Cartero
                </div>
            </div>
            <div class="text-xs text-slate-500 pt-2 border-t border-slate-100 flex justify-between items-center">
                <span>Próxima liquidación:</span>
                <strong class="text-[#960016]">Hoy a las 18:00</strong>
            </div>
        </div>

        <!-- Efectividad de Entrega DEX Bolivia -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4 hover:border-emerald-400 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Efectividad DEX Bolivia</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="text-3xl font-extrabold text-slate-900 font-heading">
                        94%
                    </div>
                    <div class="text-[11px] text-slate-500">Entregas a tiempo</div>
                </div>
                <!-- Anillo simulado en ámbar -->
                <div class="relative w-14 h-14 rounded-full border-4 border-amber-100 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full border-4 border-[#E88A38] border-t-transparent -rotate-45"></div>
                    <span class="text-[11px] font-bold text-slate-800">94%</span>
                </div>
            </div>
            <div class="text-xs text-slate-500 pt-2 border-t border-slate-100 flex justify-between items-center text-[11px]">
                <span class="text-emerald-700 font-medium">147 Entregados</span>
                <span class="text-[#E88A38] font-bold">8 Pendientes</span>
            </div>
        </div>

    </div>

    <!-- TABLA DE PEDIDOS RECIENTES CON GESTIÓN DE ESTADO & GENERACIÓN DE MANIFIESTOS -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-0">
        
        <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
            <div>
                <h2 class="text-lg font-bold text-[#960016] font-heading">
                    Pedidos Recientes Contra Entrega (COD)
                </h2>
                <p class="text-xs text-slate-500">Selecciona pedidos para emitir el manifiesto de despacho DEX Bolivia</p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Botón Generar Manifiesto DEX por Lotes -->
                <button @click="generateManifestBatch()" :disabled="generatingManifest || selectedOrders.length === 0"
                    class="px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 shadow-sm transition"
                    :class="selectedOrders.length > 0 ? 'bg-[#E88A38] text-white hover:bg-amber-600' : 'bg-slate-100 text-slate-400 cursor-not-allowed'">
                    <i class="fa-solid fa-file-csv"></i>
                    <span x-show="!generatingManifest" x-text="'Generar Manifiesto DEX (' + selectedOrders.length + ')'"></span>
                    <span x-show="generatingManifest" class="flex items-center gap-1">
                        <i class="fa-solid fa-spinner animate-spin"></i> Emitiendo...
                    </span>
                </button>
                <a href="{{ url('/checkout-demo') }}" class="btn-scarlet px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 self-start sm:self-auto shadow-sm">
                    <i class="fa-solid fa-plus text-amber-200"></i>
                    <span>Crear Pedido Demo</span>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-10 text-center">
                            <input type="checkbox" @change="toggleSelectAll($event)" class="rounded border-slate-300 text-[#ED2100] focus:ring-[#ED2100]">
                        </th>
                        <th class="py-3.5 px-5">ID Pedido</th>
                        <th class="py-3.5 px-5">Cliente</th>
                        <th class="py-3.5 px-5">Fecha</th>
                        <th class="py-3.5 px-5">Estado</th>
                        <th class="py-3.5 px-5">Monto (BOB)</th>
                        <th class="py-3.5 px-5 text-right">Acciones Operativas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-slate-50/80 transition" id="order-row-{{ $order->id }}">
                            <td class="py-4 px-4 text-center">
                                <input type="checkbox" value="{{ $order->id }}" x-model="selectedOrders"
                                    class="rounded border-slate-300 text-[#ED2100] focus:ring-[#ED2100]">
                            </td>
                            <td class="py-4 px-5 font-mono font-bold text-[#960016]">
                                #{{ $order->order_number }}
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-800">{{ $order->customer_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $order->shipping_city }}, {{ $order->shipping_department }}</div>
                            </td>
                            <td class="py-4 px-5 text-slate-500">
                                {{ $order->created_at->format('d M, Y') }}
                            </td>
                            <td class="py-4 px-5">
                                <span class="order-status-badge">
                                    @if($order->status === 'settled')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            <i class="fa-solid fa-check-double mr-1.5 text-emerald-600"></i> Liquidado
                                        </span>
                                    @elseif($order->status === 'delivered')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                            <i class="fa-solid fa-box mr-1.5 text-sky-600"></i> Entregado
                                        </span>
                                    @elseif($order->status === 'dispatched')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            <i class="fa-solid fa-truck-fast mr-1.5 text-[#E88A38]"></i> En Tránsito DEX
                                        </span>
                                    @elseif($order->status === 'confirmed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                            <i class="fa-solid fa-check mr-1.5 text-blue-600"></i> Confirmado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                            <i class="fa-solid fa-clock mr-1.5 text-slate-500"></i> {{ $order->status }}
                                        </span>
                                    @endif
                                </span>
                            </td>
                            <td class="py-4 px-5 font-mono font-extrabold text-[#960016] text-sm">
                                Bs {{ number_format($order->total_amount_bob, 2) }}
                            </td>
                            <td class="py-4 px-5 text-right space-x-1.5">
                                <a href="{{ route('orders.label', $order->id) }}" target="_blank"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 inline-flex items-center gap-1 transition"
                                    title="Imprimir Rótulo Térmico">
                                    <i class="fa-solid fa-barcode text-[#E88A38]"></i>
                                    <span class="hidden sm:inline">Rótulo</span>
                                </a>

                                <button @click="openModal('{{ $order->id }}', '{{ $order->order_number }}', '{{ $order->status }}')"
                                    class="btn-scarlet px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1 shadow-sm">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Cambiar Estado</span>
                                </button>
                                <a href="{{ url('/checkout-demo') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 inline-block">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr class="hover:bg-slate-50 transition">
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                No se encontraron pedidos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- TABLA DE MANIFIESTOS DE DESPACHO DEX BOLIVIA (HISTORIAL & DESCARGA CSV) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-[#E88A38] border border-amber-200 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                </div>
                <div>
                    <h3 class="font-bold text-[#960016] font-heading text-base">Manifiestos de Carga Emitidos</h3>
                    <p class="text-xs text-slate-500">Archivos CSV formateados para DEX Bolivia y couriers nacionales</p>
                </div>
            </div>
            <span class="text-xs font-mono font-bold px-3 py-1 bg-white border border-slate-200 rounded-full text-[#960016]">
                {{ $manifests->count() }} Manifiestos
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                    <tr>
                        <th class="py-3 px-6">Código Manifiesto</th>
                        <th class="py-3 px-6">Transportadora</th>
                        <th class="py-3 px-6">Total Envíos</th>
                        <th class="py-3 px-6">Recaudo COD Total</th>
                        <th class="py-3 px-6">Fecha Emisión</th>
                        <th class="py-3 px-6 text-right">Descargar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($manifests as $manifest)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3.5 px-6 font-mono font-bold text-[#960016]">
                            {{ $manifest->manifest_number }}
                        </td>
                        <td class="py-3.5 px-6 font-semibold text-slate-800">
                            {{ $manifest->carrier->name ?? 'DEX Bolivia' }}
                        </td>
                        <td class="py-3.5 px-6 font-mono text-slate-700">
                            {{ $manifest->total_orders }} paquetes
                        </td>
                        <td class="py-3.5 px-6 font-mono font-extrabold text-[#E88A38]">
                            Bs {{ number_format($manifest->total_cod_amount_bob, 2) }}
                        </td>
                        <td class="py-3.5 px-6 text-slate-500">
                            {{ $manifest->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-3.5 px-6 text-right">
                            <button @click="downloadManifest('{{ $manifest->id }}', '{{ $manifest->manifest_number }}')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-50 hover:bg-amber-100 text-[#E88A38] border border-amber-200 inline-flex items-center gap-1.5 transition">
                                <i class="fa-solid fa-file-arrow-down"></i> CSV DEX
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-400">
                            Aún no se han generado manifiestos de despacho. Selecciona órdenes arriba y presiona "Generar Manifiesto DEX".
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL INTERACTIVO: CAMBIAR ESTADO DE PEDIDO & AUTO-LIQUIDACIÓN -->
    <div x-show="modalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-5"
            @click.away="modalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-red-50 text-[#ED2100] flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 font-heading text-sm">Gestión de Orden COD</h3>
                        <div class="text-xs text-slate-400 font-mono" x-text="'Pedido #' + currentOrderNumber"></div>
                    </div>
                </div>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Nuevo Estado del Pedido:</label>
                    <select x-model="selectedStatus" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 text-xs font-semibold focus:outline-none focus:border-[#E88A38]">
                        <option value="confirmed">Confirmado (Listo para Empaque)</option>
                        <option value="dispatched">Despachado con Transportadora DEX</option>
                        <option value="delivered">Entregado al Cliente (Auto-Liquidación COD)</option>
                        <option value="failed_delivery">Entrega Fallida / Reprogramar</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>

                <div x-show="selectedStatus === 'dispatched'">
                    <label class="block font-bold text-slate-700 mb-1.5">Número de Guía DEX Bolivia:</label>
                    <input type="text" x-model="trackingNumber" placeholder="DEX-BO-20261028-XXXX"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-[#E88A38]">
                </div>

                <!-- Alerta cuando se selecciona Delivered -->
                <div x-show="selectedStatus === 'delivered'" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 space-y-1">
                    <div class="font-bold flex items-center gap-1.5 text-[11px]">
                        <i class="fa-solid fa-coins text-emerald-600"></i> Auto-Liquidación Financiera
                    </div>
                    <p class="text-[10px] text-emerald-700 leading-relaxed">
                        Al marcar como entregado, el recaudo COD se registra como cobrado. Las ganancias se acreditarán automáticamente a la billetera del dropshipper y los costos al proveedor.
                    </p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Notas Operativas (opcional):</label>
                    <textarea x-model="notes" rows="2" placeholder="Observaciones de entrega o guía de transporte..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-800 text-xs focus:outline-none focus:border-[#E88A38]"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button @click="modalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                    Cancelar
                </button>
                <button @click="submitStatusUpdate()" :disabled="submitting"
                    class="btn-scarlet px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm">
                    <span x-show="!submitting">Guardar y Actualizar</span>
                    <span x-show="submitting" class="flex items-center gap-1.5">
                        <i class="fa-solid fa-spinner animate-spin"></i> Actualizando...
                    </span>
                </button>
            </div>

        </div>

    </div>

    <!-- SECCIÓN MÓDULOS AVANZADOS: INVENTARIO, CRM Y NOTIFICACIONES -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- MÓDULO 1: Transferencias de Inventario Interdepartamentales -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-red-50 text-[#ED2100] border border-red-200 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-[#960016] uppercase tracking-wide">Inventario & Bodegas</h2>
                        <p class="text-[11px] text-slate-500">Traslados interdepartamentales</p>
                    </div>
                </div>
            </div>

            <!-- Mini formulario de transferencia -->
            <form @submit.prevent="submitStockTransfer()" class="space-y-3 text-xs bg-slate-50/70 p-3.5 rounded-xl border border-slate-200">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Producto:</label>
                    <select x-model="transferForm.product_id" required class="w-full bg-white border border-slate-200 rounded-lg p-2 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                        <option value="">Selecciona producto...</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->sku }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Origen:</label>
                        <select x-model="transferForm.from_warehouse_id" required class="w-full bg-white border border-slate-200 rounded-lg p-2 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                            <option value="">Origen...</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->department }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Destino:</label>
                        <select x-model="transferForm.to_warehouse_id" required class="w-full bg-white border border-slate-200 rounded-lg p-2 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                            <option value="">Destino...</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->department }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="w-1/2">
                        <label class="block font-bold text-slate-700 mb-1">Cantidad:</label>
                        <input type="number" min="1" x-model="transferForm.quantity" required placeholder="Ej: 20"
                            class="w-full bg-white border border-slate-200 rounded-lg p-2 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                    </div>
                    <div class="w-1/2 pt-4">
                        <button type="submit" :disabled="transferring"
                            class="w-full btn-scarlet py-2 rounded-lg text-xs font-bold shadow-sm flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                            <span x-show="!transferring">Transferir</span>
                            <span x-show="transferring">Enviando...</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Historial de transferencias -->
            <div class="space-y-2">
                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Últimos Traslados</span>
                @forelse($stockTransfers as $st)
                    <div class="p-2.5 rounded-lg border border-slate-100 bg-white text-xs flex items-center justify-between shadow-xs">
                        <div>
                            <div class="font-bold text-slate-800">{{ $st->product?->name ?? 'Producto' }}</div>
                            <div class="text-[10px] text-slate-500">
                                {{ $st->fromWarehouse?->department ?? 'Origen' }} <i class="fa-solid fa-arrow-right text-[#E88A38] text-[9px]"></i> {{ $st->toWarehouse?->department ?? 'Destino' }}
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-[#960016]">+{{ $st->quantity }} u.</span>
                            <div class="text-[9px] text-emerald-600 font-bold uppercase">{{ $st->status }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No hay transferencias recientes registradas.</p>
                @endforelse
            </div>
        </div>

        <!-- MÓDULO 4: Clientes CRM (Segmentación & Valor) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-[#E88A38] border border-amber-200 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-[#960016] uppercase tracking-wide">CRM Clientes COD</h2>
                        <p class="text-[11px] text-slate-500">Historial y segmentación</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    {{ $customers->count() }} registrados
                </span>
            </div>

            <div class="space-y-2.5">
                @forelse($customers as $cust)
                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-white hover:border-amber-300 transition text-xs flex items-center justify-between">
                        <div class="space-y-0.5">
                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                <span>{{ $cust->full_name }}</span>
                                <span class="text-[9px] px-1.5 py-0.2 rounded font-black uppercase
                                    @if($cust->customer_segment === 'vip') bg-amber-100 text-amber-800
                                    @elseif($cust->customer_segment === 'regular') bg-blue-100 text-blue-800
                                    @elseif($cust->customer_segment === 'at_risk') bg-red-100 text-red-800
                                    @else bg-slate-200 text-slate-700 @endif">
                                    {{ $cust->customer_segment }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2">
                                <span><i class="fa-brands fa-whatsapp text-emerald-600"></i> {{ $cust->phone }}</span>
                                <span>•</span>
                                <span>{{ $cust->city ?? 'Bolivia' }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-black text-[#960016]">Bs {{ number_format($cust->total_spent_bob, 2) }}</div>
                            <div class="text-[10px] text-slate-500">{{ $cust->completed_orders }} pedidos</div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No hay clientes sincronizados en el CRM aún.</p>
                @endforelse
            </div>
        </div>

        <!-- MÓDULO 3: Centro de Notificaciones & Auditoría -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-red-50 text-[#960016] border border-red-200 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-[#960016] uppercase tracking-wide">Notificaciones</h2>
                        <p class="text-[11px] text-slate-500">Eventos de pedidos y WhatsApp</p>
                    </div>
                </div>
            </div>

            <div class="space-y-2.5">
                @forelse($notifications as $notif)
                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50/50 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                                <span>{{ $notif->event_type }}</span>
                            </span>
                            <span class="text-[10px] text-slate-400">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-snug">{{ $notif->body }}</p>
                        <div class="text-[10px] text-slate-500 flex items-center justify-between pt-1 border-t border-slate-100">
                            <span>Destino: {{ $notif->recipient }}</span>
                            <span class="text-emerald-600 font-bold uppercase">{{ $notif->status }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No hay notificaciones emitidas recientemente.</p>
                @endforelse
            </div>
        </div>

    </div>

    <!-- SECCIÓN: GESTIÓN DE USUARIOS DE LA PLATAFORMA (PROVEEDORES, VENDEDORES, AGENTES) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 text-[#960016] border border-red-200 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-[#960016] font-heading">Gestión de Usuarios & Cuentas</h2>
                    <p class="text-xs text-slate-500">Control de acceso, roles mayoristas y dropshippers en Bolivia</p>
                </div>
            </div>

            <button @click="userModalOpen = true"
                class="btn-scarlet px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm self-start sm:self-auto">
                <i class="fa-solid fa-user-plus"></i>
                <span>Crear Nuevo Usuario</span>
            </button>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <th class="p-3">Nombre & Correo</th>
                        <th class="p-3">Rol en Dropana</th>
                        <th class="p-3">Documento (CI/NIT)</th>
                        <th class="p-3">Ubicación</th>
                        <th class="p-3">Estado</th>
                        <th class="p-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $u)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-3">
                                <div class="font-bold text-slate-800">{{ $u->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $u->email }}</div>
                                @if($u->company_or_store_name)
                                    <div class="text-[10px] text-slate-400 italic">{{ $u->company_or_store_name }}</div>
                                @endif
                            </td>
                            <td class="p-3">
                                @if($u->role === 'tenant_admin')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-[#960016]">Administrador</span>
                                @elseif($u->role === 'provider')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-[#E88A38]">Proveedor Mayorista</span>
                                @elseif($u->role === 'dropshipper')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Dropshipper</span>
                                @elseif($u->role === 'carrier_agent')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Courier DEX</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">{{ $u->role }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-600 font-medium">
                                {{ $u->document_id ?? 'Sin documento' }}
                            </td>
                            <td class="p-3 text-slate-600">
                                <div>{{ $u->city ?? 'N/D' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $u->department ?? 'Bolivia' }}</div>
                            </td>
                            <td class="p-3">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600">
                                        <i class="fa-solid fa-circle text-[6px]"></i> Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600">
                                        <i class="fa-solid fa-circle text-[6px]"></i> Suspendido
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                @if($u->id !== $admin->id)
                                    <button @click="toggleUserStatus('{{ $u->id }}')"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition
                                        {{ $u->is_active ? 'border-red-200 text-red-700 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                        {{ $u->is_active ? 'Suspender' : 'Reactivar' }}
                                    </button>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Tu cuenta</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: CREAR NUEVO USUARIO -->
    <div x-show="userModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 max-h-[90vh] overflow-y-auto"
            @click.away="userModalOpen = false">

            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-red-50 text-[#ED2100] border border-red-200 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="text-base font-bold text-[#960016] font-heading">Crear Nuevo Usuario</h3>
                </div>
                <button @click="userModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form @submit.prevent="submitCreateUser()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nombre Completo o Empresa:</label>
                    <input type="text" x-model="newUserForm.name" required placeholder="Ej: Comercial Santa Cruz SRL"
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Correo Electrónico:</label>
                        <input type="email" x-model="newUserForm.email" required placeholder="correo@ejemplo.bo"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contraseña Inicial:</label>
                        <input type="password" x-model="newUserForm.password" required placeholder="Mínimo 8 caracteres"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Rol en la Plataforma:</label>
                        <select x-model="newUserForm.role" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                            <option value="dropshipper">Dropshipper (Vendedor COD)</option>
                            <option value="provider">Proveedor Mayorista (Megabodega)</option>
                            <option value="carrier_agent">Agente Logístico (DEX Bolivia)</option>
                            <option value="tenant_admin">Administrador Secundario</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">C.I. / NIT Bolivia:</label>
                        <input type="text" x-model="newUserForm.document_id" required placeholder="Ej: 5432101 SC"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Teléfono / WhatsApp (+591):</label>
                        <input type="text" x-model="newUserForm.phone" required placeholder="Ej: +591 71234567"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nombre Tienda / Marca (opcional):</label>
                        <input type="text" x-model="newUserForm.company_or_store_name" placeholder="Ej: Tienda Digital Oriente"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Departamento:</label>
                        <select x-model="newUserForm.department" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                            <option value="Santa Cruz">Santa Cruz</option>
                            <option value="La Paz">La Paz</option>
                            <option value="Cochabamba">Cochabamba</option>
                            <option value="Oruro">Oruro</option>
                            <option value="Potosí">Potosí</option>
                            <option value="Chuquisaca">Chuquisaca</option>
                            <option value="Tarija">Tarija</option>
                            <option value="Beni">Beni</option>
                            <option value="Pando">Pando</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ciudad / Municipio:</label>
                        <input type="text" x-model="newUserForm.city" required placeholder="Ej: Santa Cruz de la Sierra"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:border-[#E88A38]">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="userModalOpen = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="creatingUser"
                        class="btn-scarlet px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-check"></i>
                        <span x-show="!creatingUser">Crear Usuario</span>
                        <span x-show="creatingUser">Creando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function adminDashboard() {
    return {
        modalOpen: false,
        currentOrderId: '',
        currentOrderNumber: '',
        selectedStatus: 'confirmed',
        trackingNumber: '',
        notes: '',
        submitting: false,
        generatingManifest: false,
        selectedOrders: [],
        allOrderIds: @json($recentOrders->pluck('id')),
        carrierId: '{{ $carriers->first()?->id ?? "" }}',
        toast: { show: false, message: '', type: 'success' },
        userModalOpen: false,
        creatingUser: false,
        newUserForm: {
            name: '',
            email: '',
            password: '',
            role: 'dropshipper',
            document_id: '',
            phone: '+591 ',
            company_or_store_name: '',
            department: 'Santa Cruz',
            city: 'Santa Cruz de la Sierra'
        },

        async submitCreateUser() {
            this.creatingUser = true;
            try {
                const token = await this.getAdminToken();
                const res = await fetch('/api/v1/admin/users', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(this.newUserForm)
                });

                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.showToast('Usuario creado exitosamente.', 'success');
                    this.userModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    this.showToast(data.message || 'Error al crear usuario.', 'error');
                }
            } catch (err) {
                this.showToast('Error de conexión al registrar usuario.', 'error');
            } finally {
                this.creatingUser = false;
            }
        },

        async toggleUserStatus(userId) {
            try {
                const token = await this.getAdminToken();
                const res = await fetch(`/api/v1/admin/users/${userId}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });

                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    this.showToast(data.message || 'Error al actualizar usuario.', 'error');
                }
            } catch (e) {
                this.showToast('Error de red al actualizar estado del usuario.', 'error');
            }
        },

        transferring: false,
        transferForm: {
            product_id: '',
            from_warehouse_id: '',
            to_warehouse_id: '',
            quantity: 10
        },

        async submitStockTransfer() {
            if (!this.transferForm.product_id || !this.transferForm.from_warehouse_id || !this.transferForm.to_warehouse_id) {
                this.showToast('Completa todos los campos de transferencia.', 'error');
                return;
            }
            if (this.transferForm.from_warehouse_id === this.transferForm.to_warehouse_id) {
                this.showToast('El almacén de origen y destino no pueden ser el mismo.', 'error');
                return;
            }

            this.transferring = true;
            try {
                const token = await this.getAdminToken();
                const res = await fetch('/api/v1/inventory/transfer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(this.transferForm)
                });

                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.showToast('Transferencia completada con éxito.', 'success');
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    this.showToast(data.message || 'Error al procesar transferencia.', 'error');
                }
            } catch (e) {
                this.showToast('Error de conexión al transferir inventario.', 'error');
            } finally {
                this.transferring = false;
            }
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectedOrders = [...this.allOrderIds];
            } else {
                this.selectedOrders = [];
            }
        },

        openModal(orderId, orderNumber, currentStatus) {
            this.currentOrderId = orderId;
            this.currentOrderNumber = orderNumber;
            this.selectedStatus = currentStatus === 'pending_confirmation' ? 'confirmed' : currentStatus;
            this.trackingNumber = 'DEX-BO-' + orderNumber;
            this.notes = '';
            this.modalOpen = true;
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 5000);
        },

        async getAdminToken() {
            const authRes = await fetch('/api/v1/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ email: 'admin@dropana.bo', password: 'password123' })
            });
            const authData = await authRes.json();
            return authData.data?.token;
        },

        async submitStatusUpdate() {
            if (!this.currentOrderId) return;
            this.submitting = true;

            try {
                const token = await this.getAdminToken();

                const response = await fetch(`/api/v1/orders/${this.currentOrderId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        status: this.selectedStatus,
                        tracking_number: this.trackingNumber,
                        notes: this.notes
                    })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.showToast(result.message || 'Estado actualizado correctamente.', 'success');
                    this.modalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    this.showToast(result.message || 'Error al actualizar estado.', 'error');
                }
            } catch (err) {
                this.showToast('Error de conexión con el servidor.', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async generateManifestBatch() {
            if (this.selectedOrders.length === 0) return;
            this.generatingManifest = true;

            try {
                const token = await this.getAdminToken();

                const response = await fetch('/api/v1/manifests', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        carrier_id: this.carrierId,
                        order_ids: this.selectedOrders,
                        notes: 'Despacho masivo generado desde Panel Admin'
                    })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.showToast('Manifiesto DEX creado exitosamente. Descargando CSV...', 'success');
                    const manifestId = result.data.id;
                    await this.downloadManifest(manifestId, result.data.manifest_number);
                    setTimeout(() => { window.location.reload(); }, 1500);
                } else {
                    this.showToast(result.message || 'Error al generar manifiesto.', 'error');
                }
            } catch (err) {
                this.showToast('Error al conectar con la API de manifiestos.', 'error');
            } finally {
                this.generatingManifest = false;
            }
        },

        async downloadManifest(manifestId, manifestNumber) {
            try {
                const token = await this.getAdminToken();
                const res = await fetch(`/api/v1/manifests/${manifestId}/download`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                if (!res.ok) throw new Error('Error al descargar archivo');

                const blob = await res.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `manifiesto_${manifestNumber || manifestId}.csv`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            } catch (err) {
                this.showToast('No se pudo descargar el archivo CSV.', 'error');
            }
        }
    }
}
</script>
@endsection
