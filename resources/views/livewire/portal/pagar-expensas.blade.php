<div>
    {{-- PAGO CONFIRMADO --}}
    @if ($pagoExitoso)
        <div class="bg-white rounded-xl shadow-sm border border-emerald-200 p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 flex items-center justify-center">
                <i class="fa-solid fa-check text-3xl text-emerald-600"></i>
            </div>
            <h2 class="text-xl font-bold text-emerald-700 mb-2">¡Pago confirmado!</h2>
            <p class="text-gray-600 mb-6">
                Tu pago fue acreditado correctamente y se aplicó a las expensas que seleccionaste.
                Si quedó un excedente, se guardó como saldo a tu favor.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                @if ($ingresoId)
                    <a href="{{ route('portal.recibo', $ingresoId) }}" target="_blank"
                       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        <i class="fa-solid fa-file-pdf"></i> Descargar recibo
                    </a>
                @endif
                <a href="{{ route('portal.dashboard') }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    Volver al inicio
                </a>
            </div>
        </div>

    {{-- QR ACTIVO EN CURSO --}}
    @elseif ($qr = $this->qrActivo)
        <div x-data="{
                    ampliar: false,
                    deadline: {{ $qr->fecha_vencimiento->timestamp * 1000 }},
                    restante: '',
                    vencido: false,
                    tick() {
                        const d = Math.max(0, this.deadline - Date.now());
                        const h = Math.floor(d / 3600000);
                        const m = Math.floor((d % 3600000) / 60000);
                        const s = Math.floor((d % 60000) / 1000);
                        this.restante = (h > 0 ? h + ' h ' : '') + String(m).padStart(2, '0') + ' min ' + String(s).padStart(2, '0') + ' s';
                        this.vencido = d <= 0;
                        if (!this.vencido) setTimeout(() => this.tick(), 1000);
                        else this.restante = 'VENCIDO';
                    },
                    descargar() {
                        const a = document.createElement('a');
                        a.href = '{{ $qr->imagen_qr }}';
                        a.download = 'QR-pago-{{ $qr->alias }}.png';
                        a.click();
                    }
                }"
             x-init="tick()"
             wire:poll.15s="verificar"
             class="bg-white rounded-xl shadow-sm border border-indigo-200 overflow-hidden">

            {{-- Barra superior con cuenta regresiva --}}
            <div class="bg-indigo-50 px-5 py-3 text-sm text-indigo-700 font-medium flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                <span><i class="fa-solid fa-hourglass-half mr-1"></i> Tu pago en curso</span>
                <span class="text-xs font-normal">
                    <span x-show="!vencido">Se verifica automáticamente cada 15 s · vence en</span>
                    <strong class="ml-1 font-mono text-base"
                            :class="vencido ? 'text-red-600' : 'text-indigo-900'"
                            x-text="restante"></strong>
                    <span class="text-gray-400">· {{ $qr->fecha_vencimiento->format('d/m H:i') }}</span>
                </span>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- QR (vertical-first: grande arriba en móvil) --}}
                <div class="flex flex-col items-center" x-data>
                    <img src="{{ $qr->imagen_qr }}" alt="QR de pago"
                         class="w-64 h-64 sm:w-72 sm:h-72 bg-white border border-gray-200 rounded-lg object-contain cursor-zoom-in"
                         @click="ampliar = true" />
                    <p class="text-xs text-gray-400 mt-2 font-mono">{{ $qr->alias }}</p>

                    <div class="flex gap-2 mt-3">
                        <button type="button" @click="ampliar = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition">
                            <i class="fa-solid fa-expand"></i> Ampliar
                        </button>
                        <button type="button" @click="descargar()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition">
                            <i class="fa-solid fa-download"></i> Descargar
                        </button>
                    </div>
                </div>

                {{-- Detalles y acciones --}}
                <div class="flex flex-col justify-center space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Concepto</span>
                        <span class="font-semibold text-gray-800">{{ $qr->glosa }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Monto a pagar</span>
                        <span class="text-2xl font-bold text-indigo-700">Bs {{ number_format((float) $qr->monto, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Vence</span>
                        <span class="text-gray-700">{{ $qr->fecha_vencimiento->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-700">
                        <i class="fa-solid fa-mobile-screen mr-1"></i>
                        Escanea el QR con tu app bancaria o <strong>descárgalo y págalo desde el celular</strong>.
                        Puedes cerrar esta página: tu QR seguirá activo (verás una burbuja en el portal) hasta que lo
                        pagues o venza.
                    </div>

                    <div class="flex gap-2">
                        {{-- Verificar ahora: "Verificando..." mientras llama +
                             cooldown 10 s tras cada chequeo (anti-spam) --}}
                        <div x-data="{
                                hasta: {{ $cooldownHasta ? $cooldownHasta->timestamp * 1000 : 0 }},
                                ahora: Date.now(),
                                tick() { this.ahora = Date.now(); if (this.hasta > this.ahora) setTimeout(() => this.tick(), 500); }
                             }"
                             x-init="tick()" class="flex-1">
                            <button wire:click="verificar(true)"
                                    wire:loading.attr="disabled"
                                    wire:target="verificar"
                                    :disabled="hasta > ahora"
                                    class="w-full px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                <span wire:loading wire:target="verificar" class="inline-flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Verificando...
                                </span>
                                <span wire:loading.remove wire:target="verificar" x-show="hasta > ahora" x-cloak
                                      x-text="'Espere ' + Math.ceil((hasta - ahora) / 1000) + ' s'"></span>
                                <span wire:loading.remove wire:target="verificar" x-show="hasta <= ahora">
                                    <i class="fa-solid fa-rotate mr-1"></i> Verificar ahora
                                </span>
                            </button>
                        </div>
                        {{-- Cancelar QR con diálogo SweetAlert2 (no alert nativo).
                             OJO: el html del Swal NO lleva comillas internas — un
                             escape mal formado aquí aborta TODO Alpine/Livewire. --}}
                        <button x-data
                                @click="Swal.fire({
                                    title: '¿Cancelar este QR?',
                                    html: 'El QR de <b>Bs {{ number_format((float) $qr->monto, 2, ',', '.') }}</b> quedará inhabilitado y nadie podrá pagarlo.<br><br>Si <b>YA</b> realizaste el pago, no lo canceles: el pago se acreditará igual.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, cancelar QR',
                                    cancelButtonText: 'No, volver',
                                    confirmButtonColor: '#dc2626',
                                    cancelButtonColor: '#6b7280'
                                }).then(r => { if (r.isConfirmed) $wire.call('cancelarQr'); })"
                                class="px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200">
                            Cancelar QR
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal de ampliación (pantalla completa) --}}
            <div x-show="ampliar" x-cloak
                 class="fixed inset-0 z-50 bg-gray-900/90 flex flex-col items-center justify-center p-4"
                 @keydown.escape.window="ampliar = false" @click.self="ampliar = false">
                <img src="{{ $qr->imagen_qr }}" alt="QR ampliado"
                     class="max-h-[80vh] max-w-[90vw] bg-white p-4 rounded-xl shadow-2xl" />
                <p class="text-white/80 text-sm mt-3 font-mono">{{ $qr->alias }} · Bs {{ number_format((float) $qr->monto, 2, ',', '.') }}</p>
                <div class="flex gap-3 mt-4">
                    <button type="button" @click="descargar()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 text-white text-sm rounded-lg hover:bg-emerald-600">
                        <i class="fa-solid fa-download"></i> Descargar imagen
                    </button>
                    <button type="button" @click="ampliar = false"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 text-white text-sm rounded-lg hover:bg-white/20">
                        <i class="fa-solid fa-xmark"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>

    {{-- FORMULARIO DE SELECCIÓN DE DEUDAS (réplica de Caja → Ingresos) --}}
    @else
        @if ($this->unidades->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                <i class="fa-solid fa-door-closed text-3xl mb-3 text-gray-300"></i>
                <p>No tienes unidades con acceso al portal.</p>
            </div>
        @elseif (empty($deudasPendientes))
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                <i class="fa-solid fa-champagne-glasses text-3xl mb-3 text-emerald-300"></i>
                <p>¡No tienes deudas pendientes en ninguna de tus propiedades!</p>
                <a href="{{ route('portal.estado-cuenta') }}" class="text-sm text-indigo-600 hover:text-indigo-800 mt-2 inline-block">
                    Ver mi estado de cuenta <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        @else
            {{-- Encabezado + acciones globales --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-gray-700">1. Elige qué pagar</h2>
                    <p class="text-xs text-gray-400">
                        Deudas de tus {{ $this->unidades->count() }} propiedad(es), agrupadas por período.
                        Marca expensa y/o agua y ajusta el monto si quieres pagar parcial.
                    </p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="seleccionarTodasDeudas" type="button"
                            class="px-3 py-1.5 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition">
                        <i class="fas fa-check-double mr-1"></i> Seleccionar todo
                    </button>
                    <button wire:click="deseleccionarTodasDeudas" type="button"
                            class="px-3 py-1.5 text-xs font-medium text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-times mr-1"></i> Limpiar
                    </button>
                </div>
            </div>

            {{-- TABLAS DE DEUDAS POR PERÍODO (mismo patrón visual de Caja) --}}
            <div class="space-y-4">
                @foreach($deudasPendientes as $periodoIndex => $periodoData)
                    @php
                        $totalPeriodo = collect($periodoData['expensas'])->sum(function($e) {
                            return floatval($e['monto_expensa'] ?? 0) + floatval($e['monto_agua'] ?? 0);
                        });
                        $periodoConSeleccionados = $this->periodoTieneSeleccionados($periodoIndex);
                    @endphp

                    <div class="border border-gray-200 rounded-lg overflow-hidden @if(!$periodoConSeleccionados) opacity-70 @endif">
                        {{-- Header del período --}}
                        <div class="@if($periodoConSeleccionados) bg-indigo-50 @else bg-gray-100 @endif px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-semibold @if($periodoConSeleccionados) text-indigo-900 @else text-gray-500 @endif">
                                <i class="fas fa-calendar-alt mr-2"></i>{{ $periodoData['periodo_nombre'] }}
                                @if($periodoData['es_ultimo_periodo'] ?? false)
                                    <span class="ml-2 text-[11px] font-medium text-emerald-700 bg-emerald-100 border border-emerald-200 rounded-full px-2 py-0.5">
                                        <i class="fas fa-piggy-bank mr-1"></i>Último mes con deuda: puedes pagar de más (quedará a tu favor)
                                    </span>
                                @endif
                            </h3>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1 text-xs">
                                    <button type="button" wire:click="seleccionarPeriodo({{ $periodoIndex }})"
                                            class="@if($periodoConSeleccionados) text-indigo-600 @else text-gray-500 @endif font-medium flex items-center gap-1 px-2 py-1 rounded hover:bg-indigo-100 transition">
                                        <i class="fas fa-check-double"></i> Todas
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button type="button" wire:click="deseleccionarPeriodo({{ $periodoIndex }})"
                                            class="text-gray-400 hover:text-gray-600 font-medium flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-100 transition">
                                        <i class="fas fa-times"></i> Ninguna
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
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Propiedad</th>
                                        <th class="px-3 py-2 text-center font-semibold text-gray-700 w-36">Expensa</th>
                                        <th class="px-3 py-2 text-center font-semibold text-gray-700 w-36">Agua</th>
                                        <th class="px-3 py-2 text-right font-semibold text-gray-700 w-28">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($periodoData['expensas'] as $expensaIndex => $expensa)
                                        @php
                                            $totalFila = floatval($expensa['monto_expensa'] ?? 0) + floatval($expensa['monto_agua'] ?? 0);
                                        @endphp
                                        <tr class="hover:bg-gray-50" wire:key="fila-{{ $expensa['expensa_id'] }}">
                                            {{-- Propiedad --}}
                                            <td class="px-3 py-2 font-medium text-gray-900">
                                                {{ $expensa['unidad_codigo'] }}
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
                                                            <input type="number" step="0.01" min="0"
                                                                   @unless($periodoData['es_ultimo_periodo'] ?? false) max="{{ $expensa['deuda_expensa'] }}" @endunless
                                                                   wire:model.debounce.300ms="deudasPendientes.{{ $periodoIndex }}.expensas.{{ $expensaIndex }}.monto_expensa"
                                                                   wire:change="actualizarDesdeInputExpensa({{ $periodoIndex }}, {{ $expensaIndex }})"
                                                                   class="w-20 text-sm rounded px-2 py-1 text-center focus:ring-indigo-500 focus:border-indigo-500 {{ ($expensa['aviso_expensa'] ?? false) ? 'border-red-400 bg-red-50 text-red-700' : 'border-gray-300' }}">
                                                        @else
                                                            <span class="text-xs text-gray-400">0.00</span>
                                                        @endif
                                                    </div>
                                                    @if($expensa['aviso_expensa'] ?? false)
                                                        <span class="text-[11px] text-red-500 font-medium">Máx. Bs {{ number_format($expensa['deuda_expensa'], 2, ',', '.') }} (tu deuda) — para pagar de más usa el último mes</span>
                                                    @else
                                                        <span class="text-xs text-gray-500">Deuda: {{ number_format($expensa['deuda_expensa'], 2, ',', '.') }}</span>
                                                    @endif
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
                                                                <input type="number" step="0.01" min="0"
                                                                       @unless($periodoData['es_ultimo_periodo'] ?? false) max="{{ $expensa['deuda_agua'] }}" @endunless
                                                                       wire:model.debounce.300ms="deudasPendientes.{{ $periodoIndex }}.expensas.{{ $expensaIndex }}.monto_agua"
                                                                       wire:change="actualizarDesdeInputAgua({{ $periodoIndex }}, {{ $expensaIndex }})"
                                                                       class="w-20 text-sm rounded px-2 py-1 text-center focus:ring-blue-500 focus:border-blue-500 {{ ($expensa['aviso_agua'] ?? false) ? 'border-red-400 bg-red-50 text-red-700' : 'border-gray-300' }}">
                                                            @else
                                                                <span class="text-xs text-gray-400">0.00</span>
                                                            @endif
                                                        </div>
                                                        @if($expensa['aviso_agua'] ?? false)
                                                            <span class="text-[11px] text-red-500 font-medium">Máx. Bs {{ number_format($expensa['deuda_agua'], 2, ',', '.') }} (tu deuda) — para pagar de más usa el último mes</span>
                                                        @else
                                                            <span class="text-xs text-gray-500">Deuda: {{ number_format($expensa['deuda_agua'], 2, ',', '.') }}</span>
                                                        @endif
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
                                            <td class="px-3 py-2 text-right font-semibold {{ $totalFila > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
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

            {{-- TOTAL GENERAL (verde, como Caja) --}}
            <div class="bg-emerald-50 border-2 border-emerald-200 rounded-lg p-4 mt-4">
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-emerald-800">Total a Pagar (todas las propiedades):</span>
                    <span class="text-2xl font-bold text-emerald-600">{{ number_format($totalAPagar, 2, ',', '.') }} Bs</span>
                </div>
            </div>

            {{-- Resumen adicional (como Caja) --}}
            <div class="mt-3 flex flex-wrap gap-3">
                <div class="bg-red-50 border-l-4 border-red-400 px-3 py-2 rounded text-sm">
                    <span class="text-gray-600">Total Deuda:</span>
                    <span class="font-bold text-red-700 ml-1">{{ number_format($totalDeuda, 2, ',', '.') }} Bs</span>
                </div>
                <div class="bg-emerald-50 border-l-4 border-emerald-400 px-3 py-2 rounded text-sm">
                    <span class="text-gray-600">Saldo a tu favor (se aplica automáticamente):</span>
                    <span class="font-bold text-emerald-700 ml-1">{{ number_format($this->saldoFavorDisponible(), 2, ',', '.') }} Bs</span>
                </div>
            </div>

            {{-- Generar QR --}}
            <div class="mt-6 flex justify-end">
                <button wire:click="generarQr"
                        wire:loading.attr="disabled"
                        @if($totalAPagar <= 0) disabled @endif
                        class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <span wire:loading wire:target="generarQr"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                    <i class="fa-solid fa-qrcode" wire:loading.remove wire:target="generarQr"></i>
                    Generar QR por {{ number_format($totalAPagar, 2, ',', '.') }} Bs
                </button>
            </div>
        @endif
    @endif
</div>
