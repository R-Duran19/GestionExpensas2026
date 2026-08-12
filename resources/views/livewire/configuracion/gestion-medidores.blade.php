<div class="space-y-6">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('configuracion.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver a Configuración
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Gestión de Medidores</h2>
                    <p class="text-gray-600 mt-1">Asigna medidores a las unidades del edificio</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Buscar por código..."
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">

                <select wire:model.live="filtroTipo"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos los tipos</option>
                    <option value="departamento">Departamentos</option>
                    <option value="oficina">Oficinas</option>
                    <option value="local">Locales</option>
                </select>

                <select wire:model.live="filtroMedidor"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todas las unidades</option>
                    <option value="con_medidor">Con medidor</option>
                    <option value="sin_medidor">Sin medidor</option>
                </select>
            </div>

            <!-- Resumen -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ App\Models\Unidad::count() }}</p>
                    <p class="text-sm text-blue-700">Total Unidades</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ App\Models\Unidad::where('tiene_medidor', true)->count() }}</p>
                    <p class="text-sm text-green-700">Con Medidor</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ App\Models\Unidad::where('tipo_medidor', 'individual')->count() }}</p>
                    <p class="text-sm text-purple-700">Individuales</p>
                </div>
                <div class="bg-orange-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-orange-600">{{ App\Models\Unidad::where('tipo_medidor', 'compartido')->count() }}</p>
                    <p class="text-sm text-orange-700">Compartidos</p>
                </div>
            </div>
        </div>

        <!-- Lista de Unidades -->
        @if($this->unidades->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unidad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Propietario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medidor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->unidades as $unidad)
                            <tr class="hover:bg-gray-50" wire:key="{{ $unidad->id }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                                             style="background-color: {{ $unidad->color }}">
                                            {{ substr($unidad->tipo_unidad->getLabel(), 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $unidad->nombre_completo }}</div>
                                            @if($unidad->piso)
                                                <div class="text-xs text-gray-500">Piso: {{ $unidad->piso }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $unidad->tipo_unidad->getColor() }} bg-opacity-10">
                                        {{ $unidad->tipo_unidad->getLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $unidad->propietarioActual?->nombre ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if(!$unidad->tiene_medidor)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            ❌ Sin medidor
                                        </span>
                                    @elseif($unidad->tipo_medidor === 'individual' || $unidad->tipo_medidor === null)
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                ✅ Individual
                                            </span>
                                            @if($unidad->numero_medidor)
                                                <div class="text-xs text-gray-600 mt-1 font-mono">
                                                    Nº: <input type="text"
                                                                   value="{{ $unidad->numero_medidor }}"
                                                                   class="w-20 px-1 py-0.5 text-xs border border-gray-300 rounded bg-white"
                                                                   wire:change="actualizarNumeroMedidor({{ $unidad->id }}, $event.target.value)">
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-400 mt-1 italic">
                                                    Sin número asignado
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                🔄 Compartido
                                            </span>
                                            @if($unidad->medidorGrupo)
                                                <div class="text-xs text-gray-600 mt-1">
                                                    Grupo: <strong>{{ $unidad->medidorGrupo->nombre }}</strong>
                                                </div>
                                            @else
                                                <div class="text-xs text-red-500 mt-1 italic">
                                                    ⚠️ Sin grupo asignado
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        @if(!$unidad->tiene_medidor)
                                            <!-- Unidad sin medidor -->
                                            <button wire:click="asignarIndividual({{ $unidad->id }})"
                                                    class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-medium">
                                                ➕ Asignar Medidor
                                            </button>
                                        @else
                                            <!-- Unidad con medidor -->
                                            <button wire:click="toggleMedidor({{ $unidad->id }})"
                                                    wire:confirm="¿Quitar medidor de {{ $unidad->nombre_completo }}?"
                                                    class="inline-flex items-center px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs font-medium"
                                                    title="Quitar medidor">
                                                ❌ Quitar
                                            </button>

                                            @if($unidad->tipo_medidor === 'individual')
                                                <!-- Botón para hacer compartido - Lleva a la página de grupos -->
                                                <a href="{{ route('configuracion.medidores-grupos.index') }}"
                                                   class="inline-flex items-center px-3 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-xs font-medium"
                                                   title="Ir a grupos de medidores">
                                                    🔄 Hacer Compartido
                                                </a>
                                            @elseif($unidad->tipo_medidor === 'compartido')
                                                <!-- Información de que es compartido - No se puede cambiar desde acá -->
                                                <a href="{{ route('configuracion.medidores-grupos.index') }}"
                                                   class="inline-flex items-center px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-xs font-medium"
                                                   title="Ver grupo de medidores">
                                                    🔗 Ver Grupo
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($this->unidades->hasPages())
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $this->unidades->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay unidades con medidor</h3>
                <p class="mt-1 text-sm text-gray-500">No se han asignado medidores a ninguna unidad todavía.</p>
            </div>
        @endif
    </div>
</div>
