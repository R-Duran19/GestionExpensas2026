<?php

namespace App\Livewire\Expensas;

use App\Models\Expensa;
use App\Models\LecturaMedidor;
use App\Models\PeriodoExpensa;
use App\Models\Propietario;
use App\Models\Unidad;
use Livewire\Component;

class ExpensaPropietarioDetalle extends Component
{
    public $periodoId;
    public $propietarioId;

    public function mount($periodo, $propietario)
    {
        $this->periodoId = $periodo;
        $this->propietarioId = $propietario;
    }

    /**
     * Obtener el período
     */
    public function getPeriodoProperty()
    {
        return PeriodoExpensa::find($this->periodoId);
    }

    /**
     * Obtener el propietario con sus unidades y expensas
     */
    public function getPropietarioProperty()
    {
        return Propietario::with([
            'unidades' => function ($query) {
                $query->activos()
                      ->with(['expensas' => function ($query) {
                          $query->where('periodo_id', $this->periodoId);
                      }])
                      ->with(['lecturasMedidor' => function ($query) {
                          $query->where('periodo_id', $this->periodoId);
                      }]);
            }
        ])->find($this->propietarioId);
    }

    /**
     * Obtener todas las expensas del propietario para este período
     */
    public function getExpensasProperty()
    {
        $unidadIds = $this->propietario->unidades->pluck('id');

        return Expensa::where('periodo_id', $this->periodoId)
                      ->whereIn('unidad_id', $unidadIds)
                      ->with(['unidad', 'periodo'])
                      ->get();
    }

    /**
     * Calcular totales del propietario
     */
    public function getTotalesProperty()
    {
        $expensas = $this->expensas;

        return [
            'subtotal_expensas' => $expensas->sum('monto_expensa'),
            'subtotal_agua' => $expensas->sum('monto_agua'),
            'deuda_anterior' => $expensas->sum('deuda_anterior'),
            'total_a_pagar' => $expensas->sum('monto_total'),
            'total_pagado' => $expensas->sum('monto_pagado'),
            'total_pendiente' => $expensas->sum('monto_pendiente'),
            'saldo_a_favor' => $expensas->sum('saldo_a_favor'),
        ];
    }

    /**
     * Obtener estado del pago del propietario
     */
    public function getEstadoProperty()
    {
        $totales = $this->totales;

        if ($totales['total_pagado'] >= $totales['total_a_pagar'] && $totales['total_a_pagar'] > 0) {
            return 'pagado';
        } elseif ($totales['total_pagado'] > 0) {
            return 'parcial';
        } elseif ($this->expensas->isEmpty()) {
            return 'sin_calcular';
        } else {
            return 'pendiente';
        }
    }

    /**
     * Obtener lectura de agua de una unidad para este período
     */
    public function getLecturaAgua($unidadId)
    {
        return LecturaMedidor::where('periodo_id', $this->periodoId)
                            ->where('unidad_id', $unidadId)
                            ->first();
    }

    /**
     * Generar expensas para todas las unidades del propietario
     */
    public function generarExpensas()
    {
        try {
            $expensaService = app(\App\Services\ExpensaService::class);

            $unidades = $this->propietario->unidades()->activos()->get();

            $expensasGeneradas = [];
            foreach ($unidades as $unidad) {
                $expensa = $expensaService->generarExpensaUnidad(
                    $this->periodoId,
                    $unidad->id
                );
                $expensasGeneradas[] = $expensa;
            }

            $montoTotal = collect($expensasGeneradas)->sum('monto_total');

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Expensas generadas',
                'mensaje' => "Se generaron {$unidades->count()} expensas para {$this->propietario->nombre} {$this->propietario->apellido}. Total: {$montoTotal} Bs",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtener color según estado
     */
    public function getEstadoColor($estado)
    {
        switch ($estado) {
            case 'pendiente':
                return 'bg-red-100 text-red-800';
            case 'parcial':
                return 'bg-yellow-100 text-yellow-800';
            case 'pagado':
                return 'bg-green-100 text-green-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Obtener texto del estado
     */
    public function getEstadoTexto($estado)
    {
        switch ($estado) {
            case 'pendiente':
                return 'Pendiente';
            case 'parcial':
                return 'Parcial';
            case 'pagado':
                return 'Pagado';
            default:
                return 'Sin calcular';
        }
    }

    public function render()
    {
        return view('livewire.expensas.expensa-propietario-detalle', [
            'periodo' => $this->periodo,
            'propietario' => $this->propietario,
            'expensas' => $this->expensas,
            'totales' => $this->totales,
            'estado' => $this->estado,
        ])->layout('layouts.app');
    }
}
