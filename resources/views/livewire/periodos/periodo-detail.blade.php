@php
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
@endphp

<div class="space-y-6">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('periodos.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver a períodos
        </a>
    </div>

    <!-- Título del Período -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r {{ $periodo->estado === 'abierto' ? 'from-green-500 to-emerald-600' : 'from-gray-500 to-gray-600' }} px-6 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ $periodo->nombre_periodo }}</h1>
                    <p class="text-white/80 mt-1">
                        @if($periodo->estado === 'abierto')
                            Período actualmente abierto
                        @else
                            Período cerrado
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-4 py-2 rounded-lg {{ $periodo->estado === 'abierto' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} text-sm font-bold">
                        <span class="w-3 h-3 rounded-full {{ $periodo->estado === 'abierto' ? 'bg-green-500' : 'bg-gray-500' }} mr-2"></span>
                        {{ $periodo->estado_label }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Fechas -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Fecha de Apertura</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    @if($periodo->fecha_apertura)
                        {{ $periodo->fecha_apertura->format('d/m/Y H:i') }}
                    @else
                        <span class="text-gray-400">No registrado</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Fecha de Cierre</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    @if($periodo->fecha_cierre)
                        {{ $periodo->fecha_cierre->format('d/m/Y H:i') }}
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </dd>
            </div>
        </div>

        <!-- Cerrado por -->
        @if($periodo->cerradoPor)
            <div class="px-6 py-3 bg-blue-50 border-b border-blue-100">
                <div class="flex items-center text-sm">
                    <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-blue-700">Cerrado por <strong>{{ $periodo->cerradoPor->name }}</strong></span>
                </div>
            </div>
        @endif
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Facturas EPSAS -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Facturas EPSAS</p>
                    <p class="mt-2 text-3xl font-bold {{ $periodo->facturasAgua->count() === 3 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $periodo->facturasAgua->count() }}/3
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-lg {{ $periodo->facturasAgua->count() === 3 ? 'bg-green-100' : 'bg-yellow-100' }} flex items-center justify-center">
                        <svg class="w-6 h-6 {{ $periodo->facturasAgua->count() === 3 ? 'text-green-600' : 'text-yellow-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            @if($periodo->estado === 'abierto')
                <div class="mt-4">
                    <a href="{{ route('periodos.facturas', $periodo->id) }}"
                       class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        {{ $periodo->facturasAgua->count() === 3 ? 'Ver facturas' : 'Registrar facturas' }} &rarr;
                    </a>
                </div>
            @endif
        </div>

        <!-- Factores de Agua -->
        @if($periodo->tiene_factores_calculados)
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Factores Calculados</p>
                    <p class="mt-2 text-xl font-bold text-blue-600">
                        {{ number_format($periodo->factor_domiciliario, 8, '.', '') }}
                    </p>
                    <p class="text-xs text-gray-500">Domiciliario</p>
                </div>
                <div class="text-right">
                    <p class="mt-2 text-xl font-bold text-purple-600">
                        {{ number_format($periodo->factor_comercial, 8, '.', '') }}
                    </p>
                    <p class="text-xs text-gray-500">Comercial</p>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-3">
                Bs/M³ • Calculado el {{ $periodo->factores_calculados_at->format('d/m/Y') }}
            </p>
        </div>
        @endif

        <!-- Lecturas de Medidor -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Lecturas Registradas</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600">
                        {{ $periodo->lecturasMedidor->count() }}
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                </div>
            </div>
            @if($periodo->estado === 'abierto')
                <div class="mt-4">
                    <a href="{{ route('periodos.lecturas', $periodo->id) }}"
                       class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        {{ $periodo->lecturasMedidor->count() > 0 ? 'Ver lecturas' : 'Registrar lecturas' }} &rarr;
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Lista de Lecturas (si hay) -->
    @if($periodo->lecturasMedidor->count() > 0)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Lecturas de Medidor</h3>
                <p class="text-sm text-gray-500 mt-1">Registro de lecturas del período</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unidad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lectura Actual</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lectura Anterior</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Consumo m³</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($periodo->lecturasMedidor as $lectura)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $lectura->unidad->nombre }}
                                    </div>
                                    @if($lectura->unidad->propietario)
                                        <div class="text-xs text-gray-500">
                                            {{ $lectura->unidad->propietario->nombre }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $lectura->tipo === 'individual' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $lectura->tipo === 'individual' ? 'Individual' : 'Compartido' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                    {{ number_format($lectura->lectura_actual, 0) }} m³
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    {{ number_format($lectura->lectura_anterior, 0) }} m³
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                    {{ number_format($lectura->consumo_m3, 1) }} m³
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $lectura->created_at->format('d/m/Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
