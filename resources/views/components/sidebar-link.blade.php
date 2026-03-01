@props(['href', 'active', 'icon'])

@php
$activeClasses = $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900';
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'group flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out ' . $activeClasses]) }}
>
    @if($icon)
        <svg class="shrink-0 h-6 w-6 sidebar-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
        </svg>
    @endif

    <span class="whitespace-nowrap sidebar-text">
        {{ $slot }}
    </span>
</a>
