<div class="space-y-6">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Factores de Prorrateo</h2>
                <p class="text-gray-600 mt-1">Configura los factores de prorrateo para cada categoría de propiedad</p>
            </div>
        </div>
    </div>

    <!-- Tarjetas de Factores -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($this->factores as $categoria => $factor)
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ $factor->categoria_label }}
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            Vigente desde: {{ $factor->vigencia_desde->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-bold text-blue-600">
                            {{ number_format($factor->factor, 2, ',', '.') }}
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Factor</p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <p><strong>Aplica para:</strong></p>
                            @if($categoria === 'domiciliaria')
                                <p>• Departamentos</p>
                                <p>• Parqueos</p>
                                <p>• Bauleras</p>
                            @else
                                <p>• Oficinas</p>
                                <p>• Locales Comerciales</p>
                            @endif
                        </div>
                        <button wire:click="abrirModalEdicion('{{ $categoria }}')"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Editar Factor
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Información -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Información sobre Factores</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Los factores se aplican al cálculo de expensas comunes</li>
                        <li>El cambio de factor es inmediato para períodos ABIERTOS</li>
                        <li>Períodos CERRADOS mantienen el factor histórico</li>
                        <li>Es obligatorio registrar un motivo para cualquier cambio</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edición -->
@if($showModal)
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Editar Factor {{ ucfirst($categoriaSeleccionada) }}
            </h3>

            <form wire:submit="actualizarFactor">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Factor Actual</label>
                        <input type="text"
                               value="{{ number_format($factorActual, 2, ',', '.') }}"
                               disabled
                               class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nuevo Factor</label>
                        <input type="number" step="0.01" min="0"
                               wire:model.live="nuevoFactor"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('nuevoFactor')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Motivo del Cambio <span class="text-red-500">*</span></label>
                        <textarea wire:model.live="motivo"
                                  rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Explique por qué necesita cambiar este factor..."></textarea>
                        @error('motivo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button"
                            wire:click="cerrarModal"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Actualizar Factor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
