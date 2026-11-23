{{-- ================================================================== --}}
{{-- PRODUCT DETAIL MODAL — Isolated Alpine Component                  --}}
{{-- Triggered via: $dispatch('open-product-modal', { id: '...' })     --}}
{{-- Lives outside the catalog grid DOM to prevent layout corruption   --}}
{{-- ================================================================== --}}
<div x-data="{
    open: false,
    product: null,
    catalog: [],
    activeImg: 0,
    selectedWh: '',
    skuCopied: false,
    infoTab: 'desc',

    init() {
        this.catalog = window.__bolidropCatalog || [];
    },

    openModal(id) {
        const p = this.catalog.find(i => i.id === String(id));
        if (!p) return;
        this.product = p;
        this.activeImg = 0;
        this.skuCopied = false;
        this.infoTab = 'desc';
        this.selectedWh = (p.stock_by_wh && p.stock_by_wh.length > 0) ? p.stock_by_wh[0].warehouse_id : '';
        this.open = true;
        document.body.style.overflow = 'hidden';
    },

    closeModal() {
        this.open = false;
        document.body.style.overflow = '';
    },

    switchProduct(id) {
        this.openModal(id);
    },

    relatedProducts() {
        if (!this.product) return [];
        const all = this.catalog.filter(p => p.id !== this.product.id);
        const same = all.filter(p => p.category_id === this.product.category_id);
        const other = all.filter(p => p.category_id !== this.product.category_id);
        return [...same, ...other].slice(0, 4);
    },

    checkoutUrl() {
        if (!this.product) return '{{ url("/checkout-demo") }}';
        let u = '{{ url("/checkout-demo") }}?product_id=' + encodeURIComponent(this.product.id);
        if (this.selectedWh) u += '&warehouse_id=' + encodeURIComponent(this.selectedWh);
        return u;
    },

    copySku() {
        if (!this.product) return;
        navigator.clipboard.writeText(this.product.sku);
        this.skuCopied = true;
        setTimeout(() => this.skuCopied = false, 2000);
    }
}"
     @open-product-modal.window="openModal($event.detail.id)"
     @keydown.escape.window="if(open) closeModal()"
     x-cloak>

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm"
         @click="closeModal()"></div>

    {{-- Modal Container --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
         class="fixed inset-0 z-[81] flex items-center justify-center p-3 sm:p-6 overflow-y-auto">

        <div @click.stop class="relative w-full max-w-4xl bg-white rounded-3xl border border-slate-200/90 shadow-2xl flex flex-col max-h-[92vh] overflow-hidden">

            {{-- Top gradient beam --}}
            <div class="h-1 w-full bg-gradient-to-r from-[#4F46E5] via-[#38BDF8] to-[#10B981] shrink-0"></div>

            {{-- Header --}}
            <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2 flex-wrap min-w-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#4F46E5] font-heading" x-text="product?.category_name"></span>
                    <span class="text-slate-300">|</span>
                    <button @click="copySku()" class="inline-flex items-center gap-1 text-[11px] font-mono font-bold bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 text-[#0F172A] px-2 py-0.5 rounded-lg transition cursor-pointer">
                        <span x-text="skuCopied ? '¡Copiado!' : ('SKU: ' + (product?.sku || ''))"></span>
                        <i class="fa-regular text-[10px] text-[#4F46E5]" :class="skuCopied ? 'fa-circle-check text-emerald-600' : 'fa-copy'"></i>
                    </button>
                    <template x-if="product?.internal_code">
                        <span class="text-[9px] font-mono font-bold bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded border border-slate-200" x-text="product.internal_code"></span>
                    </template>
                </div>
                <button @click="closeModal()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer shrink-0">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            {{-- Body (scrollable) --}}
            <div class="p-5 sm:p-7 overflow-y-auto flex-grow space-y-6">

                {{-- Main Grid: Image + Details --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">

                    {{-- Left: Image Gallery --}}
                    <div class="lg:col-span-5 space-y-3">
                        <div class="relative bg-slate-50 rounded-2xl border border-slate-200 p-4 h-64 sm:h-72 flex items-center justify-center overflow-hidden shadow-inner">
                            <template x-if="product?.images && product.images.length > 0">
                                <img :src="product.images[activeImg] || product.images[0]" :alt="product?.name" class="w-full h-full object-contain">
                            </template>
                            <template x-if="!product?.images || product.images.length === 0">
                                <div class="w-20 h-20 rounded-2xl bg-indigo-50 border border-indigo-200 text-[#4F46E5] flex items-center justify-center text-4xl shadow-inner">
                                    <i class="fa-solid fa-box-open"></i>
                                </div>
                            </template>
                            {{-- COD Badge --}}
                            <span class="absolute top-3 left-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                <i class="fa-solid fa-truck-fast mr-0.5"></i> COD
                            </span>
                            {{-- ROI Badge --}}
                            <span class="absolute top-3 right-3 px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 font-mono font-extrabold text-[10px]" x-text="'+' + (product?.roi_percent || 0) + '% ROI'"></span>
                        </div>

                        {{-- Thumbnail Strip --}}
                        <template x-if="product?.images && product.images.length > 1">
                            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                <template x-for="(img, idx) in product.images" :key="idx">
                                    <button @click="activeImg = idx"
                                            :class="activeImg === idx ? 'border-[#4F46E5] ring-2 ring-[#4F46E5]/20' : 'border-slate-200 hover:border-slate-300'"
                                            class="w-14 h-14 rounded-xl border-2 bg-white p-1 flex items-center justify-center overflow-hidden cursor-pointer transition shrink-0">
                                        <img :src="img" :alt="'Imagen ' + (idx+1)" class="w-full h-full object-contain">
                                    </button>
                                </template>
                            </div>
                        </template>

                        {{-- Provider Info Card --}}
                        <div class="p-3 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-shield-check text-[#4F46E5]"></i>
                                Proveedor Verificado
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-[#0F172A]" x-text="product?.provider_name"></div>
                                    <div class="text-[10px] text-slate-500" x-text="product?.provider_city"></div>
                                </div>
                                <template x-if="product?.provider_phone">
                                    <a :href="'https://wa.me/' + (product.provider_phone || '').replace(/[^0-9]/g, '')" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-bold hover:bg-emerald-100 transition">
                                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Product Details --}}
                    <div class="lg:col-span-7 space-y-4">

                        {{-- Title & Rich Information Tabs --}}
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="text-xl sm:text-2xl font-extrabold text-[#0F172A] font-heading leading-tight" x-text="product?.name"></h2>
                                <template x-if="product?.attributes?.video_url">
                                    <a :href="product.attributes.video_url" target="_blank" rel="noopener"
                                       class="shrink-0 px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-[10px] font-bold flex items-center gap-1 shadow-2xs transition">
                                        <i class="fa-brands fa-youtube"></i> Ver Video
                                    </a>
                                </template>
                            </div>

                            {{-- Pestañas de Detalle y Ficha Técnica --}}
                            <div class="bg-slate-50/90 rounded-2xl p-3 border border-slate-200/90 space-y-2.5 shadow-2xs">
                                <div class="flex items-center gap-1.5 border-b border-slate-200/80 pb-2 overflow-x-auto text-[11px] font-bold">
                                    <button type="button" @click="infoTab = 'desc'"
                                            :class="infoTab === 'desc' ? 'bg-[#4F46E5] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-200/60'"
                                            class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1 shrink-0">
                                        <i class="fa-solid fa-align-left text-[9px]"></i> Descripción
                                    </button>
                                    <template x-if="product?.attributes?.specs && Object.keys(product.attributes.specs).length > 0">
                                        <button type="button" @click="infoTab = 'specs'"
                                                :class="infoTab === 'specs' ? 'bg-[#4F46E5] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-200/60'"
                                                class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1 shrink-0">
                                            <i class="fa-solid fa-sliders text-[9px]"></i> Ficha Técnica
                                        </button>
                                    </template>
                                    <template x-if="product?.attributes?.box_content">
                                        <button type="button" @click="infoTab = 'box'"
                                                :class="infoTab === 'box' ? 'bg-[#4F46E5] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-200/60'"
                                                class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1 shrink-0">
                                            <i class="fa-solid fa-box-open text-[9px]"></i> En la Caja
                                        </button>
                                    </template>
                                    <template x-if="product?.attributes?.warranty_info">
                                        <button type="button" @click="infoTab = 'warranty'"
                                                :class="infoTab === 'warranty' ? 'bg-[#4F46E5] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-200/60'"
                                                class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1 shrink-0">
                                            <i class="fa-solid fa-shield-halved text-[9px]"></i> Garantía
                                        </button>
                                    </template>
                                </div>

                                {{-- Tab: Descripción --}}
                                <div x-show="infoTab === 'desc'" class="text-xs text-slate-600 leading-relaxed max-h-28 overflow-y-auto pr-1">
                                    <p class="whitespace-pre-line" x-text="product?.description || 'Producto verificado para distribución nacional con garantía y cobro contra entrega (COD).'"></p>
                                </div>

                                {{-- Tab: Ficha Técnica (Key-Value) --}}
                                <div x-show="infoTab === 'specs'" class="max-h-28 overflow-y-auto pr-1">
                                    <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                                        <template x-for="(val, key) in (product?.attributes?.specs || {})" :key="key">
                                            <div class="flex items-center justify-between px-2.5 py-1 bg-white rounded-lg border border-slate-200/80">
                                                <span class="text-slate-500 font-semibold" x-text="key + ':'"></span>
                                                <span class="font-bold text-slate-800" x-text="val"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Tab: En la Caja --}}
                                <div x-show="infoTab === 'box'" class="text-xs text-slate-600 leading-relaxed max-h-28 overflow-y-auto pr-1">
                                    <div class="flex items-start gap-2 bg-white p-2.5 rounded-xl border border-slate-200/80">
                                        <i class="fa-solid fa-circle-check text-emerald-600 text-xs mt-0.5"></i>
                                        <p class="whitespace-pre-line text-[11px] text-slate-700 font-medium" x-text="product?.attributes?.box_content"></p>
                                    </div>
                                </div>

                                {{-- Tab: Garantía --}}
                                <div x-show="infoTab === 'warranty'" class="text-xs text-slate-600 leading-relaxed max-h-28 overflow-y-auto pr-1">
                                    <div class="flex items-start gap-2 bg-emerald-50/60 p-2.5 rounded-xl border border-emerald-200/80">
                                        <i class="fa-solid fa-shield-check text-emerald-600 text-xs mt-0.5"></i>
                                        <p class="whitespace-pre-line text-[11px] text-emerald-950 font-medium" x-text="product?.attributes?.warranty_info"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Price Card --}}
                        <div class="rounded-2xl bg-gradient-to-br from-slate-50 via-indigo-50/30 to-emerald-50/30 p-3.5 border border-slate-200 space-y-2.5">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
                                <span><i class="fa-solid fa-coins text-[#4F46E5] mr-1"></i> Desglose Financiero (BOB)</span>
                                <span class="text-[9px] font-mono text-slate-400" x-text="'Peso: ' + (product?.weight_kg || 0.5) + ' kg'"></span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="bg-white rounded-xl p-2 border border-slate-200/80 shadow-2xs">
                                    <div class="text-[9px] text-slate-400 font-semibold uppercase">Costo Mayorista</div>
                                    <div class="text-xs sm:text-sm font-extrabold font-mono text-[#0F172A] mt-0.5" x-text="Number(product?.cost_price_bob || 0).toFixed(2) + ' BOB'"></div>
                                </div>
                                <div class="bg-white rounded-xl p-2 border border-slate-200/80 shadow-2xs">
                                    <div class="text-[9px] text-slate-400 font-semibold uppercase">Mín. Venta</div>
                                    <div class="text-xs sm:text-sm font-extrabold font-mono text-slate-700 mt-0.5" x-text="Number(product?.min_price_bob || 0).toFixed(2) + ' BOB'"></div>
                                </div>
                                <div class="bg-white rounded-xl p-2 border border-[#4F46E5]/20 shadow-2xs ring-1 ring-[#4F46E5]/10">
                                    <div class="text-[9px] text-[#4F46E5] font-semibold uppercase">Sugerido Tienda</div>
                                    <div class="text-xs sm:text-sm font-extrabold font-mono text-[#4F46E5] mt-0.5" x-text="Number(product?.suggested_price_bob || 0).toFixed(2) + ' BOB'"></div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-1.5 border-t border-slate-200/80 text-xs">
                                <span class="text-slate-500 font-medium">Margen Neto Estimado:</span>
                                <span class="font-extrabold font-mono text-emerald-600" x-text="'+' + Number(product?.margin_bob || 0).toFixed(2) + ' BOB (' + (product?.roi_percent || 0) + '% ROI)'"></span>
                            </div>
                        </div>

                        {{-- Warehouse Stock with radio --}}
                        <div class="space-y-1.5">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 font-heading flex items-center gap-1.5">
                                <i class="fa-solid fa-warehouse text-[#4F46E5] text-[9px]"></i>
                                Stock por Bodega — Selecciona despacho:
                            </div>
                            <div class="space-y-1 max-h-32 overflow-y-auto pr-1">
                                <template x-for="wh in (product?.stock_by_wh || [])" :key="wh.warehouse_id">
                                    <label :class="selectedWh === wh.warehouse_id ? 'bg-indigo-50/80 border-[#4F46E5]/40 ring-1 ring-[#4F46E5]/20' : 'bg-white border-slate-200 hover:bg-slate-50'"
                                           class="flex items-center justify-between px-3 py-2 rounded-xl border transition cursor-pointer">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <input type="radio" name="modal_wh" :value="wh.warehouse_id" x-model="selectedWh"
                                                   class="w-3.5 h-3.5 text-[#4F46E5] border-slate-300 focus:ring-[#4F46E5]/30 shrink-0">
                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-[#0F172A] flex items-center gap-1.5 truncate">
                                                    <span class="w-2 h-2 rounded-full shrink-0" :class="wh.stock > 10 ? 'bg-emerald-500' : (wh.stock > 0 ? 'bg-amber-500' : 'bg-rose-500')"></span>
                                                    <span x-text="wh.name || (wh.city + ' (' + wh.department + ')')"></span>
                                                </div>
                                                <div class="text-[10px] text-slate-500 truncate" x-text="wh.city + ', ' + wh.department"></div>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0 pl-2">
                                            <div class="text-xs font-mono font-bold" :class="wh.stock > 10 ? 'text-emerald-600' : (wh.stock > 0 ? 'text-amber-600' : 'text-rose-600')" x-text="wh.stock + ' uds'"></div>
                                            <div class="text-[9px] text-slate-400 uppercase font-semibold">disponibles</div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Related Products Section --}}
                <div class="pt-4 border-t border-slate-200/90 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#4F46E5]"></span>
                            <h3 class="text-xs font-extrabold text-[#0F172A] uppercase tracking-wider font-heading">
                                Productos Relacionados y Ganadores
                            </h3>
                        </div>
                        <span class="text-[10px] text-slate-400 font-medium">Clic para cambiar ficha</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <template x-for="rel in relatedProducts()" :key="rel.id">
                            <div @click="switchProduct(rel.id)"
                                 class="p-2.5 rounded-2xl bg-slate-50/70 border border-slate-200 hover:border-[#4F46E5] hover:bg-white hover:shadow-sm transition cursor-pointer group flex flex-col justify-between">
                                <div class="space-y-1.5">
                                    <div class="h-20 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center p-1.5 overflow-hidden">
                                        <template x-if="rel.images && rel.images.length > 0">
                                            <img :src="rel.images[0]" :alt="rel.name" class="w-full h-full object-contain group-hover:scale-105 transition duration-200">
                                        </template>
                                        <template x-if="!rel.images || rel.images.length === 0">
                                            <div class="text-slate-300 text-xl"><i class="fa-solid fa-box-open"></i></div>
                                        </template>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-[8px] text-[#4F46E5] font-bold uppercase truncate" x-text="rel.category_name"></div>
                                        <div class="text-xs font-bold text-[#0F172A] line-clamp-1 group-hover:text-[#4F46E5] transition" x-text="rel.name"></div>
                                    </div>
                                </div>
                                <div class="pt-1.5 mt-1.5 border-t border-slate-200/80 flex items-center justify-between text-left">
                                    <div>
                                        <div class="text-[8px] text-slate-400 uppercase font-semibold">Sugerido</div>
                                        <div class="text-xs font-extrabold font-mono text-[#0F172A]" x-text="Number(rel.suggested_price_bob).toFixed(0) + ' Bs'"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[8px] text-emerald-600 font-bold uppercase">Ganas</div>
                                        <div class="text-xs font-extrabold font-mono text-emerald-600" x-text="'+' + Number(rel.margin_bob).toFixed(0) + ' Bs'"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            {{-- Footer CTA --}}
            <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                <div class="text-xs text-slate-500 text-center sm:text-left">
                    Al pulsar vender, se precargará el producto y la bodega elegida en el generador de pedidos COD.
                </div>
                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                    <button type="button" @click="closeModal()"
                        class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer">
                        Cerrar
                    </button>
                    @if(auth()->check() && (auth()->user()->isProvider() || auth()->user()->isTenantAdmin()))
                    <template x-if="product?.provider_id === '{{ auth()->id() }}' || {{ auth()->user()->isTenantAdmin() ? 'true' : 'false' }}">
                        <a :href="'/panel/proveedor?edit=' + (product?.id || '')"
                           class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm transition">
                            <i class="fa-solid fa-pen-to-square"></i>
                            <span>Editar Producto</span>
                        </a>
                    </template>
                    @endif
                    <a :href="checkoutUrl()"
                       class="btn-scarlet px-5 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                        <i class="fa-solid fa-cart-arrow-down"></i>
                        <span>Vender Producto / Crear Pedido COD</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
