<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} — Portal</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
              integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
              crossorigin="anonymous" referrerpolicy="no-referrer">

        <!-- Alpine cloak (evita parpadeo de elementos x-show/x-collapse) -->
        <style>[x-cloak]{display:none !important;}</style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @php
                // [RESPONSIVE 2026-10-15] Enlaces del portal definidos UNA vez y
                // pintados dos veces: fila horizontal (sm+) y panel móvil.
                // NOTA: no poner comentarios Blade {{-- --}} con directivas
                // (@php, @if...) dentro del texto: el compilador puede tragarse
                // el bloque siguiente (bug encontrado hoy).
                $navLinks = [
                    ['ruta' => 'portal.dashboard', 'texto' => 'Inicio', 'icono' => 'fa-house'],
                    ['ruta' => 'portal.estado-cuenta', 'texto' => 'Estado de Cuenta', 'icono' => 'fa-file-invoice-dollar'],
                    ['ruta' => 'portal.pagar', 'texto' => 'Pagar', 'icono' => 'fa-qrcode', 'destacado' => true],
                    ['ruta' => 'portal.pagos', 'texto' => 'Mis Pagos', 'icono' => 'fa-receipt'],
                ];
                if (auth()->user()->tipo === 'propietario') {
                    $navLinks[] = ['ruta' => 'portal.inquilinos', 'texto' => 'Mis Inquilinos', 'icono' => 'fa-users'];
                }
                $navLinks[] = ['ruta' => 'portal.perfil', 'texto' => 'Mi Perfil', 'icono' => 'fa-user-gear'];
            @endphp
            <nav class="bg-white border-b border-gray-200" x-data="{ menuAbierto: false }">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center gap-6">
                            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2 min-w-0">
                                <i class="fa-solid fa-building text-indigo-600"></i>
                                <span class="font-semibold text-gray-800 text-sm sm:text-base truncate max-w-[150px] sm:max-w-none">{{ config('app.name') }}</span>
                            </a>

                            {{-- Fila de enlaces: escritorio --}}
                            <div class="hidden sm:flex items-center gap-1 text-sm">
                                @foreach ($navLinks as $link)
                                    @if (!empty($link['destacado']))
                                        <a href="{{ route($link['ruta']) }}"
                                           class="px-3 py-2 rounded-md font-medium {{ request()->routeIs($link['ruta']) ? 'bg-indigo-600 text-white' : 'bg-emerald-500 text-white hover:bg-emerald-600' }}">
                                            <i class="fa-solid {{ $link['icono'] }} mr-1"></i> {{ $link['texto'] }}
                                        </a>
                                    @else
                                        <a href="{{ route($link['ruta']) }}"
                                           class="px-3 py-2 rounded-md {{ request()->routeIs($link['ruta']) ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                            {{ $link['texto'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="hidden sm:inline text-sm text-gray-600">
                                <i class="fa-solid fa-user mr-1 {{ auth()->user()->tipo === 'inquilino' ? 'text-blue-500' : 'text-emerald-500' }}"></i>
                                {{ auth()->user()->persona?->nombre_completo ?? auth()->user()->name }}
                            </span>

                            {{-- Botón hamburguesa: SOLO móvil --}}
                            <button type="button"
                                    @click="menuAbierto = !menuAbierto"
                                    :aria-expanded="menuAbierto"
                                    aria-label="Abrir menú"
                                    class="sm:hidden inline-flex items-center justify-center h-10 w-10 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <i class="fa-solid text-lg" :class="menuAbierto ? 'fa-xmark' : 'fa-bars'"></i>
                            </button>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-sm text-gray-500 hover:text-red-600 px-3 py-2 rounded-md hover:bg-red-50" title="Cerrar sesión">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    <span class="hidden md:inline ml-1">Salir</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Panel desplegable: SOLO móvil --}}
                <div class="sm:hidden border-t border-gray-200 bg-white px-2 pt-2 pb-3 space-y-1"
                     x-show="menuAbierto"
                     x-cloak
                     @click.outside="menuAbierto = false">
                    <p class="px-3 py-1 text-xs text-gray-400">
                        <i class="fa-solid fa-user mr-1 {{ auth()->user()->tipo === 'inquilino' ? 'text-blue-500' : 'text-emerald-500' }}"></i>
                        {{ auth()->user()->persona?->nombre_completo ?? auth()->user()->name }}
                        ({{ auth()->user()->tipo }})
                    </p>
                    @foreach ($navLinks as $link)
                        @if (!empty($link['destacado']))
                            <a href="{{ route($link['ruta']) }}"
                               @click="menuAbierto = false"
                               class="flex items-center gap-2 px-3 py-2.5 rounded-md font-medium {{ request()->routeIs($link['ruta']) ? 'bg-indigo-600 text-white' : 'bg-emerald-500 text-white' }}">
                                <i class="fa-solid {{ $link['icono'] }}"></i> {{ $link['texto'] }}
                            </a>
                        @else
                            <a href="{{ route($link['ruta']) }}"
                               @click="menuAbierto = false"
                               class="flex items-center gap-2 px-3 py-2.5 rounded-md text-sm {{ request()->routeIs($link['ruta']) ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                                <i class="fa-solid {{ $link['icono'] }} w-5 text-center {{ request()->routeIs($link['ruta']) ? 'text-indigo-600' : 'text-gray-400' }}"></i>
                                {{ $link['texto'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </nav>

            <!-- Page Content -->
            <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            {{-- [PORTAL QR] Burbuja flotante: solo si el usuario tiene un pago en curso.
                 Permite volver al QR desde cualquier página sin generar otro. --}}
            @php
                $qrFlotante = (auth()->check() && auth()->user()->esPortal())
                    ? \App\Models\PagoQr::pendientes()->where('user_id', auth()->id())->first()
                    : null;
            @endphp
            @if ($qrFlotante)
                <a href="{{ route('portal.pagar') }}"
                   class="fixed bottom-6 right-6 z-40 group"
                   title="Tienes un pago en curso">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75 animate-ping"></span>
                    <span class="relative flex h-14 w-14 rounded-full bg-indigo-600 text-white items-center justify-center shadow-xl group-hover:bg-indigo-700 transition">
                        <i class="fa-solid fa-qrcode text-2xl"></i>
                        <span class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-emerald-400 border-2 border-white"></span>
                    </span>
                    <span class="absolute right-16 top-1/2 -translate-y-1/2 bg-gray-900 text-white text-xs px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none shadow-lg">
                        <strong>Pago en curso:</strong> Bs {{ number_format((float) $qrFlotante->monto, 2, ',', '.') }}
                        · vence {{ $qrFlotante->fecha_vencimiento->format('H:i') }} — ver QR
                    </span>
                </a>
            @endif

            <footer class="text-center text-xs text-gray-400 py-6">
                Portal de {{ auth()->user()->tipo === 'inquilino' ? 'inquilinos' : 'propietarios' }} · {{ config('app.name') }}
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>
