<div>
    <div class="mb-6">
        <a href="{{ route('expensas.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a expensas
        </a>
    </div>

    @if(!$propietario || !$periodo)
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="text-red-800">No se encontró la información solicitada.</div>
        </div>
    @else
        <!-- Encabezado -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Boleta de Expensas</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ $periodo->nombre_periodo }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($expensas->isNotEmpty())
                        <a href="{{ route('expensas.propietario.pdf', [$periodo->id, $propietario->id]) }}"
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Ver PDF
                        </a>
                    @endif
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $this->getEstadoColor($estado) }}">
                        {{ $this->getEstadoTexto($estado) }}
                    </span>
                </div>
            </div>

            <!-- Información del propietario -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-500">Propietario</div>
                    <div class="text-lg font-bold text-gray-900">{{ $propietario->nombre }} {{ $propietario->apellido }}</div>
                    <div class="text-sm text-gray-600 mt-1">CI: {{ $propietario->ci }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-500">Unidades</div>
                    <div class="text-lg font-bold text-gray-900">{{ $propietario->unidades->count() }}</div>
                    @if($propietario->unidades->count() > 0)
                        <div class="text-sm text-gray-600 mt-1">
                            {{ $propietario->unidades->pluck('nombre_completo')->implode(', ') }}
                        </div>
                    @endif
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-500">Estado del Período</div>
                    <div class="text-lg font-bold text-gray-900">{{ $periodo->estado_label }}</div>
                    @if($periodo->estado === 'abierto' && $expensas->isEmpty())
                        <div class="mt-2">
                            <button wire:click="generarExpensas" class="text-sm bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700">
                                Generar Expensas
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabla de unidades con sus expensas -->
        @if($expensas->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Detalle por Unidad</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidad</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Metros²</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Factor</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Expensa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agua</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($expensas as $expensa)
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $expensa->unidad->nombre_completo }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ $expensa->unidad->metros_cuadrados }} m²
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ number_format($expensa->unidad->factor_prorrateo, 8) }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                        {{ number_format($expensa->monto_expensa, 2) }} Bs
                                    </td>
                                    <td class="px-4 py-4 text-sm">
                                        @if($expensa->unidad->tiene_medidor)
                                            @php
                                                $lectura = $this->getLecturaAgua($expensa->unidad->id);
                                                // Determinar el factor correcto según la categoría de la unidad
                                                $factorAgua = $expensa->unidad->categoria_unidad === \App\Enums\CategoriaUnidad::DOMICILIARIA
                                                    ? $periodo->factor_domiciliario
                                                    : $periodo->factor_comercial;
                                            @endphp
                                            @if($lectura && $expensa->monto_agua > 0)
                                                <div class="bg-blue-50 rounded p-2 text-xs space-y-1">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Lectura anterior:</span>
                                                        <span class="font-medium text-gray-900">{{ number_format($lectura->lectura_anterior, 2) }} m³</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Lectura actual:</span>
                                                        <span class="font-medium text-gray-900">{{ number_format($lectura->lectura_actual, 2) }} m³</span>
                                                    </div>
                                                    <div class="border-t border-blue-200 pt-1 mt-1">
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Consumo total:</span>
                                                            <span class="font-bold text-blue-700">{{ number_format($lectura->consumo_m3, 2) }} m³</span>
                                                        </div>
                                                    </div>
                                                    <div class="border-t border-blue-200 pt-1 mt-1">
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-gray-600">Factor agua ({{ $expensa->unidad->categoria_unidad->getLabel() }}):</span>
                                                            <span class="font-medium text-gray-900">{{ number_format($factorAgua, 4) }} Bs/m³</span>
                                                        </div>
                                                    </div>
                                                    <div class="border-t border-blue-200 pt-1 mt-1 bg-blue-100 -mx-2 -mb-2 px-2 py-1 rounded-b">
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-xs text-gray-700">
                                                                {{ number_format($lectura->consumo_m3, 2) }} m³ × {{ number_format($factorAgua, 4) }} Bs/m³ =
                                                            </span>
                                                            <span class="font-bold text-lg text-blue-700">{{ number_format($expensa->monto_agua, 2) }} Bs</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-gray-400">Sin consumo</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                        {{ number_format($expensa->monto_expensa + $expensa->monto_agua, 2) }} Bs
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen financiero -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Resumen Financiero</h3>

                <div class="space-y-3">
                    <!-- Subtotal expensas -->
                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                        <div>
                            <div class="text-sm font-medium text-gray-900">Subtotal Expensas Comunes</div>
                            <div class="text-xs text-gray-500">Prorrateo por metros cuadrados y factor</div>
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ number_format($totales['subtotal_expensas'], 2) }} Bs</div>
                    </div>

                    <!-- Subtotal agua -->
                    @if($totales['subtotal_agua'] > 0)
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <div>
                                <div class="text-sm font-medium text-gray-900">Subtotal Agua</div>
                                <div class="text-xs text-gray-500">Consumo de medidores</div>
                            </div>
                            <div class="text-sm font-medium text-gray-900">{{ number_format($totales['subtotal_agua'], 2) }} Bs</div>
                        </div>
                    @endif

                    <!-- Deuda anterior -->
                    @if($totales['deuda_anterior'] > 0)
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <div>
                                <div class="text-sm font-medium text-red-600">Deuda Anterior</div>
                                <div class="text-xs text-gray-500">Meses impagos de períodos anteriores</div>
                            </div>
                            <div class="text-sm font-medium text-red-600">+{{ number_format($totales['deuda_anterior'], 2) }} Bs</div>
                        </div>
                    @endif

                    <!-- Total a pagar -->
                    <div class="flex justify-between items-center py-4 bg-indigo-50 rounded-lg px-4 mt-4">
                        <div class="text-lg font-bold text-gray-900">Total a Pagar</div>
                        <div class="text-2xl font-bold text-indigo-600">{{ number_format($totales['total_a_pagar'], 2) }} Bs</div>
                    </div>

                    <!-- Estado de pagos -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">Total Expensa</div>
                            <div class="text-xl font-bold text-gray-900">{{ number_format($totales['total_a_pagar'], 2) }} Bs</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm text-green-600">Pagado</div>
                            <div class="text-xl font-bold text-green-700">{{ number_format($totales['total_pagado'], 2) }} Bs</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4">
                            <div class="text-sm text-red-600">Pendiente</div>
                            <div class="text-xl font-bold text-red-700">{{ number_format($totales['total_pendiente'], 2) }} Bs</div>
                        </div>
                    </div>

                    <!-- Saldo a favor -->
                    @if($totales['saldo_a_favor'] > 0)
                        <div class="flex justify-between items-center py-3 bg-green-50 rounded-lg px-4 mt-4">
                            <div class="text-sm font-medium text-green-700">Saldo a Favor</div>
                            <div class="text-xl font-bold text-green-700">{{ number_format($totales['saldo_a_favor'], 2) }} Bs</div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <!-- Mensaje cuando no hay expensas -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay expensas calculadas</h3>
                    <p class="mt-1 text-sm text-gray-500">Las expensas para este propietario aún no han sido generadas.</p>
                    @if($periodo->estado === 'abierto')
                        <div class="mt-6">
                            <button wire:click="generarExpensas" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Generar Expensas
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
