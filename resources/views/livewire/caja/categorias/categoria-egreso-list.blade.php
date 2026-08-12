<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-tags text-red-600 mr-2"></i>
                Categorías de Egresos
            </h1>
            <p class="text-gray-600 mt-1">Gestiona las categorías para clasificar los egresos</p>
        </div>
        <button wire:click="abrirModalCrear"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2 transition w-full sm:w-auto">
            <i class="fas fa-plus"></i>
            <span class="hidden sm:inline">Nueva Categoría</span>
            <span class="sm:hidden">Nueva</span>
        </button>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <input type="text"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                       placeholder="Buscar categoría..."
                       wire:model.live.debounce.300ms="buscar">
            </div>
            <div>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        wire:model.live="filtro_estado">
                    <option value="">Todas</option>
                    <option value="activos">Activas</option>
                    <option value="inactivos">Inactivas</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Grid de Categorías --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($categorias as $categoria)
            <div class="bg-white rounded-lg shadow @if(!$categoria->activo) border-2 border-gray-300 @endif hover:shadow-md transition">
                <div class="p-6">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-full flex items-center justify-center"
                                 style="width: 50px; height: 50px; background-color: {{ $categoria->color }}20;">
                                <i class="fas fa-{{ $categoria->icono }} text-lg"
                                   style="color: {{ $categoria->color }};"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $categoria->nombre }}</h3>
                            @if($categoria->descripcion)
                                <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                    {{ \Illuminate\Support\Str::limit($categoria->descripcion, 80) }}
                                </p>
                            @endif
                        </div>
                    </div>

                    @if($categoria->tieneEgresos())
                        <div class="mb-4">
                            <small class="text-gray-600">
                                <i class="fas fa-arrow-circle-up mr-1"></i>
                                {{ $categoria->conteoEgresosActivos() }} egresos
                                <span class="ml-2">
                                    Total: Bs {{ number_format($categoria->totalEgresosActivos(), 2, ',', '.') }}
                                </span>
                            </small>
                        </div>
                    @endif

                    <div class="flex justify-between items-center">
                        <div>
                            @if($categoria->activo)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Activa
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Inactiva
                                </span>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="editarCategoria({{ $categoria->id }})"
                                    class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition"
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            @if($categoria->activo)
                                <button wire:click="confirmarDesactivar({{ $categoria->id }})"
                                        class="text-orange-600 hover:text-orange-900 p-2 rounded-lg hover:bg-orange-50 transition"
                                        title="Desactivar">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            @else
                                <button wire:click="activar({{ $categoria->id }})"
                                        class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition"
                                        title="Activar">
                                    <i class="fas fa-eye"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay categorías</h3>
                    <p class="mt-1 text-sm text-gray-500">No hay categorías registradas.</p>
                    <div class="mt-6">
                        <button wire:click="abrirModalCrear"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            Crear Primera Categoría
                        </button>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Modal Crear/Editar --}}
    @if($mostrarModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-tag text-red-600 mr-2"></i>
                        {{ $categoriaId ? 'Editar Categoría' : 'Nueva Categoría' }}
                    </h3>
                    <button type="button" wire:click="cerrarModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardarCategoria">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('nombre') border-red-500 @enderror border-gray-300"
                                   wire:model="nombre"
                                   placeholder="Ej: Mantenimiento">
                            @error('nombre')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción
                            </label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      wire:model="descripcion"
                                      rows="3"
                                      placeholder="Descripción de la categoría"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Color
                            </label>
                            <div class="flex gap-4">
                                <input type="color"
                                       class="h-10 w-20 border rounded-lg @error('color') border-red-500 @enderror border-gray-300"
                                       wire:model="color">
                                <div class="flex-1">
                                    <input type="text"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                           value="{{ $color }}"
                                           readonly>
                                </div>
                            </div>
                            @error('color')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if(!$categoriaId)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Icono
                                </label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                        wire:model="icono">
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

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button"
                                    wire:click="cerrarModal"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                <i class="fas fa-save mr-1"></i>
                                Guardar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Confirmar Desactivar --}}
    @if($mostrarModalDesactivar)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-orange-100">
                        <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165.791-1.914 2.163-2.915 2.911-.872.721-1.614 1.811-2.163 2.687-.5.979-.791-2.175-1.163-2.911-.415-.689-.791-1.455-.791-2.163 0-1.743 1.427-3.157 3.186-3.157.552 0 1.045.201 1.491.554 1.957.528.538.949 1.115 1.504 1.711 1.593.627.872.721 1.614 1.811 2.915 2.911.872.721 1.614 1.791 2.915 2.915z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-2">¿Desactivar categoría?</h3>
                    @if($categoriaADesactivar)
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500">
                                ¿Está seguro que desea desactivar la categoría <strong>{{ $categoriaADesactivar->nombre }}</strong>?
                            </p>
                            @if($categoriaADesactivar->tieneEgresos())
                                <p class="text-xs text-orange-500 mt-2">
                                    Esta categoría tiene {{ $categoriaADesactivar->conteoEgresosActivos() }} egresos asociados.
                                </p>
                            @endif
                        </div>
                    @endif
                    <div class="flex justify-center gap-3 mt-4">
                        <button wire:click="cancelarDesactivar"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                        <button wire:click="desactivar"
                                class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                            Desactivar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
