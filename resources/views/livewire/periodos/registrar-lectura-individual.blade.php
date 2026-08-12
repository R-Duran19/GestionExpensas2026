<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-4">
        <a href="{{ route('periodos.lecturas', $periodoId) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver a lecturas
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Registrar Lectura Individual</h2>
            <p class="text-gray-600 mt-1 text-sm">
                Período: <strong>{{ $periodo->nombre_periodo }}</strong>
            </p>
        </div>

        <form wire:submit="guardar" class="p-4 sm:p-6 space-y-6">
            <!-- Buscador de Unidad -->
            <div>
                <label for="buscarUnidad" class="block text-sm font-medium text-gray-700 mb-2">
                    Buscar Unidad <span class="text-red-500">*</span>
                </label>

                <!-- Input de búsqueda -->
                <div class="relative">
                    <input type="text"
                           id="buscarUnidad"
                           wire:model.live.debounce.300ms="searchUnidad"
                           placeholder="Escribe código o nombre de unidad..."
                           class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base"
                           autocomplete="off">
                    <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Lista de resultados -->
                @if($searchUnidad)
                    <div class="mt-2 border border-gray-300 rounded-lg max-h-60 overflow-y-auto bg-white shadow-lg">
                        @if(count($this->unidadesFiltradas) > 0)
                            @foreach($this->unidadesFiltradas as $unidad)
                                <button type="button"
                                        wire:click="seleccionarUnidad({{ $unidad['id'] }})"
                                        class="w-full px-4 py-3 text-left hover:bg-blue-50 border-b border-gray-100 last:border-b-0 transition-colors @if($unidadId == $unidad['id']) bg-blue-100 font-medium @endif">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <span class="font-medium text-gray-900">{{ $unidad['codigo'] }}</span>
                                            <span class="text-gray-600 mx-2">•</span>
                                            <span class="text-gray-700">{{ $unidad['nombre'] }}</span>
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1 sm:mt-0">
                                            {{ $unidad['propietario'] }}
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        @else
                            <div class="px-4 py-6 text-center text-gray-500 text-sm">
                                <svg class="mx-auto h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                @if(count($this->unidades) === 0)
                                    <p class="font-medium">Todas las unidades tienen lectura registrada</p>
                                    <p class="text-xs mt-1">Ve a la lista de lecturas para ver las registradas</p>
                                @else
                                    No se encontraron unidades
                                @endif
                            </div>
                        @endif
                    </div>
                @elseif(count($this->unidades) === 0)
                    <div class="mt-2 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">
                                    Todas las unidades tienen lectura registrada
                                </p>
                                <p class="text-sm text-yellow-700 mt-1">
                                    <a href="{{ route('periodos.lecturas', $periodoId) }}" class="underline font-medium">Ver lista de lecturas</a>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($unidadId)
                    <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="font-medium text-blue-900">Unidad seleccionada:</span>
                            <span class="text-blue-700 ml-2">{{ $this->unidadesFiltradas->firstWhere('id', $unidadId)?->nombre ?? '' }}</span>
                        </div>
                        <button type="button" wire:click="limpiarUnidad" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Cambiar
                        </button>
                    </div>
                @endif

                @error('unidadId')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if($unidadId && $lecturaAnterior !== null)
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Lectura de Medidor</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Lectura Anterior -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Lectura Anterior
                            </label>
                            <input type="text"
                                   :value="{{ number_format($lecturaAnterior, 2) }} M³"
                                   readonly
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                            <p class="mt-1 text-xs text-gray-500">
                                (Automática del período anterior)
                            </p>
                        </div>

                        <!-- Lectura Actual -->
                        <div>
                            <label for="lecturaActual" class="block text-sm font-medium text-gray-700">
                                Lectura Actual (M³) <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   id="lecturaActual"
                                   wire:model="lecturaActual"
                                   step="0.01"
                                   min="0"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('lecturaActual')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if($lecturaActual && $lecturaActual >= $lecturaAnterior)
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Consumo Calculado</p>
                                    <p class="text-xs text-blue-700 mt-1">
                                        Lectura Actual - Lectura Anterior
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ number_format($lecturaActual - $lecturaAnterior, 2) }} M³
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Observaciones -->
                <div class="mt-4">
                    <label for="observaciones" class="block text-sm font-medium text-gray-700">
                        Observaciones (opcional)
                    </label>
                    <textarea id="observaciones"
                              wire:model="observaciones"
                              rows="3"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
            @endif

            <!-- Botones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-4 space-y-3 sm:space-y-0 pt-4 border-t border-gray-200">
                <a href="{{ route('periodos.lecturas', $periodoId) }}"
                   class="w-full sm:w-auto px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm font-medium text-center">
                    Cancelar
                </a>
                <button type="submit"
                        :disabled="!$unidadId || !$lecturaActual"
                        class="w-full sm:w-auto px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    Guardar Lectura
                </button>
            </div>
        </form>
    </div>
</div>
