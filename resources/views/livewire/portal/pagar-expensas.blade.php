<div>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Pagar Expensas</h1>

    {{-- PAGO CONFIRMADO --}}
    @if ($pagoExitoso)
        <div class="bg-white rounded-xl shadow-sm border border-emerald-200 p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 flex items-center justify-center">
                <i class="fa-solid fa-check text-3xl text-emerald-600"></i>
            </div>
            <h2 class="text-xl font-bold text-emerald-700 mb-2">¡Pago confirmado!</h2>
            <p class="text-gray-600 mb-6">
                Tu pago fue acreditado correctamente y se aplicó a tus deudas más antiguas.
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
    @endif

    {{-- QR ACTIVO EN CURSO --}}
    @elseif ($qr = $this->qrActivo)
        <div class="bg-white rounded-xl shadow-sm border border-indigo-200 overflow-hidden" wire:poll.15s="verificar">
            <div class="bg-indigo-50 px-6 py-3 text-sm text-indigo-700 font-medium">
                <i class="fa-solid fa-hourglass-half mr-1"></i> Tu pago en curso
                <span class="float-right text-xs font-normal">Se verifica automáticamente cada 15 s</span>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="text-center">
                    @if ($qr->imagen_qr)
                        <img src="{{ $qr->imagen_qr }}" alt="QR de pago" class="mx-auto w-56 h-56 border border-gray-100 rounded-lg" />
                    @else
                        <div class="w-56 h-56 mx-auto flex items-center justify-center bg-gray-50 rounded-lg text-gray-400 text-sm">
                            QR no disponible
                        </div>
                    @endif
                    <p class="text-xs text-gray-400 mt-2">Alias: <span class="font-mono">{{ $qr->alias }}</span></p>
                </div>

                <div class="flex flex-col justify-center space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Unidad</span>
                        <span class="font-semibold text-gray-800">{{ $qr->unidad?->codigo ?? '#' . $qr->unidad_id }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Monto a pagar</span>
                        <span class="text-2xl font-bold text-indigo-700">Bs {{ number_format((float) $qr->monto, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Vence</span>
                        <span class="text-gray-700">{{ $qr->fecha_vencimiento->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-700">
                        <i class="fa-solid fa-mobile-screen mr-1"></i>
                        Escanea el QR con tu app bancaria (o guárdalo y págalo desde el celular).
                        Puedes cerrar esta página: tu QR seguirá activo aquí hasta que lo pagues o venza.
                    </div>

                    <div class="flex gap-2">
                        <button wire:click="verificar"
                                wire:loading.attr="disabled"
                                class="flex-1 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            <span wire:loading wire:target="verificar"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i></span>
                            Verificar ahora
                        </button>
                        <button wire:click="cancelarQr"
                                wire:confirm="¿Cancelar este QR? Si ya realizaste el pago, NO lo canceles: el pago se acreditará igual."
                                class="px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200">
                            Cancelar QR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- FORMULARIO DE PAGO --}}
    @else
        @if ($this->unidades->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                <i class="fa-solid fa-door-closed text-3xl mb-3 text-gray-300"></i>
                <p>No tienes unidades con acceso al portal.</p>
            </div>
        @else
            {{-- Selector de unidad --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Unidad a pagar</label>
                <select wire:model.live="unidadId"
                        class="w-full sm:w-72 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @foreach ($this->unidades as $u)
                        <option value="{{ $u->id }}">{{ $u->codigo }} — {{ $u->tipo_unidad->value }}</option>
                    @endforeach
                </select>
            </div>

            @if ($this->unidad)
                {{-- Resumen de deuda --}}
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-xs text-gray-500 mb-1">Deuda total (FIFO: más antigua primero)</div>
                        <div class="text-2xl font-bold text-red-600">Bs {{ number_format($this->deudaTotalUnidad(), 2) }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-xs text-gray-500 mb-1">Saldo a tu favor (se aplica automáticamente)</div>
                        <div class="text-2xl font-bold text-emerald-600">Bs {{ number_format($this->saldoFavorUnidad(), 2) }}</div>
                    </div>
                </div>

                {{-- Deudas pendientes con checkbox (FIFO) --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-700 text-sm">1. Elige qué pagar (de más antigua a más reciente)</h2>
                        <button wire:click="marcarTodo" type="button" class="text-xs text-indigo-600 hover:text-indigo-800">
                            Marcar todo
                        </button>
                    </div>

                    @forelse ($this->pendientes as $exp)
                        <label class="flex items-center gap-3 px-5 py-3 border-b border-gray-50 hover:bg-gray-50 cursor-pointer" wire:key="pend-{{ $exp->id }}">
                            <input type="checkbox" wire:model="seleccionadas" value="{{ $exp->id }}"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="flex-1 text-sm text-gray-700">
                                {{ sprintf('%02d/%d', $exp->periodo->mes, $exp->periodo->año) }}
                                <span class="text-gray-400 text-xs ml-1">
                                    (Exp. Bs {{ number_format((float) $exp->expensa_pendiente, 2) }}
                                    @if ($exp->agua_pendiente > 0) · Agua Bs {{ number_format((float) $exp->agua_pendiente, 2) }} @endif)
                                </span>
                            </span>
                            <span class="text-sm font-semibold text-gray-800">
                                Bs {{ number_format((float) $exp->expensa_pendiente + (float) $exp->agua_pendiente, 2) }}
                            </span>
                        </label>
                    @empty
                        <div class="px-5 py-8 text-center text-gray-400 text-sm">
                            <i class="fa-solid fa-champagne-glasses text-2xl mb-2 block text-emerald-300"></i>
                            ¡No tienes deudas pendientes en esta unidad!
                        </div>
                    @endforelse

                    @if ($this->pendientes->isNotEmpty())
                        <div class="px-5 py-3 bg-gray-50 flex justify-between text-sm">
                            <span class="text-gray-600">Total seleccionado</span>
                            <span class="font-bold text-gray-800">Bs {{ number_format($this->totalSeleccion, 2) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Modo de monto --}}
                @if ($this->pendientes->isNotEmpty())
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                        <h2 class="font-semibold text-gray-700 text-sm mb-3">2. Monto a pagar</h2>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <label class="flex items-center gap-2 flex-1 cursor-pointer">
                                <input type="radio" wire:model.live="modo" value="seleccion"
                                       class="text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700">
                                    Total de lo seleccionado
                                    <span class="block text-xs text-gray-400">Bs {{ number_format($this->totalSeleccion, 2) }}</span>
                                </span>
                            </label>

                            <label class="flex items-center gap-2 flex-1 cursor-pointer">
                                <input type="radio" wire:model.live="modo" value="libre"
                                       class="text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700">
                                    Otro monto
                                    <input type="number" wire:model="montoLibre" min="1" step="0.01" inputmode="decimal"
                                           @disabled($this->modo !== 'libre')
                                           class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm disabled:bg-gray-50" />
                                    @error('montoLibre') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
                                </span>
                            </label>
                        </div>

                        <p class="text-xs text-gray-400 mt-3">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Si pagas menos que la deuda total, se acredita a las deudas más antiguas primero.
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button wire:click="generarQr"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg disabled:opacity-50">
                            <span wire:loading wire:target="generarQr"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                            <i class="fa-solid fa-qrcode" wire:loading.remove wire:target="generarQr"></i>
                            Generar QR por Bs {{ number_format($this->montoAPagar(), 2) }}
                        </button>
                    </div>
                @endif
            @endif
        @endif
    @endif
</div>
