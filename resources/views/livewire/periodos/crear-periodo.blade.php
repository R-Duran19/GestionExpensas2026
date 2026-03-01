@php
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    $nombrePeriodo = $meses[$mes] . ' ' . $año;
@endphp

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('periodos.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            &larr; Volver a períodos
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">Nuevo Período de Expensas</h2>
            <p class="text-gray-600 mt-1">Se creará el siguiente período en orden cronológico</p>
        </div>

        <form wire:submit="guardar" class="p-6 space-y-6">
            <!-- Información del Período a Crear -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-16 h-16 bg-blue-600 text-white rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-6 flex-1">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $nombrePeriodo }}</h3>
                        <p class="text-gray-600 mt-1">Este es el siguiente período a crear</p>

                        @if($periodoAnterior = \App\Models\PeriodoExpensa::orderBy('año', 'desc')->orderBy('mes', 'desc')->first())
                            <p class="text-sm text-gray-500 mt-2">
                                Período anterior: <strong>{{ $meses[$periodoAnterior->mes] }} {{ $periodoAnterior->año }}</strong>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Información -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-yellow-800">Antes de continuar</h4>
                        <ul class="text-sm text-yellow-700 mt-2 space-y-1">
                            <li>• El período anterior debe estar cerrado</li>
                            <li>• Después de abrir el período, deberá registrar las 3 facturas EPSAS</li>
                            <li>• Luego deberá registrar las lecturas de medidor de todas las unidades</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="{{ route('periodos.index') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm font-medium">
                    Cancelar
                </a>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center shadow-sm">
                    <span wire:loading.remove>
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Crear Período
                    </span>
                    <span wire:loading class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Creando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
