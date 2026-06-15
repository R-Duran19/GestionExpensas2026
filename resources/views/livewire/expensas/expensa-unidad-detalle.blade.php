<div>
    <div class="mb-6">
        <a href="{{ route('unidades.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a unidades
        </a>
    </div>

    <!-- Header con selector de período -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Detalle de Expensa por Unidad</h1>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $unidad->tipo_unidad->getLabel() }} {{ $unidad->codigo }}
                    @if($periodoActual)
                        - {{ $periodoActual->nombre_periodo }}
                    @endif
                </p>
            </div>

            @if(!empty($periodosDisponibles))
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Período:</label>
                    <select wire:model.live="periodoId"
                            wire:change="cambiarPeriodo($event.target.value)"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($periodosDisponibles as $periodo)
                            <option value="{{ $periodo['id'] }}">
                                {{ $periodo['nombre'] }}
                                @if($periodo['id'] == $periodoId) (Actual) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    {{-- Mensaje si no hay expensa --}}
    @if(!$expensa)
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Sin expensa generada</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Esta unidad no tiene expensa generada para este período.
                </p>
                @if($periodoActual)
                    <div class="mt-4 text-sm text-gray-600">
                        <span class="font-medium">Período:</span> {{ $periodoActual->nombre_periodo }}<br>
                        <span class="font-medium">Unidad:</span> {{ $unidad->tipo_unidad->getLabel() }} {{ $unidad->codigo }}
                    </div>
                @endif
            </div>
        </div>
    @else

    <!-- Información de la unidad -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Información de la Unidad</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Datos de la unidad -->
            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Datos de la Unidad</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Tipo</div>
                            <div class="text-sm font-medium text-gray-900">{{ $unidad->tipo_unidad->getLabel() }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Código</div>
                            <div class="text-sm font-medium text-gray-900">{{ $unidad->codigo }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Ubicación</div>
                            <div class="text-sm font-medium text-gray-900">{{ $unidad->piso }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Metros²</div>
                            <div class="text-sm font-medium text-gray-900">{{ number_format($unidad->metros_cuadrados, 2) }} m²</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Factor Prorrateo</div>
                            <div class="text-sm font-medium text-gray-900">{{ $unidad->factor_prorrateo }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Categoría</div>
                            <div class="text-sm font-medium text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    bg-{{ $unidad->categoria_unidad->getColor() }}-100 text-{{ $unidad->categoria_unidad->getColor() }}-800">
                                    {{ $unidad->categoria_unidad->getLabel() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del propietario -->
            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Propietario Actual</h4>
                @if($unidad->propietarioActual)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-900 mb-2">
                            {{ $unidad->propietarioActual->nombre_completo }}
                        </div>
                        <div class="space-y-1 text-sm text-gray-600">
                            <div>CI: {{ $unidad->propietarioActual->ci }}</div>
                            @if($unidad->propietarioActual->telefono)
                                <div>Tel: {{ $unidad->propietarioActual->telefono }}</div>
                            @endif
                            @if($unidad->propietarioActual->email)
                                <div>{{ $unidad->propietarioActual->email }}</div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-500">
                        Sin propietario asignado
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Desglose de montos -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Desglose de Montos</h3>

        <div class="space-y-3">
            <!-- Expensa de departamento/oficina -->
            <div class="flex justify-between items-center py-3 border-b border-gray-200">
                <div>
                    <div class="text-sm font-medium text-gray-900">
                        Expensa de {{ $unidad->tipo_unidad->getLabel() }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ number_format($unidad->metros_cuadrados, 2) }} m² × {{ $unidad->factor_prorrateo }}
                    </div>
                </div>
                <div class="text-sm font-medium text-gray-900">
                    {{ number_format($expensa->monto_expensa, 2) }} Bs
                </div>
            </div>

            <!-- Agua -->
            @if($unidad->tiene_medidor)
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <div>
                        <div class="text-sm font-medium text-gray-900">Agua</div>
                        @if($expensa->monto_agua > 0)
                            <div class="text-xs text-gray-500">Consumo del período</div>
                        @else
                            <div class="text-xs text-gray-500">Sin consumo</div>
                        @endif
                    </div>
                    <div class="text-sm font-medium text-gray-900">
                        {{ number_format($expensa->monto_agua, 2) }} Bs
                    </div>
                </div>
            @endif

            <!-- Subtotal expensa actual -->
            <div class="flex justify-between items-center py-3 bg-gray-50 rounded-lg px-4 my-2">
                <div class="text-sm font-medium text-gray-700">Subtotal Expensa Actual</div>
                <div class="text-lg font-bold text-gray-900">
                    {{ number_format($subtotalExpensaActual, 2) }} Bs
                </div>
            </div>

            <!-- Deuda anterior -->
            @if($totalDeudasAnterioresCalculado > 0)
                <div class="py-3 border-b border-gray-200" x-data="{ open: false }">
                    <div class="flex justify-between items-center cursor-pointer" x-on:click="open = !open">
                        <div>
                            <div class="text-sm font-medium text-red-600">Deuda de períodos anteriores</div>
                            <div class="text-xs text-gray-500">Meses impagos anteriores</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-sm font-medium text-red-600">
                                +{{ number_format($totalDeudasAnterioresCalculado, 2) }} Bs
                            </div>
                            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                 :class="{ 'rotate-180': open }"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>

                    <div x-show="open"
                         x-collapse
                         class="mt-3 pl-4 border-l-2 border-red-200">
                        @if($deudasAnterioresDetalladas->count() > 0)
                            <div class="text-xs text-gray-600 mb-2">Desglose de deuda:</div>
                            @foreach($deudasAnterioresDetalladas as $deuda)
                                <div class="flex justify-between items-center py-1 text-xs">
                                    <div class="text-gray-600">
                                        {{ $deuda->periodo->nombre_periodo }}
                                        <span class="text-gray-400">({{ $deuda->estado_label }})</span>
                                    </div>
                                    <div class="font-medium text-red-500">
                                        {{ number_format($deuda->monto_pendiente_real, 2) }} Bs
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endif

            <!-- Total -->
            <div class="flex justify-between items-center py-4 bg-indigo-50 rounded-lg px-4 mt-4">
                <div class="text-lg font-bold text-gray-900">Total a Pagar</div>
                <div class="text-2xl font-bold text-indigo-600">
                    {{ number_format($expensa->monto_total, 2) }} Bs
                </div>
            </div>

            <!-- Información de pagos -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Estado de Pago</h4>

                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Estado:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            bg-{{ $expensa->estado_color }}-100 text-{{ $expensa->estado_color }}-800">
                            {{ $expensa->estado_label }}
                        </span>
                    </div>

                    @if($expensa->monto_pagado > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Monto Pagado:</span>
                            <span class="text-sm font-medium text-green-600">
                                {{ number_format($expensa->monto_pagado, 2) }} Bs
                            </span>
                        </div>
                    @endif

                    @if($expensa->monto_pendiente > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Monto Pendiente:</span>
                            <span class="text-sm font-medium text-red-600">
                                {{ number_format($expensa->monto_pendiente, 2) }} Bs
                            </span>
                        </div>
                    @endif

                    @if($expensa->fecha_pago)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Fecha Último Pago:</span>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $expensa->fecha_pago->format('d/m/Y') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de pagos (si existen) -->
    @if($expensa && $expensa->ingresos->count() > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Historial de Pagos</h3>

            <div class="space-y-3">
                @foreach($expensa->ingresos as $ingreso)
                    <div class="flex justify-between items-center py-3 border-b border-gray-200 last:border-b-0">
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                Recibo #{{ $ingreso->numero_recibo }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $ingreso->fecha->format('d/m/Y') }}
                                @if($ingreso->metodo_pago)
                                    - {{ ucfirst($ingreso->metodo_pago) }}
                                @endif
                            </div>
                        </div>
                        <div class="text-sm font-medium text-green-600">
                            {{ number_format($ingreso->importe, 2) }} Bs
                        </div>
                    </div>
                @endforeach
            </div>

            @if($expensa->monto_pendiente > 0)
                <div class="mt-4 pt-4 border-t border-gray-200 text-sm text-gray-500">
                    <p>Total pagado: {{ number_format($expensa->monto_pagado, 2) }} Bs de {{ number_format($expensa->monto_total, 2) }} Bs</p>
                </div>
            @endif
        </div>
    @endif

    <!-- Detalle de agua (si tiene medidor) -->
    @if($unidad->tiene_medidor && $lecturaMedidor)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Detalle de Agua</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Consumo -->
                <div class="md:col-span-2">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Consumo del Período</h4>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Lectura Anterior:</span>
                                    <span class="font-medium text-gray-900">{{ number_format($lecturaMedidor->lectura_anterior, 2) }} m³</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Lectura Actual:</span>
                                    <span class="font-medium text-gray-900">{{ number_format($lecturaMedidor->lectura_actual, 2) }} m³</span>
                                </div>
                                <div class="flex justify-between border-t border-blue-200 pt-2">
                                    <span class="text-gray-600">Consumo:</span>
                                    <span class="font-bold text-gray-900">{{ number_format($lecturaMedidor->consumo_m3, 2) }} m³</span>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm">
                                @if($expensa->monto_agua > 0 && $lecturaMedidor->consumo_m3 > 0)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Factor:</span>
                                        <span class="font-medium text-gray-900">
                                            {{ number_format($expensa->monto_agua / $lecturaMedidor->consumo_m3, 2) }} Bs/m³
                                        </span>
                                    </div>
                                @endif
                                @if($expensa->monto_agua > 0)
                                    <div class="flex justify-between border-t border-blue-200 pt-2">
                                        <span class="text-gray-600">Monto Agua:</span>
                                        <span class="font-bold text-indigo-600">{{ number_format($expensa->monto_agua, 2) }} Bs</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @endif
</div>
