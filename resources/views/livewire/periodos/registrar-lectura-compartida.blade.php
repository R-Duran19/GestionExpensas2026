<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('periodos.lecturas', $periodoId) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            &larr; Volver a lecturas
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">Registrar Lectura Compartida</h2>
            <p class="text-gray-600 mt-1">
                Período: <strong>{{ $periodo->nombre_periodo }}</strong>
            </p>
        </div>

        <form wire:submit="guardar" class="p-6 space-y-6">
            <!-- Grupo de Medidores -->
            <div>
                <label for="grupo" class="block text-sm font-medium text-gray-700">
                    Grupo de Medidores <span class="text-red-500">*</span>
                </label>
                <select id="grupo"
                        wire:model.live="grupoId"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Seleccione un grupo...</option>
                    @foreach($this->grupos as $grupo)
                        <option value="{{ $grupo['id'] }}">
                            {{ $grupo['nombre'] }} ({{ $grupo['unidades_count'] }} unidades)
                        </option>
                    @endforeach
                </select>
                @if(count($this->grupos) === 0 && \App\Models\MedidorGrupo::where('activo', true)->count() > 0)
                    <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="ml-3">
                                <p class="font-medium text-yellow-800">Todos los grupos tienen lectura registrada</p>
                                <p class="text-yellow-700 mt-1">
                                    <a href="{{ route('periodos.lecturas', $periodoId) }}" class="underline font-medium">Ver lista de lecturas</a>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                @error('grupoId')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if($this->grupoSeleccionado)
                <!-- Lectura del Medidor Compartido -->
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Lectura del Medidor Compartido</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Lectura Anterior -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Lectura Anterior
                            </label>
                            <input type="text"
                                   value="{{ number_format($lecturaAnterior, 2, '.', '') }} M³"
                                   data-lectura-anterior="{{ number_format($lecturaAnterior, 2, '.', '') }}"
                                   readonly
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                            <p class="mt-1 text-xs text-gray-500">
                                (Automática del período anterior)
                            </p>
                        </div>

                        <!-- Lectura Actual -->
                        <div>
                            <label for="lecturaActual" class="block text-sm font-medium text-gray-700">
                                Lectura Actual (M³) <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   id="lecturaActual"
                                   wire:model.live.debounce.500ms="lecturaActual"
                                   step="0.01"
                                   min="0"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('lecturaActual')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if($this->consumoTotal > 0)
                        <!-- Resumen del consumo total -->
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Consumo Total del Grupo</p>
                                    <p class="text-xs text-blue-700 mt-1">
                                        Lectura Actual - Lectura Anterior
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ number_format($this->consumoTotal, 2) }} M³
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Distribución editable -->
                @if($this->consumoTotal > 0)
                    <div class="border border-purple-200 rounded-lg p-4 bg-purple-50/40">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Distribución del consumo entre unidades</h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    El consumo total se reparte automáticamente. Ajustá manualmente si es necesario.
                                </p>
                            </div>
                            <button type="button"
                                    wire:click="repartirEquitativo"
                                    class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-purple-700 bg-white border border-purple-300 rounded-md hover:bg-purple-50">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                Repartir equitativamente
                            </button>
                        </div>

                        <div class="bg-white rounded-lg overflow-hidden border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200" id="tabla-distribucion">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unidad</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Propietario</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-purple-700 uppercase">Consumo (M³)</th>
                                        @if($this->factoresAgua)
                                            <th class="px-4 py-2 text-right text-xs font-medium text-emerald-700 uppercase">Monto Estimado (Bs)</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($this->grupoSeleccionado['unidades'] as $unidad)
                                        <tr>
                                            <td class="px-4 py-2">
                                                <div class="text-sm font-medium text-gray-900">{{ $unidad['nombre'] }}</div>
                                                <div class="text-xs text-gray-500 sm:hidden">{{ $unidad['propietario'] ?: '-' }}</div>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-600 hidden sm:table-cell">
                                                {{ $unidad['propietario'] ?: '-' }}
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       wire:model="distribucion.{{ $unidad['id'] }}"
                                                       data-consumo-input
                                                       data-factor="{{ $this->factorParaCategoria($unidad['categoria']) }}"
                                                       class="block w-full sm:w-32 sm:ml-auto px-2 py-1.5 text-right border border-purple-300 rounded-md text-sm focus:ring-purple-500 focus:border-purple-500">
                                            </td>
                                            @if($this->factoresAgua)
                                                @php
                                                    $montoEstimado = $this->montoEstimadoBs($this->distribucion[$unidad['id']] ?? null, $unidad['categoria']);
                                                @endphp
                                                <td class="px-4 py-2 text-right">
                                                    <span data-monto-cell
                                                          class="text-sm font-semibold text-emerald-700">
                                                        {{ $montoEstimado !== null ? number_format($montoEstimado, 2).' Bs' : '—' }}
                                                    </span>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="2" class="px-4 py-2 text-xs font-medium text-gray-600 uppercase">Suma</td>
                                        <td class="px-4 py-2 text-sm font-bold text-right text-purple-700" id="suma-nueva">
                                            {{ number_format($this->sumaDistribucion, 2) }}
                                        </td>
                                        @if($this->factoresAgua)
                                            <td class="px-4 py-2 text-sm font-bold text-right text-emerald-700" id="monto-total">
                                                {{ $this->montoTotalEstimado !== null ? number_format($this->montoTotalEstimado, 2).' Bs' : '—' }}
                                            </td>
                                        @endif
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-sm">
                            <div id="indicador-diferencia"></div>
                            <span class="text-xs text-gray-500">
                                Total grupo: <span id="total-grupo-texto">{{ number_format($this->consumoTotal, 2) }}</span> M³
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            💡 Si la suma no coincide exactamente con el consumo total, se ajustará al guardar.
                        </p>
                    </div>
                @endif

                <!-- Observaciones -->
                <div class="mt-4">
                    <label for="observaciones" class="block text-sm font-medium text-gray-700">
                        Observaciones (opcional)
                    </label>
                    <textarea id="observaciones"
                              wire:model="observaciones"
                              rows="3"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
            @endif

            <!-- Botones -->
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="{{ route('periodos.lecturas', $periodoId) }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm font-medium">
                    Cancelar
                </a>
                <button type="submit"
                        @disabled(!$this->grupoId || !$this->lecturaActual)
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    Registrar y Distribuir
                </button>
            </div>
        </form>
    </div>

    <script>
        const DECIMALES_RLC = 2;

        function leerLecturaActualRLC() {
            const el = document.getElementById('lecturaActual');
            if (!el) return 0;
            const v = parseFloat(el.value);
            return isNaN(v) ? 0 : v;
        }

        function leerLecturaAnteriorRLC() {
            const el = document.querySelector('[data-lectura-anterior]');
            if (!el) return 0;
            return parseFloat(el.getAttribute('data-lectura-anterior')) || 0;
        }

        function sumaDistribucionRLC() {
            const inputs = document.querySelectorAll('input[data-consumo-input]');
            let suma = 0;
            inputs.forEach(input => {
                const v = parseFloat(input.value);
                if (!isNaN(v)) suma += v;
            });
            return suma;
        }

        // Replica del redondeo monetario de MedidorService::calcularMontoAgua
        function montoAguaRLC(consumo, factor) {
            const monto = consumo * factor;
            const decimal = monto - Math.floor(monto);
            return decimal < 0.50 ? Math.floor(monto) : Math.ceil(monto);
        }

        function actualizarMontosRLC() {
            const inputs = document.querySelectorAll('input[data-consumo-input]');
            if (inputs.length === 0) return;
            let totalMonto = 0;
            inputs.forEach(input => {
                const factor = parseFloat(input.getAttribute('data-factor')) || 0;
                const td = input.closest('td');
                const siguiente = td ? td.nextElementSibling : null;
                const celdaMonto = siguiente ? siguiente.querySelector('[data-monto-cell]') : null;
                const consumo = parseFloat(input.value);
                if (!celdaMonto || factor <= 0 || isNaN(consumo) || consumo <= 0) {
                    if (celdaMonto) celdaMonto.textContent = '—';
                    return;
                }
                const monto = montoAguaRLC(consumo, factor);
                totalMonto += monto;
                celdaMonto.textContent = monto.toFixed(DECIMALES_RLC) + ' Bs';
            });
            const celdaTotalMonto = document.getElementById('monto-total');
            if (celdaTotalMonto) celdaTotalMonto.textContent = totalMonto.toFixed(DECIMALES_RLC) + ' Bs';
        }

        function actualizarVistaRLC() {
            const inputs = document.querySelectorAll('input[data-consumo-input]');
            if (inputs.length === 0) return;

            const actual = leerLecturaActualRLC();
            const anterior = leerLecturaAnteriorRLC();
            const total = actual > anterior ? actual - anterior : 0;
            const suma = sumaDistribucionRLC();
            const diff = Math.round((total - suma) * 100) / 100;

            const celdaSuma = document.getElementById('suma-nueva');
            if (celdaSuma) celdaSuma.textContent = suma.toFixed(DECIMALES_RLC);

            const celdaTotal = document.getElementById('total-grupo-texto');
            if (celdaTotal) celdaTotal.textContent = total.toFixed(DECIMALES_RLC);

            actualizarMontosRLC();

            const indicador = document.getElementById('indicador-diferencia');
            if (indicador) {
                const diffAbs = Math.abs(diff);
                if (diffAbs < 0.01) {
                    indicador.innerHTML = `
                        <span class="inline-flex items-center px-3 py-1.5 rounded-md bg-green-100 text-green-800 border border-green-300 font-semibold">
                            <svg class="w-5 h-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            La suma coincide con el consumo total
                        </span>`;
                } else if (diffAbs <= 0.10) {
                    indicador.innerHTML = `
                        <span class="inline-flex items-center px-3 py-1.5 rounded-md bg-amber-100 text-amber-900 border border-amber-400 font-semibold shadow-sm">
                            <svg class="w-5 h-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Diferencia mínima: se ajustará al guardar (${diff > 0 ? '+' : ''}${diff.toFixed(DECIMALES_RLC)} M³)
                        </span>`;
                } else {
                    indicador.innerHTML = `
                        <span class="inline-flex items-center px-3 py-1.5 rounded-md bg-red-600 text-white border border-red-700 font-semibold shadow-md">
                            <svg class="w-5 h-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ⚠️ DIFERENCIA GRANDE: ${diff > 0 ? 'faltan' : 'sobra'} <span class="text-base font-bold mx-1">${diffAbs.toFixed(DECIMALES_RLC)}</span> M³ — se ajustará proporcionalmente al guardar
                        </span>`;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', actualizarVistaRLC);
        window.addEventListener('load', actualizarVistaRLC);
        document.addEventListener('livewire:navigated', actualizarVistaRLC);
        document.addEventListener('livewire:load', actualizarVistaRLC);
        document.addEventListener('livewire:initialized', actualizarVistaRLC);

        // Recalcular cuando un input de consumo cambia
        document.addEventListener('input', (e) => {
            if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-consumo-input')) {
                actualizarVistaRLC();
            }
        });
    </script>
</div>
