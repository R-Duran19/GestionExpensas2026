<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-4 sm:mb-6">
        <a href="{{ route('periodos.show', $this->periodoId) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al período
        </a>
    </div>

    <!-- Encabezado con Progreso -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="mb-4 sm:mb-0">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Lecturas de Medidor</h2>
                <p class="text-gray-600 mt-1 text-sm">
                    Período: <strong>{{ $this->periodo->nombre_periodo }}</strong>
                </p>
            </div>
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                <a href="{{ route('periodos.lecturas-masivas', $this->periodoId) }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Lecturas Masivas
                </a>
                <a href="{{ route('lecturas.individual', $this->periodoId) }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Individual
                </a>
                <a href="{{ route('lecturas.compartida', $this->periodoId) }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Compartida
                </a>
            </div>
        </div>

        <!-- Barra de Progreso -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">
                    Progreso de Registro
                </span>
                <span class="text-sm font-medium text-gray-700">
                    {{ $this->progreso['total_registradas'] }}/{{ $this->progreso['total_unidades'] }}
                    ({{ $this->progreso['porcentaje'] }}%)
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="h-3 rounded-full transition-all duration-300
                    {{ $this->progreso['completo'] ? 'bg-green-600' : 'bg-blue-600' }}"
                     style="width: {{ $this->progreso['porcentaje'] }}%">
                </div>
            </div>
            @if(!$this->progreso['completo'])
                <p class="text-sm text-amber-600 mt-2">
                    ⚠️ Faltan {{ $this->progreso['total_pendientes'] }} lectura(s) por registrar
                </p>
            @else
                <p class="text-sm text-green-600 mt-2">
                    ✅ Todas las lecturas están registradas
                </p>
            @endif
        </div>

        <!-- Filtros -->
        <div class="flex space-x-4">
            <select wire:model.live="filtroTipo"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                <option value="">Todos los tipos</option>
                <option value="individual">Individuales</option>
                <option value="compartido">Compartidos</option>
            </select>
        </div>
    </div>

    <!-- Lista de Lecturas -->
    @if($this->lecturas->count() > 0)
        <div class="space-y-4">
            @foreach($this->lecturas as $lectura)
                @if(isset($lectura['tipo']) && $lectura['tipo'] === 'compartido' && isset($lectura['unidades']))
                    <!-- Lectura Compartida Agrupada -->
                    <div class="bg-white rounded-lg shadow-sm border border-purple-200 overflow-hidden">
                        <!-- Header del Grupo -->
                        <div class="bg-purple-50 px-6 py-4 border-b border-purple-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-lg flex items-center justify-center bg-purple-600">
                                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-purple-900">{{ $lectura['grupo_nombre'] }}</h3>
                                        <p class="text-sm text-purple-700">{{ count($lectura['unidades']) }} unidades con medidor compartido</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-600 text-white">
                                    Compartido
                                </span>
                            </div>
                        </div>

                        <!-- Detalles del Grupo -->
                        <div class="p-6">
                            <!-- Resumen de Consumo del Grupo -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-sm text-gray-600 mb-1">Lectura Anterior</p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ number_format($lectura['lectura_anterior'], 2) }} M³
                                    </p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-sm text-gray-600 mb-1">Lectura Actual</p>
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ number_format($lectura['lectura_actual'], 2) }} M³
                                    </p>
                                </div>
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <p class="text-sm text-blue-700 mb-1">Consumo Total</p>
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ number_format($lectura['consumo_total'], 2) }} M³
                                    </p>
                                </div>
                            </div>

                            <!-- Distribución por Unidad -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Distribución del consumo entre unidades
                                </h4>
                                <div class="bg-gray-50 rounded-lg overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unidad</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Propietario</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Consumo Asignado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($lectura['unidades'] as $unidad)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-3">
                                                        <div class="text-sm font-medium text-gray-900">{{ $unidad['nombre_unidad'] }}</div>
                                                        <div class="text-xs text-gray-500">{{ $unidad['codigo'] }}</div>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-gray-600">
                                                        {{ $unidad['propietario'] ?: '-' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-sm font-semibold text-blue-600 text-right">
                                                        {{ number_format($unidad['consumo_asignado'], 2) }} M³
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-purple-50">
                                            <tr>
                                                <td class="px-4 py-2 text-sm font-medium text-purple-900" colspan="2">
                                                    Total del Grupo
                                                </td>
                                                <td class="px-4 py-2 text-sm font-bold text-purple-600 text-right">
                                                    {{ number_format($lectura['consumo_total'], 2) }} M³
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            @if($lectura['observaciones'])
                                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-sm text-yellow-800">
                                        <span class="font-medium">Observaciones:</span> {{ $lectura['observaciones'] }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- Lectura Individual -->
                    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center space-x-4 mb-4 sm:mb-0">
                                <div class="h-12 w-12 rounded-lg flex items-center justify-center bg-blue-100">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ $lectura['nombre_unidad'] }}</h3>
                                    <p class="text-sm text-gray-500">{{ $lectura['codigo'] }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                Individual
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-4">
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <p class="text-xs text-gray-600 mb-1">Anterior</p>
                                <p class="text-lg font-bold text-gray-900">{{ number_format($lectura['lectura_anterior'], 2) }} M³</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <p class="text-xs text-gray-600 mb-1">Actual</p>
                                <p class="text-lg font-bold text-blue-600">{{ number_format($lectura['lectura_actual'], 2) }} M³</p>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                <p class="text-xs text-blue-700 mb-1">Consumo</p>
                                <p class="text-lg font-bold text-blue-600">{{ number_format($lectura['consumo_m3'], 2) }} M³</p>
                            </div>
                        </div>

                        @if($lectura['propietario'])
                            <div class="mt-3 text-sm text-gray-600">
                                <span class="font-medium">Propietario:</span> {{ $lectura['propietario'] }}
                            </div>
                        @endif

                        @if($lectura['observaciones'])
                            <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-sm text-yellow-800">
                                    <span class="font-medium">Observaciones:</span> {{ $lectura['observaciones'] }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay lecturas registradas</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza registrando las lecturas de medidor.</p>
            <div class="mt-6 flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                <a href="{{ route('periodos.lecturas-masivas', $this->periodoId) }}"
                   class="inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                    Ir a Lecturas Masivas
                </a>
                <a href="{{ route('lecturas.individual', $this->periodoId) }}"
                   class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                    Registrar Individual
                </a>
            </div>
        </div>
    @endif
</div>
