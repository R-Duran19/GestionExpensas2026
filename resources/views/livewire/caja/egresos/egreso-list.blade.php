<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-arrow-circle-up text-red-600 mr-2"></i>
                Egresos
            </h1>
            <p class="text-gray-600 mt-1">Gestión de egresos del edificio</p>
        </div>
        <a href="{{ route('caja.egresos.create') }}"
           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2 transition w-full sm:w-auto whitespace-nowrap">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="hidden sm:inline">Nuevo Egreso</span>
            <span class="sm:hidden">Nuevo</span>
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <input type="text"
                       wire:model.live.debounce.300ms="buscar"
                       placeholder="Buscar..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <select wire:model.live="filtro_categoria"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="text"
                       wire:model.live="filtro_proveedor"
                       placeholder="Proveedor..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <input type="date"
                       wire:model.live="filtro_fecha_desde"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <input type="date"
                       wire:model.live="filtro_fecha_hasta"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
        </div>
        @if($buscar || $filtro_categoria || $filtro_proveedor || $filtro_fecha_desde || $filtro_fecha_hasta)
            <div class="mt-3">
                <button wire:click="resetFilters"
                        class="text-sm text-red-600 hover:text-red-800 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Limpiar filtros
                </button>
            </div>
        @endif
    </div>

    {{-- Tabla de Egresos --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th wire:click="sortBy('numero_egreso')"
                            class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            N° Egreso
                            @if($sortField === 'numero_egreso')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th wire:click="sortBy('fecha')"
                            class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            Fecha
                            @if($sortField === 'fecha')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Categoría
                        </th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Concepto
                        </th>
                        <th class="hidden md:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Proveedor
                        </th>
                        <th wire:click="sortBy('importe')"
                            class="px-3 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            Importe
                            @if($sortField === 'importe')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-3 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($egresos as $egreso)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $egreso->numero_egreso }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 text-sm text-gray-900">
                                <div class="flex flex-col sm:block">
                                    <span>{{ $egreso->fecha->format('d/m/Y') }}</span>
                                    @if($egreso->categoria)
                                        <span class="lg:hidden text-xs mt-1 px-2 py-0.5 rounded-full text-white inline-block"
                                              style="background-color: {{ $egreso->categoria->color }};">
                                            {{ $egreso->categoria->nombre }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap">
                                @if($egreso->categoria)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white"
                                          style="background-color: {{ $egreso->categoria->color }};">
                                        {{ $egreso->categoria->nombre }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 text-sm text-gray-900">
                                <div class="max-w-xs">
                                    {{ $egreso->concepto }}
                                    @if($egreso->numero_comprobante)
                                        <br>
                                        <span class="text-xs text-gray-500">
                                            <i class="fas fa-receipt mr-1"></i>
                                            {{ $egreso->numero_comprobante }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="hidden md:table-cell px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $egreso->proveedor ?? '-' }}
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-right text-sm font-medium text-red-600">
                                Bs {{ number_format($egreso->importe, 2, ',', '.') }}
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-center text-sm">
                                <div class="flex justify-center gap-1 sm:gap-2">
                                    @if($egreso->comprobante_foto)
                                        <button wire:click="verComprobante('{{ $egreso->id }}')"
                                                class="text-indigo-600 hover:text-indigo-900 p-1"
                                                title="Ver comprobante">
                                            <i class="fas fa-image text-sm"></i>
                                        </button>
                                    @endif

                                    {{-- Botón de ver PDF --}}
                                    <a href="{{ route('caja.egresos.pdf', $egreso) }}"
                                       target="_blank"
                                       class="text-red-600 hover:text-red-900 p-1"
                                       title="Ver comprobante PDF">
                                        <i class="fas fa-file-pdf text-sm"></i>
                                    </a>

                                    <a href="{{ route('caja.egresos.edit', $egreso) }}"
                                       class="text-blue-600 hover:text-blue-900 p-1"
                                       title="Editar">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                    <button wire:click="confirmarEliminar({{ $egreso->id }})"
                                            class="text-red-600 hover:text-red-900 p-1"
                                            title="Eliminar">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay egresos</h3>
                                <p class="mt-1 text-sm text-gray-500">No hay egresos registrados.</p>
                                <div class="mt-6">
                                    <a href="{{ route('caja.egresos.create') }}"
                                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                        Crear Primer Egreso
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if($egresos->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $egresos->links() }}
            </div>
        @endif
    </div>

    {{-- Modal de confirmación de eliminación --}}
    @if($mostrarModalEliminar)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-2">¿Eliminar egreso?</h3>
                    @if($egresoAEliminar)
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500">
                                ¿Está seguro que desea eliminar el egreso <strong>{{ $egresoAEliminar->numero_egreso }}</strong>?
                            </p>
                            <ul class="text-left text-sm text-gray-600 mt-2">
                                <li>• Fecha: {{ $egresoAEliminar->fecha->format('d/m/Y') }}</li>
                                <li>• Concepto: {{ $egresoAEliminar->concepto }}</li>
                                <li>• Importe: Bs {{ number_format($egresoAEliminar->importe, 2, ',', '.') }}</li>
                            </ul>
                        </div>
                        <p class="text-xs text-red-500 mt-2">
                            Esta acción también eliminará el comprobante adjunto.
                        </p>
                    @endif
                    <div class="flex justify-center gap-3 mt-4">
                        <button wire:click="cancelarEliminar"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                        <button wire:click="eliminar"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de visualización de comprobante --}}
    @if($mostrarModalComprobante)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-image text-indigo-600 mr-2"></i>
                        Comprobante
                    </h3>
                    <button wire:click="cerrarModalComprobante"
                            class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 bg-gray-100" style="max-height: 70vh; overflow: auto;">
                    <img src="{{ $comprobanteUrl }}"
                         alt="Comprobante"
                         class="w-full h-auto rounded-lg shadow-lg">
                </div>
                <div class="p-4 border-t bg-gray-50 rounded-b-lg flex justify-end gap-3">
                    <a href="{{ $comprobanteUrl }}"
                       download="comprobante"
                       class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                        <i class="fas fa-download"></i>
                        Descargar
                    </a>
                    <button wire:click="cerrarModalComprobante"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        function resetFilters() {
            @this.set('buscar', '');
            @this.set('filtro_categoria', '');
            @this.set('filtro_proveedor', '');
            @this.set('filtro_fecha_desde', '');
            @this.set('filtro_fecha_hasta', '');
        }
    </script>
</div>
