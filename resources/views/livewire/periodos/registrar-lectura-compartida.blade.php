<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('periodos.lecturas', $periodoId) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            &larr; Volver a lecturas
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">Registrar Lectura Compartida</h2>
            <p class="text-gray-600 mt-1">
                Período: <strong>{{ $periodo->nombre_periodo }}</strong>
            </p>
        </div>

        <form wire:submit="guardar" class="p-6 space-y-6">
            <!-- Grupo de Medidores -->
            <div>
                <label for="grupo" class="block text-sm font-medium text-gray-700">
                    Grupo de Medidores <span class="text-red-500">*</span>
                </label>
                <select id="grupo"
                        wire:model.live="grupoId"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Seleccione un grupo...</option>
                    @foreach($this->grupos as $grupo)
                        <option value="{{ $grupo['id'] }}">
                            {{ $grupo['nombre'] }} ({{ $grupo['unidades_count'] }} unidades)
                        </option>
                    @endforeach
                </select>
                @if(count($this->grupos) === 0 && \App\Models\MedidorGrupo::where('activo', true)->count() > 0)
                    <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="ml-3">
                                <p class="font-medium text-yellow-800">Todos los grupos tienen lectura registrada</p>
                                <p class="text-yellow-700 mt-1">
                                    <a href="{{ route('periodos.lecturas', $periodoId) }}" class="underline font-medium">Ver lista de lecturas</a>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                @error('grupoId')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if($this->grupoSeleccionado)
                <!-- Información del Grupo -->
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-purple-900 mb-3">
                        {{ $this->grupoSeleccionado['nombre'] }}
                    </h3>
                    <p class="text-sm text-purple-700 mb-2">
                        <strong>Unidades del grupo ({{ $this->grupoSeleccionado['unidades_count'] }}):</strong>
                    </p>
                    <ul class="text-sm text-purple-700 space-y-1">
                        @foreach($this->grupoSeleccionado['unidades'] as $unidad)
                            <li>• {{ $unidad['nombre'] }} - {{ $unidad['propietario'] }}</li>
                        @endforeach
                    </ul>
                    <p class="text-sm text-purple-600 mt-3 italic">
                        La distribución será equitativa entre todas las unidades
                    </p>
                </div>

                <!-- Lectura del Medidor Compartido -->
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Lectura del Medidor Compartido</h3>

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
                        @php
                            $consumoTotal = $lecturaActual - $lecturaAnterior;
                            $consumoPorUnidad = $consumoTotal / $this->grupoSeleccionado['unidades_count'];
                        @endphp
                        <div class="mt-4 space-y-3">
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-blue-900">Consumo Total del Grupo</p>
                                        <p class="text-xs text-blue-700 mt-1">
                                            Lectura Actual - Lectura Anterior
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-blue-600">
                                            {{ number_format($consumoTotal, 2) }} M³
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-green-900">Distribución por Unidad</p>
                                        <p class="text-xs text-green-700 mt-1">
                                            {{ $consumoTotal }} M³ ÷ {{ $this->grupoSeleccionado['unidades_count'] }} unidades
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-green-600">
                                            {{ number_format($consumoPorUnidad, 2) }} M³
                                        </p>
                                        <p class="text-xs text-green-700">c/u</p>
                                    </div>
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
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="{{ route('periodos.lecturas', $periodoId) }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm font-medium">
                    Cancelar
                </a>
                <button type="submit"
                        :disabled="!$grupoId || !$lecturaActual"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    Registrar y Distribuir
                </button>
            </div>
        </form>
    </div>
</div>
