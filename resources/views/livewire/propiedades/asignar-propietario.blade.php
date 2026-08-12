<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Asignar Propietario a Unidad</h1>
        <p class="mt-1 text-gray-600">Selecciona un propietario y una unidad para realizar la asignación</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Formulario --}}
        <div class="p-6 bg-white rounded-lg shadow">
            <form wire:submit.prevent="asignar">
                {{-- Buscar/Seleccionar Propietario --}}
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-medium text-gray-700">Propietario *</label>
                    <div class="relative">
                        <input type="text"
                               wire:model.live.debounce.300ms="busqueda_propietario"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Buscar propietario por nombre o CI..."
                               autocomplete="off">
                        @if($propietarios_encontrados && count($propietarios_encontrados) > 0)
                            <div class="absolute z-50 w-full mt-2 bg-white border border-gray-300 rounded-lg shadow-lg min-h-[60px] max-h-64 overflow-y-auto">
                                @foreach($propietarios_encontrados as $prop)
                                    <button type="button"
                                            wire:click.prevent="seleccionarPropietario({{ $prop['id'] }})"
                                            class="w-full px-4 py-3 text-left transition-colors border-b cursor-pointer hover:bg-blue-50 last:border-b-0 focus:outline-none focus:bg-blue-50">
                                        <div class="font-medium text-gray-900">{{ $prop['nombre_completo'] }}</div>
                                        <div class="mt-1 text-xs text-gray-500">CI: {{ $prop['ci'] }} | {{ $prop['unidades_actuales'] }} unidad(es)</div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @error('propietario_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Info Propietario Seleccionado --}}
                @if($this->getInfoPropietarioProperty())
                    <div class="p-3 mb-4 border border-blue-200 rounded-lg bg-blue-50">
                        <h4 class="text-sm font-medium text-blue-900">Propietario Seleccionado</h4>
                        <div class="mt-1 text-sm text-blue-800">
                            <p><strong>{{ $this->getInfoPropietarioProperty()['nombre'] }}</strong></p>
                            <p>CI: {{ $this->getInfoPropietarioProperty()['ci'] }}</p>
                            <p>Tel: {{ $this->getInfoPropietarioProperty()['telefono'] }}</p>
                            @if($this->getInfoPropietarioProperty()['email'])
                                <p>Email: {{ $this->getInfoPropietarioProperty()['email'] }}</p>
                            @endif
                            <p>Unidades actuales: {{ $this->getInfoPropietarioProperty()['unidades_actuales'] }}</p>
                        </div>
                    </div>
                @endif

                {{-- Buscar/Seleccionar Unidad --}}
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-medium text-gray-700">
                        Unidades * <span class="text-xs text-gray-500">(Puede seleccionar múltiples)</span>
                    </label>

                    <input type="text"
                           wire:model.live.debounce.1000ms="busqueda_unidad"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Buscar unidad por código..."
                           autocomplete="off">

                    @if(!empty($unidades_encontradas) && is_array($unidades_encontradas) && count($unidades_encontradas) > 0)
                        <div class="bg-white border border-gray-300 rounded-lg shadow-lg @if(count($unidades_encontradas) > 5) max-h-80 overflow-y-auto @endif -mt-1 z-50">
                            @foreach($unidades_encontradas as $uni)
                                <button type="button"
                                        wire:click.prevent="seleccionarUnidad({{ $uni['id'] }})"
                                        class="w-full px-4 py-3 text-left transition-colors border-b cursor-pointer hover:bg-blue-50 last:border-b-0 focus:outline-none focus:bg-blue-50">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900">{{ $uni['codigo'] }} - {{ $uni['tipo'] }}</div>
                                            <div class="mt-1 text-xs text-gray-500">{{ $uni['piso'] }} | {{ $uni['m2'] }} m²</div>
                                            @if($uni['tiene_propietario'])
                                                <div class="mt-1 text-xs text-orange-600">⚠️ Tiene propietario: {{ $uni['propietario_actual'] }}</div>
                                            @endif
                                        </div>
                                        @if(in_array($uni['id'], $unidades_seleccionadas))
                                            <span class="ml-2 text-xl text-green-500">✓</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @error('unidades_seleccionadas') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Lista de Unidades Seleccionadas --}}
                <div class="mb-4 p-3 @if($this->unidadesSeleccionadasInfo) bg-green-50 border border-green-200 @else bg-gray-50 border border-gray-200 @endif rounded-lg">
                    @if($this->unidadesSeleccionadasInfo)
                        <h4 class="mb-2 text-sm font-medium text-green-900">
                            Unidades Seleccionadas ({{ count($this->unidadesSeleccionadasInfo) }})
                        </h4>
                        <div class="space-y-2 overflow-y-auto max-h-48">
                            @foreach($this->unidadesSeleccionadasInfo as $unidad)
                                <div class="flex items-center justify-between p-2 text-sm bg-white border border-green-100 rounded">
                                    <div class="flex-1">
                                        <p class="font-medium text-green-800">{{ $unidad['codigo'] }} - {{ $unidad['tipo'] }}</p>
                                        <p class="text-xs text-gray-600">{{ $unidad['piso'] }} | {{ $unidad['m2'] }} m²</p>
                                        @if($unidad['propietario_actual'] !== 'Sin asignar')
                                            <p class="text-xs text-orange-600">⚠️ Actual: {{ $unidad['propietario_actual'] }}</p>
                                        @endif
                                    </div>
                                    <button type="button"
                                            wire:click="quitarUnidad({{ $unidad['id'] }})"
                                            class="p-1 text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-6 text-center">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <p class="text-sm text-gray-500">Ninguna unidad seleccionada</p>
                            <p class="mt-1 text-xs text-gray-400">Usa el buscador arriba para agregar unidades</p>
                        </div>
                    @endif
                </div>

                {{-- Fecha Inicio --}}
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-medium text-gray-700">Fecha de Inicio *</label>
                    <input type="date"
                           wire:model.live="fecha_inicio"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('fecha_inicio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Motivo --}}
                <div class="mb-6">
                    <label class="block mb-1 text-sm font-medium text-gray-700">Motivo del Cambio</label>
                    <textarea wire:model.live="motivo"
                              rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Ej: Compra-venta, herencia, etc."></textarea>
                </div>

                {{-- Botones --}}
                <div class="flex justify-between">
                    <button type="button"
                            wire:click="limpiarFormulario"
                            class="px-4 py-2 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                        Limpiar
                    </button>
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Asignar Propietario{{ count($unidades_seleccionadas) > 1 ? ' (' . count($unidades_seleccionadas) . ' unidades)' : '' }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Panel de Información --}}
        <div>
            {{-- Instrucciones --}}
            <div class="p-4 mb-6 border border-yellow-200 rounded-lg bg-yellow-50">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Información Importante</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="space-y-1 list-disc list-inside">
                                <li>Puede seleccionar múltiples unidades para asignar al mismo propietario</li>
                                <li>Si la unidad ya tiene propietario, será reemplazado automáticamente</li>
                                <li>El propietario anterior quedará en el historial</li>
                                <li>Las unidades se activarán automáticamente</li>
                                <li>Todos los cambios quedan registrados en el log de actividad</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Asignaciones Recientes --}}
            <div class="p-4 bg-white rounded-lg shadow">
                <h3 class="mb-3 text-sm font-medium text-gray-900">Asignaciones Recientes</h3>
                @if(session('asignacion_reciente'))
                    <div class="p-3 border border-green-200 rounded-lg bg-green-50">
                        <p class="text-sm font-medium text-green-800">{{ session('asignacion_reciente') }}</p>
                        <p class="mt-1 text-xs text-green-600">{{ now()->format('H:i') }}</p>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Las asignaciones recientes aparecerán aquí.</p>
                @endif
            </div>
        </div>
    </div>
</div>


