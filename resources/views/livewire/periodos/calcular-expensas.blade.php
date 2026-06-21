<div>
    <!-- Información del período -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    Cálculo de Expensas
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    Período: {{ $periodo->nombre_periodo }}
                </p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                {{ $periodo->estado === 'abierto' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                {{ $periodo->estado_label }}
            </span>
        </div>

        <!-- Validaciones -->
        <div class="space-y-3">
            @if(!$this->validacion['valido'])
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                El período no está completo para calcular expensas
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($this->validacion['errores'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(!empty($this->validacion['advertencias']))
                <div class="bg-amber-50 border border-amber-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-amber-800">
                                Advertencias - Puedes continuar
                            </h3>
                            <div class="mt-2 text-sm text-amber-700">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($this->validacion['advertencias'] as $advertencia)
                                        <li>{{ $advertencia }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($this->validacion['valido'] && empty($this->validacion['advertencias']))
                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">
                                Período completo para calcular expensas
                            </h3>
                        </div>
                    </div>
                </div>
            @endif

            @if($this->expensasExistentes > 0)
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Ya existen {{ $this->expensasExistentes }} expensas calculadas para este período
                            </h3>
                            <p class="text-sm text-blue-700 mt-1">
                                Puedes recalcularlas para actualizar los montos.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Botones de acción -->
    @if($periodo->estado === 'abierto' && $this->validacion['valido'])
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                Acciones de Cálculo
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Calcular todas -->
                <div class="border rounded-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h4 class="text-base font-medium text-gray-900">
                                Calcular Todas las Expensas
                            </h4>
                            <p class="text-sm text-gray-600 mt-1">
                                Generará expensas para todas las unidades activas del edificio.
                            </p>
                            @if($this->expensasExistentes > 0)
                                <p class="text-xs text-amber-600 mt-2">
                                    ⚠️ Esto reemplazará las {{ $this->expensasExistentes }} expensas existentes
                                </p>
                            @endif
                            <button
                                wire:click="confirmarCalculo"
                                wire:loading.attr="disabled"
                                class="mt-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                @if($calculando)
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Calculando...
                                @else
                                    Calcular Todas
                                @endif
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recalcular (solo si hay expensas) -->
                @if($this->expensasExistentes > 0)
                    <div class="border rounded-lg p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </div>
                            <div class="ml-4 flex-1">
                                <h4 class="text-base font-medium text-gray-900">
                                    Recalcular Expensas
                                </h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    Elimina las expensas actuales y las genera nuevamente.
                                </p>
                                <p class="text-xs text-amber-600 mt-2">
                                    ⚠️ Úsalo solo si hubo cambios en lecturas o factores
                                </p>
                                <button
                                    wire:click="recalcularExpensas"
                                    wire:loading.attr="disabled"
                                    class="mt-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    @if($calculando)
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Recalculando...
                                    @else
                                        Recalcular
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Modal de confirmación -->
        @if($mostrarConfirmacion)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelarCalculo"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Confirmar Cálculo de Expensas
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            ¿Estás seguro de calcular todas las expensas para el período <strong>{{ $periodo->nombre_periodo }}</strong>?
                                        </p>
                                        @if($this->expensasExistentes > 0)
                                            <p class="text-sm text-amber-600 mt-2">
                                                ⚠️ Se reemplazarán las {{ $this->expensasExistentes }} expensas existentes.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="button"
                                wire:click="calcularExpensas"
                                wire:loading.attr="disabled"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                @if($calculando)
                                    Calculando...
                                @else
                                    Confirmar y Calcular
                                @endif
                            </button>
                            <button
                                type="button"
                                wire:click="cancelarCalculo"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Resultados -->
    @if($resultados && !$calculando)
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                Resultados del Cálculo
            </h3>

            @if($resultados['tipo'] === 'todas' || $resultados['tipo'] === 'recalculo')
                <div class="grid grid-cols-1 gap-4">
                    <div class="bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium text-green-800">
                                {{ $resultados['exitosas'] }} expensas calculadas exitosamente
                            </span>
                        </div>
                    </div>

                    @if(!empty($resultados['detalles']))
                        <div class="border rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">
                                Primeras 10 expensas:
                            </h4>
                            <div class="space-y-2">
                                @foreach($resultados['detalles'] as $detalle)
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-600">{{ $detalle['unidad'] }}</span>
                                        <span class="font-medium text-gray-900">{{ number_format($detalle['monto_total'], 2) }} Bs</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($resultados['errores']))
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <h4 class="text-sm font-medium text-red-800 mb-2">
                                Errores ({{ count($resultados['errores']) }}):
                            </h4>
                            <ul class="text-sm text-red-700 space-y-1">
                                @foreach($resultados['errores'] as $error)
                                    <li>
                                        <strong>{{ $error['unidad'] }}:</strong> {{ $error['error'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
