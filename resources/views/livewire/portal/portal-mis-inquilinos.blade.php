<div>
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Mis Inquilinos</h1>
    <p class="text-sm text-gray-500 mb-6">
        Autoriza a tus inquilinos para que puedan consultar el estado de cuenta de sus unidades y
        realizar pagos desde el portal. La autorización aplica a <strong>todas las propiedades</strong>
        asignadas a cada inquilino; puedes revocarla en cualquier momento.
    </p>

    @if ($this->grupos->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
            <i class="fa-solid fa-key text-3xl mb-3 text-gray-300"></i>
            <p>No tienes unidades alquiladas actualmente.</p>
            <p class="text-sm text-gray-400 mt-1">Cuando registres un inquilino aparecerá aquí para autorizarlo.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->grupos as $grupo)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
                     wire:key="grupo-{{ $grupo->inquilino->id }}">
                    {{-- Cabecera del inquilino --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 bg-gray-50 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-indigo-100 flex items-center justify-center">
                                <i class="fa-solid fa-user {{ $grupo->autorizado ? 'text-emerald-600' : 'text-blue-600' }}"></i>
                            </div>
                            <div>
                                <h2 class="font-semibold text-gray-900">{{ $grupo->inquilino->nombre_completo }}</h2>
                                <p class="text-xs text-gray-500">
                                    {{ $grupo->unidades->count() }} propiedad(es) asignada(s)
                                    @if ($grupo->autorizado)
                                        · <span class="text-emerald-600 font-medium">acceso autorizado</span>
                                    @elseif ($grupo->alguno)
                                        · <span class="text-amber-600 font-medium">autorización parcial</span>
                                    @else
                                        · <span class="text-gray-400">sin acceso</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($grupo->autorizado)
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-emerald-100 text-emerald-700">Autorizado</span>
                                <button wire:click="toggleAutorizacionGrupo({{ $grupo->inquilino->id }})"
                                        wire:confirm="¿Revocar el acceso al portal de {{ $grupo->inquilino->nombre_completo }} en TODAS sus propiedades ({{ $grupo->unidades->count() }})? Perderá la visibilidad de inmediato."
                                        class="px-4 py-2 text-sm bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition">
                                    <i class="fa-solid fa-ban mr-1"></i> Revocar acceso
                                </button>
                            @else
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $grupo->alguno ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $grupo->alguno ? 'Parcial' : 'Sin acceso' }}
                                </span>
                                <button wire:click="toggleAutorizacionGrupo({{ $grupo->inquilino->id }})"
                                        class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                    <i class="fa-solid fa-check mr-1"></i>
                                    Autorizar acceso ({{ $grupo->unidades->count() }})
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Propiedades del inquilino --}}
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($grupo->unidades as $fila)
                            <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg px-3 py-2"
                                 wire:key="uni-{{ $fila->unidad->id }}">
                                <span class="text-sm text-gray-800">
                                    <i class="fa-solid fa-door-open mr-1 text-indigo-400"></i>
                                    <strong>{{ $fila->unidad->codigo }}</strong>
                                    <span class="text-gray-400 text-xs ml-1">{{ $fila->unidad->tipo_unidad->value }}</span>
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $fila->autorizado ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $fila->autorizado ? '✓' : '—' }}
                                </span>
                            </div>
                        @endforeach
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
