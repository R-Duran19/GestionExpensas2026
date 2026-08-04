<?php

namespace App\Livewire\Inquilinos;

use App\Models\Inquilino;
use App\Services\InquilinoService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class InquilinoList extends Component
{
    use WithPagination;

    // Filtros
    public $busqueda = '';
    public $filtro_activo = '';

    // Para eliminación
    public $inquilino_id_a_eliminar = null;
    public $confirmar_eliminacion = false;

    protected $paginationTheme = 'tailwind';

    protected InquilinoService $inquilinoService;

    public function boot(InquilinoService $inquilinoService)
    {
        $this->inquilinoService = $inquilinoService;
    }

    public function render()
    {
        $inquilinos = Inquilino::query();

        // Búsqueda
        if ($this->busqueda) {
            $inquilinos->buscar($this->busqueda);
        }

        // Filtro por estado activo
        if ($this->filtro_activo !== '') {
            if ($this->filtro_activo === '1') {
                $inquilinos->activos();
            } else {
                $inquilinos->inactivos();
            }
        }

        // withCount evita N+1: una sola query agrega el contador de unidades actuales.
        // Usamos la relación base 'unidades' (sin wherePivot) y filtramos por la columna
        // completa de la pivot, porque wherePivot no se traduce bien dentro del subquery.
        $inquilinos = $inquilinos->withCount(['unidades as unidades_actuales_count' => function ($q) {
                $q->where('inquilino_unidad.es_inquilino_actual', true);
            }])
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.inquilinos.inquilino-list', [
            'inquilinos' => $inquilinos,
        ])->layout('layouts.app');
    }

    /**
     * Activar/Desactivar inquilino
     */
    public function toggleActivo($inquilinoId)
    {
        $inquilino = Inquilino::find($inquilinoId);

        if (!$inquilino) {
            $this->dispatch('toast-error', message: 'Inquilino no encontrado');
            return;
        }

        try {
            if ($inquilino->activo) {
                $this->inquilinoService->desactivar($inquilino);
                $this->dispatch('toast-success', message: 'Inquilino desactivado correctamente');
            } else {
                $this->inquilinoService->activar($inquilino);
                $this->dispatch('toast-success', message: 'Inquilino activado correctamente');
            }
        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: $e->getMessage());
        }
    }

    /**
     * Confirmar eliminación
     */
    public function confirmarEliminacion($inquilinoId)
    {
        $this->inquilino_id_a_eliminar = $inquilinoId;
        $this->confirmar_eliminacion = true;
    }

    /**
     * Eliminar inquilino
     */
    public function eliminarInquilino()
    {
        $inquilino = Inquilino::find($this->inquilino_id_a_eliminar);

        if (!$inquilino) {
            $this->dispatch('toast-error', message: 'Inquilino no encontrado');
            $this->confirmar_eliminacion = false;
            return;
        }

        // Verificar si tiene unidades activas
        $tieneUnidades = $inquilino->unidadesActuales()->exists();

        if ($tieneUnidades) {
            $this->dispatch('toast-error', message: 'No se puede eliminar un inquilino con unidades asignadas');
            $this->confirmar_eliminacion = false;
            return;
        }

        $inquilino->delete();
        $this->dispatch('toast-success', message: 'Inquilino eliminado correctamente');

        $this->confirmar_eliminacion = false;
        $this->inquilino_id_a_eliminar = null;
    }

    /**
     * Cancelar eliminación
     */
    public function cancelarEliminacion()
    {
        $this->confirmar_eliminacion = false;
        $this->inquilino_id_a_eliminar = null;
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
    #[On('inquilino-actualizado')]
    public function refrescarLista()
    {
        // La lista se refrescará automáticamente
    }
}
