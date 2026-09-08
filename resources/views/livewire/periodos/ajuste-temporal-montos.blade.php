<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Banner HERRAMIENTA TEMPORAL -->
    <div class="mb-4 rounded-lg border-2 border-dashed border-red-400 bg-red-50 p-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-red-800">HERRAMIENTA TEMPORAL — Sobreescritura de montos</h2>
                <p class="text-sm text-red-700 mt-1">
                    Sobrescribe directamente los montos de expensa y agua (Bs) por unidad para cuadrar el período
                    contra cálculos externos (Excel). <strong>No modifica lecturas, factores ni el cálculo original</strong>.
                    Cada cambio queda registrado en auditoría. No regenerar las expensas del período después de ajustar
                    o se perderán estos valores.
                </p>
            </div>
        </div>
    </div>

    <!-- Encabezado -->
    <div class="mb-4">
        <a href="{{ route('periodos.show', $this->periodoId) }}"
           class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al período
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Ajuste Temporal de Montos</h1>
        <p class="text-gray-600 text-sm mt-1">
            Período: <span class="font-semibold">{{ $this->periodo->mes }}/{{ $this->periodo->año }}</span>
            ({{ $this->periodo->estado === 'abierto' ? 'abierto' : 'cerrado' }}) — {{ $this->expensas->total() }} unidades con expensa generada
        </p>
    </div>

    <!-- Selector de período + filtros -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Período</label>
                <select wire:model.live="periodoId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    @foreach($this->periodos as $p)
                        <option value="{{ $p->id }}">{{ $p->mes }}/{{ $p->año }} ({{ $p->estado === 'abierto' ? 'abierto' : 'cerrado' }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Tipo</label>
                <select wire:model.live="filtroTipo"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    <option value="local">Locales</option>
                    <option value="oficina">Oficinas</option>
                    <option value="departamento">Departamentos</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Código o propietario..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    @if($this->expensas->count() > 0)
        <div class="space-y-3">
            @foreach($this->expensas as $exp)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <!-- Header unidad -->
                    <div class="p-3 sm:p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <span class="font-bold text-gray-900">{{ $exp['codigo'] }}</span>
                            <span class="ml-2 text-xs text-gray-500">{{ $exp['piso'] }} · {{ ucfirst($exp['tipo_unidad']) }}</span>
                            @if($exp['propietario'])
                                <p class="text-sm text-gray-600">{{ $exp['propietario'] }}</p>
                            @endif
                        </div>
                        <div class="mt-1 sm:mt-0 text-xs text-gray-500 text-right">
                            Actual: expensa <strong>{{ number_format($exp['monto_expensa'], 2, '.', ',') }}</strong> Bs
                            · agua <strong>{{ number_format($exp['monto_agua'], 2, '.', ',') }}</strong> Bs
                            · total <strong>{{ number_format($exp['monto_total'], 2, '.', ',') }}</strong> Bs
                            @if($exp['pagado_expensa'] > 0 || $exp['pagado_agua'] > 0)
                                <br>Pagado: {{ number_format($exp['pagado_expensa'] + $exp['pagado_agua'], 2, '.', ',') }} Bs
                            @endif
                        </div>
                    </div>

                    <!-- Formulario ajuste -->
                    <div class="p-3 sm:p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Nueva Expensa (Bs) <span class="text-gray-400">(vacío = sin cambio)</span>
                                </label>
                                <input type="text"
                                       wire:model.live="ajustes.{{ $exp['id'] }}.monto_expensa"
                                       data-ajuste-input="expensa"
                                       inputmode="decimal"
                                       autocomplete="off"
                                       placeholder="{{ number_format($exp['monto_expensa'], 2, '.', '') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-base">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Nueva Agua (Bs) <span class="text-gray-400">(vacío = sin cambio)</span>
                                </label>
                                <input type="text"
                                       wire:model.live="ajustes.{{ $exp['id'] }}.monto_agua"
                                       data-ajuste-input="agua"
                                       inputmode="decimal"
                                       autocomplete="off"
                                       placeholder="{{ number_format($exp['monto_agua'], 2, '.', '') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-base">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Total resultante</label>
                                <div data-total-cell
                                     data-expensa-actual="{{ number_format($exp['monto_expensa'], 2, '.', '') }}"
                                     data-agua-actual="{{ number_format($exp['monto_agua'], 2, '.', '') }}"
                                     style="min-height: 38px;"
                                     class="w-full px-3 py-2 border rounded-lg text-sm font-bold flex items-center justify-center border-gray-200 bg-gray-50 text-gray-400">
                                    {{ number_format($exp['monto_total'], 2, '.', '') }} Bs
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button wire:click="guardarFila({{ $exp['id'] }})"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">
                                Sobrescribir esta unidad
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Paginación -->
        @if($this->expensas->hasPages())
            <div class="px-4 sm:px-6 lg:px-8 py-4 bg-white border-t border-gray-200 mt-4 rounded-lg">
                {{ $this->expensas->links() }}
            </div>
        @endif
    @else
        <div class="bg-white rounded-lg shadow-sm px-4 py-12 text-center">
            <h3 class="text-sm font-medium text-gray-900">Sin resultados</h3>
            <p class="mt-1 text-sm text-gray-500">
                No hay expensas generadas para este período con los filtros aplicados.
                Primero generá las expensas del período y luego ajustá los montos.
            </p>
        </div>
    @endif

    <!-- Botón sticky guardar todo -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg p-4 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <button wire:click="guardarTodo"
                    class="w-full sm:w-auto sm:float-left px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors flex items-center justify-center sm:justify-start">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Sobrescribir todas las unidades editadas
            </button>
            <div class="mt-2 sm:mt-0 sm:float-right text-sm text-gray-600 text-center sm:text-right">
                <span class="font-medium text-blue-600">
                    {{ count(array_filter($this->ajustes ?? [], fn($a) => ($a['monto_expensa'] ?? '') !== '' || ($a['monto_agua'] ?? '') !== '')) }}
                </span>
                unidades con cambios listos para guardar
            </div>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="h-20"></div>

    <script>
        // JS vanilla (sin Alpine): normaliza coma->punto y calcula el total en vivo.
        function actualizarTotalATM(cell) {
            const grid = cell.closest('div.grid');
            if (!grid) return;
            const inputExp = grid.querySelector('input[data-ajuste-input="expensa"]');
            const inputAgua = grid.querySelector('input[data-ajuste-input="agua"]');
            const actualExp = parseFloat(cell.getAttribute('data-expensa-actual')) || 0;
            const actualAgua = parseFloat(cell.getAttribute('data-agua-actual')) || 0;

            const parse = (el, fallback) => {
                if (!el || el.value === '') return fallback;
                const v = parseFloat(el.value.replace(',', '.'));
                return isNaN(v) ? fallback : v;
            };

            const total = parse(inputExp, actualExp) + parse(inputAgua, actualAgua);
            const tieneCambio = (inputExp && inputExp.value !== '') || (inputAgua && inputAgua.value !== '');

            const base = 'w-full px-3 py-2 border rounded-lg text-sm font-bold flex items-center justify-center';
            cell.className = base + (tieneCambio
                ? ' border-green-300 bg-green-50 text-green-700'
                : ' border-gray-200 bg-gray-50 text-gray-400');
            cell.textContent = total.toFixed(2) + ' Bs';
        }

        function actualizarTotalesATM() {
            document.querySelectorAll('[data-total-cell]').forEach(actualizarTotalATM);
        }

        // Normalizar coma -> punto y re-disparar input para que Livewire sincronice
        document.addEventListener('input', (e) => {
            const el = e.target;
            if (el && el.hasAttribute && el.hasAttribute('data-ajuste-input')) {
                if (el.value.includes(',')) {
                    el.value = el.value.replace(/,/g, '.');
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    return;
                }
                actualizarTotalesATM();
            }
        });
        document.addEventListener('DOMContentLoaded', actualizarTotalesATM);
        window.addEventListener('load', actualizarTotalesATM);
        document.addEventListener('livewire:load', actualizarTotalesATM);
        document.addEventListener('livewire:navigated', actualizarTotalesATM);

        function registrarHooksATM() {
            if (window.Livewire && window.Livewire.hook) {
                Livewire.hook('morphed', actualizarTotalesATM);
                Livewire.hook('commit', () => setTimeout(actualizarTotalesATM, 0));
            } else {
                setTimeout(registrarHooksATM, 50);
            }
        }
        registrarHooksATM();
    </script>
</div>
