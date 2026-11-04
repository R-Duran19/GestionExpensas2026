<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600">Dashboard</a>
            <span class="text-gray-300">/</span> Morosidad
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- KPIs --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-sm text-gray-500">Morosidad global</div>
                    <div class="text-3xl font-bold text-red-600">{{ $porcentaje['porcentaje'] }}%</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-sm text-gray-500">Deuda pendiente total</div>
                    <div class="text-3xl font-bold text-amber-600">Bs {{ number_format($porcentaje['pendiente'], 0, ',', '.') }}</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-sm text-gray-500">Unidades con deuda</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $morosos->count() }}</div>
                </div>
            </div>

            {{-- Buckets por antigüedad (gráfico de barras) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-layer-group mr-1 text-red-400"></i> Deuda por antigüedad de mora</h3>
                <div class="relative" style="height: 240px">
                    <canvas id="chartBuckets"></canvas>
                </div>
            </div>

            {{-- Los más antiguos --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-hourglass-end mr-1 text-amber-400"></i> 15 moras más antiguas</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 border-b">
                                <th class="pb-2">#</th>
                                <th class="pb-2">Unidad</th>
                                <th class="pb-2">Responsable</th>
                                <th class="pb-2">Deuda desde</th>
                                <th class="pb-2 text-right">Meses de mora</th>
                                <th class="pb-2 text-right">Deuda total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($masAntiguos as $i => $m)
                                <tr class="border-b border-gray-50 {{ $loop->first ? 'bg-amber-50' : '' }}">
                                    <td class="py-2 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="py-2 font-medium text-gray-800">{{ $m['codigo'] }}</td>
                                    <td class="py-2 text-gray-600">{{ $m['responsable'] }}
                                        <span class="text-[10px] uppercase {{ $m['tipo_responsable'] === 'inquilino' ? 'text-blue-400' : 'text-emerald-400' }}">{{ $m['tipo_responsable'] }}</span>
                                    </td>
                                    <td class="py-2 text-gray-500">{{ $m['primer_periodo'] }}</td>
                                    <td class="py-2 text-right font-semibold text-amber-600">{{ $m['meses_mora'] }}</td>
                                    <td class="py-2 text-right font-semibold text-red-600">Bs {{ number_format($m['deuda'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Listado completo (ordenado por monto) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-list mr-1 text-gray-400"></i> Todas las unidades con deuda ({{ $morosos->count() }}), por monto</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 border-b">
                                <th class="pb-2">#</th>
                                <th class="pb-2">Unidad</th>
                                <th class="pb-2">Responsable</th>
                                <th class="pb-2">Desde</th>
                                <th class="pb-2 text-right">Meses</th>
                                <th class="pb-2 text-right">Deuda (Bs)</th>
                                <th class="pb-2 text-right">Consumo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($morosos as $i => $m)
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="py-1.5 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="py-1.5 font-medium text-gray-800">
                                        <a href="{{ route('metricas.consumo.unidad', $m['unidad_id']) }}" class="text-indigo-600 hover:underline">{{ $m['codigo'] }}</a>
                                    </td>
                                    <td class="py-1.5 text-gray-600">{{ $m['responsable'] }}</td>
                                    <td class="py-1.5 text-gray-500">{{ $m['primer_periodo'] }}</td>
                                    <td class="py-1.5 text-right text-amber-600">{{ $m['meses_mora'] }}</td>
                                    <td class="py-1.5 text-right font-semibold text-red-600">{{ number_format($m['deuda'], 2, ',', '.') }}</td>
                                    <td class="py-1.5 text-right text-xs">
                                        <a href="{{ route('metricas.consumo.unidad', $m['unidad_id']) }}" class="text-gray-400 hover:text-indigo-600"><i class="fa-solid fa-chart-line"></i> ver consumo</a>
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
                const data = @json($buckets);
                new Chart(document.getElementById('chartBuckets'), {
                    type: 'bar',
                    data: {
                        labels: data.map(b => b.etiqueta),
                        datasets: [
                            { label: 'Deuda (Bs)', data: data.map(b => b.monto), backgroundColor: ['#F59E0B', '#FB923C', '#EF4444', '#B91C1C'], borderRadius: 4, yAxisID: 'y' },
                            { label: 'Unidades', data: data.map(b => b.unidades), type: 'line', borderColor: '#4F46E5', backgroundColor: '#4F46E5', yAxisID: 'y1', tension: 0.2 },
                        ]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
                        scales: {
                            y: { beginAtZero: true, type: 'linear', position: 'left', title: { display: true, text: 'Bs' } },
                            y1: { beginAtZero: true, type: 'linear', position: 'right', title: { display: true, text: 'Unidades' }, grid: { drawOnChartArea: false } },
                        }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
