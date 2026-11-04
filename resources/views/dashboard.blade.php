<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- ============ PANEL DE MÉTRICAS (resumen, cada bloque con "Ver más") ============ --}}
            <div class="space-y-6">

                {{-- Fila 1: KPIs de morosidad y salud financiera --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="text-sm text-gray-500 mb-1"><i class="fa-solid fa-chart-pie mr-1 text-red-400"></i> Morosidad global</div>
                        <div class="text-3xl font-bold text-red-600">{{ $metrics['morosidad']['porcentaje'] }}%</div>
                        <div class="text-xs text-gray-400 mt-1">Bs {{ number_format($metrics['morosidad']['pendiente'], 0, ',', '.') }} sin cobrar de Bs {{ number_format($metrics['morosidad']['emitido'], 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="text-sm text-gray-500 mb-1"><i class="fa-solid fa-hourglass-half mr-1 text-amber-400"></i> Deuda más antigua</div>
                        @php $masViejo = $metrics['top_morosos']->sortByDesc('meses_mora')->first(); @endphp
                        @if ($masViejo)
                            <div class="text-3xl font-bold text-amber-600">{{ $masViejo['meses_mora'] }} meses</div>
                            <div class="text-xs text-gray-400 mt-1">{{ $masViejo['codigo'] }} · desde {{ $masViejo['primer_periodo'] }}</div>
                        @else
                            <div class="text-3xl font-bold text-emerald-600">Sin deuda</div>
                        @endif
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="text-sm text-gray-500 mb-1"><i class="fa-solid fa-qrcode mr-1 text-indigo-400"></i> Cobrado vía portal QR</div>
                        <div class="text-3xl font-bold text-indigo-600">Bs {{ number_format($metrics['canal']['qr'], 2, ',', '.') }}</div>
                        <div class="text-xs text-gray-400 mt-1">Caja manual: Bs {{ number_format($metrics['canal']['otros'], 2, ',', '.') }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="text-sm text-gray-500 mb-1"><i class="fa-solid fa-piggy-bank mr-1 text-emerald-400"></i> Saldos a favor flotantes</div>
                        <div class="text-3xl font-bold text-emerald-600">Bs {{ number_format($metrics['saldo_favor'], 2, ',', '.') }}</div>
                        <div class="text-xs text-gray-400 mt-1">Crédito pendiente de aplicar</div>
                    </div>
                </div>

                {{-- Fila 2: Morosidad (buckets + top) --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Buckets de mora --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 lg:col-span-1">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-semibold text-gray-700"><i class="fa-solid fa-layer-group mr-1 text-red-400"></i> Deuda por antigüedad</h3>
                            <a href="{{ route('metricas.morosidad') }}" class="text-xs text-indigo-600 hover:underline">Ver más</a>
                        </div>
                        <div class="space-y-2">
                            @php $maxMonto = max(1, collect($metrics['buckets_mora'])->max('monto')); @endphp
                            @foreach ($metrics['buckets_mora'] as $b)
                                <div>
                                    <div class="flex justify-between text-xs mb-0.5">
                                        <span class="text-gray-600">{{ $b['etiqueta'] }} ({{ $b['unidades'] }}u)</span>
                                        <span class="font-medium text-gray-800">Bs {{ number_format($b['monto'], 0, ',', '.') }}</span>
                                    </div>
                                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $loop->last ? 'bg-red-500' : ($loop->iteration === 3 ? 'bg-amber-500' : 'bg-orange-400') }}"
                                             style="width: {{ round($b['monto'] / $maxMonto * 100) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Top 5 morosos --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 lg:col-span-2">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-semibold text-gray-700"><i class="fa-solid fa-user-slash mr-1 text-red-400"></i> Top 5 morosos</h3>
                            <a href="{{ route('metricas.morosidad') }}" class="text-xs text-indigo-600 hover:underline">Ver todos ({{ collect($metrics['buckets_mora'])->sum('unidades') }} unidades)</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-gray-400 border-b">
                                        <th class="pb-2">Unidad</th>
                                        <th class="pb-2">Responsable</th>
                                        <th class="pb-2 text-right">Deuda</th>
                                        <th class="pb-2 text-right">Mora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($metrics['top_morosos'] as $m)
                                        <tr class="border-b border-gray-50">
                                            <td class="py-1.5 font-medium text-gray-800">{{ $m['codigo'] }}</td>
                                            <td class="py-1.5 text-gray-600">
                                                {{ $m['responsable'] }}
                                                <span class="text-[10px] uppercase {{ $m['tipo_responsable'] === 'inquilino' ? 'text-blue-400' : 'text-emerald-400' }}">{{ $m['tipo_responsable'] }}</span>
                                            </td>
                                            <td class="py-1.5 text-right font-semibold text-red-600">Bs {{ number_format($m['deuda'], 2, ',', '.') }}</td>
                                            <td class="py-1.5 text-right text-amber-600">{{ $m['meses_mora'] }} meses</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="py-3 text-center text-emerald-500">¡Sin morosos! 🎉</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Fila 3: Consumo de agua (pico por período + top) --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 lg:col-span-2">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-semibold text-gray-700"><i class="fa-solid fa-chart-column mr-1 text-blue-400"></i> Consumo de agua por período (m³)</h3>
                            <a href="{{ route('metricas.consumo') }}" class="text-xs text-indigo-600 hover:underline">Ver más</a>
                        </div>
                        <div class="relative" style="height: 220px">
                            <canvas id="chartConsumo"></canvas>
                        </div>
                        @if ($metrics['consumo_periodo']['pico'])
                            <p class="text-xs text-gray-400 mt-2">
                                <i class="fa-solid fa-arrow-trend-up mr-1"></i> Pico: <strong>{{ $metrics['consumo_periodo']['pico']['periodo'] }}</strong>
                                con {{ $metrics['consumo_periodo']['pico']['medidores'] }} m³ (medidores) · EPSAS ese mes: {{ $metrics['consumo_periodo']['pico']['epsas'] }} m³
                                · Diferencia no medida (últ. período): {{ $metrics['consumo_periodo']['perdida_ultimo'] }} m³
                            </p>
                        @endif
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-semibold text-gray-700"><i class="fa-solid fa-droplet mr-1 text-blue-400"></i> Top 5 consumo de agua</h3>
                            <a href="{{ route('metricas.consumo') }}" class="text-xs text-indigo-600 hover:underline">Ver todos</a>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse ($metrics['top_agua'] as $t)
                                    <tr class="border-b border-gray-50">
                                        <td class="py-1.5 font-medium text-gray-800">{{ $t['codigo'] }}</td>
                                        <td class="py-1.5 text-right text-gray-500">{{ $t['promedio_m3'] }} m³/mes prom.</td>
                                        <td class="py-1.5 text-right font-semibold text-blue-600">{{ $t['total_m3'] }} m³</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-3 text-center text-gray-400">Sin lecturas</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if ($metrics['anomalias_count'] > 0)
                            <a href="{{ route('metricas.consumo') }}#anomalias" class="mt-3 inline-flex items-center text-xs text-amber-600 hover:underline">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ $metrics['anomalias_count'] }} unidades con consumo anómalo (posible fuga)
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Fila 4: Cobranza por período (gráfico línea) --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-semibold text-gray-700"><i class="fa-solid fa-money-bill-trend-up mr-1 text-emerald-400"></i> Cobranza por período (emitido vs cobrado)</h3>
                        <span class="text-xs text-gray-400">Último período: {{ $metrics['cobranza']['ultimo_porcentaje'] }}% cobrado</span>
                    </div>
                    <div class="relative" style="height: 220px">
                        <canvas id="chartCobranza"></canvas>
                    </div>
                </div>
            </div>

            {{-- Actividad reciente (existente) --}}
            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-clock-rotate-left mr-1 text-gray-400"></i> Actividad reciente</h3>
                    <ul class="space-y-1 text-sm">
                        @foreach ($recentActivity as $log)
                            <li class="text-gray-600">
                                <span class="text-gray-400">{{ $log->created_at->format('d/m H:i') }}</span>
                                — <strong>{{ $log->user?->name ?? 'sistema' }}</strong> · {{ $log->action }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const colores = { azul: '#4F46E5', verde: '#10B981', rojo: '#EF4444', celeste: '#0EA5E9' };

                // Consumo por período (medidores vs EPSAS)
                const consumoData = @json($metrics['consumo_periodo']['series']);
                if (document.getElementById('chartConsumo')) {
                    new Chart(document.getElementById('chartConsumo'), {
                        type: 'bar',
                        data: {
                            labels: consumoData.map(d => d.periodo),
                            datasets: [
                                { label: 'Medidores (m³)', data: consumoData.map(d => d.medidores), backgroundColor: colores.celeste, borderRadius: 4 },
                                { label: 'EPSAS (m³)', data: consumoData.map(d => d.epsas), backgroundColor: 'rgba(79,70,229,0.35)', borderRadius: 4 },
                            ]
                        },
                        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, scales: { y: { beginAtZero: true } } }
                    });
                }

                // Cobranza por período (líneas)
                const cobranzaData = @json($metrics['cobranza']['series']);
                if (document.getElementById('chartCobranza')) {
                    new Chart(document.getElementById('chartCobranza'), {
                        type: 'line',
                        data: {
                            labels: cobranzaData.map(d => d.periodo),
                            datasets: [
                                { label: 'Emitido (Bs)', data: cobranzaData.map(d => d.emitido), borderColor: colores.azul, backgroundColor: 'rgba(79,70,229,0.1)', fill: true, tension: 0.3 },
                                { label: 'Cobrado (Bs)', data: cobranzaData.map(d => d.cobrado), borderColor: colores.verde, backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 },
                            ]
                        },
                        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, scales: { y: { beginAtZero: true } } }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
