<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-4">
        <a href="{{ route('periodos.lecturas', $periodoId) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver a lecturas
        </a>
    </div>

    @if($errorMensaje)
        <div class="mb-4 p-4 rounded-lg border border-red-300 bg-red-50 flex items-start justify-between gap-3">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-red-900">No se pudo guardar la lectura</p>
                    <p class="text-sm text-red-700 mt-0.5">{{ $errorMensaje }}</p>
                </div>
            </div>
            <button type="button" wire:click="$set('errorMensaje', null)" class="text-red-600 hover:text-red-800">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center bg-amber-100">
                    <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Editar Lectura Compartida</h2>
                    <p class="text-gray-600 mt-0.5 text-sm">
                        Grupo: <strong>{{ $grupoNombre }}</strong>
                    </p>
                </div>
            </div>
        </div>

        <form wire:submit="solicitarConfirmacion" class="p-4 sm:p-6 space-y-6">
            <!-- Lectura del grupo -->
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Lectura del Medidor Compartido</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lectura Anterior</label>
                        <input type="text"
                               value="{{ number_format($lecturaAnterior, 2) }} M³"
                               readonly
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                        <p class="mt-1 text-xs text-gray-500">(Automática del período anterior)</p>
                    </div>

                    <div>
                        <label for="lecturaActual" class="block text-sm font-medium text-gray-700">
                            Lectura Actual (M³) <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               id="lecturaActual"
                               wire:model="lecturaActual"
                               step="0.01"
                               min="0"
                               oninput="actualizarVistaJS()"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                        @error('lecturaActual')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-900">Consumo Total del Grupo</p>
                            <p class="text-xs text-blue-700 mt-1">Lectura Actual - Lectura Anterior</p>
                        </div>
                        <p class="text-2xl font-bold text-blue-600">
                            <span id="consumo-total-grupo">{{ number_format($this->consumoTotal, 2) }}</span> M³
                        </p>
                    </div>
                </div>
            </div>

            <!-- Distribución editable -->
            @if(!empty($distribucion))
                <div class="border border-purple-200 rounded-lg p-4 bg-purple-50/40">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Distribución del consumo entre unidades</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                El consumo total se reparte automáticamente. Ajustá manualmente si es necesario.
                            </p>
                        </div>
                        <button type="button"
                                onclick="repartirEquitativoJS()"
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
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Registrado (M³)</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-purple-700 uppercase">Nuevo (M³)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($this->grupoUnidadesInfo as $unidadId => $info)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-medium text-gray-900">{{ $info['nombre'] }}</div>
                                            <div class="text-xs text-gray-500 sm:hidden">{{ $info['propietario'] ?: '-' }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600 hidden sm:table-cell">
                                            {{ $info['propietario'] ?: '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500 text-right tabular-nums">
                                            {{ number_format($consumosOriginales[$unidadId] ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   wire:model="distribucion.{{ $unidadId }}"
                                                   data-consumo-input
                                                   class="consumo-input block w-full sm:w-28 sm:ml-auto px-2 py-1.5 text-right border border-purple-300 rounded-md text-sm focus:ring-purple-500 focus:border-purple-500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-2 text-xs font-medium text-gray-600 uppercase">Suma nueva</td>
                                    <td class="px-4 py-2 text-sm font-bold text-right text-purple-700" id="suma-nueva">
                                        {{ number_format($this->sumaDistribucion, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @php
                        $diff = $this->diferenciaDistribucion;
                        $okDistribucion = abs($diff) < 0.009;
                    @endphp
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <div id="indicador-diferencia">
                            <!-- Se actualiza vía JS -->
                        </div>
                        <span class="text-xs text-gray-500">
                            Total grupo: <span id="total-grupo-texto">{{ number_format($this->consumoTotal, 2) }}</span> M³
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        💡 Si la suma no coincide exactamente con el consumo total, se ajustará proporcionalmente al guardar.
                    </p>
                </div>
            @endif

            <!-- Observaciones -->
            <div>
                <label for="observaciones" class="block text-sm font-medium text-gray-700">
                    Observaciones (opcional)
                </label>
                <textarea id="observaciones"
                          wire:model="observaciones"
                          rows="3"
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500"></textarea>
            </div>

            <!-- Botones -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-4 space-y-3 sm:space-y-0 pt-4 border-t border-gray-200">
                <a href="{{ route('periodos.lecturas', $periodoId) }}"
                   class="w-full sm:w-auto px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm font-medium text-center">
                    Cancelar
                </a>
                <button type="submit"
                        class="w-full sm:w-auto px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm font-medium">
                    <svg class="inline w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Modal de confirmación (server-side, sin Alpine) -->
    @if($mostrarConfirmacion)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
            <div class="flex items-end sm:items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-40"
                     wire:click="cerrarConfirmacion"
                     aria-hidden="true"></div>

                <!-- Panel -->
                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-50">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 13l4 4L19 7M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Confirmar cambios
                                </h3>
                                <div class="mt-2 space-y-1">
                                    <p class="text-sm text-gray-500">
                                        Vas a actualizar la lectura del grupo <strong>{{ $grupoNombre }}</strong>
                                        a <strong>{{ number_format($lecturaActual ?? 0, 2) }} M³</strong>.
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Consumo total: <strong>{{ number_format($this->consumoTotal, 2) }} M³</strong>.
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Suma de la distribución: <strong>{{ number_format($this->sumaDistribucion, 2) }} M³</strong>.
                                    </p>
                                    <p class="text-xs text-amber-700 mt-2">
                                        Esta acción quedará registrada en el historial.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="button"
                                wire:click="confirmarGuardado"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Sí, guardar
                        </button>
                        <button type="button"
                                wire:click="cerrarConfirmacion"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        const LECTURA_ANTERIOR = {{ (float) $lecturaAnterior }};
        const NUM_UNIDADES = {{ count($this->unidadIds) }};
        const DECIMALES = 2;

        /**
         * Lee el valor actual de "Lectura Actual" del input.
         */
        function leerLecturaActual() {
            const el = document.getElementById('lecturaActual');
            if (!el) return 0;
            const v = parseFloat(el.value);
            return isNaN(v) ? 0 : v;
        }

        /**
         * Calcula el consumo total (lectura actual - anterior).
         */
        function consumoTotalJS() {
            const actual = leerLecturaActual();
            const total = actual - LECTURA_ANTERIOR;
            return total > 0 ? total : 0;
        }

        /**
         * Suma de todos los inputs "Nuevo".
         */
        function sumaDistribucionJS() {
            const inputs = document.querySelectorAll('input[data-consumo-input]');
            let suma = 0;
            inputs.forEach(input => {
                const v = parseFloat(input.value);
                if (!isNaN(v)) suma += v;
            });
            return suma;
        }

        /**
         * Reparte el consumo total equitativamente entre todas las unidades.
         * Las primeras N-1 reciben el valor base redondeado y la ÚLTIMA absorbe
         * el residuo de redondeo para que la suma sea exacta.
         */
        function repartirEquitativoJS() {
            const total = consumoTotalJS();
            if (total <= 0) {
                alert('La lectura actual debe ser mayor a la anterior (' + LECTURA_ANTERIOR + ').');
                return;
            }
            if (NUM_UNIDADES === 0) return;

            const valorBase = Math.round((total / NUM_UNIDADES) * 100) / 100;
            const sumaBase = valorBase * (NUM_UNIDADES - 1);
            const valorUltimo = Math.round((total - sumaBase) * 100) / 100;

            const inputs = document.querySelectorAll('input[data-consumo-input]');
            const arr = Array.from(inputs);

            // Limpiar resaltado previo
            arr.forEach(input => {
                const tr = input.closest('tr');
                if (tr) {
                    tr.classList.remove('bg-amber-50', 'border-amber-300');
                    const badge = tr.querySelector('.badge-residuo');
                    if (badge) badge.remove();
                }
            });

            arr.forEach((input, idx) => {
                const esUltimo = idx === arr.length - 1;
                const absorbeResiduo = esUltimo && Math.abs(valorUltimo - valorBase) > 0.009;

                input.value = (esUltimo ? valorUltimo : valorBase).toFixed(DECIMALES);
                input.dispatchEvent(new Event('input', { bubbles: true }));

                // Resaltar la fila que absorbe el residuo
                if (absorbeResiduo) {
                    const tr = input.closest('tr');
                    if (tr) {
                        tr.classList.add('bg-amber-50');
                        const celdaInput = input.closest('td');
                        if (celdaInput) {
                            const badge = document.createElement('span');
                            badge.className = 'badge-residuo block text-[10px] text-amber-700 font-medium mt-0.5 text-center';
                            badge.textContent = 'absorbe redondeo';
                            celdaInput.appendChild(badge);
                        }
                    }
                }
            });

            actualizarVistaJS();
        }

        /**
         * Recalcula y muestra la suma, el total y la diferencia en tiempo real.
         * Además, si el usuario NO está editando la última unidad, autoajusta
         * la última para que la suma cuadre exactamente con el consumo total.
         */
        function actualizarVistaJS() {
            const inputs = Array.from(document.querySelectorAll('input[data-consumo-input]'));
            if (inputs.length === 0) return;

            const total = consumoTotalJS();
            const foco = document.activeElement;
            const ultimoInput = inputs[inputs.length - 1];

            // Si hay más de una unidad y el usuario NO está enfocado en la última,
            // recalcular la última para que cuadre con el consumo total.
            if (inputs.length > 1 && foco !== ultimoInput && total > 0) {
                let sumaOtros = 0;
                for (let i = 0; i < inputs.length - 1; i++) {
                    const v = parseFloat(inputs[i].value);
                    if (!isNaN(v)) sumaOtros += v;
                }
                let nuevoUltimo = Math.round((total - sumaOtros) * 100) / 100;
                if (nuevoUltimo < 0) nuevoUltimo = 0;
                const valorActual = parseFloat(ultimoInput.value);
                if (isNaN(valorActual) || Math.abs(valorActual - nuevoUltimo) > 0.009) {
                    ultimoInput.value = nuevoUltimo.toFixed(DECIMALES);
                    // Avisar a Livewire (si wire:model.live) — no duele
                    ultimoInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                // Resaltar la última unidad si absorbe residuo
                const tr = ultimoInput.closest('tr');
                if (tr) {
                    const valorBase = Math.round((total / NUM_UNIDADES) * 100) / 100;
                    if (Math.abs(nuevoUltimo - valorBase) > 0.009) {
                        tr.classList.add('bg-amber-50');
                        let badge = tr.querySelector('.badge-residuo');
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'badge-residuo block text-[10px] text-amber-700 font-medium mt-0.5 text-center';
                            const celdaInput = ultimoInput.closest('td');
                            if (celdaInput) celdaInput.appendChild(badge);
                        }
                        badge.textContent = `absorbe ${nuevoUltimo < valorBase ? '-' : '+'}${Math.abs(nuevoUltimo - valorBase).toFixed(DECIMALES)}`;
                    } else {
                        tr.classList.remove('bg-amber-50');
                        const badge = tr.querySelector('.badge-residuo');
                        if (badge) badge.remove();
                    }
                }
            }

            const suma = sumaDistribucionJS();
            const diff = Math.round((total - suma) * 100) / 100;

            const celdaSuma = document.getElementById('suma-nueva');
            if (celdaSuma) celdaSuma.textContent = suma.toFixed(DECIMALES);

            const celdaTotal = document.getElementById('total-grupo-texto');
            if (celdaTotal) celdaTotal.textContent = total.toFixed(DECIMALES);

            const celdaTotalGrupo = document.getElementById('consumo-total-grupo');
            if (celdaTotalGrupo) celdaTotalGrupo.textContent = total.toFixed(DECIMALES);

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
                            Diferencia mínima: se ajustará a la última unidad al guardar (${diff > 0 ? '+' : ''}${diff.toFixed(DECIMALES)} M³)
                        </span>`;
                } else {
                    indicador.innerHTML = `
                        <span class="inline-flex items-center px-3 py-1.5 rounded-md bg-red-600 text-white border border-red-700 font-semibold shadow-md">
                            <svg class="w-5 h-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ⚠️ DIFERENCIA GRANDE: ${diff > 0 ? 'faltan' : 'sobra'} <span class="text-base font-bold mx-1">${diffAbs.toFixed(DECIMALES)}</span> M³ — se ajustará proporcionalmente al guardar
                        </span>`;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', actualizarVistaJS);
        window.addEventListener('load', actualizarVistaJS);

        // Tras cada render de Livewire (morph), recalcular
        document.addEventListener('livewire:navigated', actualizarVistaJS);
        document.addEventListener('livewire:load', actualizarVistaJS);
        if (window.Livewire) {
            document.addEventListener('livewire:initialized', actualizarVistaJS);
            window.Livewire.hook('morph.updated', actualizarVistaJS);
        }

        // Listener delegado: cuando un input de consumo cambia, recalcular
        document.addEventListener('input', (e) => {
            if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-consumo-input')) {
                actualizarVistaJS();
            }
        });
    </script>
</div>
