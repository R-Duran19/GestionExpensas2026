<?php

namespace App\Livewire\Expensas;

use App\Models\Unidad;
use App\Models\Expensa;
use App\Models\PeriodoExpensa;
use App\Models\LecturaMedidor;
use Livewire\Component;
use Livewire\Attributes\Computed;

class ExpensaUnidadDetalle extends Component
{
    public $unidadId;
    public $periodoId;
    public $unidad;
    public $expensa;
    public $periodosDisponibles = [];

    public function mount($unidad)
    {
        $this->unidadId = $unidad;
        $this->unidad = Unidad::with(['propietarioActual', 'medidorGrupo'])->findOrFail($this->unidadId);

        // Obtener el período actual (abierto más reciente)
        $periodoActual = PeriodoExpensa::abiertos()
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        if (!$periodoActual) {
            // Si no hay período abierto, usar el más reciente
            $periodoActual = PeriodoExpensa::orderBy('año', 'desc')
                ->orderBy('mes', 'desc')
                ->first();
        }

        $this->periodoId = $periodoActual ? $periodoActual->id : null;

        // Cargar períodos disponibles donde la unidad tiene expensa
        $this->cargarPeriodosDisponibles();

        // Cargar expensa del período actual
        $this->cargarExpensa();
    }

    public function cargarPeriodosDisponibles()
    {
        // Obtener todos los períodos donde esta unidad tiene expensa
        $periodosConExpensa = Expensa::where('unidad_id', $this->unidadId)
            ->with('periodo')
            ->get()
            ->pluck('periodo')
            ->sortBy(function ($periodo) {
                return $periodo->año * 100 + $periodo->mes;
            })
            ->reverse();

        $this->periodosDisponibles = $periodosConExpensa->map(function ($periodo) {
            return [
                'id' => $periodo->id,
                'nombre' => $periodo->nombre_periodo,
                'estado' => $periodo->estado,
            ];
        })->toArray();
    }

    public function cargarExpensa()
    {
        if (!$this->periodoId) {
            $this->expensa = null;
            return;
        }

        $this->expensa = Expensa::with([
            'periodo',
            'unidad.propietarioActual',
            'unidad.medidorGrupo',
        ])
        ->where('unidad_id', $this->unidadId)
        ->where('periodo_id', $this->periodoId)
        ->first();
    }

    public function cambiarPeriodo($periodoId)
    {
        $this->periodoId = $periodoId;
        $this->cargarExpensa();
    }

    #[Computed]
    public function periodoActual()
    {
        if (!$this->periodoId) {
            return null;
        }

        return PeriodoExpensa::find($this->periodoId);
    }

    #[Computed]
    public function lecturaMedidor()
    {
        if (!$this->periodoId || !$this->expensa) {
            return null;
        }

        return LecturaMedidor::where('periodo_id', $this->periodoId)
            ->where('unidad_id', $this->unidadId)
            ->first();
    }

    public function render()
    {
        return view('livewire.expensas.expensa-unidad-detalle', [
            'unidad' => $this->unidad,
            'expensa' => $this->expensa,
            'periodoActual' => $this->periodoActual,
            'periodosDisponibles' => $this->periodosDisponibles,
            'lecturaMedidor' => $this->lecturaMedidor,
        ])->layout('layouts.app');
    }
}
