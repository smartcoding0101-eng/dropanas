@extends('layouts.app')

@section('title', 'Checkout COD + QR Bolivia | Bolidrop')

@section('content')
@php
    $currentApiToken = null;
    if (Auth::check()) {
        try {
            $currentApiToken = Auth::user()->createToken('checkout_web_session')->plainTextToken;
        } catch (\Throwable $e) {
            $currentApiToken = null;
        }
    }
@endphp
<div class="space-y-8" x-data="checkoutDemo()">

    <x-page-header
        title="Checkout Contra Entrega (COD) + QR Simple Bolivia"
        subtitle="Simula el flujo completo: selecciona producto → datos del cliente → transportadora → método de pago → genera QR o guía."
        icon="fa-solid fa-cart-shopping"
        badge="Demo en Vivo • Flujo Real">
    </x-page-header>

    <!-- Layout 2 columnas (SB Admin Pro Overlap) -->
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8 !-mt-12 relative z-10">

        <!-- Formulario principal (2/3) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- PASO 1: Producto a Despachar -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 bg-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-full bg-[#0061f2] text-white flex items-center justify-center text-xs font-bold">1</div>
                        <h2 class="font-bold text-[#212832] text-sm font-heading">Producto a Despachar</h2>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium bg-slate-200/60 px-2.5 py-0.5 rounded-full">
                        {{ $products->count() }} {{ $products->count() === 1 ? 'producto en catálogo' : 'productos en catálogo' }}
                    </span>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Buscador con Autocompletado al Escribir -->
                    <div class="space-y-1.5 relative" @click.outside="productDropdownOpen = false">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-slate-700">
                                <i class="fa-solid fa-magnifying-glass text-[#0061f2] mr-1"></i>
                                Buscar Producto por Nombre, SKU o Precio
                            </label>
                            <span class="text-[11px] text-indigo-600 font-medium flex items-center gap-1">
                                <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i>
                                Autocompletado en tiempo real
                            </span>
                        </div>

                        <!-- Input del Buscador -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </div>
                            <input type="text"
                                x-model="productSearchInput"
                                @focus="productDropdownOpen = true"
                                @input="productDropdownOpen = true"
                                @keydown.escape="productDropdownOpen = false"
                                placeholder="Escribe el nombre del producto, SKU o precio para autocompletar..."
                                class="w-full pl-9 pr-14 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-[#0061f2] transition bg-white text-slate-800 placeholder-slate-400">
                            
                            <!-- Botones limpiar y toggle -->
                            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center gap-1">
                                <button type="button" x-show="productSearchInput && productSearchInput.trim() !== ''" @click="clearProductSearch()"
                                    class="p-1 text-slate-400 hover:text-slate-600 rounded-md transition" title="Limpiar búsqueda">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                                <button type="button" @click="productDropdownOpen = !productDropdownOpen"
                                    class="p-1 text-slate-400 hover:text-slate-600 rounded-md transition" title="Ver opciones">
                                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="productDropdownOpen ? 'rotate-180' : ''"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Dropdown de Sugerencias de Productos -->
                        <div x-show="productDropdownOpen" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            class="absolute z-30 left-0 right-0 mt-1 max-h-72 overflow-y-auto bg-white rounded-xl border border-slate-200 shadow-2xl divide-y divide-slate-100">
                            
                            <!-- Header de resultados -->
                            <div class="px-3.5 py-2 bg-slate-50 text-[11px] font-semibold text-slate-500 flex items-center justify-between border-b border-slate-100">
                                <span>Coincidencias encontradas (<span x-text="filteredProducts.length"></span>)</span>
                                <span class="text-[10px] text-slate-400">Haz clic para autocompletar precio y datos</span>
                            </div>

                            <template x-for="p in filteredProducts" :key="p.id">
                                <button type="button" @click="selectProduct(p)"
                                    :class="form.product_id == p.id ? 'bg-blue-50/70 border-l-4 border-[#0061f2]' : 'hover:bg-slate-50'"
                                    class="w-full px-3.5 py-2.5 text-left transition flex flex-col gap-1.5 group cursor-pointer">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="font-semibold text-xs text-slate-800 group-hover:text-[#0061f2] transition" x-text="p.name"></div>
                                        <template x-if="form.product_id == p.id">
                                            <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-[#0061f2]">
                                                <i class="fa-solid fa-check mr-1"></i>Seleccionado
                                            </span>
                                        </template>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                                        <!-- Costo -->
                                        <span class="inline-flex items-center gap-1 text-slate-600 bg-slate-100 px-2 py-0.5 rounded font-mono">
                                            <i class="fa-solid fa-tag text-slate-400 text-[10px]"></i>
                                            Costo: <strong>Bs <span x-text="p.cost.toFixed(2)"></span></strong>
                                        </span>
                                        <!-- Precio Sugerido -->
                                        <span class="inline-flex items-center gap-1 text-blue-700 bg-blue-50 px-2 py-0.5 rounded font-mono font-bold">
                                            <i class="fa-solid fa-bolt text-amber-500 text-[10px]"></i>
                                            Sugerido: Bs <span x-text="p.suggested.toFixed(2)"></span>
                                        </span>
                                        <!-- Precio Mínimo -->
                                        <span class="inline-flex items-center gap-1 text-slate-500 bg-slate-50 px-2 py-0.5 rounded font-mono">
                                            Mín: Bs <span x-text="p.min_price.toFixed(2)"></span>
                                        </span>
                                        <!-- Stock -->
                                        <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-bold">
                                            <i class="fa-solid fa-boxes-stacked text-emerald-500 text-[10px]"></i>
                                            <span x-text="p.stock + ' uds'"></span>
                                        </span>
                                        <!-- Proveedor -->
                                        <span class="inline-flex items-center gap-1 text-slate-500">
                                            <i class="fa-solid fa-store text-indigo-400 text-[10px]"></i>
                                            <span x-text="p.provider"></span>
                                        </span>
                                    </div>
                                </button>
                            </template>

                            <div x-show="filteredProducts.length === 0" class="p-6 text-center text-xs text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-slate-300 text-xl mb-2 block"></i>
                                No se encontraron productos coincidentes con tu búsqueda.
                            </div>
                        </div>
                    </div>

                    <!-- Ficha del Producto Seleccionado -->
                    <template x-if="form.product_id && selectedProduct.name">
                        <div class="p-3 rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50/50 to-indigo-50/30 flex items-center justify-between gap-3 text-xs">
                            <div class="space-y-1">
                                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                    <span x-text="selectedProduct.name"></span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
                                    <span><i class="fa-solid fa-store text-indigo-500 mr-1"></i>Proveedor: <strong class="text-slate-700" x-text="selectedProduct.provider"></strong></span>
                                    <span>•</span>
                                    <span><i class="fa-solid fa-boxes-stacked text-emerald-500 mr-1"></i>Stock verificado: <strong class="text-slate-700" x-text="selectedProduct.stock + ' uds'"></strong></span>
                                    <span>•</span>
                                    <span><i class="fa-solid fa-money-bill text-slate-400 mr-1"></i>Costo: <strong class="text-slate-700 font-mono" x-text="'Bs ' + selectedProduct.cost.toFixed(2)"></strong></span>
                                </div>
                            </div>
                            <button type="button" @click="productSearchInput = ''; productDropdownOpen = true"
                                class="shrink-0 text-[11px] font-semibold text-[#0061f2] hover:underline px-2.5 py-1 bg-white rounded-lg border border-blue-200 shadow-2xs">
                                Cambiar
                            </button>
                        </div>
                    </template>

                    <!-- Campos Cantidad y Precio de Venta (Con autocompletado y atajos rápidos) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="form.product_id">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cantidad</label>
                            <input type="number" x-model.number="form.quantity" min="1" max="50" @input="calcTotals()"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none" />
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-700">
                                    Precio de Venta al Cliente (BOB)
                                </label>
                                <span class="text-[11px] text-slate-400" x-show="selectedProduct.min_price > 0">
                                    Mínimo: <strong class="text-slate-600" x-text="'Bs ' + selectedProduct.min_price.toFixed(2)"></strong>
                                </span>
                            </div>

                            <!-- Input con datalist nativo de autocompletado -->
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                                    Bs
                                </span>
                                <input type="number"
                                    x-model.number="form.selling_price"
                                    step="0.50"
                                    list="price-suggestions-list"
                                    @input="calcTotals()"
                                    class="w-full pl-9 pr-3 py-2.5 text-sm font-semibold rounded-xl border focus:outline-none focus:ring-2 transition"
                                    :class="priceError ? 'border-red-300 bg-red-50/40 text-red-900 focus:ring-red-200 focus:border-red-500' : 'border-slate-200 bg-white text-slate-800 focus:ring-blue-500/20 focus:border-[#0061f2]'" />
                                
                                <datalist id="price-suggestions-list">
                                    <option :value="selectedProduct.suggested" label="Precio Sugerido del Proveedor"></option>
                                    <option :value="selectedProduct.min_price" label="Precio Mínimo Permitido"></option>
                                    <option :value="+(selectedProduct.suggested * 1.1).toFixed(2)" label="Margen +10%"></option>
                                    <option :value="+(selectedProduct.suggested * 1.2).toFixed(2)" label="Margen +20%"></option>
                                </datalist>
                            </div>

                            <!-- Chips de autocompletado rápido de precio (1 clic) -->
                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                <span class="text-[10px] text-slate-400 font-medium">Autocompletar precio:</span>
                                <button type="button" @click="setPrice(selectedProduct.suggested)"
                                    class="text-[11px] px-2 py-0.5 rounded-md font-medium transition cursor-pointer border"
                                    :class="form.selling_price === selectedProduct.suggested ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100'">
                                    <i class="fa-solid fa-bolt text-[9px] mr-1"></i>Sugerido: Bs <span x-text="selectedProduct.suggested.toFixed(0)"></span>
                                </button>
                                <button type="button" @click="setPrice(selectedProduct.min_price)"
                                    class="text-[11px] px-2 py-0.5 rounded-md font-medium transition cursor-pointer border"
                                    :class="form.selling_price === selectedProduct.min_price ? 'bg-slate-700 text-white border-slate-700 shadow-2xs' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'">
                                    <i class="fa-solid fa-tag text-[9px] mr-1"></i>Mín: Bs <span x-text="selectedProduct.min_price.toFixed(0)"></span>
                                </button>
                                <button type="button" @click="setPrice(+(selectedProduct.suggested * 1.1).toFixed(2))"
                                    class="text-[11px] px-2 py-0.5 rounded-md font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition cursor-pointer">
                                    +10%: Bs <span x-text="(selectedProduct.suggested * 1.1).toFixed(0)"></span>
                                </button>
                                <button type="button" @click="setPrice(+(selectedProduct.suggested * 1.2).toFixed(2))"
                                    class="text-[11px] px-2 py-0.5 rounded-md font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition cursor-pointer">
                                    +20%: Bs <span x-text="(selectedProduct.suggested * 1.2).toFixed(0)"></span>
                                </button>
                            </div>

                            <!-- Estado dinámico de ganancia / error -->
                            <div class="mt-2 text-xs">
                                <template x-if="!priceError && form.selling_price > 0">
                                    <div class="flex items-center gap-1.5 text-emerald-700 bg-emerald-50/80 px-2.5 py-1.5 rounded-lg border border-emerald-200">
                                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                        <span>Tu ganancia neta estimada: <strong class="font-mono" x-text="'Bs ' + profit.toFixed(2)"></strong> (<span x-text="profitMarginPercent()"></span>% margen)</span>
                                    </div>
                                </template>
                                <template x-if="priceError">
                                    <div class="flex items-start gap-1.5 text-red-700 bg-red-50 px-2.5 py-1.5 rounded-lg border border-red-200">
                                        <i class="fa-solid fa-triangle-exclamation text-red-600 mt-0.5"></i>
                                        <span>El precio de venta (Bs <span x-text="form.selling_price"></span>) está por debajo del mínimo de Bs <span x-text="selectedProduct.min_price.toFixed(2)"></span> permitido por el proveedor.</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Bodega y Transportadora -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 p-4 border-b border-slate-200 bg-slate-50">
                    <div class="w-7 h-7 rounded-full bg-[#0061f2] text-white flex items-center justify-center text-xs font-bold">2</div>
                    <h2 class="font-bold text-[#212832] text-sm font-heading">Bodega de Despacho y Transportadora</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bodega de Origen</label>
                            <select x-model="form.warehouse_id"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:border-emerald-500 outline-none bg-white">
                                <option value="">— Seleccionar bodega con stock —</option>
                                <template x-for="wh in availableWarehouses" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.name + ' (' + wh.city + ') — ' + wh.stock + ' uds disponibles'"></option>
                                </template>
                                <template x-if="availableWarehouses.length === 0">
                                    @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->city }})</option>
                                    @endforeach
                                </template>
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1" x-show="availableWarehouses.length > 0">
                                <i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i>Stock físico disponible verificado en bodega
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Transportadora COD</label>
                            <select x-model="form.carrier_code"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:border-emerald-500 outline-none bg-white">
                                <option value="">— Seleccionar transportadora —</option>
                                @foreach($carriers as $c)
                                <option value="{{ $c->code }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 3: Datos del cliente -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 p-4 border-b border-slate-200 bg-slate-50">
                    <div class="w-7 h-7 rounded-full bg-[#0061f2] text-white flex items-center justify-center text-xs font-bold">3</div>
                    <h2 class="font-bold text-[#212832] text-sm font-heading">Datos del Cliente Final (Bolivia)</h2>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nombre Completo</label>
                        <input type="text" x-model="form.customer_name" placeholder="Ej: Rodrigo Morales Vaca"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Teléfono (WhatsApp)</label>
                        <input type="text" x-model="form.customer_phone" placeholder="+591 78512345"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 outline-none font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Carnet de Identidad (CI / NIT)</label>
                        <input type="text" x-model="form.customer_document_id" placeholder="Ej: 7891234 SC"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Departamento de Entrega</label>
                        <select x-model="form.shipping_department"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:border-emerald-500 outline-none bg-white">
                            <option value="">— Seleccionar departamento —</option>
                            @foreach(['Santa Cruz','La Paz','Cochabamba','Oruro','Potosí','Chuquisaca','Tarija','Beni','Pando'] as $dep)
                            <option value="{{ $dep }}">{{ $dep }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Ciudad</label>
                        <input type="text" x-model="form.shipping_city" placeholder="Ej: Santa Cruz de la Sierra"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Dirección de Entrega</label>
                        <input type="text" x-model="form.shipping_address" placeholder="Ej: Av. San Martín #123, Zona Centro"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 outline-none" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Referencia de Ubicación</label>
                        <input type="text" x-model="form.shipping_reference" placeholder="Frente a la farmacia, portón café con timbre blanco"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 outline-none" />
                    </div>
                </div>
            </div>

            <!-- PASO 4: Método de Cobro -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 p-4 border-b border-slate-200 bg-slate-50">
                    <div class="w-7 h-7 rounded-full bg-[#0061f2] text-white flex items-center justify-center text-xs font-bold">4</div>
                    <h2 class="font-bold text-[#212832] text-sm font-heading">Método de Cobro al Entregar (COD Bolivia)</h2>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- COD Efectivo (Con recaudo = Contra entrega) -->
                    <label :class="form.payment_method === 'cash_on_delivery_bob' ? 'ring-2 ring-emerald-500 border-emerald-300 bg-emerald-50' : 'border-slate-200 bg-white'"
                        class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition hover:border-slate-300">
                        <input type="radio" x-model="form.payment_method" value="cash_on_delivery_bob" class="text-emerald-600 w-4 h-4 mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1.5 flex-wrap">
                                <div class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-money-bill-wave text-emerald-500"></i> Efectivo contra entrega
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Con recaudo
                                </span>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                Contra entrega • Cobro en efectivo al entregar en destino (BOB)
                            </div>
                        </div>
                    </label>

                    <!-- QR Interoperable (Sin recaudo = Pagado) -->
                    <label :class="form.payment_method === 'qr_interoperable' ? 'ring-2 ring-sky-500 border-sky-300 bg-sky-50' : 'border-slate-200 bg-white'"
                        class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition hover:border-slate-300">
                        <input type="radio" x-model="form.payment_method" value="qr_interoperable" class="text-sky-600 w-4 h-4 mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1.5 flex-wrap">
                                <div class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-qrcode text-sky-500"></i> QR Simple Interoperable
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-sky-100 text-sky-800 border border-sky-200">
                                    Sin recaudo (Pagado)
                                </span>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                Pagado previamente • Sin cobro en destino vía QR ACH Simple
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Panel Dinámico de Acción Inmediata según Moneda / Método Seleccionado -->
                <div class="mx-5 mb-5 p-4 rounded-xl border transition-all duration-200"
                     :class="{
                         'bg-emerald-50 border-emerald-200 text-emerald-900': form.payment_method === 'cash_on_delivery_bob',
                         'bg-sky-50 border-sky-200 text-sky-900': form.payment_method === 'qr_interoperable'
                     }">
                    
                    <!-- 1. DETALLE EFECTIVO (CON RECAUDO) -->
                    <template x-if="form.payment_method === 'cash_on_delivery_bob'">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold text-sm text-emerald-800">
                                    <i class="fa-solid fa-money-bill-wave text-emerald-600"></i>
                                    <span>Con recaudo • Recaudación en Efectivo al Entregar (Contra entrega)</span>
                                </div>
                                <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full font-mono"
                                      x-text="(totalAmount || 0).toFixed(2) + ' BOB'"></span>
                            </div>
                            <p class="text-xs text-emerald-700 leading-relaxed">
                                Modalidad <strong>Con recaudo (Contra entrega)</strong>: El transportista de <strong>DEX Bolivia Express</strong> cobrará este importe exacto en efectivo al momento de entregar el paquete en destino.
                            </p>
                        </div>
                    </template>

                    <!-- 2. DETALLE QR SIMPLE (SIN RECAUDO / PAGADO) -->
                    <template x-if="form.payment_method === 'qr_interoperable'">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold text-sm text-sky-800">
                                    <i class="fa-solid fa-qrcode text-sky-600"></i>
                                    <span>Sin recaudo • QR Simple Interoperable Bolivia (Pagado)</span>
                                </div>
                                <span class="text-xs bg-sky-100 text-sky-800 font-bold px-2.5 py-1 rounded-full font-mono"
                                      x-text="(totalAmount || 0).toFixed(2) + ' BOB'"></span>
                            </div>
                            <p class="text-xs text-sky-700 leading-relaxed">
                                Modalidad <strong>Sin recaudo (Pagado)</strong>: El pedido se cancela previamente vía QR ACH (compatible con <strong>Banco Unión, BCP, BNB, Bisa, Ganadero, Mercantil Santa Cruz y Tigo Money</strong>). El transportista entregará el paquete sin realizar cobro en destino.
                            </p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Botón de Enviar (Primario Escarlata #059669 con Títulos y Feedback de Estado) -->
            <div class="space-y-3 pt-2">
                <button type="button" @click.prevent="submitOrder()" :disabled="loading || priceError || !isFormValid()"
                    class="w-full py-4 px-6 btn-scarlet font-bold rounded-2xl shadow-xl flex flex-col sm:flex-row items-center justify-between gap-3 text-base transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed group">
                    
                    <div class="flex items-center gap-3.5 text-left">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-lg text-white group-hover:scale-105 transition">
                            <i class="fa-solid fa-bolt" x-show="!loading"></i>
                            <i class="fa-solid fa-circle-notch fa-spin text-white" x-show="loading" x-cloak></i>
                        </div>
                        <div>
                            <div class="text-base sm:text-lg font-black tracking-tight leading-tight text-white font-heading" x-show="!loading">
                                Confirmar y Generar Guía de Envío COD
                            </div>
                            <div class="text-base sm:text-lg font-black tracking-tight leading-tight text-white font-heading" x-show="loading" x-cloak>
                                Procesando con DEX Bolivia Express...
                            </div>
                            <div class="text-xs text-white/80 font-medium">
                                <span x-show="!loading">Despacho con recaudación en efectivo / QR al entregar</span>
                                <span x-show="loading" x-cloak>Asignando número de guía y reservando inventario...</span>
                            </div>
                        </div>
                    </div>

                    <div class="hidden sm:flex items-center gap-2 bg-black/20 px-3.5 py-1.5 rounded-xl border border-white/20 text-xs font-mono font-bold text-white shrink-0">
                        <span>Total:</span>
                        <span x-text="(totalAmount || 0).toFixed(2) + ' BOB'"></span>
                        <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
                    </div>
                </button>

                <!-- Feedback explicativo de validación si el botón está deshabilitado -->
                <div x-show="!isFormValid() && !loading" class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-[#0061f2]"></i>
                        <span>Campos requeridos: <strong class="font-bold underline" x-text="missingFieldsSummary()"></strong></span>
                    </div>
                    <button @click="fillDemoData()" type="button" class="font-bold text-[#212832] hover:text-[#0061f2] hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-wand-magic-sparkles text-xs"></i> Autocompletar datos de prueba
                    </button>
                </div>

                <!-- Alerta inmediata de error debajo del botón -->
                <div x-show="errorMsg" x-cloak class="p-4 bg-red-50 border-2 border-red-300 rounded-xl text-xs text-red-800 flex items-start gap-2.5 shadow-sm">
                    <i class="fa-solid fa-circle-xmark mt-0.5 text-red-600 text-sm"></i>
                    <div>
                        <div class="font-bold text-red-900 mb-0.5">No se pudo registrar la venta:</div>
                        <span x-text="errorMsg"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel lateral: Resumen + Resultado -->
        <div class="space-y-5" id="checkout-sidebar-summary">

            <!-- Resumen del pedido -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4 sticky top-20">
                <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-3">Resumen del Pedido</h3>

                <div x-show="!form.product_id" class="text-center py-6 text-slate-400 text-xs">
                    <i class="fa-solid fa-box-open text-3xl mb-2 block"></i>
                    Selecciona un producto para ver el resumen
                </div>

                <div x-show="form.product_id" class="space-y-3 text-sm">
                    <div class="flex justify-between items-start">
                        <span class="text-slate-600 text-xs">Producto:</span>
                        <span class="text-slate-900 font-semibold text-xs text-right max-w-[60%]" x-text="selectedProduct.name"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600 text-xs">Cantidad:</span>
                        <span class="font-semibold text-xs" x-text="form.quantity + ' uds'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600 text-xs">Precio unitario venta:</span>
                        <span class="font-semibold text-xs font-mono" x-text="form.selling_price.toFixed(2) + ' BOB'"></span>
                    </div>
                    <div class="border-t border-slate-100 pt-3 space-y-2">
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Costo proveedor:</span>
                            <span x-text="(selectedProduct.cost * form.quantity).toFixed(2) + ' BOB'"></span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Flete estimado:</span>
                            <span>~18–25 BOB</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Comisión plataforma:</span>
                            <span x-text="commission.toFixed(2) + ' BOB'"></span>
                        </div>
                    </div>
                    <div class="bg-slate-900 rounded-xl p-3 space-y-1">
                        <div class="flex justify-between text-xs text-slate-300">
                            <span>Total cobro al cliente:</span>
                            <span class="text-white font-bold font-mono" x-text="totalAmount.toFixed(2) + ' BOB'"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Tu ganancia estimada:</span>
                            <span class="text-emerald-400 font-extrabold font-mono text-base" x-text="'~' + profit.toFixed(2) + ' BOB'"></span>
                        </div>
                    </div>
                </div>

                <!-- Método seleccionado -->
                <div x-show="form.payment_method" class="bg-sky-50 border border-sky-200 rounded-xl p-3 text-xs text-sky-800">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    <span x-show="form.payment_method === 'cash_on_delivery_bob'"><strong>Con recaudo:</strong> Cobro en efectivo al momento de la entrega (Contra entrega).</span>
                    <span x-show="form.payment_method === 'qr_interoperable'"><strong>Sin recaudo (Pagado):</strong> Pedido cancelado previamente vía QR Simple (Entrega sin cobro en puerta).</span>
                </div>
            </div>

            <!-- Resultado del Pedido -->
            <div id="pedido-resultado-card" x-show="result" x-cloak class="bg-white rounded-2xl border-2 border-emerald-300 shadow-lg overflow-hidden">
                <div class="bg-emerald-500 text-white p-4 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-xl"></i>
                    <div>
                        <div class="font-bold text-sm">¡Pedido Registrado con Éxito!</div>
                        <div class="text-xs text-emerald-100" x-text="result?.order?.order_number"></div>
                    </div>
                </div>
                <div class="p-4 space-y-4 text-xs">

                    <!-- Guía Logística -->
                    <div x-show="result?.waybill">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Guía de Remisión DEX</div>
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 space-y-1 font-mono text-slate-700">
                            <div class="flex justify-between">
                                <span>Nro. Guía:</span>
                                <span class="font-bold text-slate-900" x-text="result?.waybill?.waybill_number"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Transportadora:</span>
                                <span x-text="result?.waybill?.carrier"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Monto COD:</span>
                                <span class="text-emerald-600 font-bold" x-text="result?.waybill?.cod_collection_amount_bob + ' BOB'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Resultado para Binance Pay -->
                    <div x-show="result?.payment?.provider === 'binance_pay'" class="bg-amber-50 border-2 border-amber-300 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 font-bold text-sm text-amber-950">
                                <i class="fa-brands fa-bitcoin text-amber-500 text-lg"></i>
                                <span>Orden Binance Pay Generada</span>
                            </div>
                            <span class="bg-amber-200 text-amber-950 font-black px-2.5 py-1 rounded-lg font-mono text-xs"
                                  x-text="(result?.payment?.order_amount_usdt || ((result?.order?.total_amount_bob || 0) / {{ $bcbOfficialRate ?? 11.75 }}).toFixed(2)) + ' USDT'"></span>
                        </div>
                        <div class="flex flex-col items-center gap-2 bg-white p-4 rounded-xl border border-amber-200 shadow-xs">
                            <img :src="result?.payment?.qr_image_url || result?.payment?.qr_code_url" alt="QR Binance Pay"
                                class="w-44 h-44 rounded-xl border-4 border-amber-100 shadow-sm" />
                            <div class="text-center space-y-0.5">
                                <div class="text-[11px] font-bold text-slate-800" x-text="'Ref: ' + (result?.payment?.merchant_trade_no || result?.order?.order_number)"></div>
                                <div class="text-[10px] text-slate-500">Escanea desde tu Binance App o Wallet Cripto</div>
                            </div>
                        </div>
                        <div x-show="result?.payment?.checkout_url || result?.payment?.payment_link" class="text-center pt-1">
                            <a :href="result?.payment?.checkout_url || result?.payment?.payment_link" target="_blank"
                               class="btn-scarlet bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold px-4 py-2.5 rounded-xl text-xs inline-flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                <span>Abrir y Pagar en Binance Pay</span>
                            </a>
                        </div>
                    </div>

                    <!-- Resultado para QR Simple Interoperable -->
                    <div x-show="result?.payment?.provider === 'qr_interoperable'" class="bg-sky-50 border-2 border-sky-300 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 font-bold text-sm text-sky-950">
                                <i class="fa-solid fa-qrcode text-sky-600 text-lg"></i>
                                <span>QR Simple Bolivia Generado</span>
                            </div>
                            <span class="bg-sky-200 text-sky-950 font-black px-2.5 py-1 rounded-lg font-mono text-xs"
                                  x-text="(parseFloat(result?.order?.total_amount_bob || 0)).toFixed(2) + ' BOB'"></span>
                        </div>
                        <div class="flex flex-col items-center gap-2 bg-white p-4 rounded-xl border border-sky-200 shadow-xs">
                            <img :src="result?.payment?.qr_image_url" alt="QR Simple Bolivia"
                                class="w-44 h-44 rounded-xl border-4 border-sky-100 shadow-sm" />
                            <div class="text-center space-y-0.5">
                                <div class="text-[11px] font-bold text-slate-800" x-text="'ID Transacción: ' + result?.payment?.qr_identifier"></div>
                                <div class="text-[10px] text-slate-500">Escanea con app de Banco Unión, BCP, BNB, Bisa, Ganadero o Tigo Money</div>
                            </div>
                        </div>
                    </div>

                    <!-- Resultado para Efectivo COD -->
                    <div x-show="result?.order?.payment_method === 'cash_on_delivery_bob'" class="bg-emerald-50 border-2 border-emerald-300 rounded-xl p-4 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-sm text-emerald-950">
                            <i class="fa-solid fa-money-bill-wave text-emerald-600"></i>
                            <span>Cobro en Efectivo contra Entrega Asignado (Con recaudo)</span>
                        </div>
                        <p class="text-xs text-emerald-800">
                            Modalidad <strong>Con recaudo (Contra entrega)</strong>: El transportista de DEX Bolivia Express cobrará <strong class="font-mono text-emerald-900" x-text="(parseFloat(result?.order?.total_amount_bob || 0)).toFixed(2) + ' BOB'"></strong> en billetes al entregar en el domicilio.
                        </p>
                    </div>

                    <!-- Resultado para Takenos -->
                    <div x-show="result?.payment?.provider === 'takenos'" class="bg-purple-50 border-2 border-purple-300 rounded-xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 font-bold text-sm text-purple-950">
                                <i class="fa-solid fa-wallet text-purple-600"></i>
                                <span>Enlace Takenos Generado</span>
                            </div>
                            <span class="bg-purple-200 text-purple-950 font-black px-2.5 py-1 rounded-lg font-mono text-xs"
                                  x-text="(result?.payment?.amount_usd || 0) + ' USD'"></span>
                        </div>
                        <div class="pt-2 text-center">
                            <a :href="result?.payment?.payment_url || result?.payment?.payment_link" target="_blank"
                               class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs inline-flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                <span>Pagar con Saldo Takenos</span>
                            </a>
                        </div>
                    </div>

                    <!-- Distribución financiera -->
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Distribución Financiera</div>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-600">Total del pedido:</span>
                                <span class="font-bold font-mono" x-text="parseFloat(result?.order?.total_amount_bob).toFixed(2) + ' BOB'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Flete DEX:</span>
                                <span class="font-mono" x-text="parseFloat(result?.order?.shipping_fee_bob).toFixed(2) + ' BOB'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Comisión Bolidrop:</span>
                                <span class="font-mono" x-text="parseFloat(result?.order?.platform_fee_bob).toFixed(2) + ' BOB'"></span>
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-1 text-emerald-700 font-bold">
                                <span>Tu ganancia neta:</span>
                                <span class="font-mono" x-text="parseFloat(result?.order?.dropshipper_profit_bob).toFixed(2) + ' BOB'"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button @click="resetForm()" class="w-1/2 text-center text-xs font-semibold text-slate-600 hover:text-slate-900 transition py-2.5 border border-slate-200 rounded-xl bg-slate-50 hover:bg-slate-100">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Otro pedido
                        </button>
                        <a href="{{ url('/panel/dropshipper') }}" class="btn-scarlet w-1/2 text-center text-xs font-bold py-2.5 rounded-xl shadow-sm flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-wallet"></i> Ver en Billetera
                        </a>
                    </div>
                </div>
            </div>

            <!-- Error -->
            <div x-show="errorMsg" x-cloak class="bg-red-50 border border-red-200 rounded-2xl p-4 text-xs text-red-700 flex items-start gap-2">
                <i class="fa-solid fa-circle-xmark mt-0.5 text-red-500"></i>
                <div>
                    <div class="font-bold mb-1">Error al procesar el pedido:</div>
                    <span x-text="errorMsg"></span>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function checkoutDemo() {
    return {
        loading: false,
        result: null,
        errorMsg: null,
        priceError: false,
        productDropdownOpen: false,
        productSearchInput: '',
        apiToken: '{{ $currentApiToken ?? "" }}',
        catalogProducts: {{ Js::from($productsJson ?? []) }},
        availableWarehouses: [],
        selectedProduct: { name: '', cost: 0, suggested: 0, min_price: 0, provider: '', stock: 0 },
        totalAmount: 0,
        profit: 0,
        commission: 0,
        form: {
            product_id: '{{ $selectedProduct?->id ?? ($products->first()?->id ?? "") }}',
            quantity: 1,
            selling_price: {{ $selectedProduct?->suggested_price_bob ?? ($products->first()?->suggested_price_bob ?? 0) }},
            warehouse_id: '{{ $warehouses->first()?->id ?? "" }}',
            carrier_code: '{{ $carriers->first()?->code ?? "dex_bolivia" }}',
            customer_name: '',
            customer_phone: '',
            customer_document_id: '',
            shipping_department: 'Santa Cruz',
            shipping_city: 'Santa Cruz de la Sierra',
            shipping_address: '',
            shipping_reference: '',
            payment_method: 'qr_interoperable',
        },

        get filteredProducts() {
            if (!this.productSearchInput || this.productSearchInput.trim() === '') {
                return this.catalogProducts;
            }
            const q = this.productSearchInput.toLowerCase().trim();
            return this.catalogProducts.filter(p => {
                const nameMatch = (p.name || '').toLowerCase().includes(q);
                const skuMatch  = (p.sku || '').toLowerCase().includes(q);
                const provMatch = (p.provider || '').toLowerCase().includes(q);
                const costMatch = (p.cost || '').toString().includes(q);
                const suggMatch = (p.suggested || '').toString().includes(q);
                return nameMatch || skuMatch || provMatch || costMatch || suggMatch;
            });
        },

        init() {
            this.$nextTick(() => {
                if (this.catalogProducts.length > 0) {
                    const initialId = this.form.product_id || this.catalogProducts[0].id;
                    const found = this.catalogProducts.find(p => p.id == initialId) || this.catalogProducts[0];
                    if (found) {
                        this.selectProduct(found, false);
                    }
                }
                if (!this.form.customer_name) {
                    this.fillDemoData();
                }
            });
        },

        selectProduct(p, overwritePrice = true) {
            if (!p) return;
            this.form.product_id = p.id;
            this.productSearchInput = p.name;
            this.selectedProduct = {
                name:      p.name,
                cost:      parseFloat(p.cost) || 0,
                suggested: parseFloat(p.suggested) || 0,
                min_price: parseFloat(p.min_price) || 0,
                provider:  p.provider || '',
                stock:     parseInt(p.stock) || 0,
            };
            this.availableWarehouses = p.warehouses || [];

            // Autocompletar precio sugerido
            if (overwritePrice || !this.form.selling_price || this.form.selling_price === 0) {
                this.form.selling_price = this.selectedProduct.suggested;
            }

            // Autoseleccionar la bodega si hay bodegas disponibles con stock
            if (this.availableWarehouses.length > 0) {
                const exists = this.availableWarehouses.some(w => w.id === this.form.warehouse_id);
                if (!exists) {
                    this.form.warehouse_id = this.availableWarehouses[0].id;
                }
            }

            this.productDropdownOpen = false;
            this.calcTotals();
        },

        clearProductSearch() {
            this.productSearchInput = '';
            this.productDropdownOpen = true;
        },

        setPrice(amount) {
            this.form.selling_price = parseFloat(amount) || 0;
            this.calcTotals();
        },

        profitMarginPercent() {
            const total = (parseFloat(this.form.selling_price) || 0) * (parseInt(this.form.quantity) || 1);
            if (total <= 0) return '0.0';
            return ((this.profit / total) * 100).toFixed(1);
        },

        calcTotals() {
            const qty      = parseInt(this.form.quantity) || 1;
            const price    = parseFloat(this.form.selling_price) || 0;
            const cost     = this.selectedProduct.cost || 0;
            const minPrice = this.selectedProduct.min_price || 0;

            this.priceError = price < minPrice && minPrice > 0;
            this.totalAmount = price * qty;
            this.commission  = this.totalAmount * 0.04;
            const freight   = 18;
            this.profit = Math.max(0, this.totalAmount - (cost * qty) - freight - this.commission);
        },

        missingFieldsSummary() {
            const missing = [];
            if (!this.form.product_id) missing.push('Producto');
            if (!this.form.warehouse_id) missing.push('Bodega');
            if (!this.form.carrier_code) missing.push('Courier');
            if (!this.form.customer_name?.trim()) missing.push('Nombre');
            if (!this.form.customer_phone?.trim()) missing.push('Teléfono');
            if (!this.form.shipping_address?.trim()) missing.push('Dirección');
            return missing.join(', ') || 'Todo listo';
        },

        fillDemoData() {
            if (!this.form.product_id && this.catalogProducts.length > 0) {
                this.selectProduct(this.catalogProducts[0]);
            }
            if (!this.form.warehouse_id) {
                const firstWh = document.querySelector('select[x-model="form.warehouse_id"] option:nth-child(2)');
                if (firstWh) this.form.warehouse_id = firstWh.value;
            }
            if (!this.form.carrier_code) {
                const firstCr = document.querySelector('select[x-model="form.carrier_code"] option:nth-child(2)');
                if (firstCr) this.form.carrier_code = firstCr.value;
            }
            this.form.customer_name = 'Rodrigo Morales Vaca';
            this.form.customer_phone = '+591 78512345';
            this.form.customer_document_id = '8342190 SC';
            this.form.shipping_department = 'Santa Cruz';
            this.form.shipping_city = 'Santa Cruz de la Sierra';
            this.form.shipping_address = 'Av. San Martín #450, Barrio Equipetrol';
            this.form.shipping_reference = 'Frente a la farmacia Chávez, timbre blanco';
            this.calcTotals();
        },

        isFormValid() {
            return this.form.product_id
                && this.form.warehouse_id
                && this.form.carrier_code
                && this.form.customer_name?.trim()
                && this.form.customer_phone?.trim()
                && this.form.shipping_department
                && this.form.shipping_city?.trim()
                && this.form.shipping_address?.trim()
                && this.form.payment_method
                && !this.priceError;
        },

        async submitOrder() {
            if (!this.isFormValid()) {
                this.errorMsg = 'Por favor completa todos los campos requeridos antes de confirmar.';
                return;
            }

            this.loading = true;
            this.result  = null;
            this.errorMsg = null;

            try {
                // 1. Obtener autenticación (usar token del usuario activo o autenticar demo)
                let token = this.apiToken;
                if (!token) {
                    const loginRes = await fetch('/api/v1/auth/login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ email: 'dropshipper@bolidrop.bo', password: 'password123' }),
                    });
                    const loginData = await loginRes.json();
                    
                    if (!loginData.data?.token) {
                        throw new Error(loginData.message || 'Error de autenticación demo en el servidor.');
                    }
                    token = loginData.data.token;
                    this.apiToken = token;
                }

                // 2. Despachar orden COD
                const payload = {
                    warehouse_id:        this.form.warehouse_id,
                    carrier_code:        this.form.carrier_code,
                    customer_name:       this.form.customer_name,
                    customer_phone:      this.form.customer_phone,
                    customer_document_id: this.form.customer_document_id || null,
                    shipping_department: this.form.shipping_department,
                    shipping_city:       this.form.shipping_city,
                    shipping_address:    this.form.shipping_address,
                    shipping_reference:  this.form.shipping_reference || null,
                    payment_method:      this.form.payment_method,
                    items: [{
                        product_id:        this.form.product_id,
                        quantity:          parseInt(this.form.quantity) || 1,
                        selling_price_bob: parseFloat(this.form.selling_price),
                    }],
                };

                const res = await fetch('/api/v1/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    this.result = data.data;
                    this.$nextTick(() => {
                        const resElem = document.getElementById('pedido-resultado-card');
                        if (resElem) {
                            resElem.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else {
                            window.scrollTo({ top: 300, behavior: 'smooth' });
                        }
                    });
                } else {
                    this.errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : JSON.stringify(data));
                }
            } catch (e) {
                this.errorMsg = e.message || 'Error de comunicación al procesar el pedido.';
            } finally {
                this.loading = false;
            }
        },

        resetForm() {
            this.result   = null;
            this.errorMsg = null;
            this.form.customer_name       = '';
            this.form.customer_phone      = '';
            this.form.customer_document_id = '';
            this.form.shipping_address    = '';
            this.form.shipping_reference  = '';
        },
    };
}
</script>
@endsection



