<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600">Dashboard</a>
            <span class="text-gray-300">/</span> Consumo de agua
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Consumo por período: medidores vs EPSAS --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-chart-column mr-1 text-blue-400"></i> Consumo por período: medidores vs EPSAS (m³)</h3>
                <div class="relative" style="height: 260px">
                    <canvas id="chartConsumoPeriodo"></canvas>
                </div>
                @if ($consumoPeriodo['pico'])
                    <p class="text-xs text-gray-400 mt-2">
                        <i class="fa-solid fa-arrow-trend-up mr-1"></i> Pico: <strong>{{ $consumoPeriodo['pico']['periodo'] }}</strong>
                        con {{ $consumoPeriodo['pico']['medidores'] }} m³ medidos · EPSAS facturó {{ $consumoPeriodo['pico']['epsas'] }} m³
                        · <span class="text-amber-600">Pérdida no medida (últ. período): {{ $consumoPeriodo['perdida_ultimo'] }} m³</span>
                    </p>
                @endif
            </div>

            {{-- Anomalías (posibles fugas) --}}
            <div id="anomalias" class="bg-white rounded-xl shadow-sm border border-amber-200 p-5">
                <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-triangle-exclamation mr-1 text-amber-500"></i>
                    Anomalías de consumo (posibles fugas) — <span class="text-amber-600">{{ $anomalias->count() }}</span> unidades cuyo último consumo supera +50% su promedio
                </h3>
                @if ($anomalias->isEmpty())
                    <p class="text-sm text-emerald-600"><i class="fa-solid fa-check mr-1"></i> Sin anomalías detectadas en este momento.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-400 border-b">
                                    <th class="pb-2">Unidad</th>
                                    <th class="pb-2 text-right">Promedio histórico (m³)</th>
                                    <th class="pb-2 text-right">Último período (m³)</th>
                                    <th class="pb-2 text-right">Ratio</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($anomalias as $a)
                                    <tr class="border-b border-gray-50 {{ $a['ratio'] >= 3 ? 'bg-red-50' : 'bg-amber-50' }}">
                                        <td class="py-2 font-medium text-gray-800">{{ $a['codigo'] }}</td>
                                        <td class="py-2 text-right text-gray-600">{{ $a['promedio_m3'] }}</td>
                                        <td class="py-2 text-right font-semibold text-gray-800">{{ $a['ultimo_m3'] }}</td>
                                        <td class="py-2 text-right font-bold {{ $a['ratio'] >= 3 ? 'text-red-600' : 'text-amber-600' }}">×{{ $a['ratio'] }}</td>
                                        <td class="py-2 text-right">
                                            <a href="{{ route('metricas.consumo.unidad', $a['unidad_id']) }}" class="text-xs text-indigo-600 hover:underline">ver histórico</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Consumo por categoría --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-building mr-1 text-gray-400"></i> Consumo por categoría de unidad</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ($porCategoria as $c)
                        <div class="border border-gray-100 rounded-lg p-4">
                            <div class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $c['categoria']) }} ({{ $c['unidades'] }}u)</div>
                            <div class="text-2xl font-bold text-blue-600">{{ $c['total_m3'] }} m³</div>
                            <div class="text-xs text-gray-400">{{ round($c['total_m3'] / max(1, $c['unidades']), 2) }} m³ promedio por unidad</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Ranking completo --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-ranking-star mr-1 text-blue-400"></i> Ranking completo de consumidores ({{ $topAgua->count() }} unidades)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 border-b">
                                <th class="pb-2">#</th>
                                <th class="pb-2">Unidad</th>
                                <th class="pb-2">Categoría</th>
                                <th class="pb-2 text-right">Total histórico (m³)</th>
                                <th class="pb-2 text-right">Promedio/mes (m³)</th>
                                <th class="pb-2 text-right">Último (m³)</th>
                                <th class="pb-2 text-right">Histórico</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topAgua as $i => $t)
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="py-1.5 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="py-1.5 font-medium text-gray-800">{{ $t['codigo'] }}</td>
                                    <td class="py-1.5 text-gray-500 capitalize text-xs">{{ str_replace('_', ' ', $t['categoria']) }}</td>
                                    <td class="py-1.5 text-right font-semibold text-blue-600">{{ $t['total_m3'] }}</td>
                                    <td class="py-1.5 text-right text-gray-600">{{ $t['promedio_m3'] }}</td>
                                    <td class="py-1.5 text-right text-gray-800">{{ $t['ultimo_m3'] }}</td>
                                    <td class="py-1.5 text-right">
                                        <a href="{{ route('metricas.consumo.unidad', $t['unidad_id']) }}" class="text-xs text-indigo-600 hover:underline"><i class="fa-solid fa-chart-line"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const series = @json($consumoPeriodo['series']);
                new Chart(document.getElementById('chartConsumoPeriodo'), {
                    type: 'bar',
                    data: {
                        labels: series.map(d => d.periodo),
                        datasets: [
                            { label: 'Medidores (m³)', data: series.map(d => d.medidores), backgroundColor: '#0EA5E9', borderRadius: 4 },
                            { label: 'EPSAS (m³)', data: series.map(d => d.epsas), backgroundColor: 'rgba(79,70,229,0.4)', borderRadius: 4 },
                        ]
                    },
                    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, scales: { y: { beginAtZero: true } } }
                });
            });
        </script>
    @endpush
</x-app-layout>
