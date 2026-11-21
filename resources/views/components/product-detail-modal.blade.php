<!-- Modal de Detalle Extendido de Producto + Productos Relacionados -->
<template x-teleport="body">
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-[120] overflow-y-auto"
         role="dialog" aria-modal="true" aria-labelledby="modal-product-title">
        
        <!-- Backdrop translúcido con desenfoque de fondo -->
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
             @click="closeProductModal()"></div>

        <!-- Wrapper centrado -->
        <div class="flex min-h-full items-center justify-center p-3 sm:p-6 text-center">
            
            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all w-full max-w-5xl border border-slate-200/90 flex flex-col max-h-[92vh]">

                <!-- Barra Superior con Badges y Cerrar -->
                <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2 flex-wrap min-w-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-[10px] uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <i class="fa-solid fa-truck-fast text-[9px]"></i>
                            <span>Garantía Contra Entrega (COD)</span>
                        </span>
                        <template x-if="selectedProduct?.internal_code">
                            <span class="font-mono font-bold text-[10px] bg-slate-200/80 text-slate-700 px-2 py-0.5 rounded border border-slate-300"
                                  x-text="selectedProduct.internal_code"></span>
                        </template>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider font-heading truncate"
                              x-text="selectedProduct?.category_name"></span>
                    </div>

                    <button type="button" @click="closeProductModal()"
                        class="w-8 h-8 rounded-full bg-white hover:bg-slate-200 text-slate-400 hover:text-slate-700 border border-slate-200 flex items-center justify-center transition cursor-pointer shrink-0"
                        title="Cerrar (Esc)">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Contenido Principal Scrollable -->
                <div class="p-5 sm:p-7 overflow-y-auto overflow-x-hidden flex-grow space-y-7">
                    
                    <!-- Fila Superior: Visual + Datos Operativos/Financieros -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                        
                        <!-- Columna Izquierda: Galería Visual y Proveedor (5 cols) -->
                        <div class="lg:col-span-5 space-y-4">
                            <!-- Visualizador de Imagen -->
                            <div class="relative bg-slate-50 rounded-2xl border border-slate-200 p-4 h-72 sm:h-80 flex items-center justify-center overflow-hidden shadow-inner group">
                                <template x-if="selectedProduct && selectedProduct.images && selectedProduct.images.length > 0">
                                    <img :src="selectedProduct.images[activeImageIdx] || selectedProduct.images[0]"
                                         :alt="selectedProduct.name"
                                         class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105">
                                </template>
                                <template x-if="!selectedProduct || !selectedProduct.images || selectedProduct.images.length === 0">
                                    <div class="w-24 h-24 rounded-2xl bg-indigo-50 border border-indigo-200 text-[#4F46E5] flex items-center justify-center text-5xl shadow-inner">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>
                                </template>
                            </div>

                            <!-- Selector de Miniaturas (si hay más de 1 imagen) -->
                            <template x-if="selectedProduct && selectedProduct.images && selectedProduct.images.length > 1">
                                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                    <template x-for="(img, idx) in selectedProduct.images" :key="idx">
                                        <button type="button" @click="activeImageIdx = idx"
                                            class="w-14 h-14 rounded-xl border-2 p-1 bg-slate-50 shrink-0 transition overflow-hidden cursor-pointer"
                                            :class="activeImageIdx === idx ? 'border-[#4F46E5] ring-2 ring-[#4F46E5]/20 scale-105' : 'border-slate-200 hover:border-slate-300 opacity-70 hover:opacity-100'">
                                            <img :src="img" class="w-full h-full object-contain">
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <!-- Proveedor Mayorista Verificado -->
                            <div class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-2">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-shield-halved text-[#4F46E5]"></i>
                                    <span>Proveedor Mayorista Oficial</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-extrabold text-[#0F172A]" x-text="selectedProduct?.provider_name"></span>
                                    <span class="text-slate-500 font-medium" x-text="selectedProduct?.provider_city"></span>
                                </div>
                                <template x-if="selectedProduct?.provider_phone">
                                    <div class="text-[11px] text-slate-500 flex items-center gap-1 pt-1 border-t border-indigo-100/60 font-mono">
                                        <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                                        <span x-text="selectedProduct.provider_phone"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Columna Derecha: Título, SKU, Precios, Almacén (7 cols) -->
                        <div class="lg:col-span-7 space-y-4">
                            
                            <!-- Header de Producto -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="text-xs font-mono font-bold bg-slate-100 text-[#0F172A] px-2.5 py-1 rounded-lg border border-slate-200">
                                            SKU: <strong x-text="selectedProduct?.sku"></strong>
                                        </span>
                                        <button type="button" @click="copyModalSkuText(selectedProduct?.sku)"
                                            class="px-2 py-1 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-xs font-medium text-slate-600 hover:text-[#4F46E5] transition cursor-pointer flex items-center gap-1">
                                            <i class="fa-regular" :class="copiedModalSku ? 'fa-circle-check text-emerald-600' : 'fa-copy'"></i>
                                            <span x-text="copiedModalSku ? '¡Copiado!' : 'Copiar'"></span>
                                        </button>
                                    </div>
                                    <div class="text-xs text-slate-500 font-medium">
                                        <i class="fa-solid fa-scale-balanced text-slate-400 mr-1"></i>Peso:
                                        <strong class="text-[#0F172A] font-mono" x-text="(selectedProduct?.weight_kg || 0.5) + ' kg'"></strong>
                                    </div>
                                </div>

                                <h2 id="modal-product-title"
                                    class="text-xl sm:text-2xl font-extrabold text-[#0F172A] font-heading leading-tight"
                                    x-text="selectedProduct?.name"></h2>
                                
                                <div class="bg-slate-50/80 rounded-xl p-3 border border-slate-200/80 text-xs text-slate-600 leading-relaxed max-h-28 overflow-y-auto">
                                    <div class="font-bold text-[10px] uppercase text-slate-400 mb-1">Descripción y Detalles del Producto:</div>
                                    <p x-text="selectedProduct?.description || 'Producto verificado para distribución nacional con garantía y cobro contra entrega.'"></p>
                                </div>
                            </div>

                            <!-- Simulador Financiero en BOB -->
                            <div class="rounded-2xl bg-gradient-to-br from-slate-50 via-indigo-50/30 to-emerald-50/30 p-3.5 border border-slate-200 space-y-2.5">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
                                    <span>Estructura de Precios Oficiales (BOB)</span>
                                    <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-mono font-extrabold text-[10px]"
                                          x-text="'+' + (selectedProduct?.roi_percent || 0) + '% ROI'"></span>
                                </div>

                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div class="bg-white rounded-xl p-2 border border-slate-200/80 shadow-2xs">
                                        <div class="text-[9px] text-slate-400 font-semibold uppercase">Costo Mayorista</div>
                                        <div class="text-xs sm:text-sm font-extrabold font-mono text-[#0F172A] mt-0.5"
                                             x-text="(Number(selectedProduct?.cost_price_bob || 0)).toFixed(2) + ' BOB'"></div>
                                    </div>
                                    <div class="bg-white rounded-xl p-2 border border-slate-200/80 shadow-2xs">
                                        <div class="text-[9px] text-slate-400 font-semibold uppercase">Mínimo Permitido</div>
                                        <div class="text-xs sm:text-sm font-extrabold font-mono text-slate-700 mt-0.5"
                                             x-text="(Number(selectedProduct?.min_price_bob || 0)).toFixed(2) + ' BOB'"></div>
                                    </div>
                                    <div class="bg-white rounded-xl p-2 border border-indigo-200 shadow-2xs">
                                        <div class="text-[9px] text-[#4F46E5] font-semibold uppercase">Sugerido Tienda</div>
                                        <div class="text-xs sm:text-sm font-extrabold font-mono text-[#4F46E5] mt-0.5"
                                             x-text="(Number(selectedProduct?.suggested_price_bob || 0)).toFixed(2) + ' BOB'"></div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-1.5 border-t border-slate-200/80 text-xs">
                                    <span class="font-semibold text-slate-600 flex items-center gap-1.5">
                                        <i class="fa-solid fa-coins text-amber-500"></i>
                                        <span>Tu Ganancia Neta Estimada:</span>
                                    </span>
                                    <span class="text-sm font-extrabold font-mono text-emerald-600"
                                          x-text="'+' + (Number(selectedProduct?.margin_bob || 0)).toFixed(2) + ' BOB'"></span>
                                </div>
                            </div>

                            <!-- Selector de Bodega con Stock -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
                                    <span><i class="fa-solid fa-warehouse text-[#4F46E5] mr-1"></i>Bodega de Despacho Inmediato:</span>
                                    <span class="font-mono text-[11px] text-slate-500 font-normal">
                                        Total: <strong class="text-[#0F172A] font-bold" x-text="(selectedProduct?.total_stock || 0) + ' uds'"></strong>
                                    </span>
                                </label>

                                <div class="space-y-1 max-h-36 overflow-y-auto pr-1">
                                    <template x-for="wh in (selectedProduct?.stock_by_wh || [])" :key="wh.warehouse_id">
                                        <label class="flex items-center justify-between p-2.5 rounded-xl border cursor-pointer transition"
                                               :class="selectedWarehouseId === wh.warehouse_id ? 'border-[#4F46E5] bg-indigo-50/70 ring-2 ring-[#4F46E5]/20' : 'border-slate-200 hover:bg-slate-50 bg-white'">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <input type="radio" name="modal_warehouse_select" :value="wh.warehouse_id"
                                                       x-model="selectedWarehouseId"
                                                       class="w-4 h-4 text-[#4F46E5] focus:ring-[#4F46E5] border-slate-300">
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-[#0F172A] flex items-center gap-1.5 truncate">
                                                        <span x-text="wh.name"></span>
                                                        <span class="text-[10px] text-slate-400 font-mono" x-text="'(' + wh.code + ')'"></span>
                                                    </div>
                                                    <div class="text-[10px] text-slate-500 truncate" x-text="wh.city + ', ' + wh.department + (wh.address ? ' • ' + wh.address : '')"></div>
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0 pl-2">
                                                <div class="text-xs font-mono font-bold"
                                                     :class="wh.stock > 0 ? 'text-emerald-600' : 'text-slate-400'"
                                                     x-text="wh.stock + ' uds'"></div>
                                                <div class="text-[9px] text-slate-400 uppercase font-semibold">disponibles</div>
                                            </div>
                                        </label>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Fila Inferior: PRODUCTOS RELACIONADOS -->
                    <div class="pt-5 border-t border-slate-200/90 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#4F46E5]"></span>
                                <h3 class="text-xs sm:text-sm font-extrabold text-[#0F172A] uppercase tracking-wider font-heading">
                                    Productos Relacionados y Ganadores
                                </h3>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium">Haz clic en cualquier producto para cambiar de ficha</span>
                        </div>

                        <!-- Grid de Productos Relacionados -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <template x-for="rel in getRelatedProducts()" :key="rel.id">
                                <div @click="switchProduct(rel.id)"
                                     class="p-2.5 rounded-2xl bg-white border border-slate-200 hover:border-[#4F46E5] hover:shadow-md transition cursor-pointer group flex flex-col justify-between">
                                    <div class="space-y-2">
                                        <div class="h-24 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center p-2 overflow-hidden">
                                            <template x-if="rel.images && rel.images.length > 0">
                                                <img :src="rel.images[0]" :alt="rel.name" class="w-full h-full object-contain group-hover:scale-105 transition duration-200">
                                            </template>
                                            <template x-if="!rel.images || rel.images.length === 0">
                                                <div class="text-slate-300 text-2xl"><i class="fa-solid fa-box-open"></i></div>
                                            </template>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-[9px] text-[#4F46E5] font-bold uppercase truncate" x-text="rel.category_name"></div>
                                            <div class="text-xs font-bold text-[#0F172A] line-clamp-1 group-hover:text-[#4F46E5] transition" x-text="rel.name"></div>
                                        </div>
                                    </div>
                                    <div class="pt-2 mt-2 border-t border-slate-100 flex items-center justify-between text-left">
                                        <div>
                                            <div class="text-[9px] text-slate-400 uppercase font-semibold">Sugerido</div>
                                            <div class="text-xs font-extrabold font-mono text-[#0F172A]" x-text="rel.suggested_price_bob.toFixed(0) + ' Bs'"></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-[9px] text-emerald-600 font-bold uppercase">Ganas</div>
                                            <div class="text-xs font-extrabold font-mono text-emerald-600" x-text="'+' + rel.margin_bob.toFixed(0) + ' Bs'"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

                <!-- Footer: Acción de Conversión -->
                <div class="px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                    <div class="text-xs text-slate-500 text-center sm:text-left">
                        Al pulsar vender, se precargará el producto y la bodega elegida en el generador de pedidos COD.
                    </div>

                    <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                        <button type="button" @click="closeProductModal()"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer">
                            Cerrar
                        </button>
                        <a :href="getCheckoutUrl()"
                           class="btn-scarlet px-5 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                            <i class="fa-solid fa-cart-arrow-down"></i>
                            <span>Vender Producto / Crear Pedido COD</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
