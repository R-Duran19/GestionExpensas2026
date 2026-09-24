<div>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Mi Perfil</h1>

    {{-- Aviso de cambio obligatorio --}}
    @if ($this->user->must_change_password)
        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
            <strong>Tienes una contraseña temporal.</strong>
            Debes establecer tu propia contraseña para poder usar el portal.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Datos personales (solo lectura) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-700 mb-4">
                <i class="fa-solid fa-id-card mr-1 text-indigo-400"></i> Mis datos
            </h2>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Nombre</dt>
                    <dd class="font-medium text-gray-800">{{ $this->persona?->nombre_completo ?? $this->user->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Carnet de identidad</dt>
                    <dd class="font-medium text-gray-800">{{ $this->persona?->ci ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Teléfono</dt>
                    <dd class="font-medium text-gray-800">{{ $this->persona?->telefono ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Email</dt>
                    <dd class="font-medium text-gray-800">{{ $this->persona?->email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Tipo de acceso</dt>
                    <dd class="font-medium text-gray-800 capitalize">{{ $this->user->tipo }}</dd>
                </div>
            </dl>

            <p class="text-xs text-gray-400 mt-4 border-t pt-3">
                <i class="fa-solid fa-lock mr-1"></i>
                Para corregir tus datos de contacto, comunícate con el administrador del edificio.
            </p>
        </div>

        {{-- Cambio de contraseña --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-700 mb-4">
                <i class="fa-solid fa-key mr-1 text-indigo-400"></i> Cambiar contraseña
            </h2>

            <form wire:submit="cambiarPassword" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña actual</label>
                    <input type="password" wire:model="password_actual" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" autocomplete="current-password" />
                    @error('password_actual') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña nueva</label>
                    <input type="password" wire:model="password" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" autocomplete="new-password" />
                    @error('password') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña nueva</label>
                    <input type="password" wire:model="password_confirmation" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" autocomplete="new-password" />
                    @error('password_confirmation') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg disabled:opacity-50"
                            wire:loading.attr="disabled">
                        <span wire:loading wire:target="cambiarPassword" class="mr-2">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                        </span>
                        Guardar contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
