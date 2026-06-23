<?php

namespace App\Livewire\Caja\Ingresos;

use App\Models\Ingreso;
use Livewire\Component;

class IngresoShow extends Component
{
    public $ingreso;
    public $detallesExpensas = [];

    public function mount($ingreso)
    {
        $this->ingreso = Ingreso::with([
                'categoria',
                'unidad',
                'creadoPor',
                'actualizadoPor',
                'detalles.expensa',
                'saldosFavor.unidad',
            ])
            ->findOrFail($ingreso);

        // Cargar relaciones adicionales para cada detalle
        $this->ingreso->detalles->each(function ($detalle) {
            if ($detalle->expensa) {
                $detalle->expensa->load('unidad', 'periodo');
                if ($detalle->expensa->unidad) {
                    $detalle->expensa->unidad->load('propietarios');
                }
            }
        });

        $this->detallesExpensas = $this->ingreso->detalles;
    }

    public function render()
    {
        return view('livewire.caja.ingresos.ingreso-show')
            ->layout('layouts.app');
    }
}
