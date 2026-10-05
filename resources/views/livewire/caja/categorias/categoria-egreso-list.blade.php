<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-arrow-down-circle text-red-600 mr-2"></i>Categorías de Egresos
            </h1>
            <p class="text-sm text-gray-500 mt-1">Clasificación de los pagos del edificio</p>
        </div>
        <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
            <i class="fas fa-plus"></i> Nueva Categoría
        </button>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
            <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Nombre de la categoría..."
                   class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
            <select wire:model.live="filtro_estado"
                    class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 text-sm">
                <option value="">Todas</option>
                <option value="activos">Activas</option>
                <option value="inactivos">Inactivas</option>
            </select>
        </div>
    </div>

    {{-- Grid de categorías --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($categorias as $categoria)
            <div class="bg-white rounded-lg shadow p-5" wire:key="cat-{{ $categoria->id }}">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                              style="background-color: {{ $categoria->color }}">
                            <i class="fas fa-{{ $categoria->icono }}"></i>
                        </span>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $categoria->nombre }}</h3>
                            @if($categoria->activo)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Activa</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactiva</span>
                            @endif
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-500 mb-4 min-h-[2.5rem]">{{ $categoria->descripcion ?: '—' }}</p>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <button wire:click="verHistorial({{ $categoria->id }})"
                            class="text-xs text-gray-500 hover:text-gray-700" title="Ver historial de cambios">
                        <i class="fas fa-clock-rotate-left mr-1"></i> Historial
                    </button>
                    <div class="flex gap-1">
                        <button wire:click="abrirModalEditar({{ $categoria->id }})"
                                class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition" title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button wire:click="toggleActivo({{ $categoria->id }})"
                                wire:confirm="{{ $categoria->activo
                                    ? '¿Desactivar la categoría ' . $categoria->nombre . '?'
                                    : '¿Reactivar la categoría ' . $categoria->nombre . '?' }}"
                                class="p-2 rounded-lg transition {{ $categoria->activo ? 'text-orange-600 hover:bg-orange-50' : 'text-emerald-600 hover:bg-emerald-50' }}"
                                title="{{ $categoria->activo ? 'Desactivar' : 'Reactivar' }}">
                            <i class="fas fa-{{ $categoria->activo ? 'eye-slash' : 'eye' }}"></i>
                        </button>
                        <button wire:click="confirmarEliminar({{ $categoria->id }})"
                                wire:confirm="¿Eliminar la categoría {{ $categoria->nombre }}? Esta acción no se puede deshacer."
                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-tags text-4xl text-gray-300 mb-3"></i>
                    <h3 class="text-sm font-medium text-gray-900">No hay categorías</h3>
                    <p class="mt-1 text-sm text-gray-500">No hay categorías registradas.</p>
                    <div class="mt-6">
                        <button wire:click="abrirModalCrear"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                            <i class="fas fa-plus"></i> Crear Primera Categoría
                        </button>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $categorias->links() }}
    </div>

    {{-- Modal Crear/Editar --}}
    @if($mostrarModalCrear || $mostrarModalEditar)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-tag text-red-600 mr-2"></i>
                        {{ $mostrarModalEditar ? 'Editar Categoría' : 'Nueva Categoría' }}
                    </h3>
                    <button type="button" wire:click="{{ $mostrarModalEditar ? 'cerrarModalEditar' : 'cerrarModalCrear' }}" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form wire:submit="{{ $mostrarModalEditar ? 'actualizar' : 'crear' }}">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('nombre') border-red-500 @enderror"
                                   wire:model="nombre"
                                   placeholder="Ej: Mantenimiento">
                            @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      wire:model="descripcion" rows="3"
                                      placeholder="Descripción de la categoría"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Color <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-4 items-center">
                                <input type="color" wire:model="color"
                                       class="h-10 w-20 border border-gray-300 rounded-lg @error('color') border-red-500 @enderror">
                                <input type="text" value="{{ $color }}" readonly
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            @error('color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if(!$mostrarModalEditar)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Icono <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="icono"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                    <option value="arrow-down-circle">Flecha abajo</option>
                                    <option value="shopping-cart">Carrito</option>
                                    <option value="wrench">Llave</option>
                                    <option value="tools">Herramientas</option>
                                    <option value="bolt">Rayo</option>
                                    <option value="home">Casa</option>
                                    <option value="building">Edificio</option>
                                </select>
                            </div>
                        @endif

                        {{-- Motivo obligatorio al editar (auditoría) --}}
                        @if($mostrarModalEditar)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Motivo del cambio <span class="text-red-500">*</span>
                                </label>
                                <textarea wire:model="motivo_edicion" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                          placeholder="Ej: Cambio de nombre por decisión del consorcio"></textarea>
                                @error('motivo_edicion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button"
                                    wire:click="{{ $mostrarModalEditar ? 'cerrarModalEditar' : 'cerrarModalCrear' }}"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50">
                                <i class="fas fa-save mr-1"></i> Guardar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Historial --}}
    @if($mostrarModalHistorial)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-clock-rotate-left text-gray-500 mr-2"></i>
                        Historial: {{ $categoriaSeleccionada?->nombre }}
                    </h3>
                    <button type="button" wire:click="cerrarModalHistorial" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                    @forelse ($historialCategoria as $h)
                        <div class="py-3 text-sm" wire:key="hist-{{ $h->id }}">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-800">{{ $h->nombre_anterior }} → {{ $h->nombre_nuevo }}</span>
                                <span class="text-xs text-gray-400">{{ $h->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($h->motivo)
                                <p class="text-gray-500 mt-1"><i class="fas fa-comment-dots mr-1"></i>{{ $h->motivo }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="py-6 text-center text-gray-400 text-sm">Sin cambios registrados.</p>
                    @endforelse
                </div>

                <div class="flex justify-end pt-4">
                    <button type="button" wire:click="cerrarModalHistorial"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
