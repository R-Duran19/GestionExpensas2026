<?php

namespace App\Livewire\Expensas;

use App\Models\Expensa;
use Livewire\Component;

class ExpensaDetalle extends Component
{
    public $expensaId;

    public function mount($expensa)
    {
        $this->expensaId = $expensa;
    }

    public function render()
    {
        $expensa = Expensa::with([
            'periodo',
            'unidad.propietarioActual',
            'unidad.medidorGrupo',
            'detallesPago.ingreso', // Nueva arquitectura cabecera-detalle
        ])->findOrFail($this->expensaId);

        return view('livewire.expensas.expensa-detalle', [
            'expensa' => $expensa,
        ])->layout('layouts.app');
    }
}
