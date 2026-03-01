<div class="space-y-6">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-2xl font-bold text-gray-900">
            {{ $grupoId ? 'Editar Grupo' : 'Nuevo Grupo' }} de Medidores
        </h2>
        <p class="text-gray-600 mt-1">
            {{ $grupoId ? 'Actualiza la información del grupo' : 'Crea un nuevo grupo de medidores compartidos' }}
        </p>
    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form wire:submit="guardar">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nombre -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Nombre del Grupo <span class="text-red-500">*</span></label>
                    <input type="text"
                           wire:model.live="nombre"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Ej: Locales Piso 1, Oficinas Torre A">
                    @error('nombre')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tipo de Distribución -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo de Distribución <span class="text-red-500">*</span></label>
                    <select wire:model.live="tipo_distribucion"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="equitativa">Equitativa (partes iguales)</option>
                    </select>
                    @error('tipo_distribucion')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <select wire:model.live="activo"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <!-- Observaciones -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                    <textarea wire:model.live="observaciones"
                              rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Información adicional sobre el grupo..."></textarea>
                    @error('observaciones')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Unidades Disponibles -->
            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Unidades del Grupo</h3>

                @if(collect($unidadesDisponibles)->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($unidadesDisponibles as $unidad)
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50
                                {{ in_array($unidad['id'], $unidadesSeleccionadas) ? 'bg-blue-50 border-blue-500' : 'border-gray-300' }}">
                                <input type="checkbox"
                                       wire:change="toggleUnidad({{ $unidad['id'] }})"
                                       {{ in_array($unidad['id'], $unidadesSeleccionadas) ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $unidad['nombre'] }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $unidad['codigo'] }}
                                    </p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <p class="mt-2 text-sm text-gray-600">
                        <strong>{{ count($unidadesSeleccionadas) }}</strong> unidades seleccionadas
                    </p>
                @else
                    <p class="text-sm text-gray-500">No hay unidades disponibles para asignar.</p>
                @endif
            </div>

            <!-- Botones -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('configuracion.medidores-grupos.index') }}"
                   class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    {{ $grupoId ? 'Actualizar' : 'Crear' }} Grupo
                </button>
            </div>
        </form>
    </div>

    <!-- Información -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Información sobre Grupos de Medidores</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Los grupos permiten compartir un medidor entre varias unidades</li>
                        <li>La distribución es equitativa (partes iguales)</li>
                        <li>Una unidad solo puede pertenecer a un grupo a la vez</li>
                        <li>Los grupos se usan en el registro de lecturas compartidas</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
