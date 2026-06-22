<?php

namespace App\Livewire\Propietarios;

use App\Models\Propietario;
use App\Services\PropietarioService;
use Livewire\Component;
use Livewire\WithPagination;

class PropietarioList extends Component
{
    use WithPagination;

    // Filtros
    public $busqueda = '';
    public $filtro_activo = '';

    // Para eliminación
    public $propietario_id_a_eliminar = null;
    public $confirmar_eliminacion = false;

    protected $paginationTheme = 'tailwind';

    protected PropietarioService $propietarioService;

    public function boot(PropietarioService $propietarioService)
    {
        $this->propietarioService = $propietarioService;
    }

    public function render()
    {
        $query = Propietario::query();

        // Búsqueda
        if ($this->busqueda) {
            $query->buscar($this->busqueda);
        }

        // Filtro por estado activo
        if ($this->filtro_activo !== '') {
            if ($this->filtro_activo === '1') {
                $query->activos();
            } else {
                $query->inactivos();
            }
        }

        $propietarios = $query->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.propietarios.propietario-list', [
            'propietarios' => $propietarios,
        ])->layout('layouts.app');
    }

    /**
     * Obtener número de unidades de un propietario
     */
    public function getUnidadesCount($propietarioId): int
    {
        $propietario = Propietario::find($propietarioId);
        return $propietario ? $propietario->unidadesActuales->count() : 0;
    }

    /**
     * Activar/Desactivar propietario
     */
    public function toggleActivo($propietarioId)
    {
        $propietario = Propietario::find($propietarioId);

        if (!$propietario) {
            $this->dispatch('toast-error', message: 'Propietario no encontrado');
            return;
        }

        try {
            if ($propietario->activo) {
                $this->propietarioService->desactivar($propietario);
                $this->dispatch('toast-success', message: 'Propietario desactivado correctamente');
            } else {
                $this->propietarioService->activar($propietario);
                $this->dispatch('toast-success', message: 'Propietario activado correctamente');
            }
        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: $e->getMessage());
        }
    }

    /**
     * Confirmar eliminación
     */
    public function confirmarEliminacion($propietarioId)
    {
        $this->propietario_id_a_eliminar = $propietarioId;
        $this->confirmar_eliminacion = true;
    }

    /**
     * Eliminar propietario
     */
    public function eliminarPropietario()
    {
        $propietario = Propietario::find($this->propietario_id_a_eliminar);

        if (!$propietario) {
            $this->dispatch('toast-error', message: 'Propietario no encontrado');
            $this->confirmar_eliminacion = false;
            return;
        }

        // Verificar si tiene unidades activas
        if ($this->getUnidadesCount($propietario->id) > 0) {
            $this->dispatch('toast-error', message: 'No se puede eliminar un propietario con unidades asignadas');
            $this->confirmar_eliminacion = false;
            return;
        }

        $propietario->delete();
        $this->dispatch('toast-success', message: 'Propietario eliminado correctamente');

        $this->confirmar_eliminacion = false;
        $this->propietario_id_a_eliminar = null;
    }

    /**
     * Cancelar eliminación
     */
    public function cancelarEliminacion()
    {
        $this->confirmar_eliminacion = false;
        $this->propietario_id_a_eliminar = null;
    }

    /**
     * Limpiar filtros
     */
    public function limpiarFiltros()
    {
        $this->reset(['busqueda', 'filtro_activo']);
        $this->resetPage();
    }

    /**
     * Refrescar lista después de editar
     */
    #[On('propietario-actualizado')]
    public function refrescarLista()
    {
        // La lista se refrescará automáticamente
    }
}
