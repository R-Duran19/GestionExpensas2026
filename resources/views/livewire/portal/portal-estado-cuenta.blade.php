<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Estado de Cuenta</h1>
    </div>

    @if ($this->unidades->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
            <i class="fa-solid fa-door-closed text-3xl mb-3 text-gray-300"></i>
            <p>No tienes unidades asignadas actualmente.</p>
        </div>
    @else
        {{-- Selector de unidad --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Unidad</label>
            <select wire:model.live="unidadId"
                    class="w-full sm:w-72 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @foreach ($this->unidades as $u)
                    <option value="{{ $u->id }}">{{ $u->codigo }} — {{ $u->tipo_unidad->value }} ({{ $u->metros_cuadrados }} m²)</option>
                @endforeach
            </select>
        </div>

        @if ($this->unidad)
            {{-- Resumen --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-xs text-gray-500 mb-1">Total generado</div>
                    <div class="text-xl font-bold text-gray-800">Bs {{ number_format($this->totales['total_generado'], 2) }}</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-xs text-gray-500 mb-1">Total pagado</div>
                    <div class="text-xl font-bold text-emerald-600">Bs {{ number_format($this->totales['total_pagado'], 2) }}</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-xs text-gray-500 mb-1">Deuda pendiente</div>
                    <div class="text-xl font-bold {{ $this->totales['total_pendiente'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        Bs {{ number_format($this->totales['total_pendiente'], 2) }}
                    </div>
                    <div class="text-[11px] text-gray-400 mt-1">
                        Expensa: Bs {{ number_format($this->totales['pendiente_expensa'], 2) }} ·
                        Agua: Bs {{ number_format($this->totales['pendiente_agua'], 2) }}
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-xs text-gray-500 mb-1">Saldo a tu favor</div>
                    <div class="text-xl font-bold text-emerald-600">Bs {{ number_format($this->saldoFavor, 2) }}</div>
                </div>
            </div>

            {{-- Tabla de períodos --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Período</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Expensa</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Agua</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Pagado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Pendiente</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Boleta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($this->expensas as $exp)
                                <tr wire:key="exp-{{ $exp->id }}" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-800">
                                        {{ sprintf('%02d/%d', $exp->periodo->mes, $exp->periodo->año) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">Bs {{ number_format((float) $exp->monto_expensa, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">
                                        {{ $exp->monto_agua > 0 ? 'Bs ' . number_format((float) $exp->monto_agua, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-800">Bs {{ number_format((float) $exp->monto_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-600">Bs {{ number_format($exp->total_pagado, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $exp->total_pendiente > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                        Bs {{ number_format($exp->total_pendiente, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs px-2 py-1 rounded-full
                                            {{ $exp->estado_color === 'green' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                            {{ $exp->estado_color === 'red' ? 'bg-red-100 text-red-700' : '' }}
                                            {{ $exp->estado_color === 'yellow' ? 'bg-amber-100 text-amber-700' : '' }}">
                                            {{ $exp->estado_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('portal.boleta', $exp->periodo_id) }}"
                                           target="_blank"
                                           class="text-indigo-600 hover:text-indigo-800" title="Descargar boleta PDF">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                        No hay expensas registradas para esta unidad.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-gray-100">
                    {{ $this->expensas->links() }}
                </div>
            </div>
        @endif
    @endif
</div>
