<?php

namespace App\Livewire\Propiedades;

use App\Enums\CategoriaUnidad;
use App\Enums\TipoUnidad;
use App\Models\Unidad;
use App\Models\PeriodoExpensa;
use App\Services\UnidadService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class UnidadList extends Component
{
    use WithPagination;

    // Filtros
    public $busqueda = '';
    public $filtro_tipo = '';
    public $filtro_categoria = '';
    public $filtro_activo = '';

    // Período para PDF
    public $periodoId = null;

    // Ordenamiento
    public $ordenar_por = 'codigo';
    public $direccion_orden = 'asc';

    // Para eliminación
    public $unidad_id_a_eliminar = null;
    public $confirmar_eliminacion = false;

    protected $paginationTheme = 'tailwind';

    protected UnidadService $unidadService;

    public function mount()
    {
        // Cargar el período más reciente por defecto
        $periodoReciente = PeriodoExpensa::orderBy('fecha_apertura', 'desc')->first();
        if ($periodoReciente) {
            $this->periodoId = $periodoReciente->id;
        }
    }

    public function boot(UnidadService $unidadService)
    {
        $this->unidadService = $unidadService;
    }

    public function render()
    {
        $query = Unidad::query();

        // Búsqueda
        if ($this->busqueda) {
            $query->buscar($this->busqueda);
        }

        // Filtro por tipo
        if ($this->filtro_tipo) {
            $query->where('tipo_unidad', $this->filtro_tipo);
        }

        // Filtro por categoría
        if ($this->filtro_categoria) {
            $query->where('categoria_unidad', $this->filtro_categoria);
        }

        // Filtro por estado activo
        if ($this->filtro_activo !== '') {
            if ($this->filtro_activo === '1') {
                $query->activos();
            } else {
                $query->inactivos();
            }
        }

        // Ordenamiento
        if ($this->ordenar_por === 'codigo') {
            $query->orderByRaw("SUBSTRING_INDEX(codigo, '-', -1) + 0 {$this->direccion_orden}");
        } else {
            $query->orderBy($this->ordenar_por, $this->direccion_orden);
        }

        $unidades = $query->paginate(15);

        return view('livewire.propiedades.unidad-list', [
            'unidades' => $unidades,
        ])->layout('layouts.app');
    }

    #[Computed]
    public function tiposUnidad(): array
    {
        return TipoUnidad::toSelectArray();
    }

    #[Computed]
    public function categorias(): array
    {
        return CategoriaUnidad::toSelectArray();
    }

    #[Computed]
    public function periodos()
    {
        return PeriodoExpensa::orderBy('fecha_apertura', 'desc')->get();
    }

    #[Computed]
    public function periodo()
    {
        if ($this->periodoId) {
            return PeriodoExpensa::find($this->periodoId);
        }
        return null;
    }

    /**
     * Ordenar por columna
     */
    public function ordenarPor($columna)
    {
        if ($this->ordenar_por === $columna) {
            $this->direccion_orden = $this->direccion_orden === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenar_por = $columna;
            $this->direccion_orden = 'asc';
        }
    }

    /**
     * Activar/Desactivar unidad
     */
    public function toggleActivo($unidadId)
    {
        $unidad = Unidad::find($unidadId);

        if (!$unidad) {
            $this->dispatch('toast-error', message: 'Unidad no encontrada');
            return;
        }

        if ($unidad->activo) {
            // Verificar si tiene propietario antes de desactivar
            if ($unidad->tienePropietarioActual()) {
                $this->dispatch('toast-error', message: 'No se puede desactivar una unidad con propietario asignado');
                return;
            }
            $this->unidadService->desactivar($unidad);
            $this->dispatch('toast-success', message: 'Unidad desactivada correctamente');
        } else {
            $this->unidadService->activar($unidad);
            $this->dispatch('toast-success', message: 'Unidad activada correctamente');
        }
    }

    /**
     * Confirmar eliminación
     */
    public function confirmarEliminacion($unidadId)
    {
        $this->unidad_id_a_eliminar = $unidadId;
        $this->confirmar_eliminacion = true;
    }

    /**
     * Eliminar unidad
     */
    public function eliminarUnidad()
    {
        $unidad = Unidad::find($this->unidad_id_a_eliminar);

        if (!$unidad) {
            $this->dispatch('toast-error', message: 'Unidad no encontrada');
            $this->confirmar_eliminacion = false;
            return;
        }

        // Verificar si puede ser eliminada
        if (!$this->unidadService->puedeEliminar($unidad)) {
            $this->dispatch('toast-error', message: 'No se puede eliminar una unidad con propietario asignado');
            $this->confirmar_eliminacion = false;
            return;
        }

        $unidad->delete();
        $this->dispatch('toast-success', message: 'Unidad eliminada correctamente');

        $this->confirmar_eliminacion = false;
        $this->unidad_id_a_eliminar = null;
    }

    /**
     * Cancelar eliminación
     */
    public function cancelarEliminacion()
    {
        $this->confirmar_eliminacion = false;
        $this->unidad_id_a_eliminar = null;
    }

    /**
     * Limpiar filtros
     */
    public function limpiarFiltros()
    {
        $this->reset(['busqueda', 'filtro_tipo', 'filtro_categoria', 'filtro_activo']);
        $this->resetPage();
    }

    /**
     * Refrescar lista después de editar
     */
    #[On('unidad-actualizada')]
    public function refrescarLista()
    {
        // La lista se refrescará automáticamente
    }

    /**
     * Ver expensa de la unidad
     */
    public function verExpensa($unidadId)
    {
        return redirect()->route('expensas.unidad.detalle', $unidadId);
    }

    public function getIconoTipo($tipoUnidad): string
    {
        return TipoUnidad::from($tipoUnidad)->getIcon();
    }
}
