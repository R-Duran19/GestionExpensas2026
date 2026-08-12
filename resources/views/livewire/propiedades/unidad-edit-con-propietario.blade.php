<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ $this->titulo }}</h1>
        <p class="text-gray-600 mt-1">
            Editar información de la unidad y gestionar su propietario
        </p>
    </div>

    <div class="space-y-6">
            {{-- Sección 1: Datos de la Unidad --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Datos de la Unidad
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Código --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                        <input type="text"
                               wire:model.blur="codigo"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                               placeholder="Ej: DEP-11-A">
                        @error('codigo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Tipo de Unidad --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Unidad *</label>
                        <select wire:model.blur="tipo_unidad"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione...</option>
                            @foreach($this->tiposUnidad as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('tipo_unidad')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Piso / Ubicación --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Piso / Ubicación *</label>
                        <input type="text"
                               wire:model.blur="piso"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ej: Planta 11">
                        @error('piso')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Metros Cuadrados --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Metros Cuadrados *</label>
                        <input type="number"
                               step="0.01"
                               wire:model.blur="metros_cuadrados"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ej: 85.50">
                        @error('metros_cuadrados')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Categoría --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                        <select wire:model.blur="categoria_unidad"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                @if($tipo_unidad && $tipo_unidad !== '') disabled @endif>
                            <option value="">Seleccione...</option>
                            @foreach($this->categorias as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($tipo_unidad && $tipo_unidad !== '')
                            <p class="mt-1 text-xs text-gray-500">Autoseleccionado según tipo de unidad</p>
                        @endif
                        @error('categoria_unidad')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Estado --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <div class="flex items-center h-full pt-2">
                            @if($activo)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    ✓ Activa
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    ✗ Inactiva
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            La unidad se activa automáticamente al asignar un propietario
                        </p>
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

                {{-- Botón guardar unidad --}}
                <div class="mt-4 flex justify-end">
                    <button wire:click="actualizarUnidad"
                            type="button"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Datos de Unidad
                    </button>
                </div>
            </div>

            {{-- Sección 2: Propietario Actual --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Propietario Actual
                </h2>

                @if($propietario_actual)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ $propietario_actual['nombre_completo'] }}
                                        </h3>
                                        <p class="text-sm text-gray-600">CI: {{ $propietario_actual['ci'] }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">Teléfono:</span>
                                        <span class="ml-2 font-medium">{{ $propietario_actual['telefono'] }}</span>
                                    </div>
                                    @if($propietario_actual['email'])
                                        <div>
                                            <span class="text-gray-600">Email:</span>
                                            <span class="ml-2 font-medium">{{ $propietario_actual['email'] }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <span class="text-gray-600">Desde:</span>
                                        <span class="ml-2 font-medium">
                                            {{ \Carbon\Carbon::parse($propietario_actual['fecha_inicio'])->format('d/m/Y') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 ml-4">
                                <button
                                        wire:click="confirmarDesvincularPropietario"
                                        type="button"
                                        class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition text-sm flex items-center gap-1 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Desvincular
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                        <svg class="w-12 h-12 text-yellow-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-yellow-800 font-medium">Esta unidad no tiene propietario asignado</p>
                        <p class="text-yellow-600 text-sm mt-1">Asigne un propietario para activar la unidad</p>
                    </div>
                @endif
            </div>

            {{-- Sección 3: Asignar Nuevo Propietario --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Asignar Nuevo Propietario
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Buscar propietario --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Propietario</label>
                        <div class="relative">
                            <input type="text"
                                   wire:model.live.debounce.300ms="busqueda_propietario"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Buscar por nombre, CI o email...">
                            @if($buscando_propietario)
                                <div class="absolute right-3 top-2">
                                    <svg class="w-5 h-5 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Resultados de búsqueda --}}
                        @if(count($propietarios_encontrados) > 0)
                            <div class="mt-2 border border-gray-200 rounded-lg max-h-48 overflow-y-auto bg-white shadow-lg">
                                @foreach($propietarios_encontrados as $prop)
                                    <button type="button"
                                            wire:click="seleccionarPropietario({{ $prop['id'] }})"
                                            class="w-full px-4 py-3 text-left hover:bg-gray-50 transition border-b last:border-b-0 flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $prop['nombre_completo'] }}</p>
                                            <p class="text-sm text-gray-600">CI: {{ $prop['ci'] }}</p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Propietario seleccionado --}}
                        @if($propietario_seleccionado_id)
                            @if($propietario_actual && $propietario_actual['id'] == $propietario_seleccionado_id)
                                <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-sm font-medium text-yellow-800">⚠️ {{ $busqueda_propietario }} ya es el propietario actual de esta unidad</p>
                                    <button type="button" wire:click="limpiarBusquedaPropietario" class="mt-2 text-xs text-yellow-600 hover:text-yellow-800 underline">
                                        Seleccionar otro propietario
                                    </button>
                                </div>
                            @else
                                <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <p class="text-sm font-medium text-green-800">✓ Propietario seleccionado: {{ $busqueda_propietario }}</p>
                                    <button type="button" wire:click="limpiarBusquedaPropietario" class="mt-2 text-xs text-green-600 hover:text-green-800 underline">
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
                               wire:model="fecha_inicio_propietario"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('fecha_inicio_propietario')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Botón asignar --}}
                    <div class="flex items-end">
                        @if($propietario_actual && $propietario_seleccionado_id && $propietario_actual['id'] == $propietario_seleccionado_id)
                            <button type="button"
                                    disabled
                                    class="w-full px-4 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Ya es el propietario actual
                            </button>
                        @else
                            <button type="button"
                                    wire:click="vincularPropietario"
                                    class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Asignar Propietario
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Botones de navegación --}}
        <div class="mt-6 flex justify-between">
            <a href="{{ route('unidades.index') }}"
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
                        <h3 class="text-lg font-semibold text-gray-900">¿Desvincular propietario?</h3>
                    </div>

                    <p class="text-gray-600 mb-4">
                        Esta acción desvinculará al propietario de la unidad y la unidad quedará <strong>inactiva</strong>.
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
                                wire:click="desvincularPropietario"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            Sí, Desvincular
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>