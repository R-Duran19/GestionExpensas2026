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
            {{-- Navbar simple del portal: sin navegación administrativa --}}
            <nav class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center gap-6">
                            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2">
                                <i class="fa-solid fa-building text-indigo-600"></i>
                                <span class="font-semibold text-gray-800">{{ config('app.name') }}</span>
                            </a>

                            <div class="hidden sm:flex items-center gap-1 text-sm">
                                <a href="{{ route('portal.dashboard') }}"
                                   class="px-3 py-2 rounded-md {{ request()->routeIs('portal.dashboard') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                    Inicio
                                </a>
                                <a href="{{ route('portal.estado-cuenta') }}"
                                   class="px-3 py-2 rounded-md {{ request()->routeIs('portal.estado-cuenta') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                    Estado de Cuenta
                                </a>
                                <a href="{{ route('portal.pagos') }}"
                                   class="px-3 py-2 rounded-md {{ request()->routeIs('portal.pagos') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                    Mis Pagos
                                </a>
                                @if (auth()->user()->tipo === 'propietario')
                                    <a href="{{ route('portal.inquilinos') }}"
                                       class="px-3 py-2 rounded-md {{ request()->routeIs('portal.inquilinos') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                        Mis Inquilinos
                                    </a>
                                @endif
                                <a href="{{ route('portal.perfil') }}"
                                   class="px-3 py-2 rounded-md {{ request()->routeIs('portal.perfil') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                    Mi Perfil
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="hidden sm:inline text-sm text-gray-600">
                                <i class="fa-solid fa-user mr-1 {{ auth()->user()->tipo === 'inquilino' ? 'text-blue-500' : 'text-emerald-500' }}"></i>
                                {{ auth()->user()->persona?->nombre_completo ?? auth()->user()->name }}
                            </span>

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
            </nav>

            <!-- Page Content -->
            <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            <footer class="text-center text-xs text-gray-400 py-6">
                Portal de {{ auth()->user()->tipo === 'inquilino' ? 'inquilinos' : 'propietarios' }} · {{ config('app.name') }}
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>
