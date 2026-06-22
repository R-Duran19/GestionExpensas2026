<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configurar Livewire para usar navegación SPA
        // En Livewire v4, wire:navigate está habilitado por defecto
        // Solo necesitamos asegurarnos de que los componentes usen un layout consistente
    }
}
