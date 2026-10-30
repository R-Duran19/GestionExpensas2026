<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Livewire Layout
    |--------------------------------------------------------------------------
    |
    | Livewire puede usar este layout para todas las vistas.
    |
    */

    'layout' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Livewire Lazy Loading
    |--------------------------------------------------------------------------
    |
    | Habilita la carga diferida para mejorar el rendimiento.
    |
    */

    'lazy' => true,

    /*
    |--------------------------------------------------------------------------
    | Livewire Navigation
    |--------------------------------------------------------------------------
    |
    | Configuración para la navegación SPA con wire:navigate.
    |
    */

    'navigation' => [
        'enabled' => true,
        'progress_bar' => true,
    ],
];
