<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">💰 Expensas</h1>
                <p class="text-sm text-gray-600 mt-1">Gestión de expensas por período</p>
            </div>

            <!-- Selector de Período -->
            <div class="mt-4 md:mt-0">
                <select
                    wire:model.live="periodoId"
                    class="block w-full md:w-64 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                    <option value="">-- Seleccionar Período --</option>
                    @foreach($this->periodos as $periodo)
                        <option value="{{ $periodo->id }}">
                            {{ $periodo->nombre_periodo }}
                            ({{ $periodo->estado_label }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($this->periodo)
            <!-- Estadísticas -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-sm text-blue-600">Propietarios</div>
                    <div class="text-2xl font-bold text-blue-700">{{ $this->estadisticas['total_propietarios'] }}</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm text-green-600">Pagadas</div>
                    <div class="text-2xl font-bold text-green-700">{{ $this->estadisticas['pagadas'] }}</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="text-sm text-yellow-600">Parciales</div>
                    <div class="text-2xl font-bold text-yellow-700">{{ $this->estadisticas['parciales'] }}</div>
                </div>
                <div class="bg-red-50 rounded-lg p-4">
                    <div class="text-sm text-red-600">Pendientes</div>
                    <div class="text-2xl font-bold text-red-700">{{ $this->estadisticas['pendientes'] }}</div>
                </div>
            </div>

            <!-- Totales -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600">Total Expensas</div>
                    <div class="text-xl font-bold text-gray-900">{{ number_format($this->estadisticas['monto_total'], 2) }} Bs</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm text-green-600">Total Pagado</div>
                    <div class="text-xl font-bold text-green-700">{{ number_format($this->estadisticas['monto_pagado'], 2) }} Bs</div>
                </div>
                <div class="bg-red-50 rounded-lg p-4">
                    <div class="text-sm text-red-600">Total Pendiente</div>
                    <div class="text-xl font-bold text-red-700">{{ number_format($this->estadisticas['monto_pendiente'], 2) }} Bs</div>
                </div>
            </div>

            <!-- Botón Generar Todas las Expensas -->
            @if($this->periodo->estado === 'abierto')
                <div class="mt-4 flex justify-end">
                    <button
                        wire:click="generarTodasExpensas"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        @if($generando)
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Generando...
                        @else
                            <svg class="-ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Generar Todas las Expensas
                        @endif
                    </button>
                </div>
            @endif
        @endif
    </div>

    @if($this->periodo)
        <!-- Tabla de Propietarios -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Propietarios con Expensas</h2>
                <p class="text-sm text-gray-500 mt-1">Período: {{ $this->periodo->nombre_periodo }}</p>
            </div>

            <!-- Buscador -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <input
                    type="text"
                    wire:model.live="search"
                    placeholder="Buscar propietario por nombre, apellido o CI..."
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Propietario
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                CI
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Unidades
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pagado
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
                        @forelse($this->propietariosConExpensas as $propietario)
                            @php
                                // Calcular totales del propietario
                                $unidadIds = $propietario->unidades->pluck('id');
                                $expensasPropietario = \App\Models\Expensa::where('periodo_id', $this->periodoId)
                                    ->whereIn('unidad_id', $unidadIds)
                                    ->get();

                                $montoTotal = $expensasPropietario->sum('monto_total');
                                $montoPagado = $expensasPropietario->sum('monto_pagado');
                                $montoPendiente = $expensasPropietario->sum('monto_pendiente');

                                // Determinar estado del propietario
                                if ($montoPagado >= $montoTotal && $montoTotal > 0) {
                                    $estado = 'pagado';
                                } elseif ($montoPagado > 0) {
                                    $estado = 'parcial';
                                } elseif ($expensasPropietario->isEmpty()) {
                                    $estado = 'sin_calcular';
                                } else {
                                    $estado = 'pendiente';
                                }
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium">
                                            {{ substr($propietario->nombre, 0, 1) }}{{ substr($propietario->apellido, 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $propietario->nombre }} {{ $propietario->apellido }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $propietario->unidades->count() }} unidad(es)
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $propietario->ci }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm text-gray-900">{{ $propietario->unidades->count() }}</span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($estado !== 'sin_calcular')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->getEstadoColor($estado) }}">
                                            {{ $this->getEstadoTexto($estado) }}
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Sin calcular
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                    @if($montoTotal > 0)
                                        {{ number_format($montoTotal, 2) }} Bs
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600">
                                    @if($montoPagado > 0)
                                        {{ number_format($montoPagado, 2) }} Bs
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600">
                                    @if($montoPendiente > 0)
                                        {{ number_format($montoPendiente, 2) }} Bs
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- Ver detalle -->
                                        <a
                                            href="#"
                                            wire:click="verDetallePropietario({{ $propietario->id }})"
                                            class="text-blue-600 hover:text-blue-900"
                                            title="Ver detalle"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        <!-- Regenerar expensas -->
                                        @if($this->periodo->estado === 'abierto')
                                            <button
                                                wire:click="generarExpensasPropietario({{ $propietario->id }})"
                                                wire:loading.attr="disabled"
                                                class="text-green-600 hover:text-green-900 disabled:opacity-50"
                                                title="Regenerar expensas"
                                            >
                                                @if($generando)
                                                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron propietarios</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        @if($this->search)
                                            No hay resultados para tu búsqueda.
                                        @else
                                            No hay propietarios con unidades activas.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $this->propietariosConExpensas->links() }}
            </div>
        </div>
    @else
        <!-- Mensaje cuando no hay período seleccionado -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay período seleccionado</h3>
            <p class="mt-1 text-sm text-gray-500">Selecciona un período para ver las expensas</p>
        </div>
    @endif

    <!-- Script para alertas -->
    <script>
        @this.on('alerta', (data) => {
            const tipo = data.tipo;
            const titulo = data.titulo;
            const mensaje = data.mensaje;

            // Aquí puedes usar tu librería de notificaciones preferida
            // Por ahora solo mostramos un alert nativo
            alert(`${titulo}: ${mensaje}`);
        });
    </script>
</div>
