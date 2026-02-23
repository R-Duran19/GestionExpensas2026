<?php

namespace App\Livewire\Configuracion;

use Livewire\Component;

class ConfiguracionIndex extends Component
{
    /**
     * Renderizar el componente.
     */
    public function render()
    {
        return view('livewire.configuracion.configuracion-index')
            ->layout('layouts.app');
    }
}
