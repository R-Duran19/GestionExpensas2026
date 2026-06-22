<?php

namespace App\Livewire\Periodos;

use App\Models\PeriodoExpensa;
use App\Services\PeriodoService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class PeriodoList extends Component
{
    use WithPagination;

    public $search = '';
    public $filtroEstado = '';

    // Propiedades para el modal de cierre
    public $mostrarModalCierre = false;
    public $periodoIdCerrando = null;
    public $motivoCierre = '';

    protected PeriodoService $periodoService;

    public function abrirModalCierre($periodoId)
    {
        $this->periodoIdCerrando = $periodoId;
        $this->mostrarModalCierre = true;
        $this->motivoCierre = '';
    }

    public function cerrarModalCierre()
    {
        $this->mostrarModalCierre = false;
        $this->periodoIdCerrando = null;
        $this->motivoCierre = '';
    }

    public function boot(PeriodoService $periodoService)
    {
        $this->periodoService = $periodoService;
    }

    public function cerrarPeriodo()
    {
        try {
            if (!$this->periodoIdCerrando) {
                throw new \Exception('No se seleccionó ningún período para cerrar.');
            }

            $this->periodoService->cerrarPeriodo($this->periodoIdCerrando, $this->motivoCierre);

            $this->dispatch('alerta',
                tipo: 'success',
                titulo: 'Período cerrado',
                mensaje: 'El período se cerró correctamente',
            );

            // Cerrar el modal
            $this->cerrarModalCierre();
        } catch (\Exception $e) {
            $this->dispatch('alerta',
                tipo: 'error',
                titulo: 'Error',
                mensaje: $e->getMessage(),
            );
        }
    }

    #[Computed]
    public function periodos()
    {
        $query = PeriodoExpensa::with(['cerradoPor', 'facturasAgua', 'lecturasMedidor'])
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('mes', 'like', '%' . $this->search . '%')
                  ->orWhere('año', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filtroEstado === 'abierto') {
            $query->where('estado', 'abierto');
        } elseif ($this->filtroEstado === 'cerrado') {
            $query->where('estado', 'cerrado');
        }

        return $query->paginate(10);
    }

    #[Computed]
    public function periodoAbierto()
    {
        return $this->periodoService->obtenerPeriodoAbierto();
    }

    public function render()
    {
        return view('livewire.periodos.periodo-list')
            ->layout('layouts.app');
    }
}
