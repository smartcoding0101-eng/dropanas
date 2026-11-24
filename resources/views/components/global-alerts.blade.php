{{-- ================================================================== --}}
{{-- GLOBAL ALERTS & NOTIFICATIONS SYSTEM (TAILWIND + ALPINE.JS)        --}}
{{-- Reemplaza los diálogos nativos del navegador ("localhost dice:")   --}}
{{-- Soporta: Toast flotantes, Modales de Confirmación y Prompts        --}}
{{-- ================================================================== --}}
<div x-data="{
    toasts: [],
    alertModal: {
        open: false,
        title: 'Advertencia del Sistema',
        message: '',
        type: 'warning', // warning, danger, primary, success, info
        buttonText: 'Entendido',
        resolve: null
    },
    confirmModal: {
        open: false,
        title: '¿Confirmar Acción?',
        message: '',
        type: 'danger', // danger, warning, primary, success
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        resolve: null
    },
    promptModal: {
        open: false,
        title: 'Ingresar Información',
        message: '',
        placeholder: '',
        value: '',
        resolve: null
    },

    init() {
        // Exponer helpers globales en window
        window.$toast = (message, type = 'success', title = null, duration = 4500) => {
            this.addToast(message, type, title, duration);
        };

        window.$notify = window.$toast;

        window.$alert = (message, options = {}) => {
            return new Promise((resolve) => {
                const str = String(message || '');
                let defaultType = options.type || 'warning';
                let defaultTitle = options.title || 'Advertencia del Sistema';
                if (!options.type) {
                    if (str.includes('✅') || str.toLowerCase().includes('éxito') || str.toLowerCase().includes('confirmad') || str.toLowerCase().includes('exitosamente')) {
                        defaultType = 'success';
                        defaultTitle = options.title || 'Operación Exitosa';
                    } else if (str.includes('❌') || str.toLowerCase().includes('error') || str.toLowerCase().includes('fall') || str.toLowerCase().includes('denegad')) {
                        defaultType = 'danger';
                        defaultTitle = options.title || 'Error en la Operación';
                    } else if (str.includes('⚠️') || str.toLowerCase().includes('advertencia') || str.toLowerCase().includes('cuidado') || str.toLowerCase().includes('atención')) {
                        defaultType = 'warning';
                        defaultTitle = options.title || 'Advertencia';
                    }
                }
                this.alertModal = {
                    open: true,
                    title: defaultTitle,
                    message: str.replace(/^[✅⚠️❌]\s*/, ''),
                    type: defaultType,
                    buttonText: options.buttonText || 'Entendido',
                    resolve: resolve
                };
            });
        };

        window.$confirm = (message, options = {}) => {
            return new Promise((resolve) => {
                this.confirmModal = {
                    open: true,
                    title: options.title || '¿Confirmar Acción?',
                    message: message,
                    type: options.type || 'danger',
                    confirmText: options.confirmText || 'Confirmar',
                    cancelText: options.cancelText || 'Cancelar',
                    resolve: resolve
                };
            });
        };

        window.$prompt = (message, defaultValue = '', options = {}) => {
            return new Promise((resolve) => {
                this.promptModal = {
                    open: true,
                    title: options.title || 'Ingresar Información',
                    message: message,
                    placeholder: options.placeholder || 'Escribe aquí...',
                    value: defaultValue,
                    resolve: resolve
                };
            });
        };

        // Interceptar window.alert nativo para que NUNCA aparezca 'localhost dice:'
        // Renderiza el moderno modal emergente Tailwind centrado
        window.alert = (message) => {
            return window.$alert(message);
        };

        // Interceptar formularios con atributo data-confirm
        document.addEventListener('submit', async (e) => {
            const form = e.target;
            const confirmMsg = form.getAttribute('data-confirm');
            if (confirmMsg && !form._isConfirmed) {
                e.preventDefault();
                const ok = await window.$confirm(confirmMsg, {
                    title: form.getAttribute('data-confirm-title') || '¿Confirmar Operación?',
                    type: form.getAttribute('data-confirm-type') || 'danger',
                    confirmText: form.getAttribute('data-confirm-button') || 'Continuar'
                });
                if (ok) {
                    form._isConfirmed = true;
                    form.submit();
                }
            }
        });
    },

    addToast(message, type = 'success', title = null, duration = 4500) {
        const id = Date.now() + Math.random().toString(36).substring(2, 6);
        const toast = {
            id,
            message,
            type: ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info',
            title: title || (type === 'success' ? 'Éxito' : (type === 'error' ? 'Atención' : (type === 'warning' ? 'Advertencia' : 'Notificación'))),
            duration
        };

        this.toasts.push(toast);

        if (duration > 0) {
            setTimeout(() => {
                this.removeToast(id);
            }, duration);
        }
    },

    removeToast(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    },

    handleAlert() {
        if (this.alertModal.resolve) {
            this.alertModal.resolve(true);
        }
        this.alertModal.open = false;
    },

    handleConfirm(result) {
        if (this.confirmModal.resolve) {
            this.confirmModal.resolve(result);
        }
        this.confirmModal.open = false;
    },

    handlePrompt(result) {
        if (this.promptModal.resolve) {
            this.promptModal.resolve(result ? this.promptModal.value : null);
        }
        this.promptModal.open = false;
        this.promptModal.value = '';
    }
}"
@notify.window="addToast($event.detail.message, $event.detail.type || 'success', $event.detail.title, $event.detail.duration || 4500)"
class="relative"
x-cloak>

    {{-- ================================================================== --}}
    {{-- 1. STACK DE TOASTS FLOTANTES (SUPERIOR DERECHA)                    --}}
    {{-- ================================================================== --}}
    <div class="fixed top-5 right-5 z-[10000] flex flex-col gap-3 pointer-events-none max-w-sm sm:max-w-md w-full px-4"
         aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-[-10px] scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-x-4 scale-95"
                 class="pointer-events-auto w-full bg-white/95 backdrop-blur-md rounded-2xl border shadow-2xl p-4 flex items-start gap-3.5 transition-all overflow-hidden relative group"
                 :class="{
                     'border-emerald-200 ring-1 ring-emerald-500/20': t.type === 'success',
                     'border-rose-200 ring-1 ring-rose-500/20': t.type === 'error',
                     'border-amber-200 ring-1 ring-amber-500/20': t.type === 'warning',
                     'border-indigo-200 ring-1 ring-[#4F46E5]/20': t.type === 'info'
                 }">

                {{-- Left color bar --}}
                <div class="absolute left-0 top-0 bottom-0 w-1.5"
                     :class="{
                         'bg-emerald-500': t.type === 'success',
                         'bg-rose-500': t.type === 'error',
                         'bg-amber-500': t.type === 'warning',
                         'bg-[#4F46E5]': t.type === 'info'
                     }"></div>

                {{-- Icon Badge --}}
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                     :class="{
                         'bg-emerald-50 text-emerald-600 border border-emerald-200': t.type === 'success',
                         'bg-rose-50 text-rose-600 border border-rose-200': t.type === 'error',
                         'bg-amber-50 text-amber-600 border border-amber-200': t.type === 'warning',
                         'bg-indigo-50 text-[#4F46E5] border border-indigo-200': t.type === 'info'
                     }">
                    <template x-if="t.type === 'success'">
                        <i class="fa-solid fa-circle-check text-base"></i>
                    </template>
                    <template x-if="t.type === 'error'">
                        <i class="fa-solid fa-circle-xmark text-base"></i>
                    </template>
                    <template x-if="t.type === 'warning'">
                        <i class="fa-solid fa-triangle-exclamation text-base"></i>
                    </template>
                    <template x-if="t.type === 'info'">
                        <i class="fa-solid fa-circle-info text-base"></i>
                    </template>
                </div>

                {{-- Content --}}
                <div class="flex-grow min-w-0 pr-2">
                    <h4 class="text-xs font-bold font-heading text-slate-900 leading-tight" x-text="t.title"></h4>
                    <p class="text-[12px] text-slate-600 mt-0.5 leading-relaxed break-words" x-text="t.message"></p>
                </div>

                {{-- Close button --}}
                <button type="button" @click="removeToast(t.id)"
                        class="text-slate-400 hover:text-slate-700 w-6 h-6 rounded-lg flex items-center justify-center hover:bg-slate-100 transition shrink-0 cursor-pointer">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        </template>
    </div>

    {{-- ================================================================== --}}
    {{-- 1.5. MODAL DE ADVERTENCIA / ALERTA TAILWIND (REEMPLAZA alert())     --}}
    {{-- ================================================================== --}}
    <div x-show="alertModal.open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[10002] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         @keydown.escape.window="if(alertModal.open) handleAlert()"
         x-cloak>

        <div @click.stop
             x-show="alertModal.open"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-95 translate-y-3"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-md bg-white rounded-3xl border border-slate-200/90 shadow-2xl overflow-hidden">

            {{-- Haz decorativo superior animado según tipo --}}
            <div class="h-1.5 w-full"
                 :class="{
                     'bg-gradient-to-r from-rose-500 via-red-500 to-rose-600': alertModal.type === 'danger',
                     'bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600': alertModal.type === 'warning',
                     'bg-gradient-to-r from-[#4F46E5] to-[#10B981]': alertModal.type === 'primary' || alertModal.type === 'info',
                     'bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-500': alertModal.type === 'success'
                 }"></div>

            <div class="p-6 text-center space-y-4">
                {{-- Insignia e Ícono de Advertencia --}}
                <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-inner"
                     :class="{
                         'bg-rose-50 text-rose-600 border border-rose-200 ring-4 ring-rose-500/10': alertModal.type === 'danger',
                         'bg-amber-50 text-amber-600 border border-amber-200 ring-4 ring-amber-500/10': alertModal.type === 'warning',
                         'bg-indigo-50 text-[#4F46E5] border border-indigo-200 ring-4 ring-indigo-500/10': alertModal.type === 'primary' || alertModal.type === 'info',
                         'bg-emerald-50 text-emerald-600 border border-emerald-200 ring-4 ring-emerald-500/10': alertModal.type === 'success'
                     }">
                    <template x-if="alertModal.type === 'danger'">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </template>
                    <template x-if="alertModal.type === 'warning'">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </template>
                    <template x-if="alertModal.type === 'primary' || alertModal.type === 'info'">
                        <i class="fa-solid fa-circle-info"></i>
                    </template>
                    <template x-if="alertModal.type === 'success'">
                        <i class="fa-solid fa-circle-check"></i>
                    </template>
                </div>

                {{-- Título y Mensaje --}}
                <div class="space-y-1.5">
                    <h3 class="text-base sm:text-lg font-bold font-heading text-slate-900" x-text="alertModal.title"></h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-sm mx-auto whitespace-pre-line" x-text="alertModal.message"></p>
                </div>

                {{-- Botón de Acción Principal --}}
                <div class="pt-2">
                    <button type="button"
                            @click="handleAlert()"
                            class="w-full px-5 py-2.5 rounded-xl text-white text-xs font-bold transition shadow-md hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
                            :class="{
                                'bg-rose-600 hover:bg-rose-700 shadow-rose-600/20': alertModal.type === 'danger',
                                'bg-amber-600 hover:bg-amber-700 shadow-amber-600/20': alertModal.type === 'warning',
                                'bg-[#4F46E5] hover:bg-[#312E81] shadow-indigo-600/20': alertModal.type === 'primary' || alertModal.type === 'info',
                                'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20': alertModal.type === 'success'
                            }">
                        <span x-text="alertModal.buttonText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 2. MODAL DE CONFIRMACIÓN TAILWIND (REEMPLAZA confirm())             --}}
    {{-- ================================================================== --}}
    <div x-show="confirmModal.open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[10001] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         @keydown.escape.window="if(confirmModal.open) handleConfirm(false)">

        <div @click.stop
             x-show="confirmModal.open"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-95 translate-y-3"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-md bg-white rounded-3xl border border-slate-200/90 shadow-2xl overflow-hidden">

            {{-- Top decorative beam --}}
            <div class="h-1.5 w-full"
                 :class="{
                     'bg-gradient-to-r from-rose-500 via-amber-500 to-rose-600': confirmModal.type === 'danger',
                     'bg-gradient-to-r from-amber-400 to-amber-600': confirmModal.type === 'warning',
                     'bg-gradient-to-r from-[#4F46E5] to-[#10B981]': confirmModal.type === 'primary' || confirmModal.type === 'success'
                 }"></div>

            <div class="p-6 text-center space-y-4">
                {{-- Icon Badge --}}
                <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-inner"
                     :class="{
                         'bg-rose-50 text-rose-600 border border-rose-200': confirmModal.type === 'danger',
                         'bg-amber-50 text-amber-600 border border-amber-200': confirmModal.type === 'warning',
                         'bg-indigo-50 text-[#4F46E5] border border-indigo-200': confirmModal.type === 'primary',
                         'bg-emerald-50 text-emerald-600 border border-emerald-200': confirmModal.type === 'success'
                     }">
                    <template x-if="confirmModal.type === 'danger'">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </template>
                    <template x-if="confirmModal.type === 'warning'">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </template>
                    <template x-if="confirmModal.type === 'primary'">
                        <i class="fa-solid fa-circle-question"></i>
                    </template>
                    <template x-if="confirmModal.type === 'success'">
                        <i class="fa-solid fa-circle-check"></i>
                    </template>
                </div>

                {{-- Title & Message --}}
                <div class="space-y-1.5">
                    <h3 class="text-base sm:text-lg font-bold font-heading text-slate-900" x-text="confirmModal.title"></h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-sm mx-auto" x-text="confirmModal.message"></p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button type="button"
                            @click="handleConfirm(false)"
                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs cursor-pointer">
                        <span x-text="confirmModal.cancelText"></span>
                    </button>
                    <button type="button"
                            @click="handleConfirm(true)"
                            class="flex-1 px-4 py-2.5 rounded-xl text-white text-xs font-bold transition shadow-md cursor-pointer"
                            :class="{
                                'bg-rose-600 hover:bg-rose-700 shadow-rose-600/20': confirmModal.type === 'danger',
                                'bg-amber-600 hover:bg-amber-700 shadow-amber-600/20': confirmModal.type === 'warning',
                                'bg-[#4F46E5] hover:bg-[#312E81] shadow-indigo-600/20': confirmModal.type === 'primary',
                                'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20': confirmModal.type === 'success'
                            }">
                        <span x-text="confirmModal.confirmText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 3. MODAL DE PROMPT / ENTRADA DE TEXTO (REEMPLAZA prompt())         --}}
    {{-- ================================================================== --}}
    <div x-show="promptModal.open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[10001] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         @keydown.escape.window="if(promptModal.open) handlePrompt(false)">

        <div @click.stop
             x-show="promptModal.open"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-95 translate-y-3"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-md bg-white rounded-3xl border border-slate-200/90 shadow-2xl overflow-hidden">

            <div class="h-1.5 w-full bg-gradient-to-r from-[#4F46E5] via-[#38BDF8] to-[#10B981]"></div>

            <form @submit.prevent="handlePrompt(true)" class="p-6 text-left space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 text-[#4F46E5] flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-pen-to-square text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold font-heading text-slate-900 leading-tight" x-text="promptModal.title"></h3>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="promptModal.message"></p>
                    </div>
                </div>

                <div>
                    <textarea x-model="promptModal.value"
                              :placeholder="promptModal.placeholder"
                              rows="3"
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#4F46E5] focus:bg-white transition resize-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-1">
                    <button type="button"
                            @click="handlePrompt(false)"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-[#4F46E5] hover:bg-[#312E81] text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-check text-xs"></i> Aceptar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
