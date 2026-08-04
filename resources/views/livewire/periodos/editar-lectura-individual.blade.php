<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-4">
        <a href="{{ route('periodos.lecturas', $periodoId) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver a lecturas
        </a>
    </div>

    @if($errorMensaje)
        <div class="mb-4 p-4 rounded-lg border border-red-300 bg-red-50 flex items-start justify-between gap-3">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-red-900">No se pudo guardar la lectura</p>
                    <p class="text-sm text-red-700 mt-0.5">{{ $errorMensaje }}</p>
                </div>
            </div>
            <button type="button" wire:click="$set('errorMensaje', null)" class="text-red-600 hover:text-red-800">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center bg-amber-100">
                    <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Editar Lectura Individual</h2>
                    <p class="text-gray-600 mt-0.5 text-sm">
                        Modifica la lectura registrada
                    </p>
                </div>
            </div>
        </div>

        <form wire:submit="solicitarConfirmacion" class="p-4 sm:p-6 space-y-6">
            <!-- Información de la unidad (solo lectura) -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $unidadCodigo }}</span>
                            <h3 class="text-base font-bold text-blue-900">{{ $unidadNombre }}</h3>
                        </div>
                        @if($propietario)
                            <p class="text-sm text-blue-700 mt-1">Propietario: {{ $propietario }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white">
                        Individual
                    </span>
                </div>
            </div>

            <!-- Lectura -->
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Lectura de Medidor</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Lectura Anterior -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Lectura Anterior
                        </label>
                        <input type="text"
                               :value="number_format($lecturaAnterior, 2) . ' M³'"
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
                               wire:model.live.debounce.500ms="lecturaActual"
                               step="0.01"
                               min="0"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                        @error('lecturaActual')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if($this->consumoCalculado > 0 || ($lecturaActual !== null && $lecturaActual !== ''))
                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-900">Consumo Calculado</p>
                                <p class="text-xs text-blue-700 mt-1">
                                    Lectura Actual - Lectura Anterior
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold {{ $this->consumoCalculado >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                                    {{ number_format($this->consumoCalculado, 2) }} M³
                                </p>
                            </div>
                        </div>
                        @if($lecturaActual !== null && is_numeric($lecturaActual) && (float) $lecturaActual < $lecturaAnterior)
                            <p class="mt-2 text-xs text-red-600">⚠️ La lectura actual es menor que la anterior.</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Observaciones -->
            <div>
                <label for="observaciones" class="block text-sm font-medium text-gray-700">
                    Observaciones (opcional)
                </label>
                <textarea id="observaciones"
                          wire:model="observaciones"
                          rows="3"
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500"></textarea>
            </div>

            <!-- Botones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-4 space-y-3 sm:space-y-0 pt-4 border-t border-gray-200">
                <a href="{{ route('periodos.lecturas', $periodoId) }}"
                   class="w-full sm:w-auto px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm font-medium text-center">
                    Cancelar
                </a>
                <button type="submit"
                        :disabled="!$lecturaActual"
                        class="w-full sm:w-auto px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="inline w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Modal de confirmación (server-side, sin Alpine) -->
    @if($mostrarConfirmacion)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
            <div class="flex items-end sm:items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-40"
                     wire:click="cerrarConfirmacion"
                     aria-hidden="true"></div>

                <!-- Panel -->
                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-50">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 13l4 4L19 7M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Confirmar cambios
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Vas a actualizar la lectura de <strong>{{ $unidadNombre }}</strong>
                                        ({{ $unidadCodigo }}) a <strong>{{ number_format($lecturaActual ?? 0, 2) }} M³</strong>.
                                        El consumo será de <strong>{{ number_format($this->consumoCalculado, 2) }} M³</strong>.
                                    </p>
                                    <p class="text-xs text-amber-700 mt-2">
                                        Esta acción quedará registrada en el historial.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="button"
                                wire:click="confirmarGuardado"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Sí, guardar
                        </button>
                        <button type="button"
                                wire:click="cerrarConfirmacion"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
