<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ $this->titulo }}</h1>
        <p class="text-gray-600 mt-1">
            Editar información del propietario y gestionar sus unidades
        </p>
    </div>

    <div class="space-y-6">
            {{-- Sección 1: Datos del Propietario --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Datos del Propietario
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Nombre --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text"
                               wire:model.blur="nombre"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Juan">
                        @error('nombre')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Apellido --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                        <input type="text"
                               wire:model.blur="apellido"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Pérez">
                        @error('apellido')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CI --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cédula de Identidad *</label>
                        <input type="text"
                               wire:model.blur="ci"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                               placeholder="1234567">
                        @error('ci')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Teléfono --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text"
                               wire:model.blur="telefono"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="71234567">
                        @error('telefono')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email"
                               wire:model.blur="email"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="juan.perez@email.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Observaciones --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea wire:model.blur="observaciones"
                                  rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Información adicional..."></textarea>
                        @error('observaciones')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Botón guardar propietario --}}
                <div class="mt-4 flex justify-end">
                    <button wire:click="actualizarPropietario"
                            type="button"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Datos del Propietario
                    </button>
                </div>
            </div>

            {{-- Sección 2: Unidades del Propietario --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Unidades del Propietario
                    <span class="text-sm font-normal text-gray-500">
                        ({{ count($unidades_actuales) }})
                    </span>
                </h2>

                @if(count($unidades_actuales) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($unidades_actuales as $unidad)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-900">
                                                    {{ $unidad['codigo'] }}
                                                </h3>
                                                <p class="text-sm text-gray-600">{{ $unidad['tipo_label'] }}</p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <span class="text-gray-500">Piso:</span>
                                                <span class="ml-1 font-medium">{{ $unidad['piso'] }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">M²:</span>
                                                <span class="ml-1 font-medium">{{ number_format($unidad['metros_cuadrados'], 2) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Categoría:</span>
                                                <span class="ml-1 font-medium">{{ $unidad['categoria_label'] }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Desde:</span>
                                                <span class="ml-1 font-medium">{{ $unidad['fecha_inicio'] }}</span>
                                            </div>
                                        </div>

                                        @if($unidad['activo'])
                                            <div class="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Activa
                                            </div>
                                        @else
                                            <div class="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Inactiva
                                            </div>
                                        @endif
                                    </div>

                                    <button type="button"
                                            wire:click="confirmarDesvincularUnidad({{ $unidad['id'] }})"
                                            class="ml-3 px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition text-sm flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Desvincular
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                        <svg class="w-12 h-12 text-yellow-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <p class="text-yellow-800 font-medium">Este propietario no tiene unidades asignadas</p>
                        <p class="text-yellow-600 text-sm mt-1">Use el formulario de abajo para asignar unidades</p>
                    </div>
                @endif
            </div>

            {{-- Sección 3: Vincular Nueva Unidad --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Vincular Nueva Unidad
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Buscar unidad --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Unidad Disponible</label>
                        <div class="relative">
                            <input type="text"
                                   wire:model.live.debounce.300ms="busqueda_unidad"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Buscar por código, piso...">
                            @if($buscando_unidad)
                                <div class="absolute right-3 top-2">
                                    <svg class="w-5 h-5 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Resultados de búsqueda --}}
                        @if(count($unidades_encontradas) > 0)
                            <div class="mt-2 border border-gray-200 rounded-lg max-h-48 overflow-y-auto bg-white shadow-lg">
                                @foreach($unidades_encontradas as $unidad)
                                    <button type="button"
                                            wire:click="seleccionarUnidad({{ $unidad['id'] }})"
                                            class="w-full px-4 py-3 text-left hover:bg-gray-50 transition border-b last:border-b-0 flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $unidad['codigo'] }} - {{ $unidad['nombre_completo'] }}</p>
                                            <p class="text-sm text-gray-600">{{ $unidad['tipo'] }} | Piso: {{ $unidad['piso'] }} | {{ $unidad['m2'] }} m²</p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Unidad seleccionada --}}
                        @if($unidad_seleccionada_id)
                            @if(collect($unidades_actuales)->where('id', $unidad_seleccionada_id)->isNotEmpty())
                                <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-sm font-medium text-yellow-800">⚠️ {{ $busqueda_unidad }} ya está asignada a este propietario</p>
                                    <button type="button" wire:click="limpiarBusquedaUnidad" class="mt-2 text-xs text-yellow-600 hover:text-yellow-800 underline">
                                        Seleccionar otra unidad
                                    </button>
                                </div>
                            @else
                                <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <p class="text-sm font-medium text-green-800">✓ Unidad seleccionada: {{ $busqueda_unidad }}</p>
                                    <button type="button" wire:click="limpiarBusquedaUnidad" class="mt-2 text-xs text-green-600 hover:text-green-800 underline">
                                        Cambiar selección
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Fecha de inicio --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio *</label>
                        <input type="date"
                               wire:model="fecha_inicio_unidad"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('fecha_inicio_unidad')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Botón vincular --}}
                    <div class="flex items-end">
                        @if(collect($unidades_actuales)->where('id', $unidad_seleccionada_id)->isNotEmpty())
                            <button type="button"
                                    disabled
                                    class="w-full px-4 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Ya está asignada
                            </button>
                        @else
                            <button type="button"
                                    wire:click="vincularUnidad"
                                    class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Vincular Unidad
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Botones de navegación --}}
        <div class="mt-6 flex justify-between">
            <a href="{{ route('propietarios.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                ← Volver a la Lista
            </a>
        </div>

        {{-- Modal de confirmación para desvincular --}}
        @if($mostrar_confirmacion_desvincular)
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); z-index: 9999;"
                 class="flex items-center justify-center">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">¿Desvincular unidad?</h3>
                    </div>

                    <p class="text-gray-600 mb-4">
                        Esta acción desvinculará la unidad del propietario y la unidad quedará <strong>inactiva</strong>.
                    </p>

                    <p class="text-sm text-gray-500 mb-6">
                        ¿Estás seguro de que deseas continuar?
                    </p>

                    <div class="flex justify-end gap-3">
                        <button type="button"
                                wire:click="cancelarDesvinculacion"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="desvincularUnidad"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            Sí, Desvincular
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Scripts para toast --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @this.on('toast-success', (e) => {
                    alert(e.detail.message);
                });
                @this.on('toast-error', (e) => {
                    alert(e.detail.message);
                });
            });
        </script>
    </div>