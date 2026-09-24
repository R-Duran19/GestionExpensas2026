<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Mis Pagos</h1>
        <div class="text-sm text-gray-500">
            Total histórico:
            <span class="font-bold text-emerald-600">Bs {{ number_format($this->totalPagado(), 2) }}</span>
        </div>
    </div>

    {{-- Filtro por método --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago</label>
        <select wire:model.live="filtroMetodo"
                class="w-full sm:w-64 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            <option value="">Todos</option>
            <option value="efectivo">Efectivo</option>
            <option value="transferencia">Transferencia</option>
            <option value="qr">QR</option>
            <option value="otro">Otro</option>
        </select>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Recibo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Unidad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Método</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Monto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Recibo PDF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($this->pagos as $pago)
                        <tr wire:key="pago-{{ $pago->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $pago->numero_recibo }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ \Carbon\Carbon::parse($pago->fecha)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $pago->unidad?->codigo ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full capitalize
                                    {{ $pago->metodo_pago === 'efectivo' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $pago->metodo_pago === 'transferencia' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $pago->metodo_pago === 'qr' ? 'bg-purple-100 text-purple-700' : '' }}
                                    {{ $pago->metodo_pago === 'otro' ? 'bg-gray-100 text-gray-700' : '' }}">
                                    {{ $pago->metodo_pago }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                Bs {{ number_format((float) $pago->monto_total, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('portal.recibo', $pago->id) }}"
                                   target="_blank"
                                   class="text-indigo-600 hover:text-indigo-800" title="Descargar recibo PDF">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                <i class="fa-solid fa-receipt text-3xl mb-3 text-gray-200"></i>
                                <p>No tienes pagos registrados todavía.</p>
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
