<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-chart-line text-indigo-600 mr-2"></i>
                Dashboard de Caja
            </h1>
            <p class="text-gray-600 mt-1">Resumen financiero en tiempo real</p>
        </div>
        <div class="flex flex-wrap gap-2 w-full sm:w-auto">
            <button wire:click="cargarDatos"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Actualizar
            </button>
            <a href="{{ route('caja.ingresos.index') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2 transition">
                <i class="fas fa-arrow-circle-down"></i>
                Ingresos
            </a>
            <a href="{{ route('caja.egresos.index') }}"
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2 transition">
                <i class="fas fa-arrow-circle-up"></i>
                Egresos
            </a>
        </div>
    </div>

    {{-- Tarjetas de Resumen Hoy --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {{-- Ingresos Hoy --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Ingresos Hoy</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">
                        Bs {{ number_format($datos['hoy']['total_ingresos'] ?? 0, 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-arrow-circle-down mr-1"></i>
                        {{ $datos['hoy']['cantidad_ingresos'] ?? 0 }} movimientos
                    </p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-arrow-trend-up text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Egresos Hoy --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Egresos Hoy</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">
                        Bs {{ number_format($datos['hoy']['total_egresos'] ?? 0, 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-arrow-circle-up mr-1"></i>
                        {{ $datos['hoy']['cantidad_egresos'] ?? 0 }} movimientos
                    </p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-arrow-trend-down text-red-600 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Balance Neto Hoy --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Balance Neto Hoy</p>
                    <p class="text-2xl font-bold {{ ($datos['hoy']['balance_neto'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                        Bs {{ number_format($datos['hoy']['balance_neto'] ?? 0, 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-scale-balanced mr-1"></i>
                        Diferencia
                    </p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-wallet text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Resumen Mes --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Resumen Mes</p>
                    <p class="text-xl font-bold text-gray-900 mt-1 capitalize">
                        {{ $datos['mes']['mes_nombre'] ?? '' }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-calendar mr-1"></i>
                        {{ $datos['mes']['ano'] ?? '' }}
                    </p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-calendar-check text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Gráfico y Resumen del Mes --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Gráfico de últimos 7 días --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-area text-indigo-600 mr-2"></i>
                Últimos 7 Días
            </h3>
            @if(!empty($datos['ultimos_7_dias']) && count($datos['ultimos_7_dias']) > 0)
                <div class="relative" style="height: 300px;">
                    <canvas id="grafico7Dias"></canvas>
                </div>
            @else
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-chart-area text-4xl mb-3"></i>
                    <p>No hay datos suficientes para mostrar el gráfico</p>
                </div>
            @endif
        </div>

        {{-- Resumen del Mes --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-pie-chart text-indigo-600 mr-2"></i>
                Resumen del Mes
            </h3>

            <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Ingresos Totales:</span>
                    <span class="font-semibold text-green-600">
                        Bs {{ number_format($datos['mes']['ingresos']['total'] ?? 0, 2, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Egresos Totales:</span>
                    <span class="font-semibold text-red-600">
                        Bs {{ number_format($datos['mes']['egresos']['total'] ?? 0, 2, ',', '.') }}
                    </span>
                </div>
                <hr>
                <div class="flex justify-between items-center">
                    <span class="text-gray-900 font-medium">Balance Neto:</span>
                    <span class="font-bold {{ ($datos['mes']['balance_neto'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        Bs {{ number_format($datos['mes']['balance_neto'] ?? 0, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            <h4 class="text-sm font-semibold text-gray-700 mt-6 mb-3">Ingresos por Método:</h4>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                        Efectivo
                    </span>
                    <span class="text-sm text-gray-600">
                        Bs {{ number_format($datos['mes']['ingresos']['efectivo'] ?? 0, 2, ',', '.') }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                        Transferencia
                    </span>
                    <span class="text-sm text-gray-600">
                        Bs {{ number_format($datos['mes']['ingresos']['transferencia'] ?? 0, 2, ',', '.') }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                        QR
                    </span>
                    <span class="text-sm text-gray-600">
                        Bs {{ number_format($datos['mes']['ingresos']['qr'] ?? 0, 2, ',', '.') }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                        Otro
                    </span>
                    <span class="text-sm text-gray-600">
                        Bs {{ number_format($datos['mes']['ingresos']['otro'] ?? 0, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            <hr class="my-4">
            <button wire:click="recalcularTotales"
                    class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2 transition">
                <i class="fas fa-calculator"></i>
                Recalcular Totales
            </button>
        </div>
    </div>

    {{-- Tablas de Últimos Movimientos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Últimos Ingresos --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-green-600 text-white px-6 py-4">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-arrow-circle-down mr-2"></i>
                    Últimos Ingresos
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                N° Recibo
                            </th>
                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Categoría
                            </th>
                            <th class="px-3 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Importe
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($datos['ultimos_ingresos'] ?? [] as $ingreso)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $ingreso['numero_recibo'] }}
                                    </span>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 text-sm text-gray-900">
                                    <div class="flex flex-col sm:block">
                                        <span>{{ \Carbon\Carbon::parse($ingreso['fecha'])->format('d/m') }}</span>
                                        @if($ingreso['categoria'])
                                            <span class="sm:hidden text-xs mt-1 px-2 py-0.5 rounded-full text-white inline-block"
                                                  style="background-color: {{ $ingreso['categoria_color'] }};">
                                                {{ $ingreso['categoria'] }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    @if($ingreso['categoria'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white"
                                              style="background-color: {{ $ingreso['categoria_color'] }};">
                                            {{ $ingreso['categoria'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-right text-sm font-medium text-green-600">
                                    Bs {{ number_format($ingreso['importe'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 block mx-auto"></i>
                                    No hay ingresos registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bg-gray-50 px-6 py-4">
                <a href="{{ route('caja.ingresos.index') }}"
                   class="flex items-center justify-center w-full px-4 py-2 border border-green-600 text-green-600 rounded-lg hover:bg-green-50 transition">
                    Ver Todos los Ingresos
                </a>
            </div>
        </div>

        {{-- Últimos Egresos --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-red-600 text-white px-6 py-4">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-arrow-circle-up mr-2"></i>
                    Últimos Egresos
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                N° Egreso
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Concepto
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Importe
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($datos['ultimos_egresos'] ?? [] as $egreso)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $egreso['numero_egreso'] }}
                                    </span>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($egreso['fecha'])->format('d/m') }}
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 text-sm text-gray-900">
                                    <div class="max-w-xs">
                                        {{ \Illuminate\Support\Str::limit($egreso['concepto'], 20) }}
                                        @if($egreso['categoria'])
                                            <br>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-white mt-1"
                                                  style="background-color: {{ $egreso['categoria_color'] }};">
                                                {{ $egreso['categoria'] }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-right text-sm font-medium text-red-600">
                                    Bs {{ number_format($egreso['importe'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 block mx-auto"></i>
                                    No hay egresos registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bg-gray-50 px-6 py-4">
                <a href="{{ route('caja.egresos.index') }}"
                   class="flex items-center justify-center w-full px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-50 transition">
                    Ver Todos los Egresos
                </a>
            </div>
        </div>
    </div>

    @if(!empty($datos['ultimos_7_dias']))
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            (function() {
                console.log('🚀 Iniciando script del gráfico...');

                const datos = @js($datos['ultimos_7_dias']);
                console.log('✅ Datos recibidos:', datos);
                console.log('📊 Total de días:', datos.length);

                let chartInstance = null;
                let interval = null;

                function initChart() {
                    console.log('🔍 Buscando canvas...');
                    const canvas = document.getElementById('grafico7Dias');

                    if (!canvas) {
                        console.log('❌ Canvas no encontrado, reintentando...');
                        return false;
                    }

                    console.log('✅ Canvas encontrado!');
                    console.log('📐 Canvas dimensions:', canvas.width, 'x', canvas.height);

                    // Destruir gráfico anterior si existe
                    if (chartInstance) {
                        console.log('🗑️ Destruyendo gráfico anterior...');
                        chartInstance.destroy();
                        chartInstance = null;
                    }

                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        console.log('❌ No se pudo obtener el contexto');
                        return false;
                    }

                    console.log('✅ Contexto obtenido, creando gráfico...');

                    const fechas = datos.map(d => {
                        const fecha = new Date(d.fecha);
                        return fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
                    });
                    console.log('📅 Fechas:', fechas);

                    const ingresosData = datos.map(d => d.ingresos);
                    const egresosData = datos.map(d => d.egresos);
                    const balanceData = datos.map(d => d.balance);

                    console.log('💰 Ingresos:', ingresosData);
                    console.log('💸 Egresos:', egresosData);
                    console.log('⚖️ Balance:', balanceData);

                    try {
                        chartInstance = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: fechas,
                                datasets: [
                                    {
                                        label: 'Ingresos',
                                        data: ingresosData,
                                        borderColor: '#10B981',
                                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                        tension: 0.4,
                                        fill: true,
                                    },
                                    {
                                        label: 'Egresos',
                                        data: egresosData,
                                        borderColor: '#EF4444',
                                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                        tension: 0.4,
                                        fill: true,
                                    },
                                    {
                                        label: 'Balance',
                                        data: balanceData,
                                        borderColor: '#3B82F6',
                                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                        tension: 0.4,
                                        fill: true,
                                    },
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                label += 'Bs' + context.parsed.y.toFixed(2);
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'Bs' + value.toFixed(2);
                                            }
                                        }
                                    }
                                }
                            }
                        });

                        console.log('🎉 GRÁFICO CREADO EXITOSAMENTE!');
                        console.log('📈 Chart instance:', chartInstance);

                        // Limpiar el intervalo cuando se crea exitosamente
                        if (interval) {
                            console.log('🧹 Limpiando intervalo...');
                            clearInterval(interval);
                            interval = null;
                        }

                        return true;
                    } catch (error) {
                        console.error('❌ ERROR AL CREAR EL GRÁFICO:', error);
                        console.error('Error stack:', error.stack);
                        return false;
                    }
                }

                // Intentar inmediatamente
                console.log('📋 Document ready state:', document.readyState);
                if (document.readyState === 'complete') {
                    console.log('⚡ Document ya cargado, ejecutando en 100ms...');
                    setTimeout(initChart, 100);
                } else {
                    console.log('⏳ Esperando DOMContentLoaded...');
                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('✅ DOMContentLoaded ejecutado, esperando 100ms...');
                        setTimeout(initChart, 100);
                    });
                }

                // Reintentar si falla (máximo 5 intentos)
                let attempts = 0;
                const maxAttempts = 5;
                console.log('🔄 Configurando reintentos (max ' + maxAttempts + ' intentos)...');

                interval = setInterval(function() {
                    attempts++;
                    console.log('🔄 Intento ' + attempts + '/' + maxAttempts);

                    if (initChart() || attempts >= maxAttempts) {
                        if (attempts >= maxAttempts) {
                            console.log('❌ Máximo de intentos alcanzado');
                        } else {
                            console.log('✅ Gráfico creado en intento ' + attempts + ', limpiando intervalo');
                        }
                        if (interval) {
                            clearInterval(interval);
                            interval = null;
                        }
                    }
                }, 500);

                console.log('🚀 Script configurado correctamente');
            })();
        </script>
    @endif
</div>
