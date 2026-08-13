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
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Lecturas de Medidor</h3>
                        <p class="text-sm text-gray-500 mt-1">Registro de lecturas del período (solo unidades con medidor)</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Exportar a Excel -->
                        <a href="{{ route('periodos.lecturas.exportar-excel', $periodo->id) }}"
                           class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Exportar Excel
                        </a>

                        <!-- Filtro por tipo de unidad -->
                        <select wire:model.live="filtroTipo"
                                class="block w-full sm:w-auto rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($tipoOpciones as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <!-- Buscador -->
                        <div class="relative">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text"
                                   wire:model.live.debounce.300ms="search"
                                   placeholder="Buscar por código o propietario..."
                                   class="block w-full sm:w-72 rounded-md border-gray-300 pl-9 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Lectura Anterior</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Lectura Actual</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Consumo m³</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        </tr>
                    </thead>

                    @forelse($lecturas as $row)
                        @if($row['tipo'] === 'individual')
                            <tbody class="bg-white">
                                <tr class="hover:bg-gray-50" wire:key="{{ $row['id'] }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $row['codigo'] }}</div>
                                        @if($row['propietario'])
                                            <div class="text-xs text-gray-500">{{ $row['propietario'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $row['tipo_unidad_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                        {{ number_format($row['lectura_anterior'], 2, '.', ',') }} m³
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                        {{ number_format($row['lectura_actual'], 2, '.', ',') }} m³
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                        {{ number_format($row['consumo_m3'], 2, '.', ',') }} m³
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $row['fecha']?->format('d/m/Y') }}
                                    </td>
                                </tr>
                            </tbody>
                        @else
                            <!-- Fila de grupo medidor (acordeón) -->
                            <tbody x-data="{ open: false }" wire:key="{{ $row['id'] }}" class="bg-white">
                                <tr class="cursor-pointer hover:bg-purple-50 border-l-4 border-purple-400"
                                    @click="open = !open">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-purple-500 transition-transform duration-200"
                                                 :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                            <div>
                                                <div class="text-sm font-semibold text-purple-900">{{ $row['grupo_nombre'] }}</div>
                                                <div class="text-xs text-gray-500">{{ count($row['unidades']) }} unidad(es)</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Compartido
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                        {{ number_format($row['lectura_anterior'], 2, '.', ',') }} m³
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                        {{ number_format($row['lectura_actual'], 2, '.', ',') }} m³
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-700 text-right font-bold">
                                        {{ number_format($row['consumo_total'], 2, '.', ',') }} m³
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $row['fecha']?->format('d/m/Y') }}
                                    </td>
                                </tr>
                                <!-- Contenido expandido: unidades del grupo -->
                                <tr x-show="open" x-collapse x-cloak>
                                    <td colspan="6" class="px-6 py-4 bg-purple-50/50">
                                        <div class="text-xs font-semibold text-purple-700 uppercase tracking-wide mb-2">Unidades del grupo (consumo distribuido)</div>
                                        @if(count($row['unidades']) > 0)
                                            <div class="overflow-hidden rounded-md border border-purple-200">
                                                <table class="min-w-full divide-y divide-purple-200">
                                                    <thead class="bg-purple-100">
                                                        <tr>
                                                            <th class="px-4 py-2 text-left text-xs font-semibold text-purple-800 uppercase">Unidad</th>
                                                            <th class="px-4 py-2 text-left text-xs font-semibold text-purple-800 uppercase">Propietario</th>
                                                            <th class="px-4 py-2 text-right text-xs font-semibold text-purple-800 uppercase">Consumo asignado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-purple-100">
                                                        @foreach($row['unidades'] as $unidad)
                                                            <tr wire:key="{{ $row['id'] . '_' . $unidad['codigo'] }}">
                                                                <td class="px-4 py-2 whitespace-nowrap">
                                                                    <div class="text-sm font-medium text-gray-900">{{ $unidad['codigo'] }}</div>
                                                                    <div class="text-xs text-gray-500">{{ $unidad['tipo_unidad_label'] }}</div>
                                                                </td>
                                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                                                    {{ $unidad['propietario'] ?: '-' }}
                                                                </td>
                                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                                                    {{ number_format($unidad['consumo_m3'], 2, '.', ',') }} m³
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-400 italic">No hay unidades asignadas a este grupo.</p>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        @endif
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">
                                    No se encontraron lecturas con los filtros actuales.
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

            <!-- Resumen y paginación -->
            <div class="px-6 py-4 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-gray-500">
                    Mostrando {{ $lecturas->firstItem() ?? 0 }}-{{ $lecturas->lastItem() ?? 0 }} de {{ $lecturas->total() }} resultado(s)
                </p>
                {{ $lecturas->links() }}
            </div>
        </div>
    @endif
</div>
