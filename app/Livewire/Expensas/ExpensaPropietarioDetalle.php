<?php

namespace App\Livewire\Expensas;

use App\Models\Propietario;
use App\Models\Inquilino;
use App\Models\PeriodoExpensa;
use App\Models\Expensa;
use App\Services\ExpensaService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class ExpensaPropietarioDetalle extends Component
{
    public $propietarioId;
    public $periodoId;
    public $tipo = 'propietario'; // 'propietario' o 'inquilino'
    public $propietario; // Puede ser Propietario o Inquilino
    public $periodo;
    public $unidadesConExpensas = [];
    public $periodosDisponibles = [];

    protected ExpensaService $expensaService;

    // Para regenerar expensa individual
    public $unidadIdGenerando = null;
    public $generando = false;

    public function boot(ExpensaService $expensaService)
    {
        $this->expensaService = $expensaService;
    }

    public function mount($periodo, $propietario)
    {
        $this->periodoId = $periodo;
        $this->propietarioId = $propietario;

        // Intentar detectar el tipo buscando primero en inquilinos
        $inquilino = Inquilino::find($propietario);
        if ($inquilino && $inquilino->unidadesActuales()->count() > 0) {
            $this->tipo = 'inquilino';
        }

        $this->cargarPeriodosDisponibles();
        $this->cargarDatos();
    }

    public function cargarPeriodosDisponibles()
    {
        // Obtener todos los períodos donde este responsable (propietario o inquilino) tiene expensas
        if ($this->tipo === 'inquilino') {
            $responsable = Inquilino::find($this->propietarioId);
            $unidadIds = $responsable->unidadesActuales()
                ->where('activo', true)
                ->pluck('unidades.id');
        } else {
            $responsable = Propietario::find($this->propietarioId);
            $unidadIds = $responsable->unidadesActuales()
                ->where('activo', true)
                ->pluck('unidades.id');
        }

        $periodosConExpensas = Expensa::whereIn('unidad_id', $unidadIds)
            ->with('periodo')
            ->get()
            ->pluck('periodo')
            ->sortBy(function ($periodo) {
                return $periodo->año * 100 + $periodo->mes;
            })
            ->reverse()
            ->unique('id')
            ->values();

        $this->periodosDisponibles = $periodosConExpensas->map(function ($periodo) {
            return [
                'id' => $periodo->id,
                'nombre' => $periodo->nombre_periodo,
                'estado' => $periodo->estado,
            ];
        })->toArray();
    }

    public function cargarDatos()
    {
        $this->periodo = PeriodoExpensa::find($this->periodoId);

        // Cargar según tipo
        if ($this->tipo === 'inquilino') {
            $this->propietario = Inquilino::with(['unidadesActuales' => function ($q) {
                $q->where('activo', true);
            }])->findOrFail($this->propietarioId);
        } else {
            $this->propietario = Propietario::with(['unidadesActuales' => function ($q) {
                $q->where('activo', true);
            }])->findOrFail($this->propietarioId);
        }

        // Obtener unidades del propietario con sus expensas del período
        $this->unidadesConExpensas = $this->propietario->unidadesActuales
            ->filter(fn($u) => $u->activo)
            ->map(function ($unidad) {
                $expensa = Expensa::where('periodo_id', $this->periodoId)
                    ->where('unidad_id', $unidad->id)
                    ->first();

                // Obtener lectura de medidor si tiene
                $lecturaMedidor = null;
                $detalleAgua = null;

                if ($unidad->tiene_medidor && $expensa && $expensa->monto_agua > 0) {
                    $lecturaMedidor = \App\Models\LecturaMedidor::where('periodo_id', $this->periodoId)
                        ->where('unidad_id', $unidad->id)
                        ->first();

                    if ($lecturaMedidor && $this->periodo) {
                        // Calcular el factor de agua según la categoría
                        $factorAgua = $unidad->categoria_unidad === \App\Enums\CategoriaUnidad::DOMICILIARIA
                            ? $this->periodo->factor_domiciliario
                            : $this->periodo->factor_comercial;

                        $detalleAgua = (object) [
                            'tiene_medidor' => true,
                            'lectura_anterior' => $lecturaMedidor->lectura_anterior,
                            'lectura_actual' => $lecturaMedidor->lectura_actual,
                            'consumo_m3' => $lecturaMedidor->consumo_m3,
                            'factor_agua' => $factorAgua,
                            'factor_nombre' => $unidad->categoria_unidad === \App\Enums\CategoriaUnidad::DOMICILIARIA ? 'Domiciliario' : 'Comercial',
                            'monto_calculado' => $lecturaMedidor->consumo_m3 * $factorAgua,
                            'tipo_medidor' => $lecturaMedidor->tipo_label,
                        ];
                    }
                }

                return (object) [
                    'id' => $unidad->id,
                    'codigo' => $unidad->codigo,
                    'tipo_unidad' => $unidad->tipo_unidad,
                    'piso' => $unidad->piso,
                    'metros_cuadrados' => $unidad->metros_cuadrados,
                    'nombre_completo' => $unidad->nombre_completo,
                    'expensa' => $expensa,
                    'total_emision' => $expensa ? $expensa->monto_total : 0,
                    'total_pagado' => $expensa ? $expensa->total_pagado : 0,
                    'total_pendiente' => $expensa ? $expensa->total_pendiente : 0,
                    'estado' => $expensa ? ($expensa->estaPagada() ? 'pagado' : ($expensa->tieneDeuda() ? 'pendiente' : 'parcial')) : 'sin_expensa',
                    'detalle_agua' => $detalleAgua,
                ];
            })
            ->sortBy('nombre_completo')
            ->values();
    }

    /**
     * Cambiar período
     */
    public function cambiarPeriodo($periodoId)
    {
        $this->periodoId = $periodoId;
        $this->cargarDatos();
    }

    /**
     * Obtener totales consolidados del propietario
     */
    #[Computed]
    public function totales()
    {
        $totalUnidades = $this->unidadesConExpensas->count();
        $totalEmision = $this->unidadesConExpensas->sum('total_emision');
        $totalPagado = $this->unidadesConExpensas->sum('total_pagado');
        $totalPendiente = $this->unidadesConExpensas->sum('total_pendiente');
        $unidadesPagadas = $this->unidadesConExpensas->where('estado', 'pagado')->count();
        $unidadesConDeuda = $this->unidadesConExpensas->where('estado', 'pendiente')->count();

        return (object) [
            'total_unidades' => $totalUnidades,
            'total_emision' => $totalEmision,
            'total_pagado' => $totalPagado,
            'total_pendiente' => $totalPendiente,
            'unidades_pagadas' => $unidadesPagadas,
            'unidades_con_deuda' => $unidadesConDeuda,
            'porcentaje_pagado' => $totalEmision > 0 ? ($totalPagado / $totalEmision) * 100 : 0,
        ];
    }

    /**
     * Generar expensa para una unidad específica
     */
    public function generarExpensaIndividual($unidadId)
    {
        try {
            if (!$this->periodo) {
                throw new \Exception('El período no existe');
            }

            if ($this->periodo->estado !== 'abierto') {
                throw new \Exception('Solo se pueden generar expensas en períodos abiertos');
            }

            $this->unidadIdGenerando = $unidadId;
            $this->generando = true;

            $unidad = \App\Models\Unidad::findOrFail($unidadId);
            $expensa = $this->expensaService->generarExpensaUnidad(
                $this->periodoId,
                $unidad
            );

            $this->cargarDatos();

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Expensa generada',
                'mensaje' => "Expensa de {$unidad->nombre_completo}: {$this->formatoMoneda($expensa->monto_total)} Bs",
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
     * Generar expensas para todas las unidades del propietario
     */
    public function regenerarTodasExpensas()
    {
        try {
            if (!$this->periodo) {
                throw new \Exception('El período no existe');
            }

            if ($this->periodo->estado !== 'abierto') {
                throw new \Exception('Solo se pueden generar expensas en períodos abiertos');
            }

            if (!confirm('¿Está seguro de regenerar todas las expensas de este propietario?')) {
                return;
            }

            $this->generando = true;

            $exitosas = 0;
            $errores = [];

            foreach ($this->unidadesConExpensas as $unidadData) {
                try {
                    $unidad = \App\Models\Unidad::find($unidadData->id);
                    if ($unidad) {
                        $expensa = $this->expensaService->generarExpensaUnidad($this->periodoId, $unidad);
                        $exitosas++;
                    }
                } catch (\Exception $e) {
                    $errores[] = [
                        'unidad' => $unidadData->nombre_completo,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $this->cargarDatos();

            $mensaje = "Se generaron {$exitosas} de {$this->unidadesConExpensas->count()} expensas";
            if (!empty($errores)) {
                $mensaje .= ". " . count($errores) . " errores.";
            }

            $this->dispatch('alerta', [
                'tipo' => empty($errores) ? 'success' : 'warning',
                'titulo' => 'Expensas regeneradas',
                'mensaje' => $mensaje,
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
     * Ir al formulario de pago de una unidad
     */
    public function pagarUnidad($unidadId)
    {
        return $this->redirect(route('expensas.pago', $unidadId));
    }

    /**
     * Formatear monto a moneda
     */
    protected function formatoMoneda($monto): string
    {
        return number_format($monto, 2, ',', '.');
    }

    /**
     * Obtener el tipo de responsable para mostrar en vista
     */
    public function getTipoResponsableProperty(): string
    {
        return $this->tipo === 'inquilino' ? 'Inquilino' : 'Propietario';
    }

    /**
     * Obtener clase de color según tipo
     */
    public function getTipoClaseProperty(): string
    {
        return $this->tipo === 'inquilino'
            ? 'bg-purple-100 text-purple-800'
            : 'bg-gray-100 text-gray-800';
    }

    /**
     * Obtener clase de color según estado
     */
    protected function getEstadoClase($estado): string
    {
        return match($estado) {
            'pagado' => 'bg-green-100 text-green-800',
            'pendiente' => 'bg-red-100 text-red-800',
            'parcial' => 'bg-yellow-100 text-yellow-800',
            'sin_expensa' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Obtener texto del estado
     */
    protected function getEstadoTexto($estado): string
    {
        return match($estado) {
            'pagado' => 'Pagado',
            'pendiente' => 'Pendiente',
            'parcial' => 'Parcial',
            'sin_expensa' => 'Sin calcular',
            default => 'Desconocido',
        };
    }

    public function render()
    {
        return view('livewire.expensas.expensa-propietario-detalle', [
            'propietario' => $this->propietario,
            'periodo' => $this->periodo,
            'unidadesConExpensas' => $this->unidadesConExpensas,
            'totales' => $this->totales,
            'periodosDisponibles' => $this->periodosDisponibles,
        ])->layout('layouts.app');
    }
}
