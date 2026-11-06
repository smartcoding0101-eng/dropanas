@extends('layouts.app')

@section('title', 'Checkout COD + QR Bolivia | Dropana')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-[#FFFFFF]" x-data="checkoutDemo()">

    <!-- Header -->
    <div class="mb-8 border-b border-slate-200 pb-6">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-[#960016] bg-red-50 border border-red-200 px-2.5 py-0.5 rounded-full">
                Demo en Vivo
            </span>
            <span class="text-xs text-slate-500 font-medium">Flujo real — sin pagos reales</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#960016] font-heading">
            Checkout Contra Entrega (COD) + QR Simple Bolivia
        </h1>
        <p class="text-slate-500 text-sm mt-1">
            Simula el flujo completo: selecciona producto → datos del cliente → elige transportadora → método de pago → genera QR o guía.
        </p>
    </div>

    <!-- Layout 2 columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Formulario principal (2/3) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- PASO 1: Producto -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 p-4 border-b border-slate-200 bg-slate-50">
                    <div class="w-7 h-7 rounded-full bg-[#960016] text-white flex items-center justify-center text-xs font-bold">1</div>
                    <h2 class="font-bold text-[#960016] text-sm font-heading">Producto a Despachar</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Seleccionar Producto</label>
                        <select x-model="form.product_id" @change="onProductChange()"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                            <option value="">— Seleccionar producto del catálogo —</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}"
                                data-cost="{{ $p->cost_price_bob }}"
                                data-suggested="{{ $p->suggested_price_bob }}"
                                data-min="{{ $p->min_price_bob }}"
                                data-name="{{ $p->name }}">
                                {{ $p->name }} — Costo: {{ number_format($p->cost_price_bob, 2) }} BOB
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4" x-show="form.product_id">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cantidad</label>
                            <input type="number" x-model.number="form.quantity" min="1" max="50" @input="calcTotals()"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                Precio de Venta (BOB)
                                <span class="text-slate-400 font-normal" x-show="selectedProduct.min_price > 0">
                                    — Mín: <span x-text="selectedProduct.min_price.toFixed(2)"></span>
                                </span>
                            </label>
                            <input type="number" x-model.number="form.selling_price" step="0.50" @input="calcTotals()"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none" />
                        </div>
                    </div>

                    <!-- Alerta precio mínimo -->
                    <div x-show="priceError" x-cloak class="flex items-start gap-2 bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-700">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                        <span>El precio de venta está por debajo del mínimo permitido por el proveedor.</span>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Bodega y Transportadora -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 p-4 border-b border-slate-200 bg-slate-50">
                    <div class="w-7 h-7 rounded-full bg-[#960016] text-white flex items-center justify-center text-xs font-bold">2</div>
                    <h2 class="font-bold text-[#960016] text-sm font-heading">Bodega de Despacho y Transportadora</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bodega de Origen</label>
                            <select x-model="form.warehouse_id"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:border-[#E88A38] outline-none bg-white">
                                <option value="">— Seleccionar bodega —</option>
                                @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->city }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Transportadora COD</label>
                            <select x-model="form.carrier_code"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:border-[#E88A38] outline-none bg-white">
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
                    <div class="w-7 h-7 rounded-full bg-[#960016] text-white flex items-center justify-center text-xs font-bold">3</div>
                    <h2 class="font-bold text-[#960016] text-sm font-heading">Datos del Cliente Final (Bolivia)</h2>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nombre Completo</label>
                        <input type="text" x-model="form.customer_name" placeholder="Ej: Rodrigo Morales Vaca"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#E88A38] outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Teléfono (WhatsApp)</label>
                        <input type="text" x-model="form.customer_phone" placeholder="+591 78512345"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#E88A38] outline-none font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Carnet de Identidad (CI / NIT)</label>
                        <input type="text" x-model="form.customer_document_id" placeholder="Ej: 7891234 SC"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#E88A38] outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Departamento de Entrega</label>
                        <select x-model="form.shipping_department"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:border-[#E88A38] outline-none bg-white">
                            <option value="">— Seleccionar departamento —</option>
                            @foreach(['Santa Cruz','La Paz','Cochabamba','Oruro','Potosí','Chuquisaca','Tarija','Beni','Pando'] as $dep)
                            <option value="{{ $dep }}">{{ $dep }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Ciudad</label>
                        <input type="text" x-model="form.shipping_city" placeholder="Ej: Santa Cruz de la Sierra"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#E88A38] outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Dirección de Entrega</label>
                        <input type="text" x-model="form.shipping_address" placeholder="Ej: Av. San Martín #123, Zona Centro"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#E88A38] outline-none" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Referencia de Ubicación</label>
                        <input type="text" x-model="form.shipping_reference" placeholder="Frente a la farmacia, portón café con timbre blanco"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#E88A38] outline-none" />
                    </div>
                </div>
            </div>

            <!-- PASO 4: Método de Cobro -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 p-4 border-b border-slate-200 bg-slate-50">
                    <div class="w-7 h-7 rounded-full bg-[#960016] text-white flex items-center justify-center text-xs font-bold">4</div>
                    <h2 class="font-bold text-[#960016] text-sm font-heading">Método de Cobro al Entregar (COD Bolivia)</h2>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- COD Efectivo -->
                    <label :class="form.payment_method === 'cash_on_delivery_bob' ? 'ring-2 ring-emerald-500 border-emerald-300 bg-emerald-50' : 'border-slate-200 bg-white'"
                        class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition hover:border-slate-300">
                        <input type="radio" x-model="form.payment_method" value="cash_on_delivery_bob" class="text-emerald-600 w-4 h-4" />
                        <div>
                            <div class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-money-bill-wave text-emerald-500"></i> Efectivo contra entrega
                            </div>
                            <div class="text-xs text-slate-500">COD en bolivianos (BOB)</div>
                        </div>
                    </label>

                    <!-- QR Interoperable -->
                    <label :class="form.payment_method === 'qr_interoperable' ? 'ring-2 ring-sky-500 border-sky-300 bg-sky-50' : 'border-slate-200 bg-white'"
                        class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition hover:border-slate-300">
                        <input type="radio" x-model="form.payment_method" value="qr_interoperable" class="text-sky-600 w-4 h-4" />
                        <div>
                            <div class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-qrcode text-sky-500"></i> QR Simple Interoperable
                            </div>
                            <div class="text-xs text-slate-500">Banco Unión, BCP, Bisa, Ganadero...</div>
                        </div>
                    </label>

                    <!-- Binance Pay -->
                    <label :class="form.payment_method === 'binance_pay' ? 'ring-2 ring-amber-400 border-amber-300 bg-amber-50' : 'border-slate-200 bg-white'"
                        class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition hover:border-slate-300">
                        <input type="radio" x-model="form.payment_method" value="binance_pay" class="text-amber-500 w-4 h-4" />
                        <div>
                            <div class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fa-brands fa-bitcoin text-amber-500"></i> Binance Pay (USDT)
                            </div>
                            <div class="text-xs text-slate-500">Criptomonedas estables — Sin fronteras</div>
                        </div>
                    </label>

                    <!-- Takenos -->
                    <label :class="form.payment_method === 'takenos' ? 'ring-2 ring-purple-500 border-purple-300 bg-purple-50' : 'border-slate-200 bg-white'"
                        class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition hover:border-slate-300">
                        <input type="radio" x-model="form.payment_method" value="takenos" class="text-purple-600 w-4 h-4" />
                        <div>
                            <div class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-wallet text-purple-500"></i> Takenos / USD Digital
                            </div>
                            <div class="text-xs text-slate-500">Dólares digitales — Remesas</div>
                        </div>
                    </label>
                </div>

                <!-- Panel Dinámico de Acción Inmediata según Moneda / Método Seleccionado -->
                <div class="mx-5 mb-5 p-4 rounded-xl border transition-all duration-200"
                     :class="{
                         'bg-emerald-50 border-emerald-200 text-emerald-900': form.payment_method === 'cash_on_delivery_bob',
                         'bg-sky-50 border-sky-200 text-sky-900': form.payment_method === 'qr_interoperable',
                         'bg-amber-50 border-amber-300 text-amber-950': form.payment_method === 'binance_pay',
                         'bg-purple-50 border-purple-200 text-purple-950': form.payment_method === 'takenos'
                     }">
                    
                    <!-- 1. DETALLE EFECTIVO -->
                    <template x-if="form.payment_method === 'cash_on_delivery_bob'">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold text-sm text-emerald-800">
                                    <i class="fa-solid fa-money-bill-wave text-emerald-600"></i>
                                    <span>Recaudación en Efectivo al Entregar (COD)</span>
                                </div>
                                <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full font-mono"
                                      x-text="(totalAmount || 0).toFixed(2) + ' BOB'"></span>
                            </div>
                            <p class="text-xs text-emerald-700 leading-relaxed">
                                El transportista de <strong>DEX Bolivia Express</strong> cobrará este importe exacto en billetes al momento de entregar el producto en destino.
                            </p>
                        </div>
                    </template>

                    <!-- 2. DETALLE QR SIMPLE -->
                    <template x-if="form.payment_method === 'qr_interoperable'">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold text-sm text-sky-800">
                                    <i class="fa-solid fa-qrcode text-sky-600"></i>
                                    <span>QR Simple Interoperable Bolivia (ACH)</span>
                                </div>
                                <span class="text-xs bg-sky-100 text-sky-800 font-bold px-2.5 py-1 rounded-full font-mono"
                                      x-text="(totalAmount || 0).toFixed(2) + ' BOB'"></span>
                            </div>
                            <p class="text-xs text-sky-700 leading-relaxed">
                                Se emitirá un código QR interoperable compatible con <strong>Banco Unión, BCP, BNB, Banco Bisa, Ganadero, Mercantil Santa Cruz y Tigo Money</strong>.
                            </p>
                        </div>
                    </template>

                    <!-- 3. DETALLE BINANCE PAY (CRIPTO) -->
                    <template x-if="form.payment_method === 'binance_pay'">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold text-sm text-amber-900">
                                    <i class="fa-brands fa-bitcoin text-amber-600 text-base"></i>
                                    <span>Pasarela Cripto Activa: Binance Pay (USDT)</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm bg-amber-200 text-amber-950 font-black px-3 py-1 rounded-full font-mono"
                                          x-text="((totalAmount || 0) / 12.06).toFixed(2) + ' USDT'"></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs bg-white/80 p-3 rounded-lg border border-amber-200/60">
                                <div>
                                    <span class="text-slate-500">Monto Base en Bolivianos:</span>
                                    <strong class="block text-slate-800 font-mono" x-text="(totalAmount || 0).toFixed(2) + ' BOB'"></strong>
                                </div>
                                <div>
                                    <span class="text-slate-500">Tasa Oficial BCB Aplicada:</span>
                                    <strong class="block text-amber-800 font-mono">12.06 BOB / USD</strong>
                                </div>
                            </div>
                            <p class="text-xs text-amber-800 leading-relaxed">
                                Sin comisiones de red Blockchain. Al presionar confirmar se generará el <strong>código QR de Binance Pay</strong> y el enlace directo para pagar desde la App de Binance.
                            </p>
                        </div>
                    </template>

                    <!-- 4. DETALLE TAKENOS -->
                    <template x-if="form.payment_method === 'takenos'">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold text-sm text-purple-800">
                                    <i class="fa-solid fa-wallet text-purple-600"></i>
                                    <span>Dólares Digitales: Takenos</span>
                                </div>
                                <span class="text-xs bg-purple-100 text-purple-800 font-bold px-2.5 py-1 rounded-full font-mono"
                                      x-text="((totalAmount || 0) / 12.06).toFixed(2) + ' USD'"></span>
                            </div>
                            <p class="text-xs text-purple-700 leading-relaxed">
                                Liquidación con saldo Takenos o TakeCard internacional al tipo de cambio oficial de 12.06 BOB.
                            </p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Botón de Enviar (Primario Escarlata #ED2100 con Títulos y Feedback de Estado) -->
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
                <div x-show="!isFormValid() && !loading" class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-[#E88A38]"></i>
                        <span>Campos requeridos: <strong class="font-bold underline" x-text="missingFieldsSummary()"></strong></span>
                    </div>
                    <button @click="fillDemoData()" type="button" class="font-bold text-[#960016] hover:text-[#ED2100] hover:underline flex items-center gap-1">
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
                    <span x-show="form.payment_method === 'cash_on_delivery_bob'">Cobro en efectivo al momento de la entrega.</span>
                    <span x-show="form.payment_method === 'qr_interoperable'">Se generará un QR bancario interoperable para que el cliente escanee.</span>
                    <span x-show="form.payment_method === 'binance_pay'">Se generará un código de pago Binance Pay en USDT.</span>
                    <span x-show="form.payment_method === 'takenos'">Se generará un link de pago Takenos en USD digital.</span>
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
                                  x-text="(result?.payment?.order_amount_usdt || ((result?.order?.total_amount_bob || 0) / 12.06).toFixed(2)) + ' USDT'"></span>
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
                            <span>Cobro en Efectivo contra Entrega Asignado</span>
                        </div>
                        <p class="text-xs text-emerald-800">
                            El transportista de DEX Bolivia Express cobrará <strong class="font-mono text-emerald-900" x-text="(parseFloat(result?.order?.total_amount_bob || 0)).toFixed(2) + ' BOB'"></strong> en billetes al entregar en el domicilio.
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
                                <span class="text-slate-600">Comisión Dropana:</span>
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
        selectedProduct: { name: '', cost: 0, suggested: 0, min_price: 0 },
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

        init() {
            this.$nextTick(() => {
                if (this.form.product_id) {
                    this.onProductChange();
                }
                if (!this.form.customer_name) {
                    this.fillDemoData();
                }
            });
        },

        onProductChange() {
            const sel = document.querySelector(`select[x-model="form.product_id"] option[value="${this.form.product_id}"]`);
            if (sel) {
                this.selectedProduct = {
                    name:      sel.getAttribute('data-name') || '',
                    cost:      parseFloat(sel.getAttribute('data-cost')) || 0,
                    suggested: parseFloat(sel.getAttribute('data-suggested')) || 0,
                    min_price: parseFloat(sel.getAttribute('data-min')) || 0,
                };
                if (!this.form.selling_price || this.form.selling_price === 0) {
                    this.form.selling_price = this.selectedProduct.suggested;
                }
                this.calcTotals();
            }
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
            if (!this.form.product_id) {
                const firstOpt = document.querySelector('select[x-model="form.product_id"] option:nth-child(2)');
                if (firstOpt) {
                    this.form.product_id = firstOpt.value;
                    this.onProductChange();
                }
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
                // 1. Obtener autenticación demo Dropshipper
                const loginRes = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: 'dropshipper@dropana.bo', password: 'password123' }),
                });
                const loginData = await loginRes.json();
                
                if (!loginData.data?.token) {
                    throw new Error(loginData.message || 'Error de autenticación demo en el servidor.');
                }
                const token = loginData.data.token;

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
