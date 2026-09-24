<?php

namespace App\Livewire\Periodos;

use App\Models\PeriodoExpensa;
use App\Models\Expensa;
use App\Services\ExpensaService;
use App\Services\PeriodoService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CalcularExpensas extends Component
{

    use AuthorizesRequests;
    public $periodoId;
    public $mostrarConfirmacion = false;
    public $modo = 'todas'; // 'todas' o 'individual'
    public $unidadIdSeleccionada = null;
    public $calculando = false;
    public $resultados = null;

    protected ExpensaService $expensaService;
    protected PeriodoService $periodoService;

    public function boot(ExpensaService $expensaService, PeriodoService $periodoService)
    {
        $this->expensaService = $expensaService;
        $this->periodoService = $periodoService;
    }

    public function mount(int $periodoId)
    {
        $this->periodoId = $periodoId;
    }

    /**
     * Validar período antes de calcular
     */
    #[Computed]
    public function validacion()
    {
        return $this->expensaService->validarPeriodoCompleto($this->periodoId);
    }

    /**
     * Verificar si ya hay expensas calculadas
     */
    #[Computed]
    public function expensasExistentes()
    {
        return Expensa::where('periodo_id', $this->periodoId)->count();
    }

    /**
     * Mostrar confirmación
     */
    public function confirmarCalculo()
    {
        $this->mostrarConfirmacion = true;
    }

    /**
     * Cancelar cálculo
     */
    public function cancelarCalculo()
    {
        $this->mostrarConfirmacion = false;
        $this->resultados = null;
    }

    /**
     * Ejecutar cálculo de expensas
     */
    public function calcularExpensas()
    {
        $this->authorize('create', \App\Models\Expensa::class);
        try {
            $this->calculando = true;

            // Validar período
            $validacion = $this->expensaService->validarPeriodoCompleto($this->periodoId);

            if (!$validacion['valido']) {
                throw new \Exception(implode('. ', $validacion['errores']));
            }

            // Ejecutar cálculo
            if ($this->modo === 'todas') {
                $resultados = $this->expensaService->generarExpensas($this->periodoId);

                $this->resultados = [
                    'tipo' => 'todas',
                    'exitosas' => $resultados['exitosas'],
                    'errores' => $resultados['errores'],
                    'detalles' => array_slice($resultados['detalles'], 0, 10), // Primeras 10
                ];
            } else {
                // Cálculo individual
                if (!$this->unidadIdSeleccionada) {
                    throw new \Exception('Debe seleccionar una unidad para el cálculo individual');
                }

                $unidad = \App\Models\Unidad::findOrFail($this->unidadIdSeleccionada);
                $expensa = $this->expensaService->generarExpensaUnidad(
                    $this->periodoId,
                    $unidad
                );

                $this->resultados = [
                    'tipo' => 'individual',
                    'unidad' => $unidad->nombre_completo,
                    'monto_total' => $expensa->monto_total,
                ];
            }

            // Registrar en log
            Log::info('Expensas calculadas', [
                'periodo_id' => $this->periodoId,
                'modo' => $this->modo,
                'usuario' => auth()->user()?->id,
            ]);

            $this->dispatch('expensas-calculadas');

            $this->dispatch('alerta',
                tipo: 'success',
                titulo: 'Expensas calculadas',
                mensaje: $this->resultados['tipo'] === 'todas'
                    ? "Se calcularon {$this->resultados['exitosas']} expensas correctamente"
                    : "Expensa de {$this->resultados['unidad']} calculada: {$this->resultados['monto_total']} Bs",
            );

            $this->mostrarConfirmacion = false;
        } catch (\Exception $e) {
            Log::error('Error calculando expensas', [
                'periodo_id' => $this->periodoId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('alerta',
                tipo: 'error',
                titulo: 'Error al calcular',
                mensaje: $e->getMessage(),
            );
        } finally {
            $this->calculando = false;
        }
    }

    /**
     * Recalcular expensas (eliminar y crear nuevamente)
     */
    public function recalcularExpensas()
    {
        $this->authorize('recalcular', \App\Models\Expensa::class);
        try {
            $this->calculando = true;

            $resultados = $this->expensaService->recalcularExpensas($this->periodoId);

            $this->resultados = [
                'tipo' => 'recalculo',
                'exitosas' => $resultados['exitosas'],
                'errores' => $resultados['errores'],
            ];

            $this->dispatch('alerta',
                tipo: 'success',
                titulo: 'Expensas recalculadas',
                mensaje: "Se recalcularon {$this->resultados['exitosas']} expensas correctamente",
            );

            Log::info('Expensas recalculadas', [
                'periodo_id' => $this->periodoId,
                'usuario' => auth()->user()?->id,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('alerta',
                tipo: 'error',
                titulo: 'Error al recalcular',
                mensaje: $e->getMessage(),
            );
        } finally {
            $this->calculando = false;
        }
    }

    public function render()
    {
        $periodo = PeriodoExpensa::findOrFail($this->periodoId);

        return view('livewire.periodos.calcular-expensas', [
            'periodo' => $periodo,
        ])->layout('layouts.app');
    }
}
