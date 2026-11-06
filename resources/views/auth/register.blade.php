@extends('layouts.app')

@section('title', 'Registro de Cuenta | Dropana Bolivia')

@section('content')
<div class="max-w-2xl mx-auto my-6">
    <div class="text-center mb-6">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 mb-2">
            <span class="w-2 h-2 rounded-full bg-[#ED2100]"></span>
            Únete al Ecosistema E-commerce COD N°1 de Bolivia
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#960016]">Crear Nueva Cuenta</h1>
        <p class="text-slate-500 text-sm mt-1">Vende sin inventario propio o distribuye tus productos en toda Bolivia.</p>
    </div>

    <div class="white-card rounded-2xl p-6 sm:p-8 shadow-lg border border-slate-200">
        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-3.5 text-xs text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ role: '{{ old('role', 'dropshipper') }}' }">
            @csrf

            <!-- Selector de Perfil -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Tipo de Cuenta / Rol en la Plataforma
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="p-3.5 rounded-xl border-2 cursor-pointer transition flex items-start gap-3"
                        :class="role === 'dropshipper' ? 'border-[#960016] bg-red-50/40 text-[#960016]' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700'">
                        <input type="radio" name="role" value="dropshipper" x-model="role" class="mt-1 text-[#960016] focus:ring-[#960016]">
                        <div>
                            <div class="font-bold text-sm">Dropshipper / Tienda</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Vendo productos de la megabodega y cobro ganancias contra entrega.</div>
                        </div>
                    </label>

                    <label class="p-3.5 rounded-xl border-2 cursor-pointer transition flex items-start gap-3"
                        :class="role === 'provider' ? 'border-[#960016] bg-red-50/40 text-[#960016]' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700'">
                        <input type="radio" name="role" value="provider" x-model="role" class="mt-1 text-[#960016] focus:ring-[#960016]">
                        <div>
                            <div class="font-bold text-sm">Proveedor Mayorista</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Tengo stock físico en Bolivia y quiero distribuirlo a cientos de vendedores.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Datos Generales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre Completo o Razón Social</label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="Ej: Juan Pérez o Tienda Bolivia"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Correo Electrónico</label>
                    <input type="email" name="email" required value="{{ old('email') }}" placeholder="correo@ejemplo.com"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>
            </div>

            <!-- Teléfono y Documento -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">WhatsApp / Celular (+591)</label>
                    <input type="text" name="phone" required value="{{ old('phone', '+591 ') }}" placeholder="+591 7XXXXXXX"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tipo Documento</label>
                    <select name="identity_document_type" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38]">
                        <option value="CI" {{ old('identity_document_type') == 'CI' ? 'selected' : '' }}>Cédula de Identidad (CI)</option>
                        <option value="NIT" {{ old('identity_document_type') == 'NIT' ? 'selected' : '' }}>NIT Bolivia</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Número Documento</label>
                    <input type="text" name="identity_document" required value="{{ old('identity_document') }}" placeholder="Ej: 8392102 SC"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>
            </div>

            <!-- Ubicación -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Departamento</label>
                    <select name="department" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38]">
                        @foreach(['Santa Cruz', 'La Paz', 'Cochabamba', 'Oruro', 'Potosí', 'Chuquisaca', 'Tarija', 'Beni', 'Pando'] as $dept)
                            <option value="{{ $dept }}" {{ old('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Ciudad o Municipio</label>
                    <input type="text" name="city" value="{{ old('city') }}" placeholder="Ej: Santa Cruz de la Sierra, El Alto"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>
            </div>

            <!-- Contraseñas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Contraseña (Mínimo 8 caracteres)</label>
                    <input type="password" name="password" required placeholder="••••••••"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" required placeholder="••••••••"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm focus:bg-white focus:outline-none focus:border-[#E88A38]">
                </div>
            </div>

            <div class="pt-3">
                <button type="submit" class="w-full btn-scarlet py-3.5 rounded-xl font-bold text-sm shadow-md flex items-center justify-center gap-2">
                    <span>Crear Mi Cuenta Dropana</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-500">
            ¿Ya tienes una cuenta registrada? 
            <a href="{{ route('login') }}" class="font-bold text-[#960016] hover:text-[#ED2100] transition ml-1">
                Iniciar Sesión Aquí
            </a>
        </div>
    </div>
</div>
@endsection
