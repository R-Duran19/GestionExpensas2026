@php
    // Obtener el período abierto actual
    $periodoService = new \App\Services\PeriodoService();
    $periodoAbierto = $periodoService->obtenerPeriodoAbierto();
@endphp

<!-- Mobile Sidebar Overlay -->
<div x-data="{ mobileSidebarOpen: false }" class="lg:hidden">
    <!-- Mobile Header with Hamburger -->
    <div class="fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-30 flex items-center justify-between px-4">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center">
            <x-application-logo class="h-8 w-auto fill-current text-indigo-600" />
            <span class="ml-3 text-xl font-bold text-gray-900">{{ config('app.name') }}</span>
        </a>

        <button @click="mobileSidebarOpen = true" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="mobileSidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-75 z-40"
         @click="mobileSidebarOpen = false"
         style="display: none;"></div>

    <!-- Mobile Sidebar -->
    <div x-show="mobileSidebarOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 z-50 flex flex-col"
         style="display: none;">
        <!-- Close Button -->
        <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
            <span class="text-xl font-bold text-gray-900">{{ config('app.name') }}</span>
            <button @click="mobileSidebarOpen = false" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               wire:navigate
               class="flex items-center px-3 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="shrink-0 h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <!-- Unidades -->
            <a href="{{ route('unidades.index') }}"
               wire:navigate
               class="flex items-center px-3 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('unidades.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="shrink-0 h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Unidades
            </a>

            <!-- Propietarios -->
            <a href="{{ route('propietarios.index') }}"
               wire:navigate
               class="flex items-center px-3 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('propietarios.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="shrink-0 h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Propietarios
            </a>

            <!-- Expensas -->
            <a href="{{ route('expensas.index') }}"
               wire:navigate
               class="flex items-center px-3 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('expensas.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="shrink-0 h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Expensas
            </a>

            <!-- Caja -->
            <a href="{{ route('caja.dashboard') }}"
               wire:navigate
               class="flex items-center px-3 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('caja.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="shrink-0 h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Caja
            </a>

            <!-- Pagos QR [PORTAL QR / HU-55] -->
            <a href="{{ route('pagos-qr.index') }}"
               wire:navigate
               class="flex items-center px-3 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('pagos-qr.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="shrink-0 h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                Pagos QR
            </a>

        <!-- Períodos -->
        <x-sidebar-link
            :href="route('periodos.index')"
            :active="request()->routeIs('periodos.*')"
            icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
        >
            Períodos
        </x-sidebar-link>

        <!-- Lecturas (solo si hay período abierto) -->
        @if($periodoAbierto)
            <x-sidebar-link
                :href="route('periodos.lecturas', $periodoAbierto->id)"
                :active="request()->routeIs('periodos.lecturas')"
                icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            >
                Lecturas
            </x-sidebar-link>
        @endif

        <!-- Reportes -->
        <x-sidebar-link
            :href="route('reportes.index')"
            :active="request()->routeIs('reportes.*')"
            icon="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
        >
            Reportes
        </x-sidebar-link>

        <!-- Configuración -->
        <x-sidebar-link
            :href="route('configuracion.index')"
            :active="request()->routeIs('configuracion.*')"
            icon="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"
        >
            Configuración
        </x-sidebar-link>
        </nav>

        <!-- Mobile User Section -->
        <div class="px-3 py-4 border-t border-gray-200">
            <a href="{{ route('profile.edit') }}" class="flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 mb-1">
                <div class="shrink-0 h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="ml-3">
                    <div class="font-medium text-gray-900">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); this.closest('form').submit();"
                   class="flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100">
                    <svg class="shrink-0 h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Cerrar Sesión
                </a>
            </form>
        </div>
    </div>
</div>

<!-- Desktop Sidebar -->
<div
    x-data="{
        sidebarCollapsed: true
    }"
    @mouseenter="sidebarCollapsed = false"
    @mouseleave="sidebarCollapsed = true"
    :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    class="hidden lg:flex fixed left-0 top-0 h-screen bg-white border-r border-gray-200 transition-all duration-200 ease-in-out z-40 flex-col"
