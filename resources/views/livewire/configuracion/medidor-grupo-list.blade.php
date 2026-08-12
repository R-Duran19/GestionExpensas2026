<div class="space-y-6">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Grupos de Medidores</h2>
                <p class="text-gray-600 mt-1">Gestiona los grupos de medidores compartidos</p>
            </div>
            <a href="{{ route('configuracion.medidores-grupos.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Grupo
            </a>
        </div>

        <!-- Filtros -->
        <div class="mt-4 flex space-x-4">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Buscar por nombre..."
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">

            <select wire:model.live="filtroEstado"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <option value="">Todos los estados</option>
                <option value="activo">Activos</option>
                <option value="inactivo">Inactivos</option>
            </select>
        </div>
    </div>

    <!-- Lista de Grupos -->
    @if($this->grupos->count() > 0)
        <div class="space-y-4">
            @foreach($this->grupos as $grupo)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ $grupo->nombre }}
                                </h3>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    {{ $grupo->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $grupo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $grupo->unidades->count() }} unidades
                                </span>
                            </div>
                            @if($grupo->observaciones)
                                <p class="text-sm text-gray-600 mt-1">
                                    <strong>Obs:</strong> {{ $grupo->observaciones }}
                                </p>
                            @endif
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('configuracion.medidores-grupos.edit', $grupo->id) }}"
                               class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                Editar
                            </a>
                            <button wire:click="eliminarGrupo({{ $grupo->id }})"
                                    wire:confirm="¿Está seguro de eliminar este grupo?@if($grupo->unidades->count() > 0) Tiene {{ $grupo->unidades->count() }} unidad(es) asignada(s). @endif"
                                    class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium"
                                    @if($grupo->unidades->count() > 0) disabled title="Primero desasigne las unidades" @endif>
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <!-- Unidades Asignadas -->
                    @if($grupo->unidades->count() > 0)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-sm font-medium text-gray-700 mb-2">Unidades asignadas:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($grupo->unidades as $unidad)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-800">
                                        {{ $unidad->nombre_completo }}
                                        @if($unidad->piso)
                                            <span class="ml-1 text-gray-500">(Piso {{ $unidad->piso }})</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                💡 Para desasignar unidades, ve a "Editar" y desmarca las unidades que deseas quitar.
                            </p>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mt-2 italic">Sin unidades asignadas</p>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Paginación -->
        @if($this->grupos->hasPages())
            <div class="bg-white rounded-lg shadow-sm p-4">
                {{ $this->grupos->links() }}
            </div>
        @endif
    @else
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay grupos de medidores</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo grupo de medidores compartidos.</p>
            <div class="mt-6">
                <a href="{{ route('configuracion.medidores-grupos.create') }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Crear Grupo
                </a>
            </div>
        </div>
    @endif
</div>
