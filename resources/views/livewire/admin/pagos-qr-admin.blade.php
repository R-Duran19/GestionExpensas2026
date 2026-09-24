<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fa-solid fa-qrcode mr-2 text-indigo-500"></i>Pagos QR
        </h1>
        <div class="w-48">
            <select wire:model.live="filtroEstado"
                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendientes</option>
                <option value="pagado">Pagados</option>
                <option value="expirado">Expirados</option>
                <option value="cancelado">Cancelados</option>
                <option value="pagado_monto_divergente">⚠️ Divergentes</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Alias</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pagador</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Unidad</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Monto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ingreso</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vence</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($this->pagos as $qr)
                        <tr wire:key="qr-{{ $qr->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $qr->alias }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $qr->user?->persona?->nombre_completo ?? $qr->user?->name }}
                                <span class="text-xs text-gray-400 block capitalize">{{ $qr->pagador_tipo }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $qr->unidad?->codigo ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">Bs {{ number_format((float) $qr->monto, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $colores = [
                                        'pendiente' => 'bg-blue-100 text-blue-700',
                                        'pagado' => 'bg-emerald-100 text-emerald-700',
                                        'expirado' => 'bg-gray-100 text-gray-600',
                                        'cancelado' => 'bg-yellow-100 text-yellow-700',
                                        'pagado_monto_divergente' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="text-xs px-2 py-1 rounded-full {{ $colores[$qr->estado] ?? 'bg-gray-100' }}">
                                    {{ $qr->estado }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($qr->ingreso_id)
                                    <span class="font-mono text-xs text-indigo-600">#{{ $qr->ingreso_id }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $qr->fecha_vencimiento->format('d/m H:i') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($qr->estado === PagoQr::ESTADO_PENDIENTE)
                                    <button wire:click="verificarConBanco({{ $qr->id }})"
                                            wire:loading.attr="disabled"
                                            class="text-xs px-3 py-1.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-md hover:bg-indigo-100">
                                        <i class="fa-solid fa-rotate mr-1"></i> Verificar
                                    </button>
                                @elseif ($qr->estado === PagoQr::ESTADO_DIVERGENTE)
                                    <button wire:click="acreditarDivergente({{ $qr->id }}, 'Revisión manual admin')"
                                            wire:confirm="¿Acreditar este pago divergente por el monto del QR (Bs {{ number_format((float) $qr->monto, 2) }})? La acción queda auditada."
                                            class="text-xs px-3 py-1.5 bg-red-50 text-red-700 border border-red-200 rounded-md hover:bg-red-100">
                                        <i class="fa-solid fa-gavel mr-1"></i> Acreditar
                                    </button>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                                No hay pagos QR{{ $filtroEstado ? " con estado \"{$filtroEstado}\"" : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $this->pagos->links() }}
        </div>
    </div>
</div>
