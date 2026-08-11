<div>
    <div class="mb-6">
        <a href="{{ route('expensas.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a expensas
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

            <!-- Información del responsable de pago y propietario -->
            <div class="space-y-4">
                @php
                    $inquilino = $unidad->inquilinoActual;
                    $propietario = $unidad->propietarioActual;
                @endphp

                @if($inquilino)
                    <!-- Responsable de pago (Inquilino) -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center gap-2">
                            Responsable de Pago
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                Inquilino
                            </span>
                        </h4>
                        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                            <div class="text-sm font-medium text-gray-900 mb-2">
                                {{ $inquilino->nombre_completo }}
                            </div>
                            <div class="space-y-1 text-sm text-gray-600">
                                <div>CI: {{ $inquilino->ci ?: 'Sin CI' }}</div>
                                @if($inquilino->telefono)
                                    <div>Tel: {{ $inquilino->telefono }}</div>
                                @endif
                                @if($inquilino->email)
                                    <div>{{ $inquilino->email }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($propietario)
                        <!-- Propietario (secundario) -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Propietario</h4>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <div class="text-sm font-medium text-gray-700">
                                    {{ $propietario->nombre_completo }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    CI: {{ $propietario->ci ?: 'Sin CI' }}
                                </div>
                            </div>
                        </div>
                    @endif
                @elseif($propietario)
                    <!-- Solo propietario (sin inquilino) -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center gap-2">
                            Propietario
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                Propietario
                            </span>
                        </h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-900 mb-2">
                                {{ $propietario->nombre_completo }}
                            </div>
                            <div class="space-y-1 text-sm text-gray-600">
                                <div>CI: {{ $propietario->ci ?: 'Sin CI' }}</div>
                                @if($propietario->telefono)
                                    <div>Tel: {{ $propietario->telefono }}</div>
                                @endif
                                @if($propietario->email)
                                    <div>{{ $propietario->email }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Propietario</h4>
                        <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-500">
                            Sin propietario asignado
                        </div>
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
                    {{ number_format($expensa->monto_expensa, 2, ',', '.') }} Bs
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
                        {{ number_format($expensa->monto_agua, 2, ',', '.') }} Bs
                    </div>
                </div>
            @endif

            <!-- Subtotal expensa actual -->
            <div class="flex justify-between items-center py-3 bg-gray-50 rounded-lg px-4 my-2">
                <div class="text-sm font-medium text-gray-700">Subtotal Expensa Actual</div>
                <div class="text-lg font-bold text-gray-900">
                    {{ number_format($subtotalExpensaActual, 2, ',', '.') }} Bs
                </div>
            </div>

            <!-- Deuda anterior -->
            @if($totalDeudasAnteriores > 0)
                <div class="py-3 border-b border-gray-200" x-data="{ open: false }">
                    <div class="flex justify-between items-center cursor-pointer" x-on:click="open = !open">
                        <div>
                            <div class="text-sm font-medium text-red-600">Deuda de períodos anteriores</div>
                            <div class="text-xs text-gray-500">Meses impagos anteriores</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-sm font-medium text-red-600">
                                +{{ number_format($totalDeudasAnteriores, 2, ',', '.') }} Bs
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
                                        {{ number_format(($deuda->expensa_pendiente + $deuda->agua_pendiente), 2, ',', '.') }} Bs
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endif

            <!-- Total -->
            <div class="flex justify-between items-center py-4 bg-indigo-50 rounded-lg px-4 mt-4">
                <div class="text-lg font-bold text-gray-900">Total Emitido</div>
                <div class="text-2xl font-bold text-indigo-600">
                    {{ number_format($expensa->monto_total, 2, ',', '.') }} Bs
                </div>
            </div>

            <!-- Información de pagos -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Estado de Pago</h4>

                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Estado:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            bg-{{ $estadoColor }}-100 text-{{ $estadoColor }}-800">
                            {{ $estadoTexto }}
                        </span>
                    </div>

                    <!-- Pendiente por concepto -->
                    @if($expensa->expensa_pendiente > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Pendiente Expensa:</span>
                            <span class="text-sm font-medium text-red-600">
                                {{ number_format($expensa->expensa_pendiente, 2, ',', '.') }} Bs
                            </span>
                        </div>
                    @endif

                    @if($expensa->agua_pendiente > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Pendiente Agua:</span>
                            <span class="text-sm font-medium text-red-600">
                                {{ number_format($expensa->agua_pendiente, 2, ',', '.') }} Bs
                            </span>
                        </div>
                    @endif

                    <!-- Total pendiente -->
                    @if($totalPendienteActual > 0)
                        <div class="flex justify-between items-center bg-red-50 rounded px-3 py-2 mt-2">
                            <span class="text-sm font-medium text-gray-900">Total Pendiente:</span>
                            <span class="text-sm font-bold text-red-700">
                                {{ number_format($totalPendienteActual, 2, ',', '.') }} Bs
                            </span>
                        </div>
                    @endif

                    <!-- Total pagado -->
                    @if($totalPagadoActual > 0)
                        <div class="flex justify-between items-center bg-green-50 rounded px-3 py-2 mt-2">
                            <span class="text-sm font-medium text-gray-900">Total Pagado:</span>
                            <span class="text-sm font-bold text-green-700">
                                {{ number_format($totalPagadoActual, 2, ',', '.') }} Bs
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Botón de pago si hay deuda -->
            @if($totalPendienteActual > 0)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('expensas.pago', $unidad->id) }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Registrar Pago ({{ number_format($totalPendienteActual, 2, ',', '.') }} Bs)
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Historial de pagos (si existen) -->
    @if($historialPagos->count() > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Historial de Pagos</h3>

            <div class="space-y-3">
                @foreach($historialPagos as $ingreso)
                    @if($ingreso)
                        <div class="flex justify-between items-center py-3 border-b border-gray-200 last:border-b-0{{ $ingreso->trashed() ? ' bg-red-50' : '' }}">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    Recibo #{{ $ingreso->numero_recibo }}
                                    @if($ingreso->trashed())
                                        <span class="ml-2 text-xs text-red-600">(Anulado)</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $ingreso->fecha->format('d/m/Y') }}
                                    @if($ingreso->metodo_pago)
                                        - {{ ucfirst($ingreso->metodo_pago) }}
                                    @endif
                                </div>
                                {{-- Mostrar detalles de expensas pagadas --}}
                                @if($ingreso->detalles->count() > 0)
                                    <div class="text-xs text-gray-400 mt-1">
                                        Aplicado a: {{ $ingreso->detalles->pluck('expensa.periodo.nombre_periodo')->unique()->join(', ') }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-sm font-medium text-green-600">
                                {{ number_format($ingreso->monto_total, 2, ',', '.') }} Bs
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- Saldos a favor (si existen) -->
    @if($saldosAFavor->count() > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Saldos a Favor Disponibles</h3>

            <div class="space-y-2">
                @foreach($saldosAFavor as $saldo)
                    @if($saldo->ingreso)
                        <div class="flex justify-between items-center py-2 px-3 {{ $saldo->ingreso->trashed() ? 'bg-red-50' : 'bg-blue-50' }} rounded">
                            <div class="text-sm {{ $saldo->ingreso->trashed() ? 'text-gray-500 italic' : 'text-gray-700' }}">
                                @if($saldo->ingreso->trashed())
                                    <span class="text-red-600">(Recibo anulado) </span>
                                @endif
                                Del recibo #{{ $saldo->ingreso->numero_recibo }}
                                ({{ $saldo->ingreso->fecha->format('d/m/Y') }})
                            </div>
                            <div class="text-sm font-medium {{ $saldo->ingreso->trashed() ? 'text-red-700' : 'text-blue-700' }}">
                                {{ number_format($saldo->monto_saldo, 2, ',', '.') }} Bs
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-3 pt-3 border-t border-blue-200 text-sm text-gray-600">
                <strong>Total disponible:</strong> {{ number_format($totalSaldoAFavor, 2, ',', '.') }} Bs
            </div>
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
                                        <span class="font-bold text-indigo-600">{{ number_format($expensa->monto_agua, 2, ',', '.') }} Bs</span>
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
