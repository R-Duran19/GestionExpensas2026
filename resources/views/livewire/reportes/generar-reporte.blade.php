<div>
    {{-- Modal de Generación de Reporte --}}
    @if($mostrarModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="flex justify-between items-center p-6 border-b">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-chart-bar text-blue-600 mr-2"></i>
                            Generar Reporte
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            @if($tipoReporte === 'ingresos')
                                Reporte de Ingresos
                            @elseif($tipoReporte === 'egresos')
                                Reporte de Egresos
                            @else
                                Reporte Financiero Combinado
                            @endif
                        </p>
                    </div>
                    <button wire:click="cerrarModal"
                            class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <form wire:submit.prevent="generarReporte">
                    <div class="p-6 space-y-4">
                        <!-- Tipo de Reporte (solo lectura) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de Reporte
                            </label>
                            <div class="px-3 py-2 bg-gray-100 rounded-lg text-gray-700">
                                @if($tipoReporte === 'ingresos')
                                    <i class="fas fa-arrow-circle-down text-green-600 mr-2"></i>
                                    Reporte de Ingresos
                                @elseif($tipoReporte === 'egresos')
                                    <i class="fas fa-arrow-circle-up text-red-600 mr-2"></i>
                                    Reporte de Egresos
                                @else
                                    <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                                    Reporte Financiero Combinado
                                @endif
                            </div>
                        </div>

                        <!-- Rango de Fechas -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha Desde
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="date"
                                       wire:model.live="fecha_inicio"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                @error('fecha_inicio')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha Hasta
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="date"
                                       wire:model.live="fecha_fin"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                @error('fecha_fin')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Formato de Exportación -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Formato
                            </label>
                            <div class="flex gap-3">
                                <button type="button"
                                        wire:click="$set('formato', 'pdf')"
                                        class="flex-1 px-4 py-3 rounded-lg border-2 transition flex items-center justify-center gap-2 {{ $formato === 'pdf' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400' }}">
                                    <i class="fas fa-file-pdf text-lg"></i>
                                    <div class="text-left">
                                        <div class="font-medium">PDF</div>
                                        <div class="text-xs opacity-75">Documento</div>
                                    </div>
                                </button>
                                <button type="button"
                                        wire:click="$set('formato', 'excel')"
                                        class="flex-1 px-4 py-3 rounded-lg border-2 transition flex items-center justify-center gap-2 {{ $formato === 'excel' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400' }}">
                                    <i class="fas fa-file-excel text-lg"></i>
                                    <div class="text-left">
                                        <div class="font-medium">Excel</div>
                                        <div class="text-xs opacity-75">Hoja de cálculo</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Información -->
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        El reporte incluirá todos los registros del período seleccionado con cálculos automáticos de totales.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button"
                                    wire:click="cerrarModal"
                                    class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                <i class="fas fa-download"></i>
                                Generar Reporte
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
