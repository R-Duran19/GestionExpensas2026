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

    // [FIX DOBLE ALPINE 2026-10-29] Livewire trae su Alpine por dentro del
    // bundle de Vite (app.js importa livewire.esm). La inyección automática
    // del <script src=livewire.js> creaba UNA SEGUNDA instancia de Livewire/
    // Alpine: el magic "$wire" quedaba undefined en los @click (error JS
    // "$wire is not defined") y TODOS los botones Livewire morían
    // (cancelar/verificar del QR).
    'inject_assets' => false,

    'navigation' => [
        'enabled' => true,
        'progress_bar' => true,
    ],
];
