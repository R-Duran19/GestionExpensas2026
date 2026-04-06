<div>
    <div class="bg-white rounded-lg shadow">
        <div class="bg-red-600 text-white px-6 py-4 rounded-t-lg">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold">
                    <i class="fas {{ $egreso_id ? 'fa-edit' : 'fa-plus-circle' }} mr-2"></i>
                    {{ $egreso_id ? 'Editar Egreso' : 'Nuevo Egreso' }}
                </h1>
                <a href="{{ route('caja.egresos.index') }}"
                   class="text-white hover:bg-red-700 px-3 py-2 rounded-lg transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="p-6">
            @if($egreso_id)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Editando egreso: <strong>{{ $numero_egreso }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            El comprobante fotográfico es <strong>OBLIGATORIO</strong> para todo egreso.
                        </p>
                    </div>
                </div>
            </div>

            <form wire:submit="guardar">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Columna izquierda --}}
                    <div class="space-y-4">
                        {{-- Fecha --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-calendar mr-1"></i>
                                Fecha <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('fecha') border-red-500 @enderror"
                                   wire:model="fecha">
                            @error('fecha')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Categoría --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-tag mr-1"></i>
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <select class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('categoria_egreso_id') border-red-500 @enderror"
                                        wire:model="categoria_egreso_id">
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categorias as $categoria)
                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                                <button type="button"
                                        wire:click="abrirModalCategoria"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center gap-2 text-sm font-medium"
                                        title="Crear nueva categoría">
                                    <i class="fas fa-plus-circle text-base"></i>
                                    <span>Nueva Categoría</span>
                                </button>
                            </div>
                            @error('categoria_egreso_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Concepto --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-file-alt mr-1"></i>
                                Concepto <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('concepto') border-red-500 @enderror"
                                   wire:model="concepto"
                                   placeholder="Descripción breve del egreso">
                            @error('concepto')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Número de Comprobante --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-receipt mr-1"></i>
                                N° de Factura/Recibo
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('numero_comprobante') border-red-500 @enderror"
                                   wire:model="numero_comprobante"
                                   placeholder="Ej: 001-00012345">
                            @error('numero_comprobante')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Importe --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-dollar-sign mr-1"></i>
                                Importe <span class="text-red-500">*</span>
                            </label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">Bs</span>
                                </div>
                                <input type="number"
                                       step="0.01"
                                       min="0.01"
                                       class="w-full pl-7 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('importe') border-red-500 @enderror"
                                       wire:model="importe"
                                       placeholder="0.00">
                            </div>
                            @error('importe')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Proveedor --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-store mr-1"></i>
                                Proveedor
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('proveedor') border-red-500 @enderror"
                                   wire:model="proveedor"
                                   placeholder="Nombre del proveedor">
                            @error('proveedor')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Columna derecha --}}
                    <div class="space-y-4">
                        {{-- Descripción --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-align-left mr-1"></i>
                                Descripción Detallada
                            </label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('descripcion') border-red-500 @enderror"
                                      wire:model="descripcion"
                                      rows="4"
                                      placeholder="Descripción detallada del egreso"></textarea>
                            @error('descripcion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Comprobante (OBLIGATORIO) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-camera mr-1"></i>
                                Comprobante Fotográfico <span class="text-red-500">*</span>
                            </label>

                            @if($comprobante_foto_actual)
                                <div class="mt-2">
                                    <div class="border rounded-lg p-4">
                                        <img src="{{ Storage::url($comprobante_foto_actual) }}"
                                             alt="Comprobante actual"
                                             class="max-h-48 mx-auto mb-2">
                                        <button type="button"
                                                class="w-full bg-red-100 text-red-700 px-3 py-2 rounded-lg hover:bg-red-200 transition"
                                                wire:click="limpiarComprobante">
                                            <i class="fas fa-trash mr-1"></i>
                                            Eliminar comprobante
                                        </button>
                                    </div>
                                </div>
                            @else
                                <input type="file"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('comprobante_foto') border-red-500 @enderror"
                                       wire:model="comprobante_foto"
                                       accept="image/*"
                                       required>
                                @error('comprobante_foto')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                @if($comprobante_foto)
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-600">
                                            Archivo seleccionado: {{ $comprobante_foto->getClientOriginalName() }}
                                        </p>
                                        <img src="{{ $comprobante_foto->temporaryUrl() }}"
                                             alt="Vista previa"
                                             class="max-h-48 mt-2">
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- Observaciones --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-sticky-note mr-1"></i>
                                Observaciones
                            </label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      wire:model="observaciones"
                                      rows="3"
                                      placeholder="Observaciones adicionales"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Botones de acción --}}
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-between">
                        <a href="{{ route('caja.egresos.index') }}"
                           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition">
                            <i class="fas fa-save mr-1"></i>
                            {{ $egreso_id ? 'Actualizar' : 'Guardar' }} Egreso
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal para crear categoría rápida --}}
    @if($mostrarModalCategoria)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-tag text-red-600 mr-2"></i>
                        Crear Nueva Categoría
                    </h3>
                    <button type="button" wire:click="cerrarModalCategoria" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="crearCategoriaRapida">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('nombre_categoria_nueva') border-red-500 @enderror"
                                   wire:model="nombre_categoria_nueva"
                                   placeholder="Ej: Mantenimiento General"
                                   required>
                            @error('nombre_categoria_nueva')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Color
                            </label>
                            <input type="color"
                                   class="w-full h-10 border border-gray-300 rounded-lg @error('color_categoria_nueva') border-red-500 @enderror"
                                   wire:model="color_categoria_nueva">
                            @error('color_categoria_nueva')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="button"
                                    wire:click="cerrarModalCategoria"
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-save mr-1"></i>
                                Crear
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @this.on('ver-comprobante', (ruta) => {
                console.log('Ver comprobante:', ruta);
            });
        });
    </script>
</div>
