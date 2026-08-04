<div>
    <div class="bg-white rounded-lg shadow">
        <div class="bg-green-600 text-white px-6 py-4 rounded-t-lg">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold">
                    <i class="fas {{ $ingreso_id ? 'fa-edit' : 'fa-plus-circle' }} mr-2"></i>
                    {{ $ingreso_id ? 'Editar Ingreso' : 'Nuevo Ingreso' }}
                </h1>
                <a href="{{ route('caja.ingresos.index') }}"
                   class="text-white hover:bg-green-700 px-3 py-2 rounded-lg transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="p-6">
            @if($ingreso_id)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Editando ingreso: <strong>{{ $numero_recibo }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="mostrarConfirmacion">
                {{-- SECCIÓN: DATOS GENERALES --}}
                <div class="mb-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 pb-2 border-b">
                        <i class="fas fa-file-alt mr-2"></i>Datos Generales
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Fecha --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-calendar mr-1"></i>
                                Fecha <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('fecha') border-red-500 @enderror"
                                   wire:model="fecha">
                            @error('fecha')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Número de Recibo Manual --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-hashtag mr-1"></i>
                                Número de Recibo
                            </label>
                            <div class="flex items-center gap-3">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           wire:model.live="numero_manual"
                                           class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    <span class="ms-3 text-sm font-medium text-gray-900">Manual</span>
                                </label>
                            </div>
                            @if($numero_manual)
                                <input type="text"
                                       class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('numero_recibo') border-red-500 @enderror"
                                       wire:model="numero_recibo"
                                       placeholder="Ingrese número manual">
                                @error('numero_recibo')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @else
                                <p class="mt-2 text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Se generará automáticamente al guardar
                                </p>
                            @endif
                        </div>

                        {{-- Categoría --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-tag mr-1"></i>
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <select class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('categoria_ingreso_id') border-red-500 @enderror"
                                        wire:model.live="categoria_ingreso_id">
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categorias as $categoria)
                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                                <button type="button"
                                        wire:click="abrirModalCategoria"
                                        class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex items-center justify-center"
                                        title="Crear nueva categoría">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            @error('categoria_ingreso_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Método de Pago --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-credit-card mr-1"></i>
                                Método de Pago <span class="text-red-500">*</span>
                            </label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('metodo_pago') border-red-500 @enderror"
                                wire:model.live="metodo_pago">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia/Depósito</option>
                                <option value="qr">QR</option>
                                <option value="otro">Otro</option>
                            </select>
                            @error('metodo_pago')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- SECCIÓN: PAGO DE EXPENSAS --}}
                @if($esCategoriaExpensas)
                    <div class="mb-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 pb-2 border-b">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>Pago de Expensas
                        </h2>

                        {{-- Buscador y selección de propietario --}}
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            @if(!$propietarioSeleccionado)
                                {{-- Info --}}
                                <div class="bg-indigo-50 border-l-4 border-indigo-400 p-3 mb-4">
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-info-circle text-indigo-400 mt-0.5"></i>
                                        <div>
                                            <h3 class="text-sm font-medium text-indigo-800">Busca Deudas Pendientes</h3>
                                            <p class="text-xs text-indigo-700 mt-1">
                                                Busca por nombre, CI o código de unidad (ej: DEP-11-A) para ver las deudas pendientes de expensas.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Buscador --}}
                                <div class="flex gap-2">
                                    <input type="text"
                                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                           wire:model.live="busqueda"
                                           placeholder="Nombre, CI o código de unidad..."
                                           wire:keydown.enter="buscarDeudas">
                                    <button type="button"
                                            wire:click="buscarDeudas"
                                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition font-medium">
                                        <i class="fas fa-search mr-1"></i>
                                        Buscar
                                    </button>
                                </div>

                                {{-- Resultados --}}
                                @if($resultadosBusqueda)
                                    <div class="mt-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Resultados encontrados:</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-72 overflow-y-auto">
                                            @foreach($resultadosBusqueda as $resultado)
                                                <div class="border rounded-lg p-3 hover:bg-indigo-50 cursor-pointer transition @if($propietarioSeleccionado && $propietarioSeleccionado->id == $resultado['id']) bg-indigo-100 border-indigo-400 @endif"
                                                     wire:click="seleccionarPropietario({{ $resultado['id'] }}, '{{ $resultado['tipo'] ?? 'propietario' }}')">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <p class="font-medium text-gray-900">{{ $resultado['nombre'] }}</p>
                                                            <p class="text-xs text-gray-500">CI: {{ $resultado['ci'] }}</p>
                                                        </div>
                                                        <div class="text-right ml-2">
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                                {{ count($resultado['unidades']) }} unidad(es)
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2 flex flex-wrap gap-1">
                                                        @foreach($resultado['unidades'] as $unidad)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                                {{ $unidad['codigo'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @else
                                {{-- Propietario seleccionado con tabla de deudas --}}
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-green-600"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-sm font-medium text-gray-900">{{ $propietarioSeleccionado->nombre_completo }}</h3>
                                                <p class="text-xs text-gray-500">CI: {{ $propietarioSeleccionado->ci }} | Unidades: {{ $unidadesPropietario->pluck('codigo')->join(', ') }}</p>
                                            </div>
                                        </div>
                                        <button type="button"
                                                wire:click="limpiarBusqueda"
                                                class="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-1">
                                            <i class="fas fa-times"></i>
                                            Cambiar
                                        </button>
                                    </div>

                                    {{-- TABLAS DE DEUDAS POR PERÍODO --}}
                                    @if(!empty($deudasPendientes))
                                        <div class="space-y-4">
                                            @foreach($deudasPendientes as $periodoIndex => $periodoData)
                                                @php
                                                    $totalPeriodo = collect($periodoData['expensas'])->sum(function($e) {
                                                        return floatval($e['monto_expensa'] ?? 0) + floatval($e['monto_agua'] ?? 0);
                                                    });
                                                @endphp

                                                {{-- Tarjeta del período --}}
                                                @php
                                                    $periodoConSeleccionados = $this->periodoTieneSeleccionados($periodoIndex);
                                                @endphp
                                                <div class="border border-gray-200 rounded-lg overflow-hidden @if(!$periodoConSeleccionados) opacity-60 bg-gray-50 @endif">
                                                    {{-- Header del período --}}
                                                    <div class="@if($periodoConSeleccionados) bg-indigo-50 border-indigo-100 @else bg-gray-100 border-gray-200 @endif px-4 py-2 border-b flex justify-between items-center">
                                                        <h3 class="font-semibold @if($periodoConSeleccionados) text-indigo-900 @else text-gray-500 @endif">
                                                            <i class="fas fa-calendar-alt mr-2"></i>{{ $periodoData['periodo_nombre'] }}
                                                        </h3>
                                                        <div class="flex items-center gap-3">
                                                            {{-- Botones del período --}}
                                                            <div class="flex items-center gap-1 text-xs">
                                                                <button type="button"
                                                                        wire:click="seleccionarPeriodo({{ $periodoIndex }})"
                                                                        class="@if($periodoConSeleccionados) text-indigo-600 hover:text-indigo-900 @else text-gray-500 hover:text-gray-700 @endif font-medium flex items-center gap-1 px-2 py-1 rounded hover:bg-indigo-100 transition">
                                                                    <i class="fas fa-check-double"></i>
                                                                    Seleccionar Todas
                                                                </button>
                                                                <span class="text-gray-300">|</span>
                                                                <button type="button"
                                                                        wire:click="deseleccionarPeriodo({{ $periodoIndex }})"
                                                                        class="@if($periodoConSeleccionados) text-gray-600 hover:text-gray-900 @else text-gray-400 hover:text-gray-600 @endif font-medium flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-100 transition">
                                                                    <i class="fas fa-times"></i>
                                                                    Deseleccionar
                                                                </button>
                                                            </div>
                                                            <span class="text-sm font-medium @if($periodoConSeleccionados) text-indigo-700 @else text-gray-400 @endif">
                                                                Total: {{ number_format($totalPeriodo, 2, ',', '.') }} Bs
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {{-- Tabla del período --}}
                                                    <div class="overflow-x-auto">
                                                        <table class="w-full text-sm">
                                                            <thead>
                                                                <tr class="bg-gray-50 border-b border-gray-200">
                                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Unidad</th>
                                                                    <th class="px-3 py-2 text-center font-semibold text-gray-700 w-32">Expensa</th>
                                                                    <th class="px-3 py-2 text-center font-semibold text-gray-700 w-32">Agua</th>
                                                                    <th class="px-3 py-2 text-right font-semibold text-gray-700 w-28">Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-200">
                                                                @foreach($periodoData['expensas'] as $expensaIndex => $expensa)
                                                                    @php
                                                                        $totalFila = floatval($expensa['monto_expensa'] ?? 0) + floatval($expensa['monto_agua'] ?? 0);
                                                                    @endphp
                                                                    <tr class="hover:bg-gray-50 @if(isset($expensa['unidad_id']) && $expensa['unidad_id'] == $unidad_preseleccionada_id) bg-yellow-50 border-l-4 border-yellow-400 @endif">
                                                                        {{-- Unidad --}}
                                                                        <td class="px-3 py-2 font-medium text-gray-900">
                                                                            {{ $expensa['unidad_codigo'] }}
                                                                            @if(isset($expensa['unidad_id']) && $expensa['unidad_id'] == $unidad_preseleccionada_id)
                                                                                <i class="fas fa-star text-yellow-500 ml-1" title="Unidad seleccionada"></i>
                                                                            @endif
                                                                        </td>

                                                                        {{-- COLUMNA EXPENSA --}}
                                                                        <td class="px-2 py-2 text-center">
                                                                            <div class="flex flex-col items-center justify-center">
                                                                                <div class="flex items-center gap-1 mb-1">
                                                                                    <input type="checkbox"
                                                                                           wire:model.live="deudasPendientes.{{ $periodoIndex }}.expensas.{{ $expensaIndex }}.pagar_expensa"
                                                                                           wire:change="actualizarMontoExpensa({{ $periodoIndex }}, {{ $expensaIndex }})"
                                                                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                                                                    @if($expensa['pagar_expensa'])
                                                                                        <input type="number"
                                                                                               step="0.01"
                                                                                               min="0"
                                                                                               wire:model.debounce.300ms="deudasPendientes.{{ $periodoIndex }}.expensas.{{ $expensaIndex }}.monto_expensa"
                                                                                               wire:change="actualizarDesdeInputExpensa({{ $periodoIndex }}, {{ $expensaIndex }})"
                                                                                               class="w-20 text-sm border-gray-300 rounded px-2 py-1 text-center focus:ring-indigo-500 focus:border-indigo-500">
                                                                                    @else
                                                                                        <span class="text-xs text-gray-400">0.00</span>
                                                                                    @endif
                                                                                </div>
                                                                                <span class="text-xs text-gray-500">Deuda: {{ number_format($expensa['deuda_expensa'], 2, ',', '.') }}</span>
                                                                            </div>
                                                                        </td>

                                                                        {{-- COLUMNA AGUA --}}
                                                                        @if($expensa['tiene_medidor'])
                                                                            <td class="px-2 py-2 text-center">
                                                                                <div class="flex flex-col items-center justify-center">
                                                                                    <div class="flex items-center gap-1 mb-1">
                                                                                        <input type="checkbox"
                                                                                               wire:model.live="deudasPendientes.{{ $periodoIndex }}.expensas.{{ $expensaIndex }}.pagar_agua"
                                                                                               wire:change="actualizarMontoAgua({{ $periodoIndex }}, {{ $expensaIndex }})"
                                                                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                                        @if($expensa['pagar_agua'])
                                                                                            <input type="number"
                                                                                                   step="0.01"
                                                                                                   min="0"
                                                                                                   wire:model.debounce.300ms="deudasPendientes.{{ $periodoIndex }}.expensas.{{ $expensaIndex }}.monto_agua"
                                                                                                   wire:change="actualizarDesdeInputAgua({{ $periodoIndex }}, {{ $expensaIndex }})"
                                                                                                   class="w-20 text-sm border-gray-300 rounded px-2 py-1 text-center focus:ring-blue-500 focus:border-blue-500">
                                                                                        @else
                                                                                            <span class="text-xs text-gray-400">0.00</span>
                                                                                        @endif
                                                                                    </div>
                                                                                    <span class="text-xs text-gray-500">Deuda: {{ number_format($expensa['deuda_agua'], 2, ',', '.') }}</span>
                                                                                </div>
                                                                            </td>
                                                                        @else
                                                                            <td class="px-2 py-2 text-center bg-gray-50 text-gray-400">
                                                                                <div class="flex flex-col items-center justify-center">
                                                                                    <span class="text-xs">—</span>
                                                                                    <span class="text-xs text-gray-300">Sin medidor</span>
                                                                                </div>
                                                                            </td>
                                                                        @endif

                                                                        {{-- Total fila --}}
                                                                        <td class="px-3 py-2 text-right font-semibold {{ $totalFila > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                                                            {{ number_format($totalFila, 2, ',', '.') }} Bs
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- TOTAL GENERAL --}}
                                        <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4 mt-4">
                                            <div class="flex justify-between items-center">
                                                <span class="font-semibold text-green-800">Total a Pagar (Todos los períodos):</span>
                                                <span class="text-2xl font-bold text-green-600">{{ number_format($totalAPagar, 2, ',', '.') }} Bs</span>
                                            </div>
                                        </div>

                                        {{-- Resumen adicional --}}
                                        <div class="mt-4 flex flex-wrap gap-3">
                                            <div class="bg-red-50 border-l-4 border-red-400 px-3 py-2 rounded text-sm">
                                                <span class="text-gray-600">Total Deuda:</span>
                                                <span class="font-bold text-red-700 ml-1">{{ number_format($totalDeuda, 2, ',', '.') }} Bs</span>
                                            </div>
                                            @if($saldoFavorDisponible > 0)
                                                <div class="bg-blue-50 border-l-4 border-blue-400 px-3 py-2 rounded text-sm">
                                                    <span class="text-gray-600">Saldo Disponible:</span>
                                                    <span class="font-bold text-blue-700 ml-1">{{ number_format($saldoFavorDisponible, 2, ',', '.') }} Bs</span>
                                                </div>
                                            @endif
                                            @if($saldoFavorGenerado > 0)
                                                <div class="bg-purple-50 border-l-4 border-purple-400 px-3 py-2 rounded text-sm">
                                                    <span class="text-gray-600">Saldo a Generar:</span>
                                                    <span class="font-bold text-purple-700 ml-1">{{ number_format($saldoFavorGenerado, 2, ',', '.') }} Bs</span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm text-yellow-700">
                                                        El propietario seleccionado no tiene deudas pendientes.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Importe calculado --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-dollar-sign mr-1"></i>
                                Importe a Pagar <span class="text-red-500">*</span>
                            </label>
                            <div class="relative rounded-md shadow-sm bg-green-50 border border-green-200">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">Bs</span>
                                </div>
                                <input type="text"
                                       class="w-full pl-7 px-3 py-2 bg-green-50 border-green-200 rounded-lg text-green-800 font-bold text-lg"
                                       value="{{ number_format($importe, 2, ',', '.') }}"
                                       readonly
                                       placeholder="0.00">
                            </div>
                            <p class="mt-1 text-xs text-gray-600">
                                <i class="fas fa-calculator mr-1"></i>
                                Total a Pagar ({{ number_format($totalAPagar, 2, ',', '.') }} Bs)
                                @if($saldoFavorDisponible > 0) - Saldo Disponible ({{ number_format($saldoFavorDisponible, 2, ',', '.') }} Bs)@endif
                                @if($saldoFavorGenerado > 0) + Saldo a Generar ({{ number_format($saldoFavorGenerado, 2, ',', '.') }} Bs)@endif
                            </p>
                        </div>
                    </div>
                @endif

                {{-- SECCIÓN: DETALLES ADICIONALES --}}
                <div class="mb-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 pb-2 border-b">
                        <i class="fas fa-edit mr-2"></i>Detalles Adicionales
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Descripción --}}
                        <div @if($esCategoriaExpensas) class="md:col-span-2" @endif>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-align-left mr-1"></i>
                                Descripción
                            </label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('descripcion') border-red-500 @enderror"
                                      wire:model="descripcion"
                                      rows="2"
                                      placeholder="Descripción detallada del ingreso"></textarea>
                            @error('descripcion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Unidad (solo para categorías normales) --}}
                        @if(!$esCategoriaExpensas)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-building mr-1"></i>
                                    Unidad (opcional)
                                </label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    wire:model="unidad_id">
                                    <option value="">Sin unidad asignada</option>
                                    @foreach($unidades as $unidad)
                                        <option value="{{ $unidad->id }}">{{ $unidad->codigo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Importe (solo para categorías normales) --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-dollar-sign mr-1"></i>
                                    Importe <span class="text-red-500">*</span>
                                </label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">Bs</span>
                                    </div>
                                    <input type="number"
                                           step="0.01"
                                           min="0.01"
                                           class="w-full pl-7 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('importe') border-red-500 @enderror"
                                           wire:model="importe"
                                           placeholder="0.00">
                                </div>
                                @error('importe')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- Referencia Bancaria --}}
                        @if($metodo_pago !== 'efectivo')
                            <div @if($esCategoriaExpensas) class="md:col-span-2" @endif>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-university mr-1"></i>
                                    Referencia Bancaria
                                </label>
                                <input type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('referencia_bancaria') border-red-500 @enderror"
                                       wire:model="referencia_bancaria"
                                       placeholder="Últimos 4 dígitos o número de referencia">
                                @error('referencia_bancaria')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- Observaciones --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-sticky-note mr-1"></i>
                                Observaciones
                            </label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                      wire:model="observaciones"
                                      rows="2"
                                      placeholder="Observaciones adicionales"></textarea>
                        </div>
                    </div>
                </div>

                {{-- SECCIÓN: COMPROBANTE --}}
                <div class="mb-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 pb-2 border-b">
                        <i class="fas fa-image mr-2"></i>Comprobante de Pago
                        @if($metodo_pago !== 'efectivo')
                            <span class="text-red-500 normal-case ml-1">*</span>
                        @endif
                    </h2>

                    @if($comprobante_foto_actual)
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <img src="{{ Storage::url($comprobante_foto_actual) }}"
                                 alt="Comprobante actual"
                                 class="max-h-48 mx-auto mb-2">
                            <button type="button"
                                    class="w-full bg-red-100 text-red-700 px-3 py-2 rounded-lg hover:bg-red-200 transition flex items-center justify-center gap-2"
                                    wire:click="limpiarComprobante">
                                <i class="fas fa-trash"></i>
                                Eliminar comprobante
                            </button>
                        </div>
                    @else
                        @if($metodo_pago !== 'efectivo')
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-3">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-exclamation-triangle text-yellow-400 mt-0.5"></i>
                                    <p class="text-sm text-yellow-700">
                                        Para <strong>{{ $metodo_pago }}</strong> es requerido adjuntar el comprobante
                                    </p>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('comprobante_foto') border-red-500 @enderror"
                               wire:model="comprobante_foto"
                               accept="image/*">
                        @error('comprobante_foto')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        @if($comprobante_foto)
                            <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-2">
                                    Archivo seleccionado: {{ $comprobante_foto->getClientOriginalName() }}
                                </p>
                                <img src="{{ $comprobante_foto->temporaryUrl() }}"
                                     alt="Vista previa"
                                     class="max-h-48">
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Botones de acción --}}
                <div class="pt-6 border-t border-gray-200">
                    <div class="flex justify-between">
                        <a href="{{ route('caja.ingresos.index') }}"
                           class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                            Cancelar
                        </a>
                        @if($esCategoriaExpensas)
                            <button type="submit"
                                    @if($totalAPagar <= 0) disabled @endif
                                    class="bg-green-600 hover:bg-green-700 text-white px-8 py-2.5 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed font-medium">
                                <i class="fas fa-save mr-2"></i>
                                Registrar Pago ({{ number_format($totalAPagar, 2, ',', '.') }} Bs)
                            </button>
                        @else
                            <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 text-white px-8 py-2.5 rounded-lg transition font-medium">
                                <i class="fas fa-save mr-2"></i>
                                {{ $ingreso_id ? 'Actualizar' : 'Guardar' }} Ingreso
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal para crear categoría rápida --}}
    @if($mostrarModalCategoria)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-tag text-indigo-600 mr-2"></i>
                        Crear Nueva Categoría
                    </h3>
                    <button type="button" wire:click="cerrarModalCategoria" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="crearCategoriaRapida">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('nombre_categoria_nueva') border-red-500 @enderror"
                                   wire:model="nombre_categoria_nueva"
                                   placeholder="Ej: Cuota Extraordinaria"
                                   required>
                            @error('nombre_categoria_nueva')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Color
                            </label>
                            <input type="color"
                                   class="w-full h-10 border border-gray-300 rounded-lg @error('color_categoria_nueva') border-red-500 @enderror"
                                   wire:model="color_categoria_nueva">
                            @error('color_categoria_nueva')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="button"
                                    wire:click="cerrarModalCategoria"
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-save mr-1"></i>
                                Crear
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal de confirmación de guardado --}}
    @if($mostrarModalConfirmacion)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Confirmar Registro de Ingreso
                    </h3>
                </div>

                <div class="space-y-3 mb-6">
                    @if($esCategoriaExpensas)
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <h4 class="font-medium text-indigo-900 mb-2">Resumen del Pago de Expensas</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Propietario:</span>
                                    <span class="font-medium">{{ $propietarioSeleccionado->nombre_completo }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Deuda:</span>
                                    <span class="font-medium">{{ number_format($totalDeuda, 2, ',', '.') }} Bs</span>
                                </div>
                                @if($saldoFavorDisponible > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Saldo a Favor Aplicado:</span>
                                    <span class="font-medium text-blue-600">-{{ number_format($saldoFavorDisponible, 2, ',', '.') }} Bs</span>
                                </div>
                                @endif
                                <div class="flex justify-between border-t border-indigo-200 pt-2 mt-2">
                                    <span class="text-gray-700 font-medium">Total a Pagar:</span>
                                    <span class="font-bold text-green-600 text-lg">{{ number_format($importe, 2, ',', '.') }} Bs</span>
                                </div>
                            </div>
                        </div>

                        {{-- Lista de expensas a pagar --}}
                        @php
                            $expensasAPagar = collect($deudasPendientes)
                                ->pluck('expensas')
                                ->flatten(1)
                                ->filter(fn($e) => ($e['pagar_expensa'] && floatval($e['monto_expensa'] ?? 0) > 0) || ($e['pagar_agua'] && floatval($e['monto_agua'] ?? 0) > 0));
                        @endphp

                        @if($expensasAPagar->count() > 0)
                            <div class="bg-gray-50 p-4 rounded-lg max-h-48 overflow-y-auto">
                                <h4 class="font-medium text-gray-900 mb-2 text-sm">Expensas a Pagar ({{ $expensasAPagar->count() }})</h4>
                                <ul class="space-y-1 text-sm">
                                    @foreach($expensasAPagar as $expensa)
                                        <li class="flex justify-between text-gray-700">
                                            <span>{{ $expensa['unidad_codigo'] }} - {{ $expensa['periodo'] }}</span>
                                            <span>{{ number_format(floatval($expensa['monto_expensa'] ?? 0) + floatval($expensa['monto_agua'] ?? 0), 2, ',', '.') }} Bs</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @else
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Categoría:</span>
                                    <span class="font-medium">{{ $categorias->firstWhere('id', $categoria_ingreso_id)?->nombre ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Fecha:</span>
                                    <span class="font-medium">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Método de Pago:</span>
                                    <span class="font-medium">{{ ucfirst($metodo_pago) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-2 mt-2">
                                    <span class="text-gray-700 font-medium">Importe:</span>
                                    <span class="font-bold text-green-600 text-lg">{{ number_format($importe, 2, ',', '.') }} Bs</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-info-circle"></i>
                        <span>¿Desea confirmar el registro de este ingreso?</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            wire:click="cancelarConfirmacion"
                            class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                        Cancelar
                    </button>
                    <button type="button"
                            wire:click="confirmarGuardado"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg transition font-medium">
                        <i class="fas fa-check mr-1"></i>
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @this.on('ver-comprobante', (ruta) => {
                console.log('Ver comprobante:', ruta);
            });
        });
    </script>
</div>
