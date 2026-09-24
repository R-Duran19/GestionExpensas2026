{{-- [PORTAL / Fase 1.4] Tarjeta de gestión de acceso al portal (solo admin) --}}
<div class="bg-white rounded-lg shadow mt-6">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-medium text-gray-900">
            <i class="fa-solid fa-user-shield mr-1 text-indigo-500"></i> Acceso al Portal
        </h2>
        @if ($this->acceso)
            @if ($this->acceso->activo)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Activo
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Desactivado
                </span>
            @endif
        @else
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                Sin acceso
            </span>
        @endif
    </div>

    <div class="p-6">
        @if (!$this->acceso)
            <p class="text-sm text-gray-500 mb-4">
                Esta persona aún <strong>no tiene acceso al portal</strong>. Se creará una cuenta con
                su CI como usuario y una contraseña temporal que deberás entregar en persona.
            </p>
            <button wire:click="crearAcceso"
                    wire:loading.attr="disabled"
                    wire:target="crearAcceso"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                <span wire:loading wire:target="crearAcceso"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                <i class="fa-solid fa-user-plus" wire:loading.remove wire:target="crearAcceso"></i>
                Crear acceso al portal
            </button>
        @else
            <dl class="space-y-2 text-sm mb-4">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Usuario (CI)</dt>
                    <dd class="font-medium text-gray-800">{{ $this->persona?->ci }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Último login</dt>
                    <dd class="font-medium text-gray-800">
                        {{ $this->acceso->last_login_at?->format('d/m/Y H:i') ?? 'Nunca ingresó' }}
                    </dd>
                </div>
                @if ($this->acceso->must_change_password)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Estado de contraseña</dt>
                        <dd><span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Temporal (sin cambiar)</span></dd>
                    </div>
                @endif
            </dl>

            {{-- Contraseña temporal: se muestra UNA VEZ --}}
            @if ($passwordTemporal)
                <div class="mb-4 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                    <p class="text-xs font-semibold text-emerald-800 mb-1">
                        <i class="fa-solid fa-eye mr-1"></i> Contraseña temporal (mostrada una sola vez):
                    </p>
                    <div class="flex items-center gap-2">
                        <code class="text-lg font-mono font-bold text-emerald-900 tracking-wider select-all">{{ $passwordTemporal }}</code>
                        <button type="button"
                                x-data
                                @click="navigator.clipboard.writeText('{{ $passwordTemporal }}'); $el.querySelector('i').className = 'fa-solid fa-check text-emerald-600'"
                                class="text-gray-400 hover:text-emerald-600"
                                title="Copiar">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                    <p class="text-xs text-emerald-700 mt-2">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        No se volverá a mostrar. Entrégala por WhatsApp o en persona; el usuario deberá
                        cambiarla al primer ingreso.
                    </p>
                </div>
            @endif

            <div class="flex flex-col gap-2">
                <button wire:click="resetearPassword"
                        wire:loading.attr="disabled"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition disabled:opacity-50">
                    <i class="fa-solid fa-key"></i> Resetear contraseña
                </button>

                @if ($this->acceso->activo)
                    <button wire:click="toggleActivo"
                            wire:confirm="¿Desactivar el acceso al portal de esta persona? No podrá iniciar sesión hasta que lo reactives."
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition">
                        <i class="fa-solid fa-user-slash"></i> Desactivar acceso
                    </button>
                @else
                    <button wire:click="toggleActivo"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition">
                        <i class="fa-solid fa-user-check"></i> Reactivar acceso
                    </button>
                @endif
            </div>
        @endif

        {{-- [Fase 3.3] Autorizaciones por unidad (solo inquilinos) --}}
        @if ($this->tipo === 'inquilino' && $this->unidadesInquilino->isNotEmpty())
            <div class="mt-5 pt-5 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">
                    <i class="fa-solid fa-shield-halved mr-1 text-indigo-400"></i> Acceso a unidades (RF-48)
                </h3>
                <p class="text-xs text-gray-400 mb-3">
                    El propietario autoriza/revoca desde su portal; aquí puedes gestionarlo como fallback.
                </p>

                <div class="space-y-2">
                    @foreach ($this->unidadesInquilino as $fila)
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2" wire:key="aut-{{ $fila->unidad->id }}">
                            <span class="text-sm text-gray-700">
                                <i class="fa-solid fa-door-open mr-1 text-gray-400"></i> {{ $fila->unidad->codigo }}
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $fila->autorizado ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $fila->autorizado ? 'Autorizado' : 'Sin acceso' }}
                                </span>
                                <button wire:click="toggleAutorizacion({{ $fila->unidad->id }})"
                                        wire:loading.attr="disabled"
                                        class="text-xs px-2 py-1 rounded-md border transition
                                        {{ $fila->autorizado
                                            ? 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100'
                                            : 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100' }}">
                                    {{ $fila->autorizado ? 'Revocar' : 'Autorizar' }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
