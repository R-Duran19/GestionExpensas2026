<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ $this->titulo }}</h1>
        <p class="text-gray-600 mt-1">
            @if($this->esEdicion)
                Editar información de la unidad
            @else
                Registrar una nueva unidad en el sistema
            @endif
        </p>
    </div>

    {{-- Formulario --}}
    <div class="bg-white rounded-lg shadow p-6">
        <form wire:submit.prevent="guardar">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Código --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                    <input type="text"
                           wire:model.live="codigo"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                           placeholder="Ej: DEP-11-A, OF-3-A">
                    @error('codigo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tipo de Unidad --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Unidad *</label>
                    <select wire:model.live="tipo_unidad"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($this->tiposUnidad as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('tipo_unidad')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Piso / Ubicación --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Piso / Ubicación *</label>
                    <input type="text"
                           wire:model.live="piso"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: Planta 11, Sótano 4">
                    @error('piso')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Metros Cuadrados --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Metros Cuadrados *</label>
                    <input type="number"
                           step="0.01"
                           wire:model.live="metros_cuadrados"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: 85.50">
                    @error('metros_cuadrados')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Categoría --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                    <select wire:model.live="categoria_unidad"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @if($tipo_unidad && $tipo_unidad !== '') disabled @endif>
                        <option value="">Seleccione...</option>
                        @foreach($this->categorias as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($tipo_unidad && $tipo_unidad !== '')
                        <p class="mt-1 text-xs text-gray-500">
                            Autoseleccionado según tipo de unidad
                        </p>
                    @endif
                    @error('categoria_unidad')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tiene Medidor --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">¿Tiene Medidor de Agua?</label>
                    <div class="flex items-center h-full pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   wire:model.live="tiene_medidor"
                                   class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-900">
                                {{ $tiene_medidor ? 'Sí' : 'No' }}
                            </span>
                        </label>
                    </div>
                    @if($tipo_unidad && $tipo_unidad !== '')
                        <p class="mt-1 text-xs text-gray-500">
                            Autoseleccionado según tipo de unidad
                        </p>
                    @endif
                </div>

                {{-- Observaciones --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea wire:model.live="observaciones"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Información adicional sobre la unidad..."></textarea>
                    @error('observaciones')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Información del factor de prorrateo --}}
            @if($categoria_unidad)
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Factor de Prorrateo</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Esta unidad tendrá un factor de prorrateo de <strong>{{ \App\Enums\CategoriaUnidad::from($categoria_unidad)->getFactorProrrateo() }}</strong></p>
                                <p class="text-xs mt-1">Domiciliaria: 2.1 | Comercial: 3.5</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Botones --}}
            <div class="mt-6 flex justify-end gap-3">
                <button type="button"
                        wire:click="cancelar"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $this->esEdicion ? 'Guardar Cambios' : 'Crear Unidad' }}
                </button>
            </div>
        </form>
    </div>
</div>
