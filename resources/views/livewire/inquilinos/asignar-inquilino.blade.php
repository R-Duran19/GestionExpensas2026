<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ $inquilino_id ? route('inquilinos.show', $inquilino_id) : route('inquilinos.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Asignar Unidades a Inquilino</h1>
        </div>
        @if($inquilino)
            <p class="text-gray-600">Inquilino: <span class="font-medium">{{ $this->nombreInquilino }}</span></p>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Formulario de asignación --}}
        <div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Seleccionar Unidades</h2>
                <form wire:submit.prevent="asignar">
                    <div class="space-y-4">
                        {{-- Inquilino --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Inquilino *</label>
                            @if($inquilino)
                                <input type="text" value="{{ $this->nombreInquilino }}" disabled
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                            @else
                                <select wire:model.live="inquilino_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Seleccione un inquilino</option>
                                    @foreach(\App\Models\Inquilino::activos()->orderBy('nombre')->get() as $inq)
                                        <option value="{{ $inq->id }}">{{ $inq->nombre_completo }} ({{ $inq->ci }})</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('inquilino_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Lista de unidades disponibles (checkboxes) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Unidades a asignar *
                                <span class="text-gray-500 font-normal">({{ count($unidad_ids) }} seleccionadas)</span>
                            </label>
                            @if(!$inquilino_id)
                                <p class="text-sm text-gray-500 italic">Seleccione primero un inquilino</p>
                            @elseif($unidadesDisponibles->isEmpty())
                                <p class="text-sm text-gray-500 italic">No hay unidades disponibles</p>
                            @else
                                <div class="border rounded-lg p-4 max-h-64 overflow-y-auto space-y-2 bg-gray-50">
                                    @foreach($unidadesDisponibles as $u)
                                        @if(!in_array($u->id, $unidadesYaAsignadas))
                                            <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer transition">
                                                <input type="checkbox"
                                                       value="{{ $u->id }}"
                                                       wire:model.live="unidad_ids"
                                                       class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500">
                                                <span class="ml-3">
                                                    <span class="font-medium text-gray-900">{{ $u->nombre_completo }}</span>
                                                    <span class="text-xs text-gray-500 ml-2">
                                                        {{ $u->tipo_unidad->getLabel() }} · Piso {{ $u->piso }}
                                                    </span>
                                                </span>
                                            </label>
                                        @else
                                            <div class="flex items-center p-2 bg-green-50 rounded opacity-60">
                                                <input type="checkbox" checked disabled class="h-4 w-4 text-green-600 rounded">
                                                <span class="ml-3 text-gray-600 line-through">
                                                    {{ $u->nombre_completo }} (ya asignada)
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            @error('unidad_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Fecha de inicio --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio *</label>
                            <input type="date"
                                   wire:model.live="fecha_inicio"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('fecha_inicio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Monto de alquiler --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto de Alquiler (referencial)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">$</span>
                                <input type="number"
                                       wire:model.live="monto_alquiler"
                                       step="0.01"
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                            </div>
                            @error('monto_alquiler') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            <p class="text-xs text-gray-500 mt-1">Monto referencial del contrato de alquiler (se aplicará a todas las unidades seleccionadas)</p>
                        </div>

                        {{-- Observaciones --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                            <textarea wire:model.live="observaciones"
                                      rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Comentarios sobre el contrato de alquiler..."></textarea>
                            @error('observaciones') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" wire:click="cancelar" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancelar</button>
                        @if($inquilino_id)
                            <button type="button" wire:click="verInquilino" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Ver Inquilino
                            </button>
                        @endif
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
                                @if(!$inquilino_id || count($unidad_ids) === 0) disabled @endif>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Asignar {{ count($unidad_ids) > 0 ? count($unidad_ids) : 'Unidades' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Unidades ya asignadas --}}
        <div>
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-medium text-gray-900">Unidades Asignadas</h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                        {{ $this->unidadesInquilino->count() }} unidad(es)
                    </span>
                </div>
                <div class="p-6">
                    @if($this->unidadesInquilino->count() > 0)
                        <div class="space-y-3">
                            @foreach($this->unidadesInquilino as $unidad)
                                <div class="border rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <a href="{{ route('expensas.unidad.detalle', $unidad->id) }}" class="font-medium text-blue-600 hover:text-blue-900">
                                                {{ $unidad->nombre_completo }}
                                            </a>
                                            <p class="text-sm text-gray-600 mt-1">
                                                @if($unidad->pivot->monto_alquiler)
                                                    Alquiler: ${{ number_format($unidad->pivot->monto_alquiler, 2, ',', '.') }}
                                                @else
                                                    Sin monto de alquiler
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Desde: {{ is_string($unidad->pivot->fecha_inicio) ? \Carbon\Carbon::parse($unidad->pivot->fecha_inicio)->format('d/m/Y') : $unidad->pivot->fecha_inicio?->format('d/m/Y') }}
                                            </p>
                                            @if($unidad->pivot->observaciones)
                                                <p class="text-xs text-gray-600 mt-1">{{ $unidad->pivot->observaciones }}</p>
                                            @endif
                                        </div>
                                        <div class="flex gap-2 ml-4">
                                            <a href="{{ route('expensas.unidad.detalle', $unidad->id) }}" class="text-gray-600 hover:text-gray-900" title="Ver unidad">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            <button onclick="confirmarDesasignar({{ $unidad->id }}, '{{ $unidad->codigo }}')"
                                                    class="text-red-600 hover:text-red-900" title="Desasignar unidad">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Este inquilino no tiene unidades asignadas aún</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmarDesasignar(unidadId, unidadCodigo) {
            if (confirm('¿Desasignar esta unidad del inquilino? Esta acción finalizará el alquiler.')) {
                @this.call('desasignarUnidad', unidadId);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            @this.on('toast-success', (e) => alert(e.detail.message));
            @this.on('toast-error', (e) => alert(e.detail.message));
        });
    </script>
</div>
