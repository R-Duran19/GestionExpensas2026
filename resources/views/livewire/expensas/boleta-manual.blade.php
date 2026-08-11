<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">📄 Boletas para Imprimir</h1>
                <p class="text-sm text-gray-600 mt-1">Generá un PDF consolidado con una boleta por página de las unidades que selecciones</p>
            </div>

            <!-- Volver a Expensas -->
            <div class="mt-4 md:mt-0">
                <a href="{{ route('expensas.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver a Expensas
                </a>
            </div>
        </div>

        <!-- Selector de Período -->
        <div class="mt-6 max-w-md">
            <label class="block text-sm font-medium text-gray-700 mb-2">Período de Expensa</label>
            <select
                wire:model.live="periodoId"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
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
        <!-- Panel de Selección -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                <div>
                    <h2 class="text-lg font-medium text-gray-900">Seleccionar Unidades</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Período: <strong>{{ $this->periodo->nombre_periodo }}</strong> |
                        Seleccionadas: <strong>{{ count($this->unidadesSeleccionadas) }}</strong>
                        @if(count($this->unidadesSeleccionadas) > 0)
                            <button wire:click="limpiarSeleccion" class="ml-2 text-xs text-red-600 hover:text-red-800 underline">limpiar todo</button>
                        @endif
                    </p>
                </div>

                <div class="flex gap-2 mt-4 md:mt-0">
                    <button
                        wire:click="seleccionarTodas"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Marcar página
                    </button>
                    <button
                        wire:click="deseleccionarTodas"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Desmarcar página
                    </button>
                </div>
            </div>

            <!-- Buscador y Filtros -->
            <div class="flex flex-col md:flex-row gap-4 mb-4">
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

            <!-- Botón Generar -->
            <div class="flex justify-end">
                <button
                    wire:click="generarPDFs"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                >
                    <svg wire:loading.remove wire:target="generarPDFs" class="-ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <svg wire:loading wire:target="generarPDFs" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Generar Boletas PDF ({{ count($this->unidadesSeleccionadas) }})
                </button>
            </div>
        </div>

        <!-- Boleta generada (botón único al PDF consolidado) -->
        @if($pdfGenerado)
            <div class="bg-green-50 border border-green-200 rounded-lg shadow p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-medium text-green-900">
                            ✅ PDF listo con {{ $pdfGenerado['cantidad'] }} boleta(s)
                        </h2>
                        <p class="text-sm text-green-700 mt-1">
                            Período: <strong>{{ $pdfGenerado['periodo'] }}</strong>. Una página por unidad.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ $pdfGenerado['url'] }}" target="_blank" rel="noopener"
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Abrir PDF
                        </a>
                        <a href="{{ $pdfGenerado['descargar'] }}"
                           class="inline-flex items-center px-4 py-2 border border-green-300 rounded-md shadow-sm text-sm font-medium text-green-700 bg-white hover:bg-green-50">
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Descargar
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Lista de Unidades -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left w-12">
                                <input
                                    type="checkbox"
                                    wire:click="toggleTodas"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer"
                                >
                            </th>
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
                                Total
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pendiente
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->unidades as $unidad)
                            @php
                                $expensa = $unidad->expensas->first();
                                $montoTotal = $expensa ? $expensa->monto_total : 0;
                                $totalPendiente = $expensa ? ($expensa->expensa_pendiente + $expensa->agua_pendiente) : 0;

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
                                $seleccionada = in_array($unidad->id, $this->unidadesSeleccionadas);
                            @endphp

                            <tr wire:key="unidad-{{ $unidad->id }}" class="{{ $seleccionada ? 'bg-indigo-50' : '' }} hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input
                                        type="checkbox"
                                        wire:model.live="unidadesSeleccionadas"
                                        value="{{ $unidad->id }}"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    >
                                </td>
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
                                        <div class="text-xs text-gray-400">
                                            {{ $responsable->ci ?: 'Sin CI' }}
                                        </div>
                                        @if($inquilino && $propietario)
                                            <div class="text-xs text-gray-400 mt-0.5">
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
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
                {{ $this->unidades->links() }}
            </div>
        </div>
    @else
        <!-- Mensaje cuando no hay período seleccionado -->
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Selecciona un período</h3>
            <p class="mt-2 text-sm text-gray-500">Debes seleccionar un período para ver las unidades y generar boletas.</p>
        </div>
    @endif

    <!-- Script para alertas -->
    <script>
        @this.on('alerta', (data) => {
            alert(`${data.titulo}: ${data.mensaje}`);
        });
    </script>
</div>
