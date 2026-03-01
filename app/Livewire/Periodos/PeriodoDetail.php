<?php

namespace App\Livewire\Periodos;

use App\Models\PeriodoExpensa;
use Livewire\Component;
use Livewire\Attributes\Locked;

class PeriodoDetail extends Component
{
    #[Locked]
    public $periodoId;

    public $periodo;

    public function mount($periodo)
    {
        $this->periodoId = $periodo;
        $this->periodo = PeriodoExpensa::with([
            'cerradoPor',
            'facturasAgua',
            'lecturasMedidor' => function ($query) {
                $query->with(['unidad', 'unidad.propietarios', 'unidad.edificio']);
            }
        ])->findOrFail($periodo);
    }

    public function render()
    {
        return view('livewire.periodos.periodo-detail')
            ->layout('layouts.app');
    }
}
