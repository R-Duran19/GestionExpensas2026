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
                {{ $expensa->estaPagada() ? 'bg-green-100 text-green-800' :
                   ($expensa->tieneDeuda() ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
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
                            <div class="text-sm font-medium text-gray-900">{{ number_format($expensa->unidad->metros_cuadrados, 2) }} m²</div>
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

            <!-- Total -->
            <div class="flex justify-between items-center py-4 bg-indigo-50 rounded-lg px-4 mt-4">
                <div class="text-lg font-bold text-gray-900">Total Emitido</div>
                <div class="text-2xl font-bold text-indigo-600">{{ number_format($expensa->monto_total, 2) }} Bs</div>
            </div>
        </div>
    </div>

    <!-- Estado de pagos -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Estado de Pagos</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-600">Total Expensa</div>
                <div class="text-xl font-bold text-gray-900">{{ number_format($expensa->monto_total, 2) }} Bs</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm text-green-600">Pagado</div>
                <div class="text-xl font-bold text-green-700">{{ number_format($expensa->total_pagado, 2) }} Bs</div>
            </div>
            <div class="bg-red-50 rounded-lg p-4">
                <div class="text-sm text-red-600">Pendiente</div>
                <div class="text-xl font-bold text-red-700">{{ number_format($expensa->total_pendiente, 2) }} Bs</div>
            </div>
        </div>

        <!-- Desglose por concepto -->
        <div class="border-t border-gray-200 pt-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Desglose por Concepto</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Expensa -->
                <div class="bg-gray-50 rounded p-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Expensa</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($expensa->monto_expensa, 2) }} Bs</span>
                    </div>
                    <div class="flex justify-between items-center mt-1 text-xs">
                        <span class="text-gray-500">Pendiente:</span>
                        <span class="font-medium {{ $expensa->expensa_pendiente > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($expensa->expensa_pendiente, 2) }} Bs
                        </span>
                    </div>
                    @if($expensa->monto_expensa > 0)
                        <div class="flex justify-between items-center mt-1 text-xs">
                            <span class="text-gray-500">Pagado:</span>
                            <span class="font-medium text-green-600">
                                {{ number_format($expensa->monto_expensa - $expensa->expensa_pendiente, 2) }} Bs
                            </span>
                        </div>
                    @endif
                    @if($expensa->porcentaje_pagado_expensa > 0)
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ min($expensa->porcentaje_pagado_expensa, 100) }}%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1 text-center">{{ number_format($expensa->porcentaje_pagado_expensa, 1) }}% pagado</div>
                        </div>
                    @endif
                </div>

                <!-- Agua -->
                <div class="bg-gray-50 rounded p-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Agua</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($expensa->monto_agua, 2) }} Bs</span>
                    </div>
                    <div class="flex justify-between items-center mt-1 text-xs">
                        <span class="text-gray-500">Pendiente:</span>
                        <span class="font-medium {{ $expensa->agua_pendiente > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($expensa->agua_pendiente, 2) }} Bs
                        </span>
                    </div>
                    @if($expensa->monto_agua > 0)
                        <div class="flex justify-between items-center mt-1 text-xs">
                            <span class="text-gray-500">Pagado:</span>
                            <span class="font-medium text-green-600">
                                {{ number_format($expensa->monto_agua - $expensa->agua_pendiente, 2) }} Bs
                            </span>
                        </div>
                    @endif
                    @if($expensa->porcentaje_pagado_agua > 0)
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($expensa->porcentaje_pagado_agua, 100) }}%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1 text-center">{{ number_format($expensa->porcentaje_pagado_agua, 1) }}% pagado</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Historial de pagos -->
        @if($expensa->detallesPago->count() > 0)
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Historial de Pagos</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Recibo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Aplicado</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($expensa->detallesPago as $detalle)
                                @if($detalle->ingreso)
                                    <tr{{ $detalle->ingreso->trashed() ? ' class="bg-red-50"' : '' }}>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ $detalle->ingreso->fecha->format('d/m/Y') }}
                                            @if($detalle->ingreso->trashed())
                                                <span class="ml-2 text-xs text-red-600">(Anulado)</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600">
                                            #{{ $detalle->ingreso->numero_recibo }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600">
                                            {{ ucfirst($detalle->ingreso->metodo_pago) }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 text-right">
                                            {{ number_format($detalle->monto_aplicado, 2) }} Bs
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                                {{ ucfirst($detalle->concepto) }}
                                            </span>
                                        </td>
                                    </tr>
                                @else
                                    <tr class="bg-gray-100">
                                        <td colspan="5" class="px-4 py-2 text-sm text-gray-500 italic">
                                            Pago eliminado (ID detalle: {{ $detalle->id }})
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 text-sm text-gray-500">
                    <p>Total aplicado: {{ number_format($expensa->detallesPago->sum('monto_aplicado'), 2) }} Bs de {{ number_format($expensa->monto_total, 2) }} Bs</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Botón de pago si hay deuda -->
    @if($expensa->total_pendiente > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">¿Deseas registrar un pago?</h3>
                    <p class="text-sm text-gray-600 mt-1">Saldo pendiente: <strong>{{ number_format($expensa->total_pendiente, 2) }} Bs</strong></p>
                </div>
                <a href="{{ route('expensas.pago', $expensa->unidad_id) }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Registrar Pago
                </a>
            </div>
        </div>
    @endif
</div>
