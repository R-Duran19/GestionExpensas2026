<?php

namespace App\Livewire\Expensas;

use App\Models\Expensa;
use App\Models\ExpensaTotal;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use App\Services\ExpensaService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class ExpensaList extends Component
{
    use WithPagination;

    // Filtros
    public $periodoId = null;
    public $search = '';
    public $filtroEstado = '';
    public $filtroTipo = '';

    // Para generar expensa individual
    public $unidadIdGenerando = null;
    public $generando = false;

    protected ExpensaService $expensaService;

    public function boot(ExpensaService $expensaService)
    {
        $this->expensaService = $expensaService;
    }

    public function mount()
    {
        // Por defecto, cargar el período abierto más reciente
        $periodoAbierto = PeriodoExpensa::abiertos()
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        if ($periodoAbierto) {
            $this->periodoId = $periodoAbierto->id;
        }
    }

    /**
     * Obtener el período actual seleccionado
     */
    #[Computed]
    public function periodo()
    {
        if (!$this->periodoId) {
            return null;
        }

        return PeriodoExpensa::find($this->periodoId);
    }

    /**
     * Obtener todos los períodos para el selector
     */
    #[Computed]
    public function periodos()
    {
        return PeriodoExpensa::orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->get();
    }

    /**
     * Obtener lista de propietarios con sus expensas agrupadas
     */
    #[Computed]
    public function propietariosConExpensas()
    {
        // Obtener IDs de propietarios que tienen unidades activas (desde la tabla pivot)
        $propietarioIds = \DB::table('propietario_unidad')
            ->join('unidades', 'propietario_unidad.unidad_id', '=', 'unidades.id')
            ->where('unidades.activo', true)
            ->where('propietario_unidad.es_propietario_actual', true)
            ->pluck('propietario_id')
            ->unique()
            ->sort();

        $query = \App\Models\Propietario::whereIn('id', $propietarioIds)
            ->with(['unidades' => function ($query) {
                $query->activos()
                      ->with(['expensas' => function ($query) {
                          if ($this->periodoId) {
                              $query->where('periodo_id', $this->periodoId);
                          }
                      }]);
            }])
            ->with(['expensaTotal' => function ($query) {
                if ($this->periodoId) {
                    $query->where('periodo_id', $this->periodoId);
                }
            }])
            ->orderBy('apellido')
            ->orderBy('nombre');

        // Filtro de búsqueda (por propietario O por unidad)
        if ($this->search) {
            $termino = '%' . $this->search . '%';

            // Buscar unidades que coinciden con el término de búsqueda
            $unidadIds = \DB::table('unidades')
                ->where('codigo', 'like', $termino)
                ->pluck('id');

            // Obtener IDs de propietarios actuales de esas unidades
            $propietarioIdsDesdeUnidades = [];
            if ($unidadIds->isNotEmpty()) {
                $propietarioIdsDesdeUnidades = \DB::table('propietario_unidad')
                    ->whereIn('unidad_id', $unidadIds)
                    ->where('es_propietario_actual', true)
                    ->pluck('propietario_id')
                    ->unique()
                    ->toArray();
            }

            $query->where(function ($q) use ($termino, $propietarioIdsDesdeUnidades) {
                // Búsqueda por datos del propietario (nombre, apellido, CI)
                $q->where('nombre', 'like', $termino)
                  ->orWhere('apellido', 'like', $termino)
                  ->orWhere('ci', 'like', $termino)
                  // O por propietarios de las unidades encontradas
                  ->orWhereIn('id', $propietarioIdsDesdeUnidades);
            });
        }

        // Paginar
        return $query->paginate(15);
    }

    /**
     * Obtener lista de unidades con sus expensas
     */
    #[Computed]
    public function unidadesConExpensas()
    {
        $query = Unidad::activos()
            ->with(['propietarios', 'expensas' => function ($query) {
                if ($this->periodoId) {
                    $query->where('periodo_id', $this->periodoId);
                }
            }])
            ->leftJoin('expensas', function ($join) {
                $join->on('unidades.id', '=', 'expensas.unidad_id');
                if ($this->periodoId) {
                    $join->where('expensas.periodo_id', '=', $this->periodoId);
                }
            })
            ->select('unidades.*', 'expensas.id as expensa_id', 'expensas.estado as expensa_estado', 'expensas.monto_total', 'expensas.monto_pagado', 'expensas.monto_pendiente', 'expensas.deuda_anterior')
            ->orderBy('unidades.tipo_unidad')
            ->orderByRaw("SUBSTRING_INDEX(unidades.codigo, '-', -1) + 0");

        // Filtro de búsqueda
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('unidades.codigo', 'like', '%' . $this->search . '%')
                  ->orWhereHas('propietarioActual', function ($q2) {
                      $q2->where('nombre', 'like', '%' . $this->search . '%')
                         ->orWhere('apellido', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Filtro por tipo de unidad
        if ($this->filtroTipo) {
            $query->where('unidades.tipo_unidad', $this->filtroTipo);
        }

        // Paginar
        return $query->paginate(15);
    }

    /**
     * Obtener estadísticas del período (agrupado por propietarios)
     */
    #[Computed]
    public function estadisticas()
    {
        if (!$this->periodoId) {
            return [
                'total_propietarios' => 0,
                'total_unidades' => 0,
                'expensas_calculadas' => 0,
                'pendientes' => 0,
                'parciales' => 0,
                'pagadas' => 0,
                'monto_total' => 0,
                'monto_pagado' => 0,
                'monto_pendiente' => 0,
            ];
        }

        // Contar unidades activas
        $unidades = Unidad::activos()->count();

        // Obtener estadísticas desde expensa_total (agregación en BD)
        $estadisticasExpensas = ExpensaTotal::where('periodo_id', $this->periodoId)
            ->selectRaw('
                COUNT(*) as total_registros,
                SUM(CASE WHEN estado = "pendiente" THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = "parcial" THEN 1 ELSE 0 END) as parciales,
                SUM(CASE WHEN estado = "pagado" THEN 1 ELSE 0 END) as pagadas,
                SUM(monto_total) as monto_total,
                SUM(monto_pagado) as monto_pagado,
                SUM(monto_pendiente) as monto_pendiente
            ')
            ->first();

        // Si no hay expensas totales, retornar valores vacíos
        if (!$estadisticasExpensas || $estadisticasExpensas->total_registros === 0) {
            // Backward compatibility: verificar si hay expensas individuales
            $expensasCount = Expensa::where('periodo_id', $this->periodoId)->count();

            return [
                'total_propietarios' => 0,
                'total_unidades' => $unidades,
                'expensas_calculadas' => $expensasCount,
                'pendientes' => 0,
                'parciales' => 0,
                'pagadas' => 0,
                'monto_total' => 0,
                'monto_pagado' => 0,
                'monto_pendiente' => 0,
            ];
        }

        // Contar propietarios únicos desde expensa_total
        $totalPropietarios = ExpensaTotal::where('periodo_id', $this->periodoId)
            ->distinct('propietario_id')
            ->count('propietario_id');

        return [
            'total_propietarios' => $totalPropietarios,
            'total_unidades' => $unidades,
            'expensas_calculadas' => Expensa::where('periodo_id', $this->periodoId)->count(),
            'pendientes' => $estadisticasExpensas->pendientes ?? 0,
            'parciales' => $estadisticasExpensas->parciales ?? 0,
            'pagadas' => $estadisticasExpensas->pagadas ?? 0,
            'monto_total' => $estadisticasExpensas->monto_total ?? 0,
            'monto_pagado' => $estadisticasExpensas->monto_pagado ?? 0,
            'monto_pendiente' => $estadisticasExpensas->monto_pendiente ?? 0,
        ];
    }

    /**
     * Generar expensas para todas las unidades de un propietario
     */
    public function generarExpensasPropietario($propietarioId)
    {
        try {
            if (!$this->periodoId) {
                throw new \Exception('Debe seleccionar un período primero');
            }

            $propietario = \App\Models\Propietario::find($propietarioId);

            if (!$propietario) {
                throw new \Exception('Propietario no encontrado');
            }

            $unidades = $propietario->unidades()->activos()->get();

            if ($unidades->isEmpty()) {
                throw new \Exception('El propietario no tiene unidades activas');
            }

            $this->generando = true;

            // Generar expensa para cada unidad del propietario
            $expensasGeneradas = [];
            foreach ($unidades as $unidad) {
                $expensa = $this->expensaService->generarExpensaUnidad(
                    $this->periodoId,
                    $unidad->id
                );
                $expensasGeneradas[] = $expensa;
            }

            $montoTotal = collect($expensasGeneradas)->sum('monto_total');

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Expensas generadas',
                'mensaje' => "Se generaron {$unidades->count()} expensas para {$propietario->nombre} {$propietario->apellido}. Total: {$montoTotal} Bs",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        } finally {
            $this->generando = false;
        }
    }

    /**
     * Generar expensa para una unidad específica
     */
    public function generarExpensaIndividual($unidadId)
    {
        try {
            if (!$this->periodoId) {
                throw new \Exception('Debe seleccionar un período primero');
            }

            $this->unidadIdGenerando = $unidadId;
            $this->generando = true;

            $expensa = $this->expensaService->generarExpensaUnidad(
                $this->periodoId,
                $unidadId
            );

            $unidad = Unidad::find($unidadId);

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Expensa generada',
                'mensaje' => "Expensa de {$unidad->nombre_completo}: {$expensa->monto_total} Bs",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        } finally {
            $this->unidadIdGenerando = null;
            $this->generando = false;
        }
    }

    /**
     * Generar todas las expensas del período
     */
    public function generarTodasExpensas()
    {
        try {
            if (!$this->periodoId) {
                throw new \Exception('Debe seleccionar un período primero');
            }

            if ($this->periodo->estado !== 'abierto') {
                throw new \Exception('Solo se pueden generar expensas en períodos abiertos');
            }

            $this->generando = true;

            // Obtener todas las unidades activas con propietario
            $unidades = Unidad::activos()
                ->whereHas('propietarios')
                ->get();

            if ($unidades->isEmpty()) {
                throw new \Exception('No hay unidades activas con propietario');
            }

            // Generar expensa para cada unidad
            $expensasGeneradas = [];
            foreach ($unidades as $unidad) {
                try {
                    $expensa = $this->expensaService->generarExpensaUnidad(
                        $this->periodoId,
                        $unidad->id
                    );
                    $expensasGeneradas[] = $expensa;
                } catch (\Exception $e) {
                    // Continuar con la siguiente unidad si falla una
                    \Log::error("Error generando expensa para unidad {$unidad->id}: " . $e->getMessage());
                }
            }

            $montoTotal = collect($expensasGeneradas)->sum('monto_total');

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Expensas generadas',
                'mensaje' => "Se generaron " . count($expensasGeneradas) . " expensas de {$unidades->count()} unidades. Total: {$montoTotal} Bs",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        } finally {
            $this->generando = false;
        }
    }

    /**
     * Ver detalle de expensas de un propietario
     */
    public function verDetallePropietario($propietarioId)
    {
        // Redirigir a la vista de detalle del propietario
        return $this->redirect(route('expensas.propietario', [$this->periodoId, $propietarioId]));
    }

    /**
     * Ver detalle de expensa
     */
    public function verDetalle($expensaId)
    {
        // Redirigir a la vista de detalle
        return $this->redirect(route('expensas.detalle', $expensaId));
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
        return view('livewire.expensas.expensa-list');
    }
}
