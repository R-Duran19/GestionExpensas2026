<?php

namespace App\Livewire\Periodos;

use App\Services\PeriodoService;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CrearPeriodo extends Component
{

    use AuthorizesRequests;
    #[Validate('required|integer|min:1|max:12')]
    public $mes = 1;

    #[Validate('required|integer|min:2000|max:2100')]
    public $año;

    protected PeriodoService $periodoService;

    public function boot(PeriodoService $periodoService)
    {
        $this->periodoService = $periodoService;
    }

    public function mount()
    {
        // Obtener el último período creado
        $ultimoPeriodo = \App\Models\PeriodoExpensa::orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        if ($ultimoPeriodo) {
            // Calcular el siguiente período
            $this->año = $ultimoPeriodo->año;
            $this->mes = $ultimoPeriodo->mes + 1;

            // Si el último fue diciembre, el siguiente es enero del año siguiente
            if ($ultimoPeriodo->mes === 12) {
                $this->mes = 1;
                $this->año = $ultimoPeriodo->año + 1;
            }
        } else {
            // Si no hay períodos, usar el mes/año actual
            $this->año = date('Y');
            $this->mes = date('n');
        }
    }

    public function guardar()
    {
        $this->authorize('create', \App\Models\PeriodoExpensa::class);
        $this->validate();

        try {
            // Verificar si se puede abrir el período
            $verificacion = $this->periodoService->puedeAbrirPeriodo($this->mes, $this->año);

            if (!$verificacion['puede']) {
                $this->dispatch('alerta',
                    tipo: 'error',
                    titulo: 'No se puede abrir el período',
                    mensaje: $verificacion['motivo'],
                );
                return;
            }

            // Abrir el período
            $periodo = $this->periodoService->abrirPeriodo($this->mes, $this->año);

            // Mostrar alerta de éxito
            $this->dispatch('alerta',
                tipo: 'success',
                titulo: 'Período creado',
                mensaje: "El período {$periodo->nombre_periodo} se abrió correctamente.",
            );

            // Redirigir a la lista de períodos
            return $this->redirectRoute('periodos.index');
        } catch (\Exception $e) {
            $this->dispatch('alerta',
                tipo: 'error',
                titulo: 'Error',
                mensaje: $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view('livewire.periodos.crear-periodo')
            ->layout('layouts.app');
    }
}
