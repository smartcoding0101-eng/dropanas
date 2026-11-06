@extends('layouts.app')

@section('title', 'Panel Proveedor | Megabodega Dropana Bolivia')

@section('content')
<div class="space-y-8 bg-[#FFFFFF]" x-data="proveedorDashboard()">

    <!-- Header del panel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-[#E88A38] text-white flex items-center justify-center text-lg font-extrabold font-heading shadow-sm">
                    {{ strtoupper(substr($provider?->name ?? 'P', 0, 1)) }}
                </div>
                <div>
                    <div class="text-xs text-[#E88A38] font-bold uppercase tracking-wider">Panel del Proveedor / Mayorista</div>
                    <div class="text-2xl font-extrabold text-[#960016] font-heading leading-tight">
                        {{ $provider?->company_or_store_name ?? $provider?->name ?? 'Mi Megabodega' }}
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                <span class="flex items-center gap-1.5 font-medium">
                    <i class="fa-solid fa-location-dot text-[#ED2100]"></i>
                    {{ $provider?->city ?? 'Santa Cruz de la Sierra' }}, {{ $provider?->department ?? 'Santa Cruz' }}
                </span>
                <span>•</span>
                <span class="flex items-center gap-1">
                    <i class="fa-solid fa-id-card text-[#E88A38]"></i>
                    NIT: {{ $provider?->document_id ?? '1029384019' }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-3 self-start md:self-auto">
            <button @click="productModalOpen = true"
                class="btn-scarlet inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm">
                <i class="fa-solid fa-plus text-amber-200"></i> Publicar Nuevo Producto
            </button>
            <a href="{{ url('/catalogo') }}"
                class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-semibold text-xs inline-flex items-center gap-2 shadow-sm transition">
                <i class="fa-solid fa-boxes-stacked text-[#E88A38]"></i> Catálogo
            </a>
        </div>
    </div>

    <!-- Notificación Toast -->
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

    <!-- Tarjetas de estadísticas (Fondo #FFFFFF con bordes definidos) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Ingresos por liquidaciones (#E88A38 Naranja Fuego) -->
        <div class="bg-[#E88A38] rounded-2xl p-5 text-white shadow-md space-y-2">
            <div class="flex items-center gap-2 text-amber-100 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-sack-dollar text-white"></i> Ingresos Liquidados
            </div>
            <div class="text-3xl font-extrabold font-mono">
                Bs {{ number_format($stats['total_revenue'], 2) }}
            </div>
            <div class="text-amber-100/95 text-[11px] font-medium">Costos cobrados por ventas COD</div>
        </div>

        <!-- Saldo billetera (#FFFFFF) -->
        <div class="bg-[#FFFFFF] rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2">
            <div class="flex items-center gap-2 text-[#E88A38] text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-wallet text-[#ED2100]"></i> Saldo Billetera
            </div>
            <div class="text-2xl font-extrabold font-mono text-emerald-600">
                Bs {{ number_format((float)($wallet?->balance_available ?? 0), 2) }}
            </div>
            <div class="text-slate-500 text-[11px]">Disponible para retiro inmediato</div>
        </div>

        <!-- Productos activos (#FFFFFF) -->
        <div class="bg-[#FFFFFF] rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2">
            <div class="flex items-center gap-2 text-slate-500 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-cube text-[#E88A38]"></i> Productos Activos
            </div>
            <div class="text-2xl font-extrabold text-[#960016] font-heading">{{ $stats['active_products'] }}</div>
            <div class="text-slate-500 text-[11px]">de {{ $stats['total_products'] }} totales publicados</div>
        </div>

        <!-- Bodegas (#FFFFFF) -->
        <div class="bg-[#FFFFFF] rounded-2xl border border-slate-200 p-5 shadow-sm space-y-2">
            <div class="flex items-center gap-2 text-slate-500 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-warehouse text-[#E88A38]"></i> Bodegas Activas
            </div>
            <div class="text-2xl font-extrabold text-[#960016] font-heading">{{ $stats['total_warehouses'] }}</div>
            <div class="text-slate-500 text-[11px]">Puntos de despacho COD</div>
        </div>
    </div>

    <!-- Bodegas con Inventario (#FFFFFF) -->
    @if($warehouses->isNotEmpty())
    <div class="bg-[#FFFFFF] rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-warehouse text-[#E88A38]"></i>
                <h3 class="font-bold text-[#960016] text-sm font-heading">Mis Bodegas de Despacho</h3>
            </div>
            <span class="text-xs text-slate-500 font-medium">Santa Cruz & El Alto</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x divide-slate-200">
            @foreach($warehouses as $warehouse)
            <div class="p-5 space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-bold text-slate-900 text-sm">{{ $warehouse->name }}</div>
                        <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-1">
                            <i class="fa-solid fa-location-dot text-[#ED2100]"></i>
                            {{ $warehouse->address }}, {{ $warehouse->city }}, {{ $warehouse->department }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            <i class="fa-solid fa-phone text-[#E88A38] mr-1"></i> {{ $warehouse->contact_phone }}
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Activa
                    </span>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-[#E88A38] mb-2">Inventario en Almacén:</div>
                    @forelse($warehouse->inventories as $inv)
                    <div class="flex justify-between items-center text-xs py-2 px-3 rounded-lg bg-slate-50 border border-slate-200/80 mb-1.5">
                        <span class="text-slate-800 font-medium">{{ $inv->product->name }}</span>
                        <span class="font-mono font-bold text-[#960016]">{{ $inv->stock_available }} uds</span>
                    </div>
                    @empty
                    <div class="text-xs text-slate-400 italic">Sin inventario registrado</div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Productos del Proveedor (#FFFFFF) -->
    <div class="bg-[#FFFFFF] rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked text-[#E88A38]"></i>
                <h3 class="font-bold text-[#960016] text-sm font-heading">Mis Productos Publicados</h3>
            </div>
            <div class="flex items-center gap-2">
                <button @click="productModalOpen = true"
                    class="btn-scarlet px-3.5 py-1.5 rounded-lg font-bold text-xs shadow-sm flex items-center gap-1.5">
                    <i class="fa-solid fa-plus text-amber-200"></i> Nuevo Producto
                </button>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-white text-[#960016] border border-slate-200 font-mono">
                    {{ $products->count() }} productos
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200 tracking-wider">
                    <tr>
                        <th class="py-3 px-5">Producto / SKU</th>
                        <th class="py-3 px-5">Categoría</th>
                        <th class="py-3 px-5">Costo Mayorista</th>
                        <th class="py-3 px-5">Precio Sugerido</th>
                        <th class="py-3 px-5">Stock Total</th>
                        <th class="py-3 px-5">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($products as $product)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-5">
                            <div class="flex items-center gap-3">
                                @if(!empty($product->images) && count($product->images) > 0)
                                    <img src="{{ $product->images[0] }}" alt="{{ $product->name }}" class="w-11 h-11 object-cover rounded-xl border border-slate-200 shadow-sm shrink-0">
                                @else
                                    <div class="w-11 h-11 rounded-xl bg-red-50/60 border border-red-100 text-[#960016] flex items-center justify-center shrink-0 text-base">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="font-bold text-slate-900">{{ $product->name }}</div>
                                    <div class="text-[11px] text-[#E88A38] font-mono font-semibold">SKU: {{ $product->sku }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-5 text-slate-500">
                            {{ $product->category->name ?? 'General' }}
                        </td>
                        <td class="py-3.5 px-5 font-mono font-bold text-slate-900">
                            Bs {{ number_format($product->cost_price_bob, 2) }}
                        </td>
                        <td class="py-3.5 px-5 font-mono font-bold text-[#E88A38]">
                            Bs {{ number_format($product->suggested_price_bob, 2) }}
                        </td>
                        <td class="py-3.5 px-5 font-mono font-bold text-[#960016]">
                            {{ $product->warehouseInventories->sum('stock_available') }} uds
                        </td>
                        <td class="py-3.5 px-5">
                            @if($product->status === 'published')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Publicado
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                {{ $product->status }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            No tienes productos registrados aún.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL INTERACTIVO: CREAR Y PUBLICAR NUEVO PRODUCTO MAYORISTA -->
    <div x-show="productModalOpen" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-6"
            @click.away="productModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-red-50 text-[#ED2100] border border-red-200 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#960016] font-heading text-lg">Publicar Producto Mayorista</h3>
                        <p class="text-xs text-slate-500">Disponible para miles de dropshippers en Bolivia</p>
                    </div>
                </div>
                <button @click="productModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="submitProduct()" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nombre del Producto:</label>
                    <input type="text" x-model="form.name" required placeholder="Ej: Mini Proyector LED Portátil Full HD"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 text-xs focus:outline-none focus:border-[#E88A38]">
                </div>

                <!-- Carga de Imagen del Producto -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Foto / Imagen del Producto:</label>
                    <div class="flex flex-col sm:flex-row gap-3 items-start p-3 bg-slate-50 border border-slate-200 rounded-2xl">
                        <!-- Preview Box -->
                        <div class="w-20 h-20 rounded-xl border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden shrink-0 relative group">
                            <template x-if="imagePreview">
                                <div class="w-full h-full relative">
                                    <img :src="imagePreview" class="w-full h-full object-cover">
                                    <button type="button" @click="removeImage()"
                                        class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[9px] shadow hover:bg-red-700">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </template>
                            <template x-if="!imagePreview">
                                <div class="text-center p-1 text-slate-400">
                                    <i class="fa-solid fa-image text-lg mb-0.5 text-[#E88A38]"></i>
                                    <div class="text-[8px] font-bold">Sin foto</div>
                                </div>
                            </template>
                        </div>

                        <!-- Selector / Drag Drop & URL -->
                        <div class="flex-1 space-y-2 w-full">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="$refs.fileInput.click()"
                                    class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs inline-flex items-center gap-1.5 shadow-sm transition">
                                    <i class="fa-solid fa-upload text-[#E88A38]"></i> Subir Foto
                                </button>
                                <span class="text-[10px] text-slate-500 truncate" x-text="imageFile ? imageFile.name : 'PNG, JPG, WEBP (Máx 5MB)'"></span>
                                <input type="file" x-ref="fileInput" @change="handleFileChange($event)" accept="image/png,image/jpeg,image/webp,image/jpg" class="hidden">
                            </div>

                            <div class="flex items-center gap-1.5">
                                <span class="text-[9px] text-slate-400 font-bold uppercase shrink-0">O URL:</span>
                                <input type="url" x-model="form.image_url" @input="handleUrlChange()" placeholder="https://ejemplo.com/foto.jpg"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-slate-800 text-[11px] focus:outline-none focus:border-[#E88A38]">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">SKU / Código Único:</label>
                        <input type="text" x-model="form.sku" required placeholder="PROJ-MINI-01"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 text-xs font-mono focus:outline-none focus:border-[#E88A38]">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Categoría:</label>
                        <select x-model="form.category_id" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 text-xs font-semibold focus:outline-none focus:border-[#E88A38]">
                            <option value="">Seleccionar categoría...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Costo Proveedor (BOB):</label>
                        <input type="number" step="0.5" x-model.number="form.cost_price_bob" required placeholder="120.00"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 text-xs font-mono font-bold focus:outline-none focus:border-[#E88A38]">
                        <span class="text-[10px] text-slate-400">Lo que cobras neto</span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Precio Mínimo (BOB):</label>
                        <input type="number" step="0.5" x-model.number="form.min_price_bob" required placeholder="150.00"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 text-xs font-mono focus:outline-none focus:border-[#E88A38]">
                        <span class="text-[10px] text-slate-400">Piso de venta</span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Precio Sugerido (BOB):</label>
                        <input type="number" step="0.5" x-model.number="form.suggested_price_bob" required placeholder="199.00"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-[#E88A38] text-xs font-mono font-bold focus:outline-none focus:border-[#E88A38]">
                        <span class="text-[10px] text-[#E88A38]">Margen Dropshipper</span>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                    <div class="font-bold text-[#960016] flex items-center gap-2">
                        <i class="fa-solid fa-warehouse text-[#E88A38]"></i>
                        <span>Asignación Inicial de Inventario Físico</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-600 mb-1">Bodega de Ingreso:</label>
                            <select x-model="form.initial_warehouse_id"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs focus:outline-none focus:border-[#E88A38]">
                                <option value="">Sin inventario inicial</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->city }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-slate-600 mb-1">Cantidad Inicial (Uds):</label>
                            <input type="number" x-model.number="form.initial_stock" placeholder="50" min="0"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-800 text-xs font-mono focus:outline-none focus:border-[#E88A38]">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="productModalOpen = false" class="px-4 py-2.5 rounded-xl font-semibold text-slate-600 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="submitting"
                        class="btn-scarlet px-6 py-2.5 rounded-xl font-bold flex items-center gap-2 shadow-sm">
                        <span x-show="!submitting">Publicar en Catálogo</span>
                        <span x-show="submitting" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-spinner animate-spin"></i> Guardando...
                        </span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

<script>
function proveedorDashboard() {
    return {
        productModalOpen: false,
        submitting: false,
        toast: { show: false, message: '', type: 'success' },
        imageFile: null,
        imagePreview: null,
        form: {
            name: '',
            sku: '',
            category_id: '{{ $categories->first()?->id ?? "" }}',
            cost_price_bob: 120,
            suggested_price_bob: 199,
            min_price_bob: 140,
            initial_warehouse_id: '{{ $warehouses->first()?->id ?? "" }}',
            initial_stock: 50,
            is_cod_available: true,
            image_url: ''
        },

        handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                this.showToast('La imagen no debe superar los 5MB.', 'error');
                return;
            }
            this.imageFile = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        handleUrlChange() {
            if (this.form.image_url && (this.form.image_url.startsWith('http://') || this.form.image_url.startsWith('https://') || this.form.image_url.startsWith('/'))) {
                this.imagePreview = this.form.image_url;
                this.imageFile = null;
            }
        },

        removeImage() {
            this.imageFile = null;
            this.imagePreview = null;
            this.form.image_url = '';
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 5000);
        },

        async submitProduct() {
            this.submitting = true;
            try {
                // Obtener token con el usuario proveedor
                const authRes = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: 'proveedor@dropana.bo', password: 'password123' })
                });
                const authData = await authRes.json();
                const token = authData.data?.token;

                // Crear FormData para soportar carga de imagen física
                const formData = new FormData();
                for (const key in this.form) {
                    if (this.form[key] !== null && this.form[key] !== undefined && this.form[key] !== '') {
                        formData.append(key, this.form[key]);
                    }
                }
                if (this.imageFile) {
                    formData.append('image', this.imageFile);
                }

                const response = await fetch('/api/v1/products', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    this.showToast(result.message || 'Producto publicado con éxito.', 'success');
                    this.productModalOpen = false;
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    this.showToast(result.message || 'Error al guardar el producto.', 'error');
                }
            } catch (err) {
                this.showToast('Error de comunicación con el servidor.', 'error');
            } finally {
                this.submitting = false;
            }
        }
    }
}
</script>
@endsection
