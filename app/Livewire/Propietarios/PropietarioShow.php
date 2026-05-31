<?php

namespace App\Livewire\Propietarios;

use App\Models\Propietario;
use Livewire\Component;

class PropietarioShow extends Component
{
    public $propietarioId;
    public $propietario;
    public $unidades = [];

    public function mount($propietario)
    {
        $this->propietarioId = $propietario;
        $this->propietario = Propietario::findOrFail($propietario);

        // Cargar unidades del propietario
        $unidades = $this->propietario->unidadesActuales;

        $this->unidades = collect($unidades)->map(function ($unidad) {
            $fechaInicio = null;
            $fechaFin = null;

            if ($unidad->pivot->fecha_inicio) {
                $fechaInicio = is_string($unidad->pivot->fecha_inicio)
                    ? \Carbon\Carbon::parse($unidad->pivot->fecha_inicio)->format('d/m/Y')
                    : $unidad->pivot->fecha_inicio->format('d/m/Y');
            }

            if ($unidad->pivot->fecha_fin) {
                $fechaFin = is_string($unidad->pivot->fecha_fin)
                    ? \Carbon\Carbon::parse($unidad->pivot->fecha_fin)->format('d/m/Y')
                    : $unidad->pivot->fecha_fin->format('d/m/Y');
            }

            return [
                'id' => $unidad->id,
                'codigo' => $unidad->codigo,
                'nombre_completo' => $unidad->nombre_completo,
                'tipo' => $unidad->tipo_unidad->value,
                'piso' => $unidad->piso,
                'metros_cuadrados' => $unidad->metros_cuadrados,
                'categoria' => $unidad->categoria_unidad->value,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.propietarios.propietario-show')
            ->layout('layouts.app');
    }
}
