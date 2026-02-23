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
                   wire:model.live="search"
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($this->grupos as $grupo)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ $grupo->nombre }}
                        </h3>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $grupo->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $grupo->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                        <p>
                            <strong>Tipo:</strong> {{ $grupo->tipo_distribucion_label }}
                        </p>
                        <p>
                            <strong>Unidades:</strong> {{ $grupo->unidades->count() }} asignadas
                        </p>
                        @if($grupo->observaciones)
                            <p>
                                <strong>Obs:</strong> {{ $grupo->observaciones }}
                            </p>
                        @endif
                    </div>

                    <div class="flex space-x-2">
                        <a href="{{ route('configuracion.medidores-grupos.edit', $grupo->id) }}"
                           class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium text-center">
                            Editar
                        </a>
                        <button wire:click="toggleEstado({{ $grupo->id }})"
                                class="flex-1 px-3 py-2 {{ $grupo->activo ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} rounded-lg transition-colors text-sm font-medium">
                            {{ $grupo->activo ? 'Desactivar' : 'Activar' }}
                        </button>
                        <button wire:click="eliminarGrupo({{ $grupo->id }})"
                                wire:confirm="¿Está seguro de eliminar este grupo?"
                                class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
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
