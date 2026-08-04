<div>
    {{-- Header con navegación --}}
    <div class="mb-6">
        <a href="{{ route('caja.ingresos.index') }}"
           class="inline-flex items-center text-green-600 hover:text-green-800 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a Ingresos
        </a>
    </div>

    {{-- Título principal --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-file-invoice-dollar text-green-600 mr-2"></i>
                Detalle de Ingreso
            </h1>
            <p class="text-gray-600 mt-1">Recibo N° {{ $ingreso->numero_recibo }}</p>
        </div>
        <div class="flex gap-2">
            {{-- Botón ver PDF --}}
            <a href="{{ route('caja.ingresos.pdf', $ingreso) }}"
               target="_blank"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-file-pdf"></i>
                Ver PDF
            </a>

            {{-- Botón editar --}}
            <a href="{{ route('caja.ingresos.edit', $ingreso) }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-edit"></i>
                Editar
            </a>
        </div>
    </div>

    {{-- Información principal del recibo --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-receipt text-green-600 mr-2"></i>
                Información del Recibo
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <span class="text-xs text-gray-500 uppercase">N° de Recibo</span>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ $ingreso->numero_recibo }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase">Fecha</span>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ $ingreso->fecha->format('d/m/Y') }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase">Método de Pago</span>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ $ingreso->metodo_pago_formateado }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase">Importe Total</span>
                    <p class="text-xl font-bold text-green-600 mt-1">Bs {{ number_format($ingreso->importe, 2, ',', '.') }}</p>
                </div>
            </div>

            @if($ingreso->referencia_bancaria)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <span class="text-xs text-gray-500 uppercase">Referencia Bancaria</span>
                    <p class="text-lg text-gray-900 mt-1">{{ $ingreso->referencia_bancaria }}</p>
                </div>
            @endif

            @if($ingreso->descripcion)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <span class="text-xs text-gray-500 uppercase">Descripción</span>
                    <p class="text-lg text-gray-900 mt-1">{{ $ingreso->descripcion }}</p>
                </div>
            @endif

            @if($ingreso->categoria)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <span class="text-xs text-gray-500 uppercase">Categoría</span>
                    <div class="mt-1">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white"
                              style="background-color: {{ $ingreso->categoria->color }};">
                            {{ $ingreso->categoria->nombre }}
                        </span>
                    </div>
                </div>
            @endif

            @if($ingreso->observaciones)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <span class="text-xs text-gray-500 uppercase">Observaciones</span>
                    <p class="text-sm text-gray-700 mt-1">{{ $ingreso->observaciones }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Detalles de expensas (si aplica) --}}
    @if($ingreso->tipo_ingreso === 'expensa' && $detallesExpensas->isNotEmpty())
        @php
            // El pagador se obtiene directamente del ingreso (campo estructurado pagador_tipo/pagador_id).
            // Si no está cargado (ingresos viejos), no se muestra el bloque "Pagado por".
            $pagador = $ingreso->pagador;

            // Agrupar detalles por período
            $detallesPorPeriodo = $detallesExpensas
                ->filter(fn($d) => $d->expensa && $d->expensa->periodo)
                ->groupBy(fn($d) => $d->expensa->periodo->id);
        @endphp

        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-list-ul text-purple-600 mr-2"></i>
                    Detalle de Pagos de Expensas
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $detallesExpensas->count() }} expensa(s) pagada(s) en {{ $detallesPorPeriodo->count() }} período(s)
                </p>
            </div>

            {{-- Mostrar quién pagó (propietario o inquilino) --}}
            @if($pagador)
                <div class="bg-purple-50 border-l-4 border-purple-500 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-purple-100 rounded-full p-2">
                            <i class="fas fa-user text-purple-600"></i>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase">Pagado por</span>
                            <p class="font-semibold text-gray-900">
                                {{ $pagador->nombre_completo }}
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    @if($ingreso->pagador_tipo === 'inquilino') bg-blue-100 text-blue-800
                                    @else bg-green-100 text-green-800 @endif">
                                    {{ $ingreso->pagador_tipo_formateado }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Iterar por cada período --}}
            @foreach($detallesPorPeriodo as $periodoId => $detalles)
                @php
                    $periodo = $detalles->first()->expensa->periodo;
                    $subtotalPeriodo = $detalles->sum('monto_aplicado');
                @endphp

                {{-- Encabezado del período --}}
                <div class="bg-gray-100 border-b border-gray-200 px-6 py-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-indigo-100 rounded-full p-2">
                                <i class="fas fa-calendar text-indigo-600 text-sm"></i>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900">
                                {{ $periodo->nombre_periodo }}
                            </h3>
                        </div>
                        <span class="text-sm font-medium text-gray-600">
                            {{ $detalles->count() }} unidad(es)
                        </span>
                    </div>
                </div>

                {{-- Tabla de unidades del período --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Unidad
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Concepto
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Expensa
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Agua
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($detalles as $detalle)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($detalle->expensa && $detalle->expensa->unidad)
                                            <span class="font-semibold">{{ $detalle->expensa->unidad->codigo }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        @if($detalle->concepto === 'expensa')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Expensa
                                            </span>
                                        @elseif($detalle->concepto === 'agua')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                                                Agua
                                            </span>
                                        @elseif($detalle->concepto === 'ambos')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                Ambos
                                            </span>
                                        @else
                                            <span class="text-gray-400">{{ $detalle->concepto }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                        @if($detalle->monto_expensa_aplicado > 0)
                                            Bs {{ number_format($detalle->monto_expensa_aplicado, 2, ',', '.') }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                        @if($detalle->monto_agua_aplicado > 0)
                                            Bs {{ number_format($detalle->monto_agua_aplicado, 2, ',', '.') }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                        Bs {{ number_format($detalle->monto_aplicado, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-indigo-50 font-semibold">
                            <tr>
                                <td colspan="4" class="px-6 py-3 text-right text-gray-700">
                                    Subtotal {{ $periodo->nombre_periodo }}:
                                </td>
                                <td class="px-6 py-3 text-right font-bold text-indigo-700">
                                    Bs {{ number_format($subtotalPeriodo, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if(!$loop->last)
                    <div class="border-t-2 border-dashed border-gray-300 my-4"></div>
                @endif
            @endforeach

            {{-- Total general --}}
            <div class="bg-purple-100 border-t-2 border-purple-200 px-6 py-4">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-900">Total General:</span>
                    <span class="text-2xl font-bold text-purple-700">
                        Bs {{ number_format($detallesExpensas->sum('monto_aplicado'), 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    @endif

    {{-- Saldos a favor generados por este ingreso --}}
    @if($ingreso->saldosFavor && $ingreso->saldosFavor->isNotEmpty())
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-wallet text-green-600 mr-2"></i>
                    Saldos a Favor Generados
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $ingreso->saldosFavor->count() }} saldo(s) a favor generado(s)
                </p>
            </div>

            @foreach($ingreso->saldosFavor as $saldo)
                <div class="p-6 @if(!$loop->first) border-t border-gray-200 @endif">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="bg-green-100 rounded-full p-2">
                                    <i class="fas fa-home text-green-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        Unidad: {{ $saldo->unidad->codigo }}
                                    </h3>
                                    @if($saldo->unidad->propietarios && $prop = $saldo->unidad->propietarios->firstWhere('pivot.es_propietario_actual', true))
                                        <p class="text-sm text-gray-600">{{ $prop->nombre_completo }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 ml-11">
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">Monto Original</span>
                                    <p class="font-semibold text-gray-900">Bs {{ number_format($saldo->monto_original, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">Aplicado</span>
                                    <p class="font-semibold text-gray-900">Bs {{ number_format($saldo->monto_aplicado, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">Saldo Disponible</span>
                                    <p class="font-bold text-green-600">Bs {{ number_format($saldo->monto_saldo, 2, ',', '.') }}</p>
                                </div>
                            </div>

                            <div class="ml-11 mt-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($saldo->estado === 'disponible') bg-green-100 text-green-800
                                    @elseif($saldo->estado === 'consumido') bg-gray-100 text-gray-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($saldo->estado) }}
                                </span>
                                @if($saldo->consumido_totalmente_en)
                                    <span class="text-xs text-gray-500 ml-3">
                                        Consumido: {{ $saldo->consumido_totalmente_en->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Barra de progreso --}}
                        <div class="ml-6 text-right" style="min-width: 150px;">
                            <div class="text-xs text-gray-500 mb-1">{{ number_format($saldo->porcentaje_consumido, 1) }}% consumido</div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full"
                                     style="width: {{ $saldo->porcentaje_consumido }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Comprobante (si existe) --}}
    @if($ingreso->comprobante_foto)
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-image text-indigo-600 mr-2"></i>
                    Comprobante
                </h2>
            </div>
            <div class="p-6">
                <div class="bg-gray-100 rounded-lg p-4 text-center">
                    <img src="{{ asset('storage/' . $ingreso->comprobante_foto) }}"
                         alt="Comprobante"
                         class="max-h-96 mx-auto rounded-lg shadow-lg">
                </div>
            </div>
        </div>
    @endif

    {{-- Información de auditoría --}}
    <div class="bg-gray-50 rounded-lg">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-sm font-semibold text-gray-700">
                <i class="fas fa-info-circle text-gray-500 mr-2"></i>
                Información de Auditoría
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Creado por:</span>
                    <span class="ml-2 text-gray-900">{{ $ingreso->creadoPor?->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Fecha de creación:</span>
                    <span class="ml-2 text-gray-900">{{ $ingreso->created_at->format('d/m/Y H:i') }}</span>
                </div>
                @if($ingreso->actualizadoPor)
                    <div>
                        <span class="text-gray-500">Actualizado por:</span>
                        <span class="ml-2 text-gray-900">{{ $ingreso->actualizadoPor->name }}</span>
                    </div>
                @endif
                @if($ingreso->updated_at != $ingreso->created_at)
                    <div>
                        <span class="text-gray-500">Última actualización:</span>
                        <span class="ml-2 text-gray-900">{{ $ingreso->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
