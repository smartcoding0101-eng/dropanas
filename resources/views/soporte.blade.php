@extends('layouts.app')

@section('title', 'Soporte – Dropana Bolivia | Ayuda y Asistencia')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10" x-data="{ openFaq: null }">

    <!-- Hero Soporte -->
    <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-50 border border-red-200 text-xs font-bold text-[#960016]">
            <i class="fa-solid fa-headset"></i>
            <span>Centro de Soporte Dropana Bolivia</span>
        </div>
        <h1 class="text-4xl font-black text-[#960016] font-heading">¿En qué podemos ayudarte?</h1>
        <p class="text-slate-500 text-base max-w-xl mx-auto">
            Nuestro equipo está disponible de Lunes a Sábado de <strong>8:00 AM a 6:00 PM</strong> (hora Bolivia).
            Tiempo de respuesta promedio: <strong class="text-emerald-600">menos de 2 horas</strong>.
        </p>
    </div>

    <!-- Canales de contacto rápido -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <a href="https://wa.me/59170000000?text=Hola%20Dropana%2C%20necesito%20soporte%20con..." target="_blank"
           class="interactive-card bg-white border border-slate-200 rounded-2xl p-5 flex flex-col items-center text-center gap-3 hover:border-emerald-400 transition shadow-sm group">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl group-hover:scale-110 transition">
                <i class="fa-brands fa-whatsapp"></i>
            </div>
            <div>
                <div class="font-bold text-slate-800 text-sm">WhatsApp Soporte</div>
                <div class="text-xs text-slate-500 mt-0.5">Respuesta inmediata en horario laboral</div>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wide bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full border border-emerald-200">
                Recomendado
            </span>
        </a>

        <a href="{{ url('/contactenos') }}"
           class="interactive-card bg-white border border-slate-200 rounded-2xl p-5 flex flex-col items-center text-center gap-3 hover:border-amber-400 transition shadow-sm group">
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-[#E88A38] text-xl group-hover:scale-110 transition">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <div>
                <div class="font-bold text-slate-800 text-sm">Formulario de Contacto</div>
                <div class="text-xs text-slate-500 mt-0.5">Respuesta en máximo 24 horas hábiles</div>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wide bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-200">
                Formulario Web
            </span>
        </a>

        <div class="interactive-card bg-white border border-slate-200 rounded-2xl p-5 flex flex-col items-center text-center gap-3 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-[#960016] text-xl">
                <i class="fa-solid fa-phone"></i>
            </div>
            <div>
                <div class="font-bold text-slate-800 text-sm">Llamada Directa</div>
                <div class="text-xs text-slate-500 mt-0.5">Disponible Lun–Vie 9AM–5PM</div>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wide bg-red-50 text-red-700 px-2 py-0.5 rounded-full border border-red-200">
                +591 7X-XXX-XXX
            </span>
        </div>
    </div>

    <!-- Categorías de ayuda -->
    <div>
        <h2 class="text-xl font-bold text-slate-800 mb-5 font-heading">Categorías de Soporte</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach([
                ['icon'=>'fa-cart-shopping','color'=>'red','label'=>'Pedidos COD','sub'=>'Creación, estado y cancelación'],
                ['icon'=>'fa-wallet','color'=>'amber','label'=>'Billetera y Retiros','sub'=>'Pagos, saldos y transferencias'],
                ['icon'=>'fa-box-open','color'=>'blue','label'=>'Devoluciones','sub'=>'Paquetes rechazados y retornos'],
                ['icon'=>'fa-store','color'=>'purple','label'=>'Integración Tienda','sub'=>'Shopify, WooCommerce, API'],
                ['icon'=>'fa-warehouse','color'=>'green','label'=>'Inventario','sub'=>'Stock, almacén y productos'],
                ['icon'=>'fa-truck-fast','color'=>'orange','label'=>'Logística y Envíos','sub'=>'Rastreo, demoras y rutas'],
                ['icon'=>'fa-user-shield','color'=>'slate','label'=>'Mi Cuenta','sub'=>'Acceso, contraseña y datos'],
                ['icon'=>'fa-building-columns','color'=>'red','label'=>'Liquidaciones','sub'=>'Pagos bancarios y comisiones'],
            ] as $cat)
            <div class="bg-white border border-slate-200 rounded-2xl p-4 text-center hover:border-[#E88A38] transition cursor-pointer interactive-card shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center mx-auto mb-2 text-slate-600">
                    <i class="fa-solid {{ $cat['icon'] }}"></i>
                </div>
                <div class="text-xs font-bold text-slate-800">{{ $cat['label'] }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">{{ $cat['sub'] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Seguimiento de pedido rápido -->
    <div class="bg-gradient-to-r from-[#960016]/5 to-[#E88A38]/5 border border-red-100 rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <div class="flex-1">
                <h3 class="font-bold text-slate-800 text-base">📦 Rastrear un pedido sin iniciar sesión</h3>
                <p class="text-slate-500 text-sm mt-1">Ingresa el código de guía que recibiste por WhatsApp para conocer el estado de tu envío.</p>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <input type="text" placeholder="Ej: DRP-2026-001234"
                    class="flex-1 sm:w-56 border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:outline-none focus:border-[#E88A38]">
                <a href="{{ url('/tracking') }}" class="btn-scarlet px-5 py-2.5 rounded-xl text-white text-sm font-bold whitespace-nowrap">
                    Rastrear
                </a>
            </div>
        </div>
    </div>

    <!-- FAQ Accordion -->
    <div>
        <h2 class="text-xl font-bold text-slate-800 mb-5 font-heading">Preguntas Frecuentes</h2>
        <div class="space-y-3">
            @foreach([
                [
                    'q' => '¿Cuánto tarda en llegar un pedido COD en Bolivia?',
                    'a' => 'Los pedidos en Santa Cruz y La Paz se entregan en <strong>24–48 horas hábiles</strong>. Para el resto del país (Cochabamba, Oruro, Potosí, Tarija, Sucre, Beni, Pando) el tiempo es de <strong>48–72 horas hábiles</strong>. Se realizan hasta <strong>2 intentos de entrega</strong> sin costo adicional.'
                ],
                [
                    'q' => '¿Cuándo recibo el pago de mis ventas entregadas?',
                    'a' => 'Las utilidades se acreditan automáticamente en tu <strong>Billetera Digital Dropana</strong> una vez confirmada la entrega exitosa del paquete. Puedes solicitar retiro a tu cuenta bancaria (BNB, BCP, Banco Unión, etc.) en cualquier momento — el proceso toma <strong>máximo 24 horas hábiles</strong>.'
                ],
                [
                    'q' => '¿Qué pasa si el cliente rechaza el pedido?',
                    'a' => 'Si en el primer intento no se puede entregar, se realiza un <strong>segundo intento</strong> al siguiente día hábil. Si tampoco es posible, el paquete regresa al almacén de Dropana y se registra como <strong>devolución</strong>. El costo del flete de retorno se deduce de tu saldo. Puedes ver y gestionar tus devoluciones desde el panel.'
                ],
                [
                    'q' => '¿Puedo conectar mi tienda Shopify o WooCommerce?',
                    'a' => 'Sí. Desde tu panel de Dropshipper ve a la sección <strong>"Integración de Tienda"</strong> e ingresa tu URL de tienda y las credenciales de la API. Los pedidos de tu tienda online se importarán automáticamente y se crearán como envíos COD en Dropana.'
                ],
                [
                    'q' => '¿Es gratuito registrarse en Dropana?',
                    'a' => '<strong>Sí, completamente gratuito.</strong> No se cobra registro, membresía ni fee mensual. Solo pagas la tarifa de envío cuando generas un pedido (tarifa plana por departamento). El almacenaje para proveedores también es <strong>$0</strong>.'
                ],
                [
                    'q' => '¿En qué ciudades opera la red COD de Dropana Bolivia?',
                    'a' => 'Operamos en los <strong>9 departamentos de Bolivia</strong>: Santa Cruz, La Paz, El Alto, Cochabamba, Oruro, Potosí, Chuquisaca (Sucre), Tarija, Beni y Pando. La red de repartidores propios cubre Santa Cruz, La Paz y El Alto. Para el resto del país trabajamos con operadores logísticos aliados.'
                ],
            ] as $i => $faq)
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                <button @click="openFaq === {{ $i }} ? openFaq = null : openFaq = {{ $i }}"
                        class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-slate-50 transition gap-4">
                    <span class="font-semibold text-slate-800 text-sm">{{ $faq['q'] }}</span>
                    <i class="fa-solid fa-chevron-down text-slate-400 transition-transform shrink-0"
                       :class="openFaq === {{ $i }} ? 'rotate-180 text-[#960016]' : ''"></i>
                </button>
                <div x-show="openFaq === {{ $i }}" x-collapse class="px-6 pb-5 text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                    <div class="pt-4">{!! $faq['a'] !!}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- CTA final -->
    <div class="bg-[#960016] rounded-2xl p-8 text-center text-white space-y-4">
        <h3 class="text-xl font-black font-heading">¿No encontraste lo que buscabas?</h3>
        <p class="text-white/80 text-sm">Nuestro equipo de soporte está listo para ayudarte. Escríbenos y te respondemos en menos de 2 horas.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="https://wa.me/59170000000?text=Hola%20Dropana%2C%20necesito%20ayuda%20con..." target="_blank"
               class="bg-white text-[#960016] px-6 py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 hover:bg-red-50 transition">
                <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                Contactar por WhatsApp
            </a>
            <a href="{{ url('/contactenos') }}"
               class="border border-white/30 text-white px-6 py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 hover:bg-white/10 transition">
                <i class="fa-solid fa-envelope"></i>
                Enviar Formulario
            </a>
        </div>
    </div>

</div>
@endsection
