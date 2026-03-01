<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('periodos.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            &larr; Volver a períodos
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">Registrar Facturas EPSAS</h2>
            <p class="text-gray-600 mt-1">
                Período: <strong>{{ $periodo->nombre_periodo }}</strong>
            </p>
        </div>

        <form wire:submit="guardarFacturas" class="p-6">
            <div class="space-y-8">
                @foreach($facturas as $key => $factura)
                    <div class="border border-gray-200 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Factura {{ ucfirst(str_replace('_', ' ', $factura['tipo'])) }}
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $factura['categoria'] === 'domiciliaria' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ ucfirst($factura['categoria']) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Número de Factura -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Número de Factura
                                </label>
                                <input type="text"
                                       wire:model="facturas.{{ $key }}.numero_factura"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                @error('facturas.'.$key.'.numero_factura')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Fecha de Factura -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Fecha de Factura
                                </label>
                                <input type="date"
                                       wire:model="facturas.{{ $key }}.fecha_factura"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                @error('facturas.'.$key.'.fecha_factura')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Importe -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Importe (Bs.)
                                </label>
                                <input type="number"
                                       wire:model="facturas.{{ $key }}.importe"
                                       step="0.01"
                                       min="0"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                @error('facturas.'.$key.'.importe')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Consumo M³ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Consumo (M³)
                                </label>
                                <input type="number"
                                       wire:model="facturas.{{ $key }}.consumo_m3"
                                       step="0.01"
                                       min="0"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                @error('facturas.'.$key.'.consumo_m3')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Observaciones -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Observaciones (opcional)
                                </label>
                                <textarea wire:model="facturas.{{ $key }}.observaciones"
                                          rows="2"
                                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Resumen de Factores -->
            @if($mostrarResumen)
                <div class="mt-8 @if($periodo->tiene_factores_calculados) bg-blue-50 border border-blue-200 @else bg-green-50 border border-green-200 @endif rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold @if($periodo->tiene_factores_calculados) text-blue-900 @else text-green-900 @endif">
                            Factores de Agua @if($periodo->tiene_factores_calculados) Guardados @else Calculados @endif
                        </h3>
                        @if($periodo->tiene_factores_calculados)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Guardados en BD
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg p-4 @if($periodo->tiene_factores_calculados) border border-blue-200 @else border border-green-200 @endif">
                            <h4 class="font-semibold text-gray-900">Factor Domiciliario</h4>
                            <div class="mt-2 flex items-baseline">
                                <span class="text-3xl font-bold @if($periodo->tiene_factores_calculados) text-blue-600 @else text-green-600 @endif font-mono">
                                    {{ number_format($factorDomiciliario, 8, '.', '') }}
                                </span>
                                <span class="ml-2 text-gray-600">Bs/M³</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                Para: Departamentos, Parqueos, Bauleras
                            </p>
                            @if($periodo->tiene_factores_calculados)
                                <p class="text-xs text-blue-600 mt-2">
                                    Calculado el {{ $periodo->factores_calculados_at->format('d/m/Y H:i') }}
                                </p>
                            @endif
                        </div>

                        <div class="bg-white rounded-lg p-4 @if($periodo->tiene_factores_calculados) border border-blue-200 @else border border-green-200 @endif">
                            <h4 class="font-semibold text-gray-900">Factor Comercial</h4>
                            <div class="mt-2 flex items-baseline">
                                <span class="text-3xl font-bold @if($periodo->tiene_factores_calculados) text-blue-600 @else text-green-600 @endif font-mono">
                                    {{ number_format($factorComercial, 8, '.', '') }}
                                </span>
                                <span class="ml-2 text-gray-600">Bs/M³</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                Para: Oficinas, Locales
                            </p>
                            @if($periodo->tiene_factores_calculados)
                                <p class="text-xs text-blue-600 mt-2">
                                    Calculado el {{ $periodo->factores_calculados_at->format('d/m/Y H:i') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    @if($periodo->tiene_factores_calculados && $periodo->factoresCalculadosPor)
                        <p class="text-xs text-gray-500 mt-4 text-right">
                            Calculado por: {{ $periodo->factoresCalculadosPor->name }}
                        </p>
                    @endif
                </div>
            @endif

            <!-- Botones -->
            <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
                <button type="button"
                        wire:click="calcularFactores"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                    Calcular Factores
                </button>

                <div class="flex space-x-4">
                    @if($mostrarResumen)
                        <button type="button"
                                wire:click="continuar"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                            Continuar a Lecturas &rarr;
                        </button>
                    @endif
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                        Guardar Facturas
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
