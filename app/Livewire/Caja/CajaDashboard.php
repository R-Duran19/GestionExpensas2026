<?php

namespace App\Livewire\Caja;

use App\Services\CajaService;
use Livewire\Component;

class CajaDashboard extends Component
{
    public $datos = [];
    public $anoSeleccionado;
    public $mesSeleccionado;

    public function mount()
    {
        $hoy = now();
        $this->anoSeleccionado = $hoy->year;
        $this->mesSeleccionado = $hoy->month;
        $this->cargarDatos();
    }

    public function updatedAnoSeleccionado()
    {
        $this->cargarDatos();
    }

    public function updatedMesSeleccionado()
    {
        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        $service = app(CajaService::class);
        $this->datos = $service->obtenerDatosDashboard();
    }

    public function recalcularTotales()
    {
        try {
            $service = app(CajaService::class);
            $resultado = $service->recalcularTodosLosTotales();

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => $resultado['mensaje'] . '. Fechas procesadas: ' . $resultado['fechas_procesadas'],
            ]);

            $this->cargarDatos();

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al recalcular totales: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.caja.caja-dashboard');
    }
}
