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
            'ingresos',
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

    #[Computed]
    public function subtotalExpensaActual()
    {
        if (!$this->expensa) {
            return 0;
        }

        // Suma de expensa + agua (sin deuda anterior)
        return $this->expensa->monto_expensa + $this->expensa->monto_agua;
    }

    #[Computed]
    public function deudasAnterioresDetalladas()
    {
        if (!$this->expensa || !$this->periodoId) {
            return collect();
        }

        // Obtener expensas anteriores pendientes o parciales de la misma unidad
        $periodoActual = PeriodoExpensa::find($this->periodoId);

        if (!$periodoActual) {
            return collect();
        }

        // Buscar expensas de períodos anteriores
        $expensasPendientes = Expensa::with('periodo')
            ->where('unidad_id', $this->unidadId)
            ->whereHas('periodo', function ($query) use ($periodoActual) {
                // Períodos anteriores al actual
                $query->where(function ($q) use ($periodoActual) {
                    $q->where('año', '<', $periodoActual->año)
                      ->orWhere(function ($q) use ($periodoActual) {
                          $q->where('año', '=', $periodoActual->año)
                             ->where('mes', '<', $periodoActual->mes);
                      });
                });
            })
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->get();

        // Ordenar por período (más antiguo primero para calcular diferencias)
        $expensasPendientes = $expensasPendientes->sortBy(function ($expensa) {
            return $expensa->periodo->año * 100 + $expensa->periodo->mes;
        })->values();

        // Calcular el monto pendiente real de cada período (sin incluir deudas anteriores acumuladas)
        $deudaAnteriorAcumulada = 0;
        $expensasConMontosReales = $expensasPendientes->map(function ($expensa) use (&$deudaAnteriorAcumulada) {
            // El monto pendiente real es: monto_pendiente - deuda_anterior_acumulada
            // Pero como deuda_anterior ya incluye las deudas previas, calculamos la diferencia
            $montoPendienteReal = $expensa->monto_pendiente - $deudaAnteriorAcumulada;

            // Actualizar la deuda acumulada para el siguiente período
            $deudaAnteriorAcumulada = $expensa->monto_pendiente;

            // Crear un objeto con el monto calculado
            $expensa->monto_pendiente_real = max(0, $montoPendienteReal);
            return $expensa;
        });

        // Reordenar para mostrar (más reciente primero)
        return $expensasConMontosReales->sortByDesc(function ($expensa) {
            return $expensa->periodo->año * 100 + $expensa->periodo->mes;
        })->values();
    }

    #[Computed]
    public function totalDeudasAnterioresCalculado()
    {
        return $this->deudasAnterioresDetalladas->sum('monto_pendiente_real');
    }

    public function render()
    {
        return view('livewire.expensas.expensa-unidad-detalle', [
            'unidad' => $this->unidad,
            'expensa' => $this->expensa,
            'periodoActual' => $this->periodoActual,
            'periodosDisponibles' => $this->periodosDisponibles,
            'lecturaMedidor' => $this->lecturaMedidor,
            'subtotalExpensaActual' => $this->subtotalExpensaActual,
            'deudasAnterioresDetalladas' => $this->deudasAnterioresDetalladas,
            'totalDeudasAnterioresCalculado' => $this->totalDeudasAnterioresCalculado,
        ])->layout('layouts.app');
    }
}
