<div>
    <div class="mb-6">
        <a href="{{ route('expensas.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a expensas
        </a>
    </div>

    <!-- Información principal -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Detalle de Expensa</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $expensa->periodo->nombre_periodo }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $expensa->estado === 'pagado' ? 'bg-green-100 text-green-800' :
                   ($expensa->estado === 'parcial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                {{ $expensa->estado_label }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Información de la unidad -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-3">Unidad</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Tipo</div>
                            <div class="text-sm font-medium text-gray-900">{{ $expensa->unidad->tipo_unidad->getLabel() }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Código</div>
                            <div class="text-sm font-medium text-gray-900">{{ $expensa->unidad->codigo }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Metros²</div>
                            <div class="text-sm font-medium text-gray-900">{{ $expensa->unidad->metros_cuadrados }} m²</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Factor</div>
                            <div class="text-sm font-medium text-gray-900">{{ $expensa->unidad->factor_prorrateo }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del propietario -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-3">Propietario</h3>
                @if($expensa->unidad->propietario_actual)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $expensa->unidad->propietario_actual->nombre }} {{ $expensa->unidad->propietario_actual->apellido }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">CI: {{ $expensa->unidad->propietario_actual->ci }}</div>
                        @if($expensa->unidad->propietario_actual->telefono)
                            <div class="text-sm text-gray-600 mt-1">Tel: {{ $expensa->unidad->propietario_actual->telefono }}</div>
                        @endif
                        @if($expensa->unidad->propietario_actual->email)
                            <div class="text-sm text-gray-600 mt-1">{{ $expensa->unidad->propietario_actual->email }}</div>
                        @endif
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
            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                <div>
                    <div class="text-sm font-medium text-gray-900">Expensa de {{ $expensa->unidad->tipo_unidad->getLabel() }}</div>
                    <div class="text-xs text-gray-500">{{ $expensa->unidad->metros_cuadrados }} m² × {{ $expensa->unidad->factor_prorrateo }}</div>
                </div>
                <div class="text-sm font-medium text-gray-900">{{ number_format($expensa->monto_expensa, 2) }} Bs</div>
            </div>

            <!-- Agua -->
            @if($expensa->unidad->tiene_medidor)
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <div>
                        <div class="text-sm font-medium text-gray-900">Agua</div>
                        @if($expensa->monto_agua > 0)
                            <div class="text-xs text-gray-500">Consumo del período</div>
                        @else
                            <div class="text-xs text-gray-500">Sin consumo</div>
                        @endif
                    </div>
                    <div class="text-sm font-medium text-gray-900">{{ number_format($expensa->monto_agua, 2) }} Bs</div>
                </div>
            @endif

            <!-- Deuda anterior -->
            @if($expensa->deuda_anterior > 0)
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <div>
                        <div class="text-sm font-medium text-red-600">Deuda de períodos anteriores</div>
                        <div class="text-xs text-gray-500">Meses impagos anteriores</div>
                    </div>
                    <div class="text-sm font-medium text-red-600">+{{ number_format($expensa->deuda_anterior, 2) }} Bs</div>
                </div>
            @endif

            <!-- Total -->
            <div class="flex justify-between items-center py-4 bg-indigo-50 rounded-lg px-4 mt-4">
                <div class="text-lg font-bold text-gray-900">Total a Pagar</div>
                <div class="text-2xl font-bold text-indigo-600">{{ number_format($expensa->monto_total, 2) }} Bs</div>
            </div>
        </div>
    </div>

    <!-- Estado de pagos -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Estado de Pagos</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-600">Total Expensa</div>
                <div class="text-xl font-bold text-gray-900">{{ number_format($expensa->monto_total, 2) }} Bs</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm text-green-600">Pagado</div>
                <div class="text-xl font-bold text-green-700">{{ number_format($expensa->monto_pagado, 2) }} Bs</div>
            </div>
            <div class="bg-red-50 rounded-lg p-4">
                <div class="text-sm text-red-600">Pendiente</div>
                <div class="text-xl font-bold text-red-700">{{ number_format($expensa->monto_pendiente, 2) }} Bs</div>
            </div>
        </div>

        @if($expensa->pagos->count() > 0)
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Historial de Pagos</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Modalidad</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($expensa->pagos as $pago)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600">{{ ucfirst($pago->modalidad) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($pago->monto, 2) }} Bs</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            {{ $pago->estado === 'verificado' ? 'bg-green-100 text-green-800' :
                                               ($pago->estado === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($pago->estado) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
