<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('propietarios.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Detalles del Propietario</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Información personal --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Información Personal</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">Nombre completo</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $propietario->nombre_completo }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">CI</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $propietario->ci }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">Teléfono</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $propietario->telefono ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">Email</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $propietario->email ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wider">Estado</span>
                            <p class="text-sm font-medium mt-1">
                                @if($propietario->activo)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactivo</span>
                                @endif
                            </p>
                        </div>
                        @if($propietario->observaciones)
                            <div>
                                <span class="text-xs text-gray-500 uppercase tracking-wider">Observaciones</span>
                                <p class="text-sm text-gray-700 mt-1">{{ $propietario->observaciones }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Acciones --}}
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('propietarios.edit', $propietario->id) }}"
                               class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- [PORTAL / Fase 1.4] Gestión de acceso al portal --}}
            <livewire:portal.acceso-portal :tipo="'propietario'" :persona-id="$propietario->id" :key="'acceso-prop-'.$propietario->id" />
        </div>

        {{-- Unidades asignadas --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-medium text-gray-900">Unidades Asignadas</h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        {{ count($unidades) }} unidad(es)
                    </span>
                </div>
                <div class="p-6">
                    @if(count($unidades) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Piso</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">M²</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Desde</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($unidades as $unidad)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ $unidad['codigo'] }}</td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ $unidad['tipo'] }}</td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ $unidad['piso'] }}</td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ $unidad['metros_cuadrados'] }}</td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ $unidad['categoria'] }}</td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ $unidad['fecha_inicio'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Este propietario no tiene unidades asignadas</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
