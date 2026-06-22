<div>
    <div class="mb-6">
        <a href="{{ route('expensas.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a expensas
        </a>
    </div>

    <!-- Información del propietario y período -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-16 w-16 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium text-xl">
                    {{ substr($propietario->nombre, 0, 1) }}{{ substr($propietario->apellido ?? '', 0, 1) }}
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $propietario->nombre_completo }}</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        CI: {{ $propietario->ci ?: 'Sin CI' }}
                        @if($propietario->telefono)
                            | Tel: {{ $propietario->telefono }}
                        @endif
                    </p>
                    @if($propietario->email)
                        <p class="text-sm text-gray-500">{{ $propietario->email }}</p>
                    @endif
                </div>
            </div>
            <div class="mt-4 md:mt-0">
                <!-- Selector de Período -->
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Período</div>
                        <select
                            wire:model.live="periodoId"
                            wire:change="cambiarPeriodo($event.target.value)"
                            class="mt-1 block w-full md:w-48 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        >
                            @foreach($periodosDisponibles as $periodoOpt)
                                <option value="{{ $periodoOpt['id'] }}" {{ $periodoId == $periodoOpt['id'] ? 'selected' : '' }}>
                                    {{ $periodoOpt['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-xs text-gray-500 mt-1">{{ $periodo->estado_label }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de totales -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
            <div class="text-sm text-blue-600">Unidades</div>
            <div class="text-2xl font-bold text-blue-700">{{ $totales->total_unidades }}</div>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <div class="text-sm text-green-600">Pagadas</div>
            <div class="text-2xl font-bold text-green-700">{{ $totales->unidades_pagadas }}</div>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4">
            <div class="text-sm text-yellow-600">Con Deuda</div>
            <div class="text-2xl font-bold text-yellow-700">{{ $totales->unidades_con_deuda }}</div>
        </div>
        <div class="bg-indigo-50 rounded-lg p-4">
            <div class="text-sm text-indigo-600">Porcentaje Pagado</div>
            <div class="text-2xl font-bold text-indigo-700">{{ number_format($totales->porcentaje_pagado, 0) }}%</div>
        </div>
    </div>

    <!-- Totales monetarios -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">Total Emisión</div>
            <div class="text-xl font-bold text-gray-900">{{ number_format($totales->total_emision, 2, ',', '.') }} Bs</div>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <div class="text-sm text-green-600">Total Pagado</div>
            <div class="text-xl font-bold text-green-700">{{ number_format($totales->total_pagado, 2, ',', '.') }} Bs</div>
        </div>
        <div class="bg-red-50 rounded-lg p-4">
            <div class="text-sm text-red-600">Total Pendiente</div>
            <div class="text-xl font-bold text-red-700">{{ number_format($totales->total_pendiente, 2, ',', '.') }} Bs</div>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-wrap gap-2 justify-between items-center">
            <div class="text-sm text-gray-600">
                Acciones para todas las unidades del propietario
            </div>
            <div class="flex gap-2">
                @if($periodo->estado === 'abierto')
                    <button
                        wire:click="regenerarTodasExpensas"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        @if($generando)
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Regenerando...
                        @else
                            <svg class="-ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Regenerar Todas
                        @endif
                    </button>
                @endif

                <!-- PDF consolidado -->
                <a
                    href="{{ route('expensas.pdf.mostrar', [$periodo->id, $propietario->id]) }}"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                    <svg class="-ml-1 mr-2 h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Ver PDF Consolidado
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de unidades con sus expensas -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Unidades del Propietario</h2>
            <p class="text-sm text-gray-500 mt-1">Detalle de expensas por unidad</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Unidad
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Expensa
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Agua
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Pendiente
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($unidadesConExpensas as $unidad)
                        @php
                            $expensa = $unidad->expensa;
                            $montoExpensa = $expensa ? $expensa->monto_expensa : 0;
                            $montoAgua = $expensa ? $expensa->monto_agua : 0;
                            $montoTotal = $expensa ? $expensa->monto_total : 0;
                            $expensaPendiente = $expensa ? $expensa->expensa_pendiente : 0;
                            $aguaPendiente = $expensa ? $expensa->agua_pendiente : 0;
                            $totalPendiente = $expensaPendiente + $aguaPendiente;

                            $estadoClase = $this->getEstadoClase($unidad->estado);
                            $estadoTexto = $this->getEstadoTexto($unidad->estado);
                        @endphp

                        <tr wire:key="unidad-{{ $unidad->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium">
                                        {{ $unidad->codigo }}
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $unidad->tipo_unidad->getLabel() }} {{ $unidad->codigo }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $unidad->piso }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $estadoClase }}">
                                    {{ $estadoTexto }}
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                @if($expensa && $montoExpensa > 0)
                                    {{ number_format($montoExpensa, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                @if($expensa && $montoAgua > 0)
                                    {{ number_format($montoAgua, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                @if($expensa && $montoTotal > 0)
                                    {{ number_format($montoTotal, 2, ',', '.') }} Bs
                                @else
                                    -
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if($totalPendiente > 0)
                                    <span class="text-red-600">{{ number_format($totalPendiente, 2, ',', '.') }} Bs</span>
                                @else
                                    <span class="text-green-600">-</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- Ver detalle individual -->
                                    @if($expensa)
                                        <a
                                            href="{{ route('expensas.detalle', $expensa->id) }}"
                                            class="text-blue-600 hover:text-blue-900"
                                            title="Ver detalle de expensa"
                                            target="_blank"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                    @endif

                                    <!-- Pagar -->
                                    @if($totalPendiente > 0.01 || !$expensa)
                                        <a
                                            href="{{ route('expensas.pago', $unidad->id) }}"
                                            class="text-green-600 hover:text-green-900"
                                            title="Registrar pago"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </a>
                                    @endif

                                    <!-- Generar expensa individual -->
                                    @if($periodo->estado === 'abierto')
                                        <button
                                            wire:click="generarExpensaIndividual({{ $unidad->id }})"
                                            wire:loading.attr="disabled"
                                            class="text-indigo-600 hover:text-indigo-900 disabled:opacity-50"
                                            title="Generar expensa"
                                        >
                                            @if($unidadIdGenerando == $unidad->id && $generando)
                                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            @else
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            @endif
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Fila de detalle de agua (expandible) -->
                        @if($unidad->detalle_agua)
                        <tr wire:key="detalle-agua-{{ $unidad->id }}" class="bg-blue-50">
                            <td colspan="7" class="px-6 py-4">
                                <div class="flex items-start">
                                    <svg class="h-5 w-5 text-blue-500 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-blue-900 mb-2">Detalle del cálculo de agua</div>
                                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                                            <div>
                                                <div class="text-gray-500">Tipo medidor</div>
                                                <div class="font-medium text-gray-900">{{ $unidad->detalle_agua->tipo_medidor }}</div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Lectura anterior</div>
                                                <div class="font-medium text-gray-900">{{ number_format($unidad->detalle_agua->lectura_anterior, 2) }} m³</div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Lectura actual</div>
                                                <div class="font-medium text-gray-900">{{ number_format($unidad->detalle_agua->lectura_actual, 2) }} m³</div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Consumo</div>
                                                <div class="font-medium text-blue-700">{{ number_format($unidad->detalle_agua->consumo_m3, 2) }} m³</div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Factor {{ $unidad->detalle_agua->factor_nombre }}</div>
                                                <div class="font-medium text-gray-900">{{ number_format($unidad->detalle_agua->factor_agua, 4) }} Bs/m³</div>
                                            </div>
                                        </div>
                                        <div class="mt-3 pt-3 border-t border-blue-200 flex items-center justify-between">
                                            <div class="text-sm text-gray-600">
                                                <span class="font-medium">Cálculo:</span>
                                                {{ number_format($unidad->detalle_agua->consumo_m3, 2) }} m³
                                                × {{ number_format($unidad->detalle_agua->factor_agua, 4) }} Bs/m³
                                                = <span class="font-bold text-blue-700">{{ number_format($unidad->detalle_agua->monto_calculado, 2) }} Bs</span>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $unidad->detalle_agua->factor_nombre }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Sin unidades</h3>
                                <p class="mt-1 text-sm text-gray-500">El propietario no tiene unidades activas asignadas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Script para alertas -->
    <script>
        @this.on('alerta', (data) => {
            const tipo = data.tipo;
            const titulo = data.titulo;
            const mensaje = data.mensaje;

            alert(`${titulo}: ${mensaje}`);
        });
    </script>
</div>
