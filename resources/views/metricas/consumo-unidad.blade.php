<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('metricas.consumo') }}" class="text-gray-400 hover:text-gray-600">Consumo</a>
            <span class="text-gray-300">/</span> {{ $unidad->codigo }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- KPIs de la unidad --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-sm text-gray-500">Último consumo</div>
                    <div class="text-3xl font-bold text-blue-600">{{ $historico['ultimo_m3'] }} m³</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-sm text-gray-500">Promedio propio (histórico)</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $historico['promedio_propio'] }} m³</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-sm text-gray-500">Último período vs edificio</div>
                    <div class="text-3xl font-bold {{ ($historico['vs_edificio_ultimo'] ?? 1) > 1.2 ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ $historico['vs_edificio_ultimo'] ? '×' . $historico['vs_edificio_ultimo'] : '—' }}
                    </div>
                    <div class="text-xs text-gray-400">×1.0 = igual que el promedio del edificio</div>
                </div>
            </div>

            {{-- Gráfico histórico --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-700 mb-3">
                    <i class="fa-solid fa-chart-column mr-1 text-blue-400"></i>
                    Histórico de consumo por medidor — {{ $unidad->codigo }}
                    <span class="text-xs font-normal text-gray-400">({{ $unidad->categoria_unidad->getLabel() }})</span>
                </h3>
                <div class="relative" style="height: 300px">
                    <canvas id="chartUnidad"></canvas>
                </div>
                @if (count($historico['serie']) === 0)
                    <p class="text-sm text-gray-400 text-center mt-2">Sin lecturas registradas para esta unidad.</p>
                @endif
            </div>

            {{-- Tabla detalle --}}
            @if (count($historico['serie']) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-table mr-1 text-gray-400"></i> Detalle por período</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-400 border-b">
                                    <th class="pb-2">Período</th>
                                    <th class="pb-2 text-right">Consumo propio (m³)</th>
                                    <th class="pb-2 text-right">Promedio edificio (m³/unidad)</th>
                                    <th class="pb-2 text-right">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($historico['serie'] as $p)
                                    @php $dif = round($p['m3'] - $p['promedio_edificio'], 2); @endphp
                                    <tr class="border-b border-gray-50">
                                        <td class="py-2 font-medium text-gray-800">{{ $p['periodo'] }}</td>
                                        <td class="py-2 text-right font-semibold text-blue-600">{{ $p['m3'] }}</td>
                                        <td class="py-2 text-right text-gray-500">{{ $p['promedio_edificio'] }}</td>
                                        <td class="py-2 text-right {{ $dif > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $dif > 0 ? '+' : '' }}{{ $dif }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <a href="{{ route('metricas.consumo') }}" class="inline-flex items-center text-sm text-indigo-600 hover:underline">
                <i class="fa-solid fa-arrow-left mr-1"></i> Volver a Consumo de agua
            </a>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const serie = @json($historico['serie']);
                if (!serie.length) return;
                new Chart(document.getElementById('chartUnidad'), {
                    type: 'bar',
                    data: {
                        labels: serie.map(p => p.periodo),
                        datasets: [
                            { label: 'Consumo propio (m³)', data: serie.map(p => p.m3), backgroundColor: '#0EA5E9', borderRadius: 4 },
                            { label: 'Promedio edificio (m³/unidad)', data: serie.map(p => p.promedio_edificio), type: 'line', borderColor: '#9CA3AF', backgroundColor: '#9CA3AF', tension: 0.2, borderDash: [5, 5], pointRadius: 3 },
                        ]
                    },
                    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'm³' } } } }
                });
            });
        </script>
    @endpush
</x-app-layout>
