<div>
    {{-- Encabezado de bienvenida --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Hola, {{ $this->user->persona?->nombre_completo ?? $this->user->name }} 👋
        </h1>
        <p class="text-gray-500 text-sm mt-1">
            Portal de {{ $this->user->tipo === 'inquilino' ? 'inquilino' : 'propietario' }} ·
            {{ \App\Models\ConfiguracionEdificio::obtenerConfiguracion()->nombre_edificio ?? '' }}
        </p>
    </div>

    {{-- Resumen global --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1">
                <i class="fa-solid fa-file-invoice-dollar mr-1 text-gray-400"></i> Deuda total
            </div>
            <div class="text-3xl font-bold {{ $this->deudaTotal() > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                Bs {{ number_format($this->deudaTotal(), 2) }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1">
                <i class="fa-solid fa-piggy-bank mr-1 text-gray-400"></i> Saldo a tu favor
            </div>
            <div class="text-3xl font-bold text-emerald-600">
                Bs {{ number_format($this->saldoTotal(), 2) }}
            </div>
        </div>
    </div>

    {{-- Tarjetas por unidad --}}
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Mis unidades</h2>

    @if ($this->unidades->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
            <i class="fa-solid fa-door-closed text-3xl mb-3 text-gray-300"></i>
            <p>No tienes unidades asignadas actualmente.</p>
            <p class="text-sm text-gray-400 mt-1">Si crees que es un error, contacta al administrador.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($this->unidades as $unidad)
                @php
                    $deuda = $this->deudasPorUnidad()[$unidad->id] ?? 0;
                    $saldo = $this->saldosPorUnidad()[$unidad->id] ?? 0;
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5" wire:key="unidad-{{ $unidad->id }}">
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-semibold text-gray-800">
                            <i class="fa-solid fa-door-open mr-1 text-indigo-400"></i> {{ $unidad->codigo }}
                        </span>
                        <span class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-600">
                            {{ $unidad->tipo_unidad->value }}
                        </span>
                    </div>

                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-gray-500">Deuda</span>
                        <span class="text-lg font-bold {{ $deuda > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            Bs {{ number_format($deuda, 2) }}
                        </span>
                    </div>

                    @if ($saldo > 0)
                        <div class="flex justify-between items-baseline mt-1">
                            <span class="text-sm text-gray-500">Saldo a favor</span>
                            <span class="text-sm font-semibold text-emerald-600">
                                Bs {{ number_format($saldo, 2) }}
                            </span>
                        </div>
                    @endif

                    <div class="mt-4 pt-3 border-t border-gray-100">
                        <a href="{{ route('portal.estado-cuenta') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            Ver estado de cuenta <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- [METRICAS 2026-10-30] Histórico de consumo de agua por medidor --}}
    @if (count($this->consumoHistorico['serie']) > 0)
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-semibold text-gray-700"><i class="fa-solid fa-droplet mr-1 text-blue-400"></i> Tu consumo de agua por período (m³)</h3>
                <span class="text-xs text-gray-400">Línea punteada = promedio del edificio por unidad</span>
            </div>
            <div class="relative" style="height: 240px">
                <canvas id="chartConsumoPortal"></canvas>
            </div>
            <div class="grid grid-cols-3 gap-3 mt-4 text-center">
                <div class="bg-blue-50 rounded-lg p-2">
                    <div class="text-xs text-gray-500">Total histórico</div>
                    <div class="text-lg font-bold text-blue-600">{{ $this->consumoHistorico['total_m3'] }} m³</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-2">
                    <div class="text-xs text-gray-500">Tu promedio mensual</div>
                    <div class="text-lg font-bold text-gray-800">{{ $this->consumoHistorico['promedio_m3'] }} m³</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-2">
                    <div class="text-xs text-gray-500">Último período</div>
                    <div class="text-lg font-bold text-emerald-600">{{ $this->consumoHistorico['serie'] ? $this->consumoHistorico['serie'][count($this->consumoHistorico['serie'])-1]['m3'] : 0 }} m³</div>
                </div>
            </div>
        </div>
    @endif

    {{-- [METRICAS 2026-10-30] Timeline pagado/parcial/pendiente por período --}}
    @if ($this->timelinePagos->count() > 0)
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-700 mb-4"><i class="fa-solid fa-timeline mr-1 text-indigo-400"></i> Historial por período</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->timelinePagos as $t)
                    @php
                        $colores = [
                            'pagado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'parcial' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'pendiente' => 'bg-red-100 text-red-700 border-red-200',
                        ];
                        $iconos = ['pagado' => 'fa-check', 'parcial' => 'fa-circle-half-stroke', 'pendiente' => 'fa-clock'];
                    @endphp
                    <div class="border rounded-lg px-3 py-2 {{ $colores[$t['estado']] }}">
                        <div class="text-xs font-medium uppercase">{{ $t['periodo'] }}</div>
                        <div class="text-sm font-bold">
                            <i class="fa-solid {{ $iconos[$t['estado']] }} mr-1"></i>
                            {{ ['pagado' => 'Pagado', 'parcial' => 'Parcial', 'pendiente' => 'Pendiente'][$t['estado']] }}
                        </div>
                        @if ($t['pendiente'] > 0)
                            <div class="text-[10px] opacity-75">Falta Bs {{ number_format($t['pendiente'], 2, ',', '.') }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
            <a href="{{ route('portal.pagar') }}" class="mt-4 inline-flex items-center text-sm text-indigo-600 hover:underline">
                <i class="fa-solid fa-qrcode mr-1"></i> Pagar pendientes ahora
            </a>
        </div>
    @endif

    {{-- Acceso directo al pago QR --}}
    <div class="mt-8 bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-700 flex items-center justify-between">
        <span><i class="fa-solid fa-qrcode mr-1"></i> Paga tus expensas en línea con código QR bancario.</span>
        <a href="{{ route('portal.pagar') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium text-xs">
            Ir a Pagar <i class="fa-solid fa-arrow-right ml-1"></i>
        </a>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const serie = @json($this->consumoHistorico['serie']);
            if (!serie.length) return;
            new Chart(document.getElementById('chartConsumoPortal'), {
                type: 'bar',
                data: {
                    labels: serie.map(p => p.periodo),
                    datasets: [
                        { label: 'Tu consumo (m³)', data: serie.map(p => p.m3), backgroundColor: '#0EA5E9', borderRadius: 4 },
                    ]
                },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'm³' } } } }
            });
        });
    </script>
@endpush
