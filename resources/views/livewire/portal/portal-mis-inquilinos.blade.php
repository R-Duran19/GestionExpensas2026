<div>
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Mis Inquilinos</h1>
    <p class="text-sm text-gray-500 mb-6">
        Autoriza a tus inquilinos para que puedan consultar el estado de cuenta de su unidad y
        realizar pagos desde el portal. Puedes revocar el acceso en cualquier momento.
    </p>

    @if ($this->alquiladas->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
            <i class="fa-solid fa-key text-3xl mb-3 text-gray-300"></i>
            <p>No tienes unidades alquiladas actualmente.</p>
            <p class="text-sm text-gray-400 mt-1">Cuando registres un inquilino aparecerá aquí para autorizarlo.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->alquiladas as $fila)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5" wire:key="alq-{{ $fila->unidad->id }}">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-gray-800">
                                    <i class="fa-solid fa-door-open mr-1 text-indigo-400"></i> {{ $fila->unidad->codigo }}
                                </span>
                                <span class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-600">
                                    {{ $fila->unidad->tipo_unidad->value }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <i class="fa-solid fa-user mr-1 {{ $fila->autorizado ? 'text-emerald-500' : 'text-blue-500' }}"></i>
                                Inquilino: <strong>{{ $fila->inquilino?->nombre_completo ?? '—' }}</strong>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium
                                {{ $fila->autorizado ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $fila->autorizado ? 'Autorizado' : 'Sin acceso' }}
                            </span>

                            @if ($fila->autorizado)
                                <button wire:click="toggleAutorizacion({{ $fila->unidad->id }})"
                                        wire:confirm="¿Revocar el acceso al portal de {{ $fila->inquilino?->nombre_completo }}? Perderá la visibilidad de esta unidad de inmediato."
                                        class="px-4 py-2 text-sm bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition">
                                    <i class="fa-solid fa-ban mr-1"></i> Revocar
                                </button>
                            @else
                                <button wire:click="toggleAutorizacion({{ $fila->unidad->id }})"
                                        class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                    <i class="fa-solid fa-check mr-1"></i> Autorizar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-6 bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-700">
        <i class="fa-solid fa-circle-info mr-1"></i>
        Después de autorizar, comunícate con el administrador del edificio para que cree la
        <strong>cuenta del inquilino</strong> (usuario = su CI). Sin autorización previa, el
        administrador no le creará acceso.
    </div>
</div>