>
    <!-- Logo -->
    <div class="flex items-center h-16 px-4 border-b border-gray-200 shrink-0">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center overflow-hidden">
            <x-application-logo class="shrink-0 h-8 w-auto fill-current text-indigo-600" />
            <span class="sidebar-text ml-3 text-xl font-bold text-gray-900 whitespace-nowrap">
                {{ config('app.name') }}
            </span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <!-- Dashboard -->
        <x-sidebar-link
            :href="route('dashboard')"
            :active="request()->routeIs('dashboard')"
            icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
        >
            Dashboard
        </x-sidebar-link>

        <!-- Unidades -->
        <x-sidebar-link
            :href="route('unidades.index')"
            :active="request()->routeIs('unidades.*')"
            icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        >
            Unidades
        </x-sidebar-link>

        <!-- Propietarios -->
        <x-sidebar-link
            :href="route('propietarios.index')"
            :active="request()->routeIs('propietarios.*')"
            icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
        >
            Propietarios
        </x-sidebar-link>

        <!-- Expensas -->
        <x-sidebar-link
            :href="route('expensas.index')"
            :active="request()->routeIs('expensas.*')"
            icon="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
        >
            Expensas
        </x-sidebar-link>

        <!-- Caja -->
        <x-sidebar-link
            :href="route('caja.dashboard')"
            :active="request()->routeIs('caja.*')"
            icon="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
        >
            Caja
        </x-sidebar-link>

        <!-- Períodos -->
        <x-sidebar-link
            :href="route('periodos.index')"
            :active="request()->routeIs('periodos.*')"
            icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
        >
            Períodos
        </x-sidebar-link>

        <!-- Lecturas (solo si hay período abierto) -->
        @if($periodoAbierto)
            <x-sidebar-link
                :href="route('periodos.lecturas', $periodoAbierto->id)"
                :active="request()->routeIs('periodos.lecturas')"
                icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            >
                Lecturas
            </x-sidebar-link>
        @endif

        <!-- Reportes -->
        <x-sidebar-link
            :href="route('reportes.index')"
            :active="request()->routeIs('reportes.*')"
            icon="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
        >
            Reportes
        </x-sidebar-link>

        <!-- Configuración -->
        <x-sidebar-link
            :href="route('configuracion.index')"
            :active="request()->routeIs('configuracion.*')"
            icon="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"
        >
            Configuración
        </x-sidebar-link>
    </nav>

    <!-- Bottom Section: User -->
    <div class="px-3 py-4 border-t border-gray-200 shrink-0 relative" x-data="{ open: false }" @click.outside="open = false">
        <!-- User Menu Button -->
        <button @click="open = ! open"
            class="group flex items-center w-full px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 transition-colors duration-200">
            <div class="shrink-0 h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium">
                {{ substr(Auth::user()->name, 0, 1) }}
            </div>
            <div class="sidebar-text ml-3 text-left overflow-hidden">
                <div class="font-medium text-gray-900 truncate">{{ Auth::user()->name }}</div>
                <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</div>
            </div>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute bottom-full left-3 mb-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
            style="display: none;">
            <div class="py-1">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    {{ __('Perfil') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        {{ __('Cerrar Sesión') }}
                    </a>
                </form>
            </div>
        </div>
    </div>

    <style>
        [x-data*="sidebarCollapsed"] .sidebar-text {
            display: none;
        }
        [x-data*="sidebarCollapsed"]:hover .sidebar-text {
            display: block;
        }
        [x-data*="sidebarCollapsed"] .sidebar-icon {
            margin-right: 0;
        }
        [x-data*="sidebarCollapsed"]:hover .sidebar-icon {
            margin-right: 0.75rem;
        }
        [x-data*="sidebarCollapsed"]:hover .sidebar-icon {
            transition: margin-right 200ms ease-in-out;
        }
    </style>
</div>
