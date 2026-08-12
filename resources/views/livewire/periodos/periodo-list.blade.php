<div class="space-y-6">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Períodos de Expensas</h2>
                <p class="text-gray-600 mt-1">Gestiona los períodos mensuales de expensas</p>
            </div>
            <div class="flex items-center space-x-3">
                @if($this->periodoAbierto)
                    <a href="{{ route('periodos.facturas', $this->periodoAbierto->id) }}"
                       class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors text-sm font-medium flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Registrar Facturas
                        <span class="ml-2 px-2 py-0.5 bg-yellow-600 rounded-full text-xs">
                            {{ $this->periodoAbierto->facturasAgua->count() }}/3
                        </span>
                    </a>
                @endif

                <a href="{{ route('periodos.create') }}"
                   class="px-4 py-2 {{ $this->periodoAbierto ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }} text-white rounded-lg transition-colors text-sm font-medium flex items-center"
                   @if($this->periodoAbierto) disabled title="Ya existe un período abierto" @endif>
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Período
                </a>
            </div>
        </div>

        <!-- Alerta de período abierto incompleto -->
        @if($this->periodoAbierto)
            @php
                $facturasRegistradas = $this->periodoAbierto->facturasAgua->count();
                $facturasIncompletas = $facturasRegistradas < 3;
            @endphp

            @if($facturasIncompletas)
                <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg flex items-center justify-between">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-semibold text-yellow-800">Período Incompleto</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                El período <strong>{{ $this->periodoAbierto->nombre_periodo }}</strong> está abierto pero solo tiene
                                <span class="font-bold">{{ $facturasRegistradas }}/3 facturas</span> registradas.
                                <span class="text-yellow-800 font-medium">Complete las facturas antes de continuar.</span>
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('periodos.facturas', $this->periodoAbierto->id) }}"
                       class="ml-4 inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors flex-shrink-0">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Registrar Facturas
                    </a>
                </div>
            @else
                <div class="mt-4 p-4 bg-green-50 border-l-4 border-green-400 rounded-lg flex items-center">
                    <svg class="w-5 h-5 text-green-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-green-800">Período Abierto Completo</h4>
                        <p class="text-sm text-green-700 mt-1">
                            El período <strong>{{ $this->periodoAbierto->nombre_periodo }}</strong> tiene todas las facturas registradas.
                            Puede continuar con las lecturas de medidor.
                        </p>
                    </div>
                </div>
            @endif
        @endif

        <!-- Filtros -->
        <div class="mt-4 flex space-x-4">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Buscar por mes o año..."
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">

            <select wire:model.live="filtroEstado"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <option value="">Todos los estados</option>
                <option value="abierto">Abiertos</option>
                <option value="cerrado">Cerrados</option>
            </select>
        </div>
    </div>

    <!-- Lista de Períodos -->
    @if($this->periodos->count() > 0)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Período
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha Apertura
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha Cierre
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Facturas
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Lecturas
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->periodos as $periodo)
                            <tr class="hover:bg-gray-50 {{ $periodo->estado === 'abierto' ? 'bg-green-50/50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-gradient-to-br {{ $periodo->estado === 'abierto' ? 'from-green-500 to-emerald-600' : 'from-gray-400 to-gray-500' }} flex items-center justify-center text-white font-bold text-sm">
                                            {{ substr($periodo->nombre_periodo, 0, 3) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900">
                                                {{ $periodo->nombre_periodo }}
                                            </div>
                                            @if($periodo->estado === 'abierto')
                                                <div class="text-xs text-green-600 font-medium">Período actual</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                        {{ $periodo->estado === 'abierto' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-gray-100 text-gray-800 border border-gray-200' }}">
                                        <span class="w-2 h-2 rounded-full mr-2 {{ $periodo->estado === 'abierto' ? 'bg-green-500' : 'bg-gray-500' }}"></span>
                                        {{ $periodo->estado_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex flex-col">
                                        <span>{{ $periodo->fecha_apertura?->format('d/m/Y') ?? '-' }}</span>
                                        @if($periodo->fecha_apertura)
                                            <span class="text-xs text-gray-400">{{ $periodo->fecha_apertura->format('H:i') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($periodo->fecha_cierre)
                                        <div class="flex flex-col">
                                            <span>{{ $periodo->fecha_cierre->format('d/m/Y') }}</span>
                                            <span class="text-xs text-gray-400">{{ $periodo->fecha_cierre->format('H:i') }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="h-2 rounded-full {{ $periodo->facturasAgua->count() === 3 ? 'bg-green-500' : 'bg-yellow-500' }}"
                                                 style="width: {{ ($periodo->facturasAgua->count() / 3) * 100 }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium {{ $periodo->facturasAgua->count() === 3 ? 'text-green-600' : 'text-yellow-600' }}">
                                            {{ $periodo->facturasAgua->count() }}/3
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium
                                        {{ $periodo->lecturasMedidor->count() > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $periodo->lecturasMedidor->count() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        {{-- Ver detalle --}}
                                        <a href="{{ route('periodos.show', $periodo->id) }}"
                                           class="text-blue-600 hover:text-blue-900"
                                           title="Ver detalle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>

                                        {{-- Registrar facturas (solo si está abierto y faltan facturas) --}}
                                        @if($periodo->estado === 'abierto' && $periodo->facturasAgua->count() < 3)
                                            <a href="{{ route('periodos.facturas', $periodo->id) }}"
                                               class="text-yellow-600 hover:text-yellow-900"
                                               title="Registrar facturas">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </a>
                                        @endif

                                        {{-- Ver facturas (si están completas) --}}
                                        @if($periodo->estado === 'abierto' && $periodo->facturasAgua->count() === 3)
                                            <a href="{{ route('periodos.facturas', $periodo->id) }}"
                                               class="text-green-600 hover:text-green-900"
                                               title="Ver facturas completas">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </a>
                                        @endif

                                        {{-- Cerrar período (solo si está abierto) --}}
                                        @if($periodo->estado === 'abierto')
                                            <button
                                                    wire:click="abrirModalCierre({{ $periodo->id }})"
                                                    class="text-red-600 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 rounded p-1"
                                                    title="Cerrar período">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($this->periodos->hasPages())
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $this->periodos->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay períodos registrados</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo período de expensas.</p>
            <div class="mt-6">
                <a href="{{ route('periodos.create') }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Crear Período
                </a>
            </div>
        </div>
    @endif

    {{-- Modal de Confirmación de Cierre --}}
    @if($mostrarModalCierre)
    <div x-data
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="$wire.cerrarModalCierre()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Cerrar Período
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    ¿Está seguro que desea cerrar este período? Una vez cerrado, no se podrán modificar las lecturas ni facturas.
                                </p>
                                <input wire:model="motivoCierre"
                                          type="text"
                                          placeholder="Motivo del cierre (opcional)"
                                          class="mt-3 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="cerrarPeriodo"
                            wire:loading.attr="disabled"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove>Cerrar Período</span>
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Cerrando...
                        </span>
                    </button>
                    <button type="button"
                            wire:click="cerrarModalCierre"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

