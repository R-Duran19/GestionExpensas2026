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

        // [REMEDIACIÓN-1.3] El update endpoint (/livewire/update) lleva solo el
        // middleware 'web', así que CheckAdmin/CheckPortal nunca re-evaluaban
        // el estado del usuario en las interacciones. Los middleware
        // persistentes de Livewire se re-aplican en cada update según la ruta
        // de la que proviene el snapshot: un usuario DESACTIVADO o degradado
        // queda bloqueado también por el canal de interacciones, y el estado
        // se re-chequea en CADA request (no solo en el mount inicial).
        Livewire::addPersistentMiddleware(\App\Http\Middleware\CheckAdmin::class);
        Livewire::addPersistentMiddleware(\App\Http\Middleware\CheckPortal::class);
    }
}
