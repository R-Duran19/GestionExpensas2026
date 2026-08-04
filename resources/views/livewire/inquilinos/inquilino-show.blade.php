<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('inquilinos.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Detalles del Inquilino</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Información personal --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Información Personal</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">Nombre completo</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $inquilino->nombre_completo }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">CI</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $inquilino->ci }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">Teléfono</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $inquilino->telefono ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">Email</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $inquilino->email ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">Estado</span>
                            <p class="text-sm font-medium mt-1">
                                @if($inquilino->activo)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactivo</span>
                                @endif
                            </p>
                        </div>
                        @if($inquilino->observaciones)
                            <div>
                                <span class="text-xs text-gray-500 uppercase tracking-wider">Observaciones</span>
                                <p class="text-sm text-gray-700 mt-1">{{ $inquilino->observaciones }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Acciones --}}
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('inquilinos.asignar-unidades', $inquilino->id) }}"
                               class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Asignar Unidades
                            </a>
                            <a href="{{ route('inquilinos.edit', $inquilino->id) }}"
                               class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Unidades asignadas --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-medium text-gray-900">Unidades Asignadas</h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        {{ $inquilino->unidadesActuales->count() }} unidad(es)
                    </span>
                </div>
                <div class="p-6">
                    @if($inquilino->unidadesActuales->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Piso</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">M²</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alquiler</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Desde</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($inquilino->unidadesActuales as $unidad)
                                        <tr class="hover:bg-gray-50" wire:key="unidad-{{ $unidad->id }}">
                                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                                <a href="{{ route('expensas.unidad.detalle', $unidad->id) }}" class="text-blue-600 hover:text-blue-900">
                                                    {{ $unidad->codigo }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ $unidad->tipo_unidad->getLabel() }}</td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ $unidad->piso }}</td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ $unidad->metros_cuadrados }}</td>
                                            <td class="px-4 py-4 text-sm text-gray-600">
                                                @if($unidad->pivot->monto_alquiler)
                                                    $ {{ number_format($unidad->pivot->monto_alquiler, 2, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-600">
                                                @if($unidad->pivot->fecha_inicio)
                                                    {{ is_string($unidad->pivot->fecha_inicio)
                                                        ? \Carbon\Carbon::parse($unidad->pivot->fecha_inicio)->format('d/m/Y')
                                                        : $unidad->pivot->fecha_inicio->format('d/m/Y') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <button onclick="confirmarDesasignar({{ $unidad->id }}, '{{ $unidad->codigo }}')"
                                                        class="text-red-600 hover:text-red-900" title="Desasignar unidad">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Este inquilino no tiene unidades asignadas</p>
                            <a href="{{ route('inquilinos.asignar-unidades', $inquilino->id) }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Asignar Unidades
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmarDesasignar(unidadId, unidadCodigo) {
        if (confirm('¿Desasignar la unidad ' + unidadCodigo + ' del inquilino? Esta acción finalizará el alquiler.')) {
            @this.call('desasignarUnidad', unidadId);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        @this.on('toast-success', (e) => alert(e.detail.message));
        @this.on('toast-error', (e) => alert(e.detail.message));
    });
</script>
