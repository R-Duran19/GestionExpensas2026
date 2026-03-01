@props(['active', 'icon'])

@php
$activeClasses = $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900';
@endphp

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="space-y-1">
    <!-- Botón principal -->
    <button
        @click="open = !open"
        class="group flex items-center w-full px-3 py-3 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out {{ $activeClasses }}"
    >
        @if($icon)
            <svg class="shrink-0 h-6 w-6 sidebar-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
            </svg>
        @endif

        <span class="flex-1 text-left whitespace-nowrap sidebar-text">
            {{ $slot }}
        </span>

        <!-- Flecha -->
        <svg class="shrink-0 h-4 w-4 sidebar-text transition-transform duration-200"
             :class="open ? 'rotate-180' : ''"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Submenú -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="pl-6 space-y-1"
         style="display: none;">
        {{ $submenu }}
    </div>
</div>
