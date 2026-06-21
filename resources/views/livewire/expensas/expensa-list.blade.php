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
                    <div class="text-sm text-blue-600">Unidades</div>
                    <div class="text-2xl font-bold text-blue-700">{{ $this->estadisticas['total_unidades'] }}</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm text-green-600">Pagadas</div>
                    <div class="text-2xl font-bold text-green-700">{{ $this->estadisticas['pagadas'] }}</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="text-sm text-yellow-600">Con Deuda</div>
                    <div class="text-2xl font-bold text-yellow-700">{{ $this->estadisticas['con_deuda'] }}</div>
                </div>
                <div class="bg-indigo-50 rounded-lg p-4">
                    <div class="text-sm text-indigo-600">Calculadas</div>
                    <div class="text-2xl font-bold text-indigo-700">{{ $this->estadisticas['expensas_calculadas'] }}</div>
                </div>
            </div>

            <!-- Totales -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600">Total Emisión</div>
                    <div class="text-xl font-bold text-gray-900">{{ number_format($this->estadisticas['monto_total_emision'], 2, ',', '.') }} Bs</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm text-green-600">Total Pagado</div>
                    <div class="text-xl font-bold text-green-700">{{ number_format($this->estadisticas['monto_pagado'], 2, ',', '.') }} Bs</div>
                </div>
                <div class="bg-red-50 rounded-lg p-4">
                    <div class="text-sm text-red-600">Total Deuda</div>
                    <div class="text-xl font-bold text-red-700">{{ number_format($this->estadisticas['monto_deuda_total'], 2, ',', '.') }} Bs</div>
                </div>
            </div>

            <!-- Saldo a Favor -->
            @if($this->estadisticas['saldo_a_favor'] > 0)
                <div class="mt-4 bg-blue-50 rounded-lg p-4">
                    <div class="text-sm text-blue-600">Total Saldos a Favor Disponibles</div>
                    <div class="text-xl font-bold text-blue-700">{{ number_format($this->estadisticas['saldo_a_favor'], 2, ',', '.') }} Bs</div>
                </div>
            @endif

            <!-- Botones de Acción -->
            <div class="mt-4 flex flex-wrap gap-2 justify-between">
                <div class="flex gap-2">
                    @if($this->periodo->estado === 'abierto')
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
                                Generar Todas
                            @endif
                        </button>

                        <button
                            wire:click="recalcularExpensas"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Recalcular
                        </button>
                    @endif
                </div>

                <div class="flex gap-2">
                    @if($this->periodo->estado === 'abierto')
                        <button
                            wire:click="cerrarPeriodo"
                            class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            <svg class="-ml-1 mr-2 h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Cerrar Período
                        </button>
                    @else
                        <button
                            wire:click="reabrirPeriodo"
                            class="inline-flex items-center px-4 py-2 border border-yellow-300 rounded-md shadow-sm text-sm font-medium text-yellow-700 bg-white hover:bg-yellow-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                        >
                            <svg class="-ml-1 mr-2 h-4 w-4 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                            Reabrir Período
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if($this->periodo)
        <!-- Tabla de Unidades -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Unidades</h2>
                <p class="text-sm text-gray-500 mt-1">Período: {{ $this->periodo->nombre_periodo }}</p>
            </div>

            <!-- Buscador y Filtros -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-col md:flex-row gap-4">
                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Buscar por código de unidad o propietario..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    >

                    <select
                        wire:model.live="filtroTipo"
                        class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    >
                        <option value="">Todos los tipos</option>
                        <option value="departamento">Departamentos</option>
                        <option value="oficina">Oficinas</option>
                        <option value="local">Locales</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Unidad
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Propietario
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
                        @forelse($this->unidadesConExpensas as $unidad)
                            @php
                                // Obtener expensa de esta unidad para el período actual
                                $expensa = $unidad->expensas->first();

                                // Valores a mostrar
                                $montoExpensa = $expensa ? $expensa->monto_expensa : 0;
                                $montoAgua = $expensa ? $expensa->monto_agua : 0;
                                $montoTotal = $expensa ? $expensa->monto_total : 0;
                                $expensaPendiente = $expensa ? $expensa->expensa_pendiente : 0;
                                $aguaPendiente = $expensa ? $expensa->agua_pendiente : 0;
                                $totalPendiente = $expensaPendiente + $aguaPendiente;

                                // Determinar estado
                                $estadoClass = 'bg-gray-100 text-gray-800';
                                $estadoTexto = 'Sin calcular';

                                if ($expensa) {
                                    if ($totalPendiente <= 0.01) {
                                        $estadoClass = 'bg-green-100 text-green-800';
                                        $estadoTexto = 'Pagado';
                                    } elseif ($totalPendiente < $montoTotal) {
                                        $estadoClass = 'bg-yellow-100 text-yellow-800';
                                        $estadoTexto = 'Parcial';
                                    } else {
                                        $estadoClass = 'bg-red-100 text-red-800';
                                        $estadoTexto = 'Pendiente';
                                    }
                                }
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

                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($unidad->propietarioActual)
                                        <div class="text-sm text-gray-900">
                                            {{ $unidad->propietarioActual->nombre_completo }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $unidad->propietarioActual->ci }}
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500">Sin propietario</div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $estadoClass }}">
                                        {{ $estadoTexto }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                    @if($expensa && $montoExpensa > 0)
                                        {{ number_format($montoExpensa, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                    @if($expensa && $montoAgua > 0)
                                        {{ number_format($montoAgua, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                    @if($expensa && $montoTotal > 0)
                                        {{ number_format($montoTotal, 2) }} Bs
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if($totalPendiente > 0)
                                        <span class="text-red-600">{{ number_format($totalPendiente, 2) }} Bs</span>
                                    @else
                                        <span class="text-green-600">-</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- Ver detalle -->
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

                                        <!-- Ver PDF -->
                                        @if($unidad->propietarioActual && $expensa)
                                            <a
                                                href="{{ route('expensas.pdf.mostrar', [$this->periodo->id, $unidad->propietarioActual->id]) }}"
                                                class="text-red-600 hover:text-red-900"
                                                title="Ver PDF de boleta"
                                                target="_blank"
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
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
                                        @if($this->periodo->estado === 'abierto')
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
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron unidades</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        @if($this->search || $this->filtroTipo)
                                            No hay resultados para tu búsqueda.
                                        @else
                                            No hay unidades activas registradas.
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
                {{ $this->unidadesConExpensas->links() }}
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
