<?php

namespace App\Livewire\Expensas;

use App\Models\Expensa;
use App\Models\Pago;
use Livewire\Component;

class ExpensaDetalle extends Component
{
    public $expensaId;

    public function mount($expensaId)
    {
        $this->expensaId = $expensaId;
    }

    public function render()
    {
        $expensa = Expensa::with([
            'periodo',
            'unidad.propietarioActual',
            'unidad.medidorGrupo',
            'pagos'
        ])->findOrFail($this->expensaId);

        return view('livewire.expensas.expensa-detalle', [
            'expensa' => $expensa,
        ])->layout('layouts.app');
    }
}
