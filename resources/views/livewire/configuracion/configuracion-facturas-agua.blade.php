<div class="space-y-6">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Facturas de Agua EPSAS</h2>
                <p class="text-gray-600 mt-1">Configura los números de factura para los servicios de agua</p>
            </div>
        </div>
    </div>

    <!-- Tarjetas de Facturas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($this->facturas as $factura)
            <div class="bg-white rounded-lg shadow-sm p-6" wire:key="factura-{{ $factura->id }}">
                @if($showModal && $facturaId === $factura->id)
                    <!-- Formulario de Edición -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Editar {{ $factura->tipo_label }}
                        </h3>

                        <form wire:submit="actualizarNumeroFactura">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Número Actual</label>
                                    <input type="text"
                                           value="{{ $numeroActual }}"
                                           disabled
                                           class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nuevo Número <span class="text-red-500">*</span></label>
                                    <input type="text"
                                           wire:model.live="nuevoNumero"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="Ej: DOM-001, COM-002">
                                    @error('nuevoNumero')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Motivo del Cambio <span class="text-red-500">*</span></label>
                                    <textarea wire:model.live="motivo"
                                              rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="Explique por qué necesita cambiar este número..."></textarea>
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
                                    Actualizar Número
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <!-- Vista Normal -->
                    <div>
                        <div class="mb-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                {{ $factura->categoria === 'domiciliaria' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $factura->categoria_label }}
                            </span>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            {{ $factura->tipo_label }}
                        </h3>

                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <p class="text-sm text-gray-600 mb-1">Número de Factura</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ $factura->numero_factura }}
                            </p>
                        </div>

                        <div class="text-sm text-gray-600 mb-4">
                            <p><strong>Creado por:</strong> {{ $factura->creadoPor->name ?? 'Sistema' }}</p>
                            <p><strong>Fecha:</strong> {{ $factura->created_at->format('d/m/Y') }}</p>
                        </div>

                        <button wire:click="abrirModalEdicion({{ $factura->id }})"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Editar Número
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Información -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Información sobre Facturas EPSAS</h3>
                <div class="mt-2 text-sm text-green-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Existen 3 facturas principales: 2 domiciliarias y 1 comercial</li>
                        <li>Estas facturas se utilizan mensualmente para registrar los datos de EPSAS</li>
                        <li>El cambio de número de factura solo afecta el texto, no cálculos</li>
                        <li>Es obligatorio registrar un motivo para cualquier cambio</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
