<?php

namespace App\Livewire\Inquilinos;

use App\Models\Inquilino;
use App\Models\Unidad;
use App\Services\InquilinoService;
use Livewire\Component;

class InquilinoShow extends Component
{
    public $inquilinoId;
    public $inquilino;

    protected InquilinoService $inquilinoService;

    public function boot(InquilinoService $inquilinoService)
    {
        $this->inquilinoService = $inquilinoService;
    }

    public function mount($inquilino)
    {
        $this->inquilinoId = $inquilino;
        $this->cargarInquilino();
    }

    /**
     * Cargar el inquilino con sus unidades actuales (se usa también tras desasignar)
     */
    protected function cargarInquilino(): void
    {
        $this->inquilino = Inquilino::with(['unidadesActuales' => function ($q) {
            $q->orderBy('pivot_fecha_inicio', 'desc');
        }])->findOrFail($this->inquilinoId);
    }

    /**
     * Desasignar una unidad del inquilino
     */
    public function desasignarUnidad($unidadId)
    {
        try {
            $unidad = Unidad::findOrFail($unidadId);
            $inquilino = Inquilino::findOrFail($this->inquilinoId);

            $this->inquilinoService->desasignarUnidad($unidad, $inquilino);

            // Recargar la relación para reflejar el cambio sin recargar la página
            $this->cargarInquilino();

            $this->dispatch('toast-success', message: "Unidad {$unidad->codigo} desasignada correctamente");

        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.inquilinos.inquilino-show')
            ->layout('layouts.app');
    }
}
