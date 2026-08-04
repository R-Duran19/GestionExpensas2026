<?php

namespace App\Livewire\Expensas;

use App\Models\Unidad;
use App\Models\Expensa;
use App\Models\PeriodoExpensa;
use App\Models\LecturaMedidor;
use App\Services\PagoExpensaService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class ExpensaUnidadDetalle extends Component
{
    public $unidadId;
    public $periodoId;
    public $unidad;
    public $expensa;
    public $periodosDisponibles = [];

    // Sincroniza el período con la URL (?periodoId=) igual que en ExpensaList,
    // permitiendo recargas, bookmarks y navegación hacia/desde el listado consistente.
    protected $queryString = ['periodoId'];

    protected PagoExpensaService $pagoExpensaService;

    public function boot(PagoExpensaService $pagoExpensaService)
    {
        $this->pagoExpensaService = $pagoExpensaService;
    }

    public function mount($unidad)
    {
        $this->unidadId = $unidad;
        $this->unidad = Unidad::with(['propietarioActual', 'medidorGrupo'])->findOrFail($this->unidadId);

        // Livewire ya inicializó $this->periodoId desde la URL (queryString) antes del mount.
        // Solo asignamos valor por defecto si no viene ninguno.
        if (!$this->periodoId) {
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
        }

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
            'detallesPago.ingreso',
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

    /**
     * Obtener deuda detallada de la unidad
     */
    #[Computed]
    public function deudaUnidad()
    {
        if (!$this->unidadId) {
            return null;
        }

        return $this->pagoExpensaService->obtenerDeudaUnidad($this->unidadId);
    }

    /**
     * Obtener deudas anteriores detalladas
     */
    #[Computed]
    public function deudasAnterioresDetalladas()
    {
        if (!$this->periodoId) {
            return collect();
        }

        // Obtener expensas anteriores de la misma unidad
        $periodoActual = PeriodoExpensa::find($this->periodoId);

        if (!$periodoActual) {
            return collect();
        }

        // Buscar expensas de períodos anteriores
        $expensasAnteriores = Expensa::with('periodo')
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
            ->where(function ($q) {
                // Solo expensas con deuda pendiente
                $q->where('expensa_pendiente', '>', 0)
                  ->orWhere('agua_pendiente', '>', 0);
            })
            ->get();

        // Ordenar por período (más reciente primero para mostrar)
        return $expensasAnteriores->sortByDesc(function ($expensa) {
            return $expensa->periodo->año * 100 + $expensa->periodo->mes;
        })->values();
    }

    /**
     * Obtener total de deudas anteriores
     */
    #[Computed]
    public function totalDeudasAnteriores()
    {
        return $this->deudasAnterioresDetalladas->sum(function ($expensa) {
            return $expensa->expensa_pendiente + $expensa->agua_pendiente;
        });
    }

    /**
     * Obtener historial de pagos de la unidad
     * Incluye ingresos anulados para mostrar historial completo
     */
    #[Computed]
    public function historialPagos()
    {
        if (!$this->unidadId) {
            return collect();
        }

        return \App\Models\Ingreso::with(['detalles.expensa.periodo'])
            ->whereHas('detalles.expensa', function ($q) {
                $q->where('unidad_id', $this->unidadId);
            })
            ->pagosExpensas()
            ->withTrashed() // Incluir ingresos anulados
            ->orderBy('fecha', 'desc')
            ->get()
            ->take(10);
    }

    /**
     * Obtener saldos a favor disponibles
     */
    #[Computed]
    public function saldosAFavor()
    {
        if (!$this->unidadId) {
            return collect();
        }

        return \App\Models\SaldoAFavor::deUnidad($this->unidadId)
            ->disponibles()
            ->with('ingreso')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Obtener total de saldos a favor
     */
    #[Computed]
    public function totalSaldoAFavor()
    {
        return $this->saldosAFavor->sum('monto_saldo');
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

    /**
     * Obtener subtotal de expensa actual (expensa + agua)
     */
    #[Computed]
    public function subtotalExpensaActual()
    {
        if (!$this->expensa) {
            return 0;
        }

        return $this->expensa->monto_expensa + $this->expensa->monto_agua;
    }

    /**
     * Obtener total pendiente de la expensa actual
     */
    #[Computed]
    public function totalPendienteActual()
    {
        if (!$this->expensa) {
            return 0;
        }

        return $this->expensa->expensa_pendiente + $this->expensa->agua_pendiente;
    }

    /**
     * Obtener total pagado de la expensa actual
     */
    #[Computed]
    public function totalPagadoActual()
    {
        if (!$this->expensa) {
            return 0;
        }

        return $this->expensa->monto_total - ($this->expensa->expensa_pendiente + $this->expensa->agua_pendiente);
    }

    /**
     * Verificar si la expensa está pagada
     */
    #[Computed]
    public function estaPagada()
    {
        if (!$this->expensa) {
            return false;
        }

        return $this->expensa->estaPagada();
    }

    /**
     * Obtener color según estado de deuda
     */
    #[Computed]
    public function estadoColor()
    {
        if (!$this->expensa) {
            return 'gray';
        }

        return $this->expensa->estado_color;
    }

    /**
     * Obtener texto del estado
     */
    #[Computed]
    public function estadoTexto()
    {
        if (!$this->expensa) {
            return 'Sin calcular';
        }

        return $this->expensa->estado_label;
    }

    /**
     * Obtener nombre del propietario
     */
    public function getPropietarioNombre(): string
    {
        if ($this->unidad->propietarioActual) {
            return trim($this->unidad->propietarioActual->nombre . ' ' . $this->unidad->propietarioActual->apellido);
        }
        return 'Sin propietario';
    }

    /**
     * Formatear monto a moneda
     */
    protected function formatoMoneda($monto): string
    {
        return number_format($monto, 2, ',', '.');
    }

    /**
     * Ir al formulario de pago
     */
    public function irAPagar()
    {
        return $this->redirect(route('expensas.pago', $this->unidadId));
    }

    public function render()
    {
        return view('livewire.expensas.expensa-unidad-detalle', [
            'unidad' => $this->unidad,
            'expensa' => $this->expensa,
            'periodoActual' => $this->periodoActual,
            'periodosDisponibles' => $this->periodosDisponibles,
            'lecturaMedidor' => $this->lecturaMedidor,
            'deudasAnterioresDetalladas' => $this->deudasAnterioresDetalladas,
            'totalDeudasAnteriores' => $this->totalDeudasAnteriores,
            'historialPagos' => $this->historialPagos,
            'saldosAFavor' => $this->saldosAFavor,
            'totalSaldoAFavor' => $this->totalSaldoAFavor,
            'subtotalExpensaActual' => $this->subtotalExpensaActual,
            'totalPendienteActual' => $this->totalPendienteActual,
            'totalPagadoActual' => $this->totalPagadoActual,
            'estadoColor' => $this->estadoColor,
            'estadoTexto' => $this->estadoTexto,
        ])->layout('layouts.app');
    }
}
