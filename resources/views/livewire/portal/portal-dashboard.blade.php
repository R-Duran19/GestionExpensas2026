<div>
    {{-- Encabezado de bienvenida --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Hola, {{ $this->user->persona?->nombre_completo ?? $this->user->name }} 👋
        </h1>
        <p class="text-gray-500 text-sm mt-1">
            Portal de {{ $this->user->tipo === 'inquilino' ? 'inquilino' : 'propietario' }} ·
            {{ \App\Models\ConfiguracionEdificio::obtenerConfiguracion()->nombre_edificio ?? '' }}
        </p>
    </div>

    {{-- Resumen global --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1">
                <i class="fa-solid fa-file-invoice-dollar mr-1 text-gray-400"></i> Deuda total
            </div>
            <div class="text-3xl font-bold {{ $this->deudaTotal() > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                Bs {{ number_format($this->deudaTotal(), 2) }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500 mb-1">
                <i class="fa-solid fa-piggy-bank mr-1 text-gray-400"></i> Saldo a tu favor
            </div>
            <div class="text-3xl font-bold text-emerald-600">
                Bs {{ number_format($this->saldoTotal(), 2) }}
            </div>
        </div>
    </div>

    {{-- Tarjetas por unidad --}}
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Mis unidades</h2>

    @if ($this->unidades->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
            <i class="fa-solid fa-door-closed text-3xl mb-3 text-gray-300"></i>
            <p>No tienes unidades asignadas actualmente.</p>
            <p class="text-sm text-gray-400 mt-1">Si crees que es un error, contacta al administrador.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($this->unidades as $unidad)
                @php
                    $deuda = $this->deudasPorUnidad()[$unidad->id] ?? 0;
                    $saldo = $this->saldosPorUnidad()[$unidad->id] ?? 0;
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5" wire:key="unidad-{{ $unidad->id }}">
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-semibold text-gray-800">
                            <i class="fa-solid fa-door-open mr-1 text-indigo-400"></i> {{ $unidad->codigo }}
                        </span>
                        <span class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-600">
                            {{ $unidad->tipo_unidad->value }}
                        </span>
                    </div>

                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-gray-500">Deuda</span>
                        <span class="text-lg font-bold {{ $deuda > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            Bs {{ number_format($deuda, 2) }}
                        </span>
                    </div>

                    @if ($saldo > 0)
                        <div class="flex justify-between items-baseline mt-1">
                            <span class="text-sm text-gray-500">Saldo a favor</span>
                            <span class="text-sm font-semibold text-emerald-600">
                                Bs {{ number_format($saldo, 2) }}
                            </span>
                        </div>
                    @endif

                    <div class="mt-4 pt-3 border-t border-gray-100">
                        <a href="{{ route('portal.estado-cuenta') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            Ver estado de cuenta <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Aviso: pago en línea próximo --}}
    <div class="mt-8 bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-700">
        <i class="fa-solid fa-circle-info mr-1"></i>
        El pago en línea con código QR estará disponible próximamente. Por ahora, tus pagos se
        registran con el administrador del edificio.
    </div>
</div>
