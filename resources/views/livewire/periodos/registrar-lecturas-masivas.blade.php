<div class="min-h-screen pb-24">
    <!-- Header -->
    <div class="bg-white shadow-sm sticky top-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Lecturas Individuales</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ $this->periodo->nombre_periodo }} - Medidores individuales por unidad</p>
                </div>
                <a href="{{ route('periodos.lecturas', $periodoId) }}"
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
                    <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span class="hidden sm:inline">Volver</span>
                </a>
            </div>

            <!-- Filtros -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text"
                       wire:model.live="search"
                       placeholder="Buscar por código o nombre..."
                       class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-base">

                <select wire:model.live="filtroTipo"
                        class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos los tipos</option>
                    <option value="departamento">Departamentos</option>
                    <option value="oficina">Oficinas</option>
                    <option value="local">Locales</option>
                </select>

                <select wire:model.live="filtroEstado"
                        class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todas las unidades</option>
                    <option value="sin_lectura">Sin lectura</option>
                    <option value="con_lectura">Con lectura</option>
                </select>
            </div>

            <!-- Resumen -->
            <div class="mt-4 grid grid-cols-3 gap-2 sm:gap-4">
                <div class="bg-blue-50 rounded-lg p-2 sm:p-3 text-center">
                    <p class="text-xl sm:text-2xl font-bold text-blue-600">{{ $this->unidades->total() }}</p>
                    <p class="text-xs text-blue-700">Pendientes</p>
                </div>
                <div class="bg-green-50 rounded-lg p-2 sm:p-3 text-center">
                    <p class="text-xl sm:text-2xl font-bold text-green-600">
                        {{ \App\Models\LecturaMedidor::where('periodo_id', $periodoId)
                            ->whereHas('unidad', fn($q) => $q->where('tipo_medidor', 'individual'))
                            ->count() }}
                    </p>
                    <p class="text-xs text-green-700">Registradas</p>
                </div>
                <div class="bg-orange-50 rounded-lg p-2 sm:p-3 text-center">
                    <p class="text-xl sm:text-2xl font-bold text-orange-600">
                        {{ \App\Models\Unidad::where('activo', true)->where('tiene_medidor', true)->where('tipo_medidor', 'individual')->count() }}
                    </p>
                    <p class="text-xs text-orange-700">Total Individuales</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Unidades -->
    @if($this->unidades->count() > 0)
        <div class="px-4 sm:px-6 lg:px-8 py-4 space-y-3">
            @foreach($this->unidades as $unidad)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <!-- Header de la unidad -->
                    <div class="p-3 sm:p-4 border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 rounded-lg flex items-center justify-center text-white font-bold text-sm shrink-0"
                                     style="background-color: {{ \App\Models\Unidad::find($unidad['id'])?->color ?? '#3B82F6' }}">
                                    {{ strtoupper(substr($unidad['tipo_unidad'], 0, 1)) }}
                                </div>
                                <div>
                                    <span class="font-bold text-gray-900">{{ $unidad['codigo'] }}</span>
                                    <p class="text-sm text-gray-600">{{ $unidad['nombre'] }}</p>
                                </div>
                            </div>
                            <div class="mt-2 sm:mt-0 text-sm text-gray-500">
                                {{ $unidad['propietario'] }}
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de lectura -->
                    <div class="p-3 sm:p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3"
                             x-data="{
                                anterior: {{ number_format((float) $unidad['lectura_anterior'], 2, '.', '') }},
                                actual: '',
                                init() {
                                    const inp = this.$refs.actualInput;
                                    if (inp) this.actual = inp.value;
                                },
                                get consumo() {
                                    const v = parseFloat(String(this.actual).replace(',', '.'));
                                    if (isNaN(v)) return null;
                                    return v - this.anterior;
                                }
                             }">
                            <!-- Lectura Anterior -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Anterior (M³)
                                </label>
                                <input type="text"
                                       value="{{ number_format((float) $unidad['lectura_anterior'], 2, '.', '') }}"
                                       readonly
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 text-sm">
                            </div>

                            <!-- Lectura Actual -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Actual (M³) <span class="text-red-500">*</span>
                                </label>
                                <input type="number"
                                       wire:model.live="lecturas.{{ $unidad['id'] }}.lectura_actual"
                                       x-ref="actualInput"
                                       x-on:input="actual = $event.target.value"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-base">
                            </div>

                            <!-- Consumo (Calculadora en tiempo real) -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Consumo (M³)
                                </label>
                                <div class="w-full px-3 py-2 border rounded-lg text-sm font-bold flex items-center justify-center"
                                     style="min-height: 38px;"
                                     :class="consumo === null
                                         ? 'border-gray-200 bg-gray-50 text-gray-400'
                                         : (consumo < 0
                                             ? 'border-red-300 bg-red-50 text-red-600'
                                             : (consumo === 0
                                                 ? 'border-blue-300 bg-blue-50 text-blue-700'
                                                 : 'border-green-300 bg-green-50 text-green-700'))">
                                    <span x-text="consumo === null ? '—' : (consumo.toFixed(2) + ' M³')"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Botón guardar individual -->
                        <div class="mt-3 flex justify-end">
                            <button wire:click="guardarFila({{ $unidad['id'] }})"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Paginación -->
        @if($this->unidades->hasPages())
            <div class="px-4 sm:px-6 lg:px-8 py-4 bg-white border-t border-gray-200">
                {{ $this->unidades->links() }}
            </div>
        @endif
    @else
        @if($this->unidades->total() === 0 && \App\Models\LecturaMedidor::where('periodo_id', $periodoId)->where('tipo', 'individual')->count() > 0)
            <!-- Todas las lecturas están completas -->
            <div class="px-4 sm:px-6 lg:px-8 py-16 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">¡Todas las lecturas están registradas!</h3>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">
                    Se han registrado {{ \App\Models\LecturaMedidor::where('periodo_id', $periodoId)->where('tipo', 'individual')->count() }} lecturas individuales en este período.
                </p>
                <a href="{{ route('periodos.lecturas', $periodoId) }}"
                   class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Ver lista de lecturas
                </a>
            </div>
        @else
            <!-- No hay unidades con los filtros -->
            <div class="px-4 sm:px-6 lg:px-8 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay unidades</h3>
                <p class="mt-1 text-sm text-gray-500">No se encontraron unidades con los filtros aplicados.</p>
            </div>
        @endif
    @endif

    <!-- Botón Sticky - Guardar Todo -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg p-4 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <button wire:click="guardarTodo"
                    class="w-full sm:w-auto sm:float-left px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors flex items-center justify-center sm:justify-start">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Guardar Lecturas Individuales
            </button>

            <!-- Contador de lectura -->
            <div class="mt-2 sm:mt-0 sm:float-right text-sm text-gray-600 text-center sm:text-right">
                <span class="font-medium text-blue-600">{{ count(array_filter($this->lecturas, fn($l) => !empty($l['lectura_actual']))) }}</span>
                de <span>{{ $this->unidades->count() }}</span> listas para guardar
            </div>

            <div class="clearfix"></div>
        </div>
    </div>

    <!-- Espacio extra para el botón sticky -->
    <div class="h-20"></div>
</div>
