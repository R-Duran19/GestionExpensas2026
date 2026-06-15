<div>
    <!-- Header con título y botones de exportación -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte General de Expensas</h1>
            <p class="text-sm text-gray-500 mt-1">
                Vista {{ $vista === 'resumida' ? 'Resumida' : 'Detallada' }}
                @if($agrupacion === 'periodo')
                    - Por Período
                @else
                    - Acumulado
                @endif
            </p>
        </div>

        <div class="flex gap-2">
            <button wire:click="exportarExcel"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Excel
            </button>

            <button wire:click="exportarPDF"
                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                PDF
            </button>
        </div>
    </div>

    {{-- Tarjetas de totales --}}
    @if($this->totalesGenerales)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Generado -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Generado</dt>
                                <dd class="text-lg font-semibold text-gray-900">Bs.{{ $this->totalesGenerales['generado'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Cobrado -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Cobrado</dt>
                                <dd class="text-lg font-semibold text-gray-900">Bs.{{ $this->totalesGenerales['cobrado'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Deuda -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Deuda</dt>
                                <dd class="text-lg font-semibold text-gray-900">Bs.{{ $this->totalesGenerales['deuda'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- % Cobranza -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">% Cobranza</dt>
                                <dd class="text-lg font-semibold text-gray-900">{{ $this->totalesGenerales['porcentaje_cobranza'] }}%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Panel de filtros --}}
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Filtros del Reporte</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Tipo de Unidad -->
                <div>
                    <label for="filtroTipoUnidad" class="block text-sm font-medium text-gray-700">Tipo de Unidad</label>
                    <select wire:model.live="filtroTipoUnidad" id="filtroTipoUnidad"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        @foreach($this->tiposUnidad as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Período -->
                <div>
                    <label for="filtroPeriodo" class="block text-sm font-medium text-gray-700">Período</label>
                    <select wire:model.live="filtroPeriodo" id="filtroPeriodo"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="todos">Todos los períodos</option>
                        @foreach($this->periodos as $periodo)
                            <option value="{{ $periodo['id'] }}">{{ $periodo['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Unidad -->
                <div>
                    <label for="filtroUnidad" class="block text-sm font-medium text-gray-700">Unidad</label>
                    <select wire:model.live="filtroUnidad" id="filtroUnidad"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="todas">Todas las unidades</option>
                        @foreach($this->unidades as $unidad)
                            <option value="{{ $unidad['id'] }}">{{ $unidad['codigo'] }} ({{ $unidad['tipo'] }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="p-6 bg-gray-50">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <!-- Toggle Vista -->
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">Vista:</span>
                    <div class="inline-flex rounded-md shadow-sm" role="group">
                        <button wire:click="setVista('resumida')"
                                class="{{ $vista === 'resumida' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }} px-4 py-2 text-sm font-medium rounded-l-lg border border-gray-300 focus:z-10 focus:ring-2 focus:ring-indigo-500">
                            Resumida
                        </button>
                        <button wire:click="setVista('detallada')"
                                class="{{ $vista === 'detallada' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }} px-4 py-2 text-sm font-medium rounded-r-lg border border-t border-b border-r border-gray-300 focus:z-10 focus:ring-2 focus:ring-indigo-500">
                            Detallada
                        </button>
                    </div>
                </div>

                <!-- Toggle Agrupación -->
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">Agrupación:</span>
                    <div class="inline-flex rounded-md shadow-sm" role="group">
                        <button wire:click="setAgrupacion('periodo')"
                                class="{{ $agrupacion === 'periodo' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }} px-4 py-2 text-sm font-medium rounded-l-lg border border-gray-300 focus:z-10 focus:ring-2 focus:ring-indigo-500">
                            Por Período
                        </button>
                        <button wire:click="setAgrupacion('acumulado')"
                                class="{{ $agrupacion === 'acumulado' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }} px-4 py-2 text-sm font-medium rounded-r-lg border border-t border-b border-r border-gray-300 focus:z-10 focus:ring-2 focus:ring-indigo-500">
                            Acumulado
                        </button>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex gap-2">
                    <button wire:click="generarReporte"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Generar
                    </button>

                    <button wire:click="limpiarFiltros"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de resultados --}}
    <div class="bg-white shadow rounded-lg">
        @if($cargando)
            <div class="p-12 text-center">
                <svg class="animate-spin h-12 w-12 text-indigo-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-600">Generando reporte...</p>
            </div>
        @elseif(empty($this->vistaActual))
            <div class="p-12 text-center">
                <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay datos disponibles</h3>
                <p class="text-gray-600">Intenta cambiar los filtros o selecciona diferentes opciones.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                @if($vista === 'resumida')
                    {{-- Vista Resumida --}}
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tipo Unidad
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Período
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Unidades
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Generado
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cobrado
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Deuda
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    % Cobranza
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->vistaActual as $fila)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $fila['tipo_unidad'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $fila['periodo'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ $fila['unidades'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Bs.{{ number_format($fila['generado'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium text-right">
                                        Bs.{{ number_format($fila['cobrado'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium text-right">
                                        Bs.{{ number_format($fila['deuda'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($fila['porcentaje_cobranza'], 1) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        {{-- Footer con totales de columnas --}}
                        @if($this->totalesColumnas)
                        <tfoot class="bg-blue-50 border-t-2 border-blue-300">
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900">
                                    Σ TOTAL
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                    Todos
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                    {{ number_format($this->totalesColumnas['unidades'], 0) }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                    Bs.{{ number_format($this->totalesColumnas['generado'], 2) }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-green-600 text-right font-bold">
                                    Bs.{{ number_format($this->totalesColumnas['cobrado'], 2) }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-red-600 text-right font-bold">
                                    Bs.{{ number_format($this->totalesColumnas['deuda'], 2) }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                    {{ number_format(($this->totalesColumnas['cobrado'] / $this->totalesColumnas['generado']) * 100, 1) }}%
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                @else
                    {{-- Vista Detallada --}}
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Unidad
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tipo
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Propietario
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Período
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Generado
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cobrado
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Deuda
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    % Cobranza
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->vistaActual as $fila)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $fila['unidad'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $fila['tipo'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $fila['propietario'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $fila['periodo'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Bs.{{ number_format($fila['generado'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium text-right">
                                        Bs.{{ number_format($fila['cobrado'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium text-right">
                                        Bs.{{ number_format($fila['deuda'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($fila['estado'] === 'Pagado')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Pagado
                                            </span>
                                        @elseif($fila['estado'] === 'Parcial')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Parcial
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Pendiente
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($fila['porcentaje_cobranza'], 1) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        {{-- Footer con totales de columnas --}}
                        @if($this->totalesColumnas)
                        <tfoot class="bg-blue-50 border-t-2 border-blue-300">
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900">
                                    Σ TOTAL
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                    Todos
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                    Todos
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                    Todos
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                    Bs.{{ number_format($this->totalesColumnas['generado'], 2) }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-green-600 text-right font-bold">
                                    Bs.{{ number_format($this->totalesColumnas['cobrado'], 2) }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500 text-center">
                                    —
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                    {{ number_format(($this->totalesColumnas['cobrado'] / $this->totalesColumnas['generado']) * 100, 1) }}%
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                @endif
            </div>

            {{-- Resumen de registros --}}
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <p class="text-sm text-gray-700">
                    Mostrando <span class="font-medium">{{ count($this->vistaActual) }}</span> registros
                </p>
            </div>
        @endif
    </div>

    {{-- Script para notificaciones --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', ({ message, type }) => {
                // Aquí puedes agregar tu sistema de notificaciones
                // Por ahora, usamos alert simple
                alert(message);
            });
        });
    </script>
</div>