<?php

namespace App\Livewire\Expensas;

use App\Models\Expensa;
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

    // Tab activo: 'unidades' o 'propietarios'
    public $tabActivo = 'unidades';

    // Para generar expensa individual
    public $unidadIdGenerando = null;
    public $propietarioIdRegenerando = null;
    public $generando = false;

    // Modal de reapertura de período (antes usaba prompt() PHP que no funciona)
    public $mostrarModalReabrir = false;
    public $motivoReapertura = '';

    protected ExpensaService $expensaService;

    protected $queryString = ['periodoId', 'search', 'filtroEstado', 'filtroTipo', 'tabActivo'];

    public function boot(ExpensaService $expensaService)
    {
        $this->expensaService = $expensaService;
    }

    public function mount()
    {
        // Solo asignar período por defecto si NO viene en la URL (queryString).
        // Livewire inicializa $this->periodoId desde la URL antes de mount(),
        // así que si ya tiene valor, lo respetamos para permitir recargas y links compartibles.
        if (!$this->periodoId) {
            $periodoAbierto = PeriodoExpensa::abiertos()
                ->orderBy('año', 'desc')
                ->orderBy('mes', 'desc')
                ->first();

            if ($periodoAbierto) {
                $this->periodoId = $periodoAbierto->id;
            }
        }
    }

    /**
     * Cambiar el tab activo
     */
    public function setTab($tab)
    {
        $this->tabActivo = $tab;
        $this->resetPage(); // Resetear paginación al cambiar tab
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
     * Obtener lista de unidades con sus expensas
     */
    #[Computed]
    public function unidadesConExpensas()
    {
        $query = Unidad::activos()
            ->with([
                'propietarioActual',
                'inquilinoActual',
                'expensas' => function ($query) {
                    if ($this->periodoId) {
                        $query->where('periodo_id', $this->periodoId);
                    }
                }
            ]);

        // Filtro de búsqueda
        if ($this->search) {
            // Limpiar y dividir en palabras (maneja espacios múltiples)
            $termino = trim($this->search);
            if (!empty($termino)) {
                $palabras = preg_split('/\s+/', $termino, -1, PREG_SPLIT_NO_EMPTY);
                $palabras = array_filter($palabras, fn($p) => !empty(trim($p)));

                // Closure reutilizable: busca en código, propietario o inquilino
                $aplicarFiltroNombre = function ($q, $termino) {
                    $q->where('unidades.codigo', 'like', "%{$termino}%")
                      ->orWhereHas('propietarioActual', function ($propQ) use ($termino) {
                          $propQ->where('nombre', 'like', "%{$termino}%")
                                ->orWhere('apellido', 'like', "%{$termino}%");
                      })
                      ->orWhereHas('inquilinoActual', function ($inqQ) use ($termino) {
                          $inqQ->where('nombre', 'like', "%{$termino}%")
                               ->orWhere('apellido', 'like', "%{$termino}%");
                      });
                };

                if (count($palabras) === 1) {
                    // Si es una sola palabra, búsqueda simple
                    $termino = $palabras[0];
                    $query->where(fn ($q) => $aplicarFiltroNombre($q, $termino));
                } else {
                    // Si son múltiples palabras, buscar combinaciones
                    $query->where(function ($q) use ($palabras, $aplicarFiltroNombre) {
                        foreach ($palabras as $index => $palabra) {
                            $method = $index === 0 ? 'where' : 'orWhere';
                            $q->$method(fn ($subQ) => $aplicarFiltroNombre($subQ, $palabra));
                        }
                    });
                }
            }
        }

        // Filtro por tipo de unidad
        if ($this->filtroTipo) {
            $query->where('unidades.tipo_unidad', $this->filtroTipo);
        }

        // Ordenar por tipo de unidad (departamento, oficina, local, parqueo, baulera)
        // y luego orden natural por código (ej: DEP-1, DEP-2, ..., DEP-10, DEP-11)
        $query->orderByRaw(Unidad::ordenPorTipoUnidadRaw('unidades.tipo_unidad'))
              ->orderByRaw(Unidad::ordenPorCodigoRaw('unidades.codigo', 'ASC'));

        // Paginar
        return $query->paginate(20);
    }

    /**
     * Obtener estadísticas del período
     */
    #[Computed]
    public function estadisticas()
    {
        if (!$this->periodoId) {
            return [
                'total_unidades' => 0,
                'expensas_calculadas' => 0,
                'con_deuda' => 0,
                'pagadas' => 0,
                'monto_total_emision' => 0,
                'monto_deuda_total' => 0,
                'monto_pagado' => 0,
                'saldo_a_favor' => 0,
            ];
        }

        // Contar unidades activas
        $totalUnidades = Unidad::activos()->count();

        // Obtener estadísticas desde expensas
        $expensas = Expensa::where('periodo_id', $this->periodoId)->get();

        $expensasCalculadas = $expensas->count();
        $montoTotalEmision = $expensas->sum('monto_total');

        // Contar expensas con deuda (expensa_pendiente > 0 o agua_pendiente > 0)
        $conDeuda = $expensas->filter(fn($e) => $e->tieneDeuda())->count();

        // Contar expensas pagadas (ambos saldos en 0)
        $pagadas = $expensas->filter(fn($e) => $e->estaPagada())->count();

        // Monto total de deuda
        $montoDeudaTotal = $expensas->sum('expensa_pendiente') + $expensas->sum('agua_pendiente');

        // Monto pagado
        $montoPagado = $montoTotalEmision - $montoDeudaTotal;

        // Total saldos a favor del período
        $saldoAFavor = \App\Models\SaldoAFavor::whereHas('ingreso', function ($q) {
            $q->where('tipo_ingreso', 'expensa');
        })->where('estado', 'disponible')->sum('monto_saldo');

        return [
            'total_unidades' => $totalUnidades,
            'expensas_calculadas' => $expensasCalculadas,
            'con_deuda' => $conDeuda,
            'pagadas' => $pagadas,
            'monto_total_emision' => $montoTotalEmision,
            'monto_deuda_total' => $montoDeudaTotal,
            'monto_pagado' => $montoPagado,
            'saldo_a_favor' => $saldoAFavor,
        ];
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

            // Validar que el período esté completo
            $validacion = $this->expensaService->validarPeriodoCompleto($this->periodoId);
            if (!$validacion['valido']) {
                $errores = implode(', ', $validacion['errores']);
                throw new \Exception("El período no está completo: {$errores}");
            }

            $this->generando = true;

            // Generar todas las expensas del período
            $resultados = $this->expensaService->generarExpensas($this->periodoId);

            $mensaje = "Se generaron {$resultados['exitosas']} expensas";
            if (!empty($resultados['errores'])) {
                $mensaje .= " con " . count($resultados['errores']) . " errores";
            }

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Expensas generadas',
                'mensaje' => $mensaje,
            ]);

            // Si hubo errores, mostrar advertencias
            if (!empty($resultados['errores'])) {
                foreach ($resultados['errores'] as $error) {
                    \Log::warning("Error generando expensa: {$error['unidad']} - {$error['error']}");
                }
            }

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

            $unidad = Unidad::findOrFail($unidadId);
            $expensa = $this->expensaService->generarExpensaUnidad(
                $this->periodoId,
                $unidad
            );

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
     * Recalcular todas las expensas del período
     */
    public function recalcularExpensas()
    {
        try {
            if (!$this->periodoId) {
                throw new \Exception('Debe seleccionar un período primero');
            }

            if ($this->periodo->estado === 'cerrado') {
                throw new \Exception('No se puede recalcular un período cerrado. Reabra el período primero.');
            }

            $this->generando = true;

            $resultados = $this->expensaService->recalcularExpensas($this->periodoId);

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Expensas recalculadas',
                'mensaje' => "Se recalcularon {$resultados['exitosas']} expensas correctamente",
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
     * Cerrar período
     */
    public function cerrarPeriodo()
    {
        try {
            if (!$this->periodoId) {
                throw new \Exception('Debe seleccionar un período primero');
            }

            $this->expensaService->cerrarPeriodo($this->periodoId);

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Período cerrado',
                'mensaje' => 'El período ha sido cerrado correctamente',
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
     * Reabrir período: abre el modal para capturar el motivo.
     * (Antes usaba prompt() nativo de JS dentro de PHP, lo cual NO funciona.)
     */
    public function abrirModalReabrir()
    {
        $this->motivoReapertura = '';
        $this->mostrarModalReabrir = true;
    }

    public function confirmarReapertura()
    {
        try {
            if (!$this->periodoId) {
                throw new \Exception('Debe seleccionar un período primero');
            }

            $this->expensaService->reabrirPeriodo($this->periodoId, $this->motivoReapertura ?: null);

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Período reabierto',
                'mensaje' => 'El período ha sido reabierto correctamente',
            ]);

            $this->mostrarModalReabrir = false;
            $this->motivoReapertura = '';

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    public function cancelarReapertura()
    {
        $this->mostrarModalReabrir = false;
        $this->motivoReapertura = '';
    }

    /**
     * Ir al formulario de pago de una unidad
     */
    public function pagarUnidad($unidadId)
    {
        return $this->redirect(route('expensas.pago', $unidadId));
    }

    /**
     * Ver detalle de expensa de una unidad
     */
    public function verDetalleUnidad($unidadId)
    {
        return $this->redirect(route('expensas.unidad.detalle', [
            'unidad' => $unidadId,
            'periodoId' => $this->periodoId,
        ]));
    }

    /**
     * Obtener propietarios con sus unidades y expensas consolidadas
     */
    #[Computed]
    public function propietariosConExpensas()
    {
        if (!$this->periodoId) {
            return collect();
        }

        $responsables = collect();

        // 1. Obtener PROPIETARIOS con unidades activas (solo unidades SIN inquilino actual)
        $propietarios = \App\Models\Propietario::whereHas('unidadesActuales', function ($q) {
            $q->where('activo', true);
        })
        ->with(['unidadesActuales' => function ($q) {
            $q->where('activo', true);
        }])
        ->get()
        ->map(function ($propietario) {
            // Solo unidades que NO tienen inquilino actual
            $unidades = $propietario->unidadesActuales
                ->filter(fn($u) => $u->activo && !$u->tieneInquilinoActual());

            if ($unidades->isEmpty()) {
                return null;
            }

            // Cargar expensas del período para estas unidades
            $unidadIds = $unidades->pluck('id');
            $expensas = Expensa::where('periodo_id', $this->periodoId)
                ->whereIn('unidad_id', $unidadIds)
                ->get()
                ->keyBy('unidad_id');

            // Calcular totales
            $totalUnidades = $unidades->count();
            $totalExpensas = 0;
            $totalAgua = 0;
            $totalPagado = 0;
            $totalPendiente = 0;
            $unidadesConDeuda = 0;
            $unidadesPagadas = 0;

            foreach ($unidades as $unidad) {
                $expensa = $expensas->get($unidad->id);
                if ($expensa) {
                    $totalExpensas += $expensa->monto_expensa;
                    $totalAgua += $expensa->monto_agua;
                    $totalPagado += $expensa->total_pagado;
                    $totalPendiente += $expensa->total_pendiente;

                    if ($expensa->tieneDeuda()) {
                        $unidadesConDeuda++;
                    }
                    if ($expensa->estaPagada()) {
                        $unidadesPagadas++;
                    }
                }
            }

            return (object) [
                'id' => $propietario->id,
                'tipo' => 'propietario',
                'nombre' => $propietario->nombre,
                'apellido' => $propietario->apellido,
                'nombre_completo' => $propietario->nombre_completo,
                'ci' => $propietario->ci,
                'telefono' => $propietario->telefono,
                'email' => $propietario->email,
                'unidades' => $unidades,
                'expensas' => $expensas,
                'total_unidades' => $totalUnidades,
                'total_expensas' => $totalExpensas,
                'total_agua' => $totalAgua,
                'total_emision' => $totalExpensas + $totalAgua,
                'total_pagado' => $totalPagado,
                'total_pendiente' => $totalPendiente,
                'unidades_con_deuda' => $unidadesConDeuda,
                'unidades_pagadas' => $unidadesPagadas,
                'tiene_deuda' => $totalPendiente > 0.01,
                'esta_pagado' => $totalPendiente <= 0.01 && ($totalExpensas + $totalAgua) > 0,
                'porcentaje_pagado' => ($totalExpensas + $totalAgua) > 0
                    ? ($totalPagado / ($totalExpensas + $totalAgua)) * 100
                    : 0,
            ];
        })
        ->filter(fn($p) => $p !== null);

        // 2. Obtener INQUILINOS con unidades activas
        $inquilinos = \App\Models\Inquilino::whereHas('unidadesActuales', function ($q) {
            $q->where('activo', true);
        })
        ->with(['unidadesActuales' => function ($q) {
            $q->where('activo', true);
        }])
        ->get()
        ->map(function ($inquilino) {
            $unidades = $inquilino->unidadesActuales;

            if ($unidades->isEmpty()) {
                return null;
            }

            // Cargar expensas del período para estas unidades
            $unidadIds = $unidades->pluck('id');
            $expensas = Expensa::where('periodo_id', $this->periodoId)
                ->whereIn('unidad_id', $unidadIds)
                ->get()
                ->keyBy('unidad_id');

            // Calcular totales
            $totalUnidades = $unidades->count();
            $totalExpensas = 0;
            $totalAgua = 0;
            $totalPagado = 0;
            $totalPendiente = 0;
            $unidadesConDeuda = 0;
            $unidadesPagadas = 0;

            foreach ($unidades as $unidad) {
                $expensa = $expensas->get($unidad->id);
                if ($expensa) {
                    $totalExpensas += $expensa->monto_expensa;
                    $totalAgua += $expensa->monto_agua;
                    $totalPagado += $expensa->total_pagado;
                    $totalPendiente += $expensa->total_pendiente;

                    if ($expensa->tieneDeuda()) {
                        $unidadesConDeuda++;
                    }
                    if ($expensa->estaPagada()) {
                        $unidadesPagadas++;
                    }
                }
            }

            return (object) [
                'id' => $inquilino->id,
                'tipo' => 'inquilino',
                'nombre' => $inquilino->nombre,
                'apellido' => $inquilino->apellido,
                'nombre_completo' => $inquilino->nombre_completo,
                'ci' => $inquilino->ci,
                'telefono' => $inquilino->telefono,
                'email' => $inquilino->email,
                'unidades' => $unidades,
                'expensas' => $expensas,
                'total_unidades' => $totalUnidades,
                'total_expensas' => $totalExpensas,
                'total_agua' => $totalAgua,
                'total_emision' => $totalExpensas + $totalAgua,
                'total_pagado' => $totalPagado,
                'total_pendiente' => $totalPendiente,
                'unidades_con_deuda' => $unidadesConDeuda,
                'unidades_pagadas' => $unidadesPagadas,
                'tiene_deuda' => $totalPendiente > 0.01,
                'esta_pagado' => $totalPendiente <= 0.01 && ($totalExpensas + $totalAgua) > 0,
                'porcentaje_pagado' => ($totalExpensas + $totalAgua) > 0
                    ? ($totalPagado / ($totalExpensas + $totalAgua)) * 100
                    : 0,
            ];
        })
        ->filter(fn($p) => $p !== null);

        // 3. Fusionar propietarios e inquilinos
        $responsables = $propietarios->concat($inquilinos);

        // 4. Aplicar búsqueda si existe
        if ($this->search) {
            $termino = trim($this->search);
            if (!empty($termino)) {
                $palabras = preg_split('/\s+/', $termino, -1, PREG_SPLIT_NO_EMPTY);
                $palabras = array_filter($palabras, fn($p) => !empty(trim($p)));

                $responsables = $responsables->filter(function ($responsable) use ($palabras) {
                    $nombreCompleto = strtolower($responsable->nombre_completo);
                    $ci = strtolower($responsable->ci ?? '');

                    foreach ($palabras as $palabra) {
                        $palabra = strtolower($palabra);
                        if (str_contains($nombreCompleto, $palabra) || str_contains($ci, $palabra)) {
                            return true;
                        }
                    }
                    return false;
                });
            }
        }

        // 5. Ordenar alfabéticamente por nombre
        return $responsables->sortBy('nombre_completo');
    }

    /**
     * Generar expensas para todas las unidades de un propietario o inquilino
     */
    public function regenerarExpensasPropietario($propietarioId, $tipo = 'propietario')
    {
        try {
            if (!$this->periodoId) {
                throw new \Exception('Debe seleccionar un período primero');
            }

            if ($this->periodo->estado !== 'abierto') {
                throw new \Exception('Solo se pueden generar expensas en períodos abiertos');
            }

            // Cargar según tipo
            if ($tipo === 'inquilino') {
                $responsable = \App\Models\Inquilino::with('unidadesActuales')->findOrFail($propietarioId);
            } else {
                $responsable = \App\Models\Propietario::with('unidadesActuales')->findOrFail($propietarioId);
            }

            $unidades = $responsable->unidadesActuales->filter(fn($u) => $u->activo);

            if ($unidades->isEmpty()) {
                $tipoLabel = $tipo === 'inquilino' ? 'El inquilino' : 'El propietario';
                throw new \Exception("{$tipoLabel} no tiene unidades activas");
            }

            $this->propietarioIdRegenerando = $propietarioId;
            $this->generando = true;

            $exitosas = 0;
            $errores = [];

            foreach ($unidades as $unidad) {
                try {
                    $expensa = $this->expensaService->generarExpensaUnidad($this->periodoId, $unidad);
                    $exitosas++;
                } catch (\Exception $e) {
                    $errores[] = [
                        'unidad' => $unidad->nombre_completo,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $mensaje = "Se generaron {$exitosas} de {$unidades->count()} expensas para {$responsable->nombre_completo}";
            if (!empty($errores)) {
                $mensaje .= ". " . count($errores) . " errores.";
            }

            $this->dispatch('alerta', [
                'tipo' => empty($errores) ? 'success' : 'warning',
                'titulo' => 'Expensas generadas',
                'mensaje' => $mensaje,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        } finally {
            $this->propietarioIdRegenerando = null;
            $this->generando = false;
        }
    }

    /**
     * Redirigir al detalle de expensas de un propietario (vista con todas sus unidades)
     */
    public function verDetallePropietario($propietarioId)
    {
        return $this->redirect(route('expensas.propietario.detalle', [$this->periodoId, $propietarioId]));
    }

    /**
     * Formatear monto a moneda
     */
    protected function formatoMoneda($monto): string
    {
        return number_format($monto, 2, ',', '.');
    }

    public function render()
    {
        return view('livewire.expensas.expensa-list')
            ->layout('layouts.app')
            ->title('Expensas - Lista');
    }
}
