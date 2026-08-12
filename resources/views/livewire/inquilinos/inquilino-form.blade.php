<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ $this->titulo }}</h1>
        <p class="text-gray-600 mt-1">
            @if($this->esEdicion) Editar @else Registrar nuevo @endif inquilino
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form wire:submit.prevent="guardar">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Nombre --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text"
                           wire:model.live="nombre"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Juan">
                    @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Apellido --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                    <input type="text"
                           wire:model.live="apellido"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Pérez">
                    @error('apellido') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- CI --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cédula de Identidad *</label>
                    <input type="text"
                           wire:model.live="ci"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                           placeholder="1234567">
                    @error('ci') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Teléfono --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                    <input type="text"
                           wire:model.live="telefono"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="71234567">
                    @error('telefono') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email"
                           wire:model.live="email"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="juan.perez@email.com">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Observaciones --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea wire:model.live="observaciones"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Información adicional..."></textarea>
                    @error('observaciones') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Botones --}}
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" wire:click="cancelar" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $this->esEdicion ? 'Guardar Cambios' : 'Crear Inquilino' }}
                </button>
            </div>
        </form>
    </div>
</div>
