        @else
            @if($datosHorizontales && !empty($datosHorizontales['filas']))
                <style>
                    .truncate {
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                        cursor: help;
                    }
                </style>
                {{-- Tabla Horizontal con Scroll --}}
                <div class="overflow-x-auto">
                    @if($vista === 'resumida')
                        {{-- Vista Resumida Horizontal --}}
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 shadow-sm">
                                        Tipo Unidad
                                    </th>
                                    @foreach($datosHorizontales['periodos'] as $periodo)
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            {{ $periodo['nombre'] }}
                                        </th>
                                    @endforeach
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider whitespace-nowrap bg-indigo-50">
                                        TOTAL GENERADO
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-green-700 uppercase tracking-wider whitespace-nowrap bg-green-50">
                                        TOTAL PAGADO
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-red-700 uppercase tracking-wider whitespace-nowrap bg-red-50">
                                        TOTAL DEUDA
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($datosHorizontales['filas'] as $fila)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white shadow-sm">
                                            {{ $fila['tipo_unidad'] }}
                                        </td>
                                        @foreach($fila['periodos'] as $periodo)
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                                                Bs.{{ number_format($periodo['generado'], 2) }}
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold bg-indigo-50">
                                            Bs.{{ number_format($fila['totales']['generado'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-green-600 font-medium text-right bg-green-50">
                                            Bs.{{ number_format($fila['totales']['cobrado'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-red-600 font-medium text-right bg-red-50">
                                            Bs.{{ number_format($fila['totales']['deuda'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            {{-- Footer con totales generales --}}
                            @if($this->totalesColumnas)
                            <tfoot class="bg-blue-50 border-t-2 border-blue-300">
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 sticky left-0 bg-blue-50 shadow-sm">
                                        Σ TOTAL
                                    </td>
                                    @foreach($datosHorizontales['periodos'] as $periodo)
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-right">
                                            —
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold bg-indigo-100">
                                        Bs.{{ number_format($this->totalesColumnas['generado'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-green-700 text-right font-bold bg-green-100">
                                        Bs.{{ number_format($this->totalesColumnas['cobrado'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-red-700 text-right font-bold bg-red-100">
                                        Bs.{{ number_format($this->totalesColumnas['deuda'], 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    @else
                        {{-- Vista Detallada Horizontal --}}
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 shadow-sm">
                                        Unidad
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" style="width: 120px;">
                                        Propietario
                                    </th>
                                    @foreach($datosHorizontales['periodos'] as $periodo)
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            {{ $periodo['nombre'] }}
                                        </th>
                                    @endforeach
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider whitespace-nowrap bg-indigo-50">
                                        TOTAL GENERADO
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-green-700 uppercase tracking-wider whitespace-nowrap bg-green-50">
                                        TOTAL PAGADO
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-red-700 uppercase tracking-wider whitespace-nowrap bg-red-50">
                                        TOTAL DEUDA
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-900 uppercase tracking-wider whitespace-nowrap">
                                        % COBRANZA
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($datosHorizontales['filas'] as $fila)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white shadow-sm">
                                            {{ $fila['unidad'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500" style="max-width: 120px; overflow: hidden;">
                                            <div class="truncate" title="{{ $fila['propietario'] }}">
                                                {{ $fila['propietario'] }}
                                            </div>
                                        </td>
                                        @foreach($fila['periodos'] as $periodo)
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                                                Bs.{{ number_format($periodo['generado'], 2) }}
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold bg-indigo-50">
                                            Bs.{{ number_format($fila['totales']['generado'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-green-600 font-medium text-right bg-green-50">
                                            Bs.{{ number_format($fila['totales']['cobrado'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-red-600 font-medium text-right bg-red-50">
                                            Bs.{{ number_format($fila['totales']['deuda'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                                            {{ number_format($fila['totales']['porcentaje_cobranza'], 1) }}%
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            {{-- Footer con totales generales --}}
                            @if($this->totalesColumnas)
                            <tfoot class="bg-blue-50 border-t-2 border-blue-300">
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 sticky left-0 bg-blue-50 shadow-sm" colspan="2">
                                        Σ TOTAL
                                    </td>
                                    @foreach($datosHorizontales['periodos'] as $periodo)
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-right">
                                            —
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold bg-indigo-100">
                                        Bs.{{ number_format($this->totalesColumnas['generado'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-green-700 text-right font-bold bg-green-100">
                                        Bs.{{ number_format($this->totalesColumnas['cobrado'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-red-700 text-right font-bold bg-red-100">
                                        Bs.{{ number_format($this->totalesColumnas['deuda'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                        {{ $this->totalesColumnas['generado'] > 0 ? number_format(($this->totalesColumnas['cobrado'] / $this->totalesColumnas['generado']) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    @endif
                </div>

                {{-- Resumen de registros --}}
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <p class="text-sm text-gray-700">
                        Mostrando <span class="font-medium">{{ count($datosHorizontales['filas']) }}</span> registros
                        @if($filtroAño)
                            del año <span class="font-medium">{{ $filtroAño }}</span>
                        @endif
                    </p>
                </div>
            @endif
        @endif
