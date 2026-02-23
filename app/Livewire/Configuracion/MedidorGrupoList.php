<?php

namespace App\Livewire\Configuracion;

use App\Models\MedidorGrupo;
use App\Services\MedidorGrupoService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class MedidorGrupoList extends Component
{
    use WithPagination;

    public $search = '';
    public $filtroEstado = '';

    protected MedidorGrupoService $medidorGrupoService;

    public function boot(MedidorGrupoService $medidorGrupoService)
    {
        $this->medidorGrupoService = $medidorGrupoService;
    }

    public function eliminarGrupo($grupoId)
    {
        try {
            $this->medidorGrupoService->eliminarGrupo($grupoId);

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Grupo eliminado',
                'mensaje' => 'El grupo de medidor se eliminó correctamente',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    public function toggleEstado($grupoId)
    {
        try {
            $grupo = $this->medidorGrupoService->toggleEstado($grupoId);

            $estado = $grupo->activo ? 'activado' : 'desactivado';

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Estado actualizado',
                'mensaje' => "El grupo se {$estado} correctamente",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    #[Computed]
    public function grupos()
    {
        $query = MedidorGrupo::with(['unidades', 'creadoPor'])
            ->orderBy('nombre');

        if ($this->search) {
            $query->where('nombre', 'like', '%' . $this->search . '%');
        }

        if ($this->filtroEstado === 'activo') {
            $query->where('activo', true);
        } elseif ($this->filtroEstado === 'inactivo') {
            $query->where('activo', false);
        }

        return $query->paginate(10);
    }

    public function render()
    {
        return view('livewire.configuracion.medidor-grupo-list');
    }
}
