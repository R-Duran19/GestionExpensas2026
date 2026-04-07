<div>
    <div class="bg-white rounded-lg shadow">
        <div class="bg-indigo-600 text-white px-6 py-4 rounded-t-lg">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold">
                    <i class="fas fa-money-bill-wave mr-2"></i>
                    Registrar Pago de Expensas
                </h1>
                <button wire:click="cancelar"
                        class="text-white hover:bg-indigo-700 px-3 py-2 rounded-lg transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </button>
            </div>
        </div>

        <div class="p-6">
            {{-- Información de la Expensa --}}
            @if($expensaTotal)
                <div class="bg-gray-50 border-l-4 border-indigo-500 p-4 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-indigo-500 text-xl"></i>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-lg font-medium text-gray-900">
                                Información del Propietario y Período
                            </h3>
                            <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-600">Propietario:</span>
                                    <span class="ml-2 text-gray-900">{{ $propietario->nombre }} {{ $propietario->apellido }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600">Período:</span>
                                    <span class="ml-2 text-gray-900">{{ $periodo->nombre_periodo }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600">Estado:</span>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->estadoColor }}-100 text-{{ $this->estadoColor }}-800">
                                        {{ $this->estadoTexto }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600">Total Expensa:</span>
                                    <span class="ml-2 text-gray-900 font-semibold">{{ number_format($this->montoTotal, 2, ',', '.') }} Bs</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600">Pagado:</span>
                                    <span class="ml-2 text-green-600 font-semibold">{{ number_format($this->montoPagado, 2, ',', '.') }} Bs</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600">Pendiente:</span>
                                    <span class="ml-2 text-red-600 font-semibold">{{ number_format($this->montoPendiente, 2, ',', '.') }} Bs</span>
                                </div>
                                @if($expensaTotal->saldo_a_favor > 0)
                                    <div class="md:col-span-2">
                                        <span class="font-medium text-gray-600">Saldo a Favor:</span>
                                        <span class="ml-2 text-blue-600 font-semibold">{{ number_format($expensaTotal->saldo_a_favor, 2, ',', '.') }} Bs</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="guardarPago">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Columna izquierda --}}
                    <div class="space-y-4">
                        {{-- Fecha del Pago --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-calendar mr-1"></i>
                                Fecha del Pago <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('fecha_pago') border-red-500 @enderror"
                                   wire:model="fecha_pago">
                            @error('fecha_pago')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Monto del Pago --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-dollar-sign mr-1"></i>
                                Monto del Pago <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       step="0.01"
                                       min="0.01"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('monto_pago') border-red-500 @enderror"
                                       wire:model="monto_pago"
                                       placeholder="0.00">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Bs</span>
                                </div>
                            </div>
                            @error('monto_pago')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @if($this->montoPendiente > 0)
                                <p class="mt-1 text-sm text-gray-500">
                                    Deuda pendiente: <strong>{{ number_format($this->montoPendiente, 2, ',', '.') }} Bs</strong>
                                </p>
                            @endif
                        </div>

                        {{-- Método de Pago --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-credit-card mr-1"></i>
                                Método de Pago <span class="text-red-500">*</span>
                            </label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('metodo_pago') border-red-500 @enderror"
                                    wire:model="metodo_pago">
                                <option value="efectivo">💵 Efectivo</option>
                                <option value="transferencia">🏦 Transferencia/Depósito</option>
                                <option value="qr">📱 QR</option>
                                <option value="otro">📎 Otro</option>
                            </select>
                            @error('metodo_pago')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Referencia Bancaria (condicional) --}}
                        @if($this->requiereReferencia())
                            <div wire:model.live="metodo_pago">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-hashtag mr-1"></i>
                                    Referencia Bancaria <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('referencia_bancaria') border-red-500 @enderror"
                                       wire:model="referencia_bancaria"
                                       placeholder="Últimos 4 dígitos o número de referencia">
                                @error('referencia_bancaria')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">
                                    Ingresa los últimos 4 dígitos de la transacción
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Columna derecha --}}
                    <div class="space-y-4">
                        {{-- Comprobante --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-image mr-1"></i>
                                Comprobante de Pago
                                @if($this->requiereComprobante())
                                    <span class="text-red-500">*</span>
                                @else
                                    <span class="text-gray-400">(opcional)</span>
                                @endif
                            </label>

                            {{-- Advertencia de comprobante requerido --}}
                            @if($this->requiereComprobante())
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-3">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                Para <strong>{{ $this->metodo_pago }}</strong> es requerido adjuntar el comprobante de pago
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Input de archivo con vista previa --}}
                            <div class="rounded-lg p-4 bg-gray-50 border @error('comprobante_foto') border-red-500 @else border-gray-300 @enderror">
                                @if($this->comprobante_foto)
                                    {{-- Mostrar vista previa del archivo seleccionado --}}
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600 mb-2">
                                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                            Archivo seleccionado: <strong>{{ $this->comprobante_foto->getClientOriginalName() }}</strong>
                                        </p>
                                        <img src="{{ $this->comprobante_foto->temporaryUrl() }}"
                                             alt="Vista previa del comprobante"
                                             class="max-h-48 mx-auto rounded-lg border border-gray-300 shadow-sm">
                                        <button type="button"
                                                wire:click="comprobante_foto = null"
                                                class="mt-3 px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition text-sm font-medium">
                                            <i class="fas fa-trash mr-1"></i>
                                            Eliminar comprobante
                                        </button>
                                    </div>
                                @else
                                    {{-- Input para subir nuevo archivo --}}
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="mt-2">
                                            <label for="comprobante-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                                <span>Seleccionar archivo</span>
                                                <input id="comprobante-upload"
                                                       type="file"
                                                       class="sr-only"
                                                       wire:model="comprobante_foto"
                                                       accept="image/*">
                                            </label>
                                            <p class="text-xs text-gray-500 mt-1">
                                                PNG, JPG, GIF hasta 10MB
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @error('comprobante_foto')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @endif
                        </div>

                        {{-- Observaciones --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-sticky-note mr-1"></i>
                                Observaciones
                            </label>
                            <textarea
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('observaciones') border-red-500 @enderror"
                                wire:model="observaciones"
                                rows="4"
                                placeholder="Notas adicionales sobre el pago..."></textarea>
                            @error('observaciones')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Botones de Acción --}}
                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                    <button type="button"
                            wire:click="cancelar"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancelar
                    </button>
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        @if($procesando)
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Procesando...
                        @else
                            <i class="fas fa-save"></i>
                            Registrar Pago
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Script para alertas --}}
    <script>
        @this.on('alerta', (data) => {
            if (data.tipo === 'success') {
                alert('✅ ' + data.titulo + '\n\n' + data.mensaje);
            } else if (data.tipo === 'error') {
                alert('❌ ' + data.titulo + '\n\n' + data.mensaje);
            }
        });
    </script>
</div>
