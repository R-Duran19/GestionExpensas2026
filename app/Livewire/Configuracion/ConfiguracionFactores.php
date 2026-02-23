<?php

namespace App\Livewire\Configuracion;

use App\Services\FactorService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class ConfiguracionFactores extends Component
{
    public $showModal = false;
    public $categoriaSeleccionada = '';
    public $factorActual = 0;
    public $nuevoFactor = '';
    public $motivo = '';

    protected FactorService $factorService;

    public function boot(FactorService $factorService)
    {
        $this->factorService = $factorService;
    }

    #[Computed]
    public function factores()
    {
        return $this->factorService->obtenerFactoresVigentes();
    }

    public function abrirModalEdicion($categoria)
    {
        $this->categoriaSeleccionada = $categoria;
        $this->factorActual = $this->factores[$categoria]->factor ?? 0;
        $this->nuevoFactor = '';
        $this->motivo = '';
        $this->showModal = true;
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->reset(['categoriaSeleccionada', 'factorActual', 'nuevoFactor', 'motivo']);
    }

    public function actualizarFactor()
    {
        $this->validate([
            'nuevoFactor' => 'required|numeric|gt:0',
            'motivo' => 'required|string|min:3',
        ]);

        try {
            $this->factorService->actualizarFactor(
                $this->categoriaSeleccionada,
                (float) $this->nuevoFactor,
                $this->motivo
            );

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Factor actualizado',
                'mensaje' => "El factor {$this->categoriaSeleccionada} se actualizó correctamente",
            ]);

            $this->cerrarModal();
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.configuracion.configuracion-factores');
    }
}
