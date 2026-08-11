<div>
    <div class="mb-6">
        <a href="{{ route('configuracion.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a Configuración
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">🏢 Datos del Edificio</h1>
                <p class="text-sm text-gray-600 mt-1">
                    @if($existe)
                        Editando configuración existente
                    @else
                        Creando configuración inicial (no existe ningún registro aún)
                    @endif
                </p>
            </div>
        </div>
    </div>

    @if(!$existe)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Primera configuración</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Hasta ahora los PDFs usaban valores por defecto. Al guardar este formulario, los datos que ingreses aparecerán en los PDFs de expensas, recibos y comprobantes.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form wire:submit="guardar" class="space-y-6">
        <!-- Datos básicos -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Datos Básicos</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Edificio <span class="text-red-600">*</span></label>
                    <input type="text" wire:model.live="nombre_edificio"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('nombre_edificio') border-red-500 @enderror">
                    @error('nombre_edificio') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <input type="text" wire:model.live="direccion"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" wire:model.live="telefono"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" wire:model.live="email"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('email') border-red-500 @enderror">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CUIT / NIT</label>
                    <input type="text" wire:model.live="cuit"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input type="text" wire:model.live="ciudad"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Provincia / Departamento</label>
                    <input type="text" wire:model.live="provincia"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
                    <input type="text" wire:model.live="codigo_postal"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Mensaje de notas para PDFs -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-2">Mensaje para los PDFs</h2>
            <p class="text-sm text-gray-600 mb-4">
                Este texto aparecerá en la sección "Notas" de los PDFs de expensas, recibos y comprobantes.
                Si lo dejás vacío, se mostrará el mensaje por defecto (Banco BISA).
            </p>

            <label class="block text-sm font-medium text-gray-700 mb-1">Texto de Notas</label>
            <textarea wire:model.live="informacion_adicional" rows="6"
                      placeholder="Ej: ESTIMADOS SEÑORES COPROPIETARIOS E INQUILINOS, LES RECORDAMOS QUE PUEDEN HACER SUS PAGOS EN EL BANCO..."
                      class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-mono">{{ $informacion_adicional }}</textarea>

            <p class="text-xs text-gray-500 mt-2">
                💡 Los saltos de línea se respetan en el PDF.
            </p>
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('configuracion.index') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                <svg wire:loading.remove wire:target="guardar" class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg wire:loading wire:target="guardar" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>@if($existe) Guardar Cambios @else Crear Configuración @endif</span>
            </button>
        </div>
    </form>

    <script>
        @this.on('alerta', (data) => {
            alert(`${data.titulo}: ${data.mensaje}`);
        });
    </script>
</div>
