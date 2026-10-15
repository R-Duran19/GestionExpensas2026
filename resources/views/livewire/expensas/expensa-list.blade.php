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

        <!-- Tabs de navegación -->
        @if($this->periodo)
            <div class="mt-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        wire:click="setTab('unidades')"
                        class="{{ $tabActivo === 'unidades' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}
                               whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    >
                        <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Por Unidades
                    </button>
                    <button
                        wire:click="setTab('propietarios')"
                        class="{{ $tabActivo === 'propietarios' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}
                               whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    >
                        <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Por Propietarios
                    </button>
                </nav>
            </div>
        @endif

        @if($this->periodo && $tabActivo === 'unidades')
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
                            x-data
                            @click="Swal.fire({
                                title: '¿Recalcular expensas?',
                                text: 'Se eliminarán las expensas actuales del período y se generarán nuevamente.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, recalcular',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#4F46E5',
                                cancelButtonColor: '#6b7280'
                            }).then(r => { if (r.isConfirmed) $wire.call('recalcularExpensas') })"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Recalcular
                        </button>

                        <!-- Boleta Manual -->
                        <a
                            href="{{ route('expensas.boleta.manual') }}"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            <svg class="-ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Boleta Manual
                        </a>
                    @endif
                </div>

                <div class="flex gap-2">
                    @if($this->periodo->estado === 'abierto')
                        <button
                            x-data
                            @click="Swal.fire({
                                title: '¿Cerrar período?',
                                text: 'Una vez cerrado, no se podrán modificar los montos de emisión.',
                                input: 'textarea',
                                inputPlaceholder: 'Motivo del cierre (opcional)...',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, cerrar',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#dc2626',
                                cancelButtonColor: '#6b7280'
                            }).then(r => { if (r.isConfirmed) $wire.call('cerrarPeriodo', (r.value || '').trim()) })"
                            class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            <svg class="-ml-1 mr-2 h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Cerrar Período
                        </button>
                    @else
                        <button
                            x-data
                            @click="Swal.fire({
                                title: '¿Reabrir período?',
                                text: 'Las expensas volverán a ser modificables.',
                                input: 'textarea',
                                inputPlaceholder: 'Motivo de la reapertura (opcional)...',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, reabrir',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#d97706',
                                cancelButtonColor: '#6b7280'
                            }).then(r => { if (r.isConfirmed) $wire.call('reabrirPeriodo', (r.value || '').trim()) })"
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

    @if($this->periodo && $tabActivo === 'unidades')
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
                        placeholder="Buscar por código de unidad, propietario o inquilino..."
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
                                Responsable
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
                    <tbody class="divide-y divide-gray-200">
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

                            <tr wire:key="unidad-{{ $unidad->id }}" class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-100">
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
                                    @php
                                        $inquilino = $unidad->inquilinoActual;
                                        $propietario = $unidad->propietarioActual;
                                        $responsable = $inquilino ?: $propietario;
                                    @endphp
                                    @if($responsable)
                                        <div class="flex items-center gap-2">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $responsable->nombre_completo }}
                                            </div>
                                            @if($inquilino)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    Inquilino
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $responsable->ci ?: 'Sin CI' }}
                                        </div>
                                        @if($inquilino && $propietario)
                                            <div class="text-xs text-gray-400 mt-1">
                                                Propietario: {{ $propietario->nombre_completo }}
                                            </div>
                                        @endif
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
                                                href="{{ route('expensas.unidad.detalle', ['unidad' => $unidad->id, 'periodoId' => $this->periodoId]) }}"
                                                class="text-blue-600 hover:text-blue-900"
                                                title="Ver detalle de expensa"
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                        @endif

                                        <!-- Ver PDF -->
                                        @php
                                            $responsablePdfTipo = $unidad->inquilinoActual ? 'inquilino' : 'propietario';
                                            $responsablePdfId = $unidad->inquilinoActual?->id ?? $unidad->propietarioActual?->id;
                                        @endphp
                                        @if($responsablePdfId && $expensa)
                                            <a
                                                href="{{ route('expensas.pdf.mostrar', [$this->periodo->id, $responsablePdfTipo, $responsablePdfId]) }}"
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
    @elseif($this->periodo && $tabActivo === 'propietarios')
        <!-- Tabla de Propietarios -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Propietarios</h2>
                <p class="text-sm text-gray-500 mt-1">Período: {{ $this->periodo->nombre_periodo }}</p>
            </div>

            <!-- Buscador -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <input
                    type="text"
                    wire:model.live="search"
                    placeholder="Buscar por nombre o CI del propietario..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Propietario
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Unidades
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Emisión
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pagado
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pendiente
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->propietariosConExpensas as $propietario)
                            @php
                                $estadoClass = 'bg-gray-100 text-gray-800';
                                $estadoTexto = 'Sin expensas';

                                if ($propietario->total_emision > 0) {
                                    if ($propietario->esta_pagado) {
                                        $estadoClass = 'bg-green-100 text-green-800';
                                        $estadoTexto = 'Pagado';
                                    } elseif ($propietario->tiene_deuda) {
                                        if ($propietario->porcentaje_pagado > 0) {
                                            $estadoClass = 'bg-yellow-100 text-yellow-800';
                                            $estadoTexto = 'Parcial';
                                        } else {
                                            $estadoClass = 'bg-red-100 text-red-800';
                                            $estadoTexto = 'Pendiente';
                                        }
                                    }
                                }
                            @endphp

                            <tr wire:key="prop-{{ $propietario->id }}" class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-100">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium text-sm">
                                            {{ substr($propietario->nombre, 0, 1) }}{{ substr($propietario->apellido ?? '', 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center gap-2">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $propietario->nombre_completo }}
                                                </div>
                                                @if($propietario->tipo === 'inquilino')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                        Inquilino
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                        Propietario
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                CI: {{ $propietario->ci ?: 'Sin CI' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        {{ $propietario->total_unidades }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $propietario->unidades_pagadas }} pagadas / {{ $propietario->unidades_con_deuda }} con deuda
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                    @if($propietario->total_emision > 0)
                                        {{ number_format($propietario->total_emision, 2, ',', '.') }} Bs
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    @if($propietario->total_pagado > 0)
                                        <span class="text-green-600 font-medium">{{ number_format($propietario->total_pagado, 2, ',', '.') }} Bs</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if($propietario->total_pendiente > 0)
                                        <span class="text-red-600">{{ number_format($propietario->total_pendiente, 2, ',', '.') }} Bs</span>
                                    @else
                                        <span class="text-green-600">-</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $estadoClass }}">
                                        {{ $estadoTexto }}
                                    </span>
                                    @if($propietario->total_emision > 0 && $propietario->porcentaje_pagado > 0)
                                        <div class="mt-1 text-xs text-gray-500">{{ number_format($propietario->porcentaje_pagado, 0) }}% pagado</div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- Ver detalle (todas las unidades) -->
                                        <a
                                            href="{{ route('expensas.propietario.detalle', [$this->periodo->id, $propietario->id]) }}"
                                            class="text-blue-600 hover:text-blue-900"
                                            title="Ver detalle de todas las unidades"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        <!-- Ver PDF consolidado -->
                                        <a
                                            href="{{ route('expensas.pdf.mostrar', [$this->periodo->id, $propietario->tipo ?? 'propietario', $propietario->id]) }}"
                                            class="text-red-600 hover:text-red-900"
                                            title="Ver PDF de boleta consolidada"
                                            target="_blank"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        </a>

                                        <!-- Regenerar expensas (todas las unidades) -->
                                        @if($this->periodo->estado === 'abierto')
                                            <button
                                                wire:click="regenerarExpensasPropietario({{ $propietario->id }}, '{{ $propietario->tipo ?? 'propietario' }}')"
                                                wire:loading.attr="disabled"
                                                class="text-indigo-600 hover:text-indigo-900 disabled:opacity-50"
                                                title="Regenerar expensas de todas las unidades"
                                            >
                                                @if($propietarioIdRegenerando == $propietario->id && $generando)
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
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
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
