<?php

namespace App\Livewire\Caja\Categorias;

use App\Models\CategoriaEgreso;
use App\Services\CategoriaEgresoService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class CategoriaEgresoList extends Component
{
    use WithPagination;

    public $buscar = '';
    public $filtro_estado = '';

    public $mostrarModalCrear = false;
    public $mostrarModalEditar = false;
    public $mostrarModalHistorial = false;
    public $categoriaSeleccionada = null;
    public $historialCategoria = [];

    public $nombre = '';
    public $descripcion = '';
    public $color = '#EF4444';
    public $icono = 'arrow-down-circle';

    public $motivo_edicion = '';

    protected $paginationTheme = 'tailwind';

    protected $rules = [
        'nombre' => 'required|string|max:100|unique:categorias_egresos,nombre',
        'descripcion' => 'nullable|string',
        'color' => 'required|string|max:7',
        'icono' => 'required|string|max:50',
    ];

    protected $messages = [
        'nombre.required' => 'El nombre es obligatorio',
        'nombre.unique' => 'Ya existe una categoría con ese nombre',
        'color.required' => 'El color es obligatorio',
    ];

    public function abrirModalCrear()
    {
        $this->resetFormulario();
        $this->mostrarModalCrear = true;
    }

    public function crear()
    {
        $this->validate();

        try {
            $service = app(CategoriaEgresoService::class);
            $service->crear([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'color' => $this->color,
                'icono' => $this->icono,
            ]);

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => 'Categoría creada correctamente',
            ]);

            $this->cerrarModalCrear();

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al crear la categoría: ' . $e->getMessage(),
            ]);
        }
    }

    public function abrirModalEditar(CategoriaEgreso $categoria)
    {
        $this->categoriaSeleccionada = $categoria;
        $this->nombre = $categoria->nombre;
        $this->descripcion = $categoria->descripcion;
        $this->color = $categoria->color;
        $this->icono = $categoria->icono;
        $this->motivo_edicion = '';
        $this->mostrarModalEditar = true;
    }

    public function actualizar()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:categorias_egresos,nombre,' . $this->categoriaSeleccionada->id,
            'descripcion' => 'nullable|string',
            'color' => 'required|string|max:7',
            'icono' => 'required|string|max:50',
            'motivo_edicion' => 'required|string',
        ], [
            'motivo_edicion.required' => 'El motivo de la edición es obligatorio',
        ]);

        try {
            $service = app(CategoriaEgresoService::class);
            $service->actualizar($this->categoriaSeleccionada, [
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'color' => $this->color,
                'icono' => $this->icono,
            ], $this->motivo_edicion);

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => 'Categoría actualizada correctamente',
            ]);

            $this->cerrarModalEditar();

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al actualizar la categoría: ' . $e->getMessage(),
            ]);
        }
    }

    public function confirmarEliminar(CategoriaEgreso $categoria)
    {
        try {
            $service = app(CategoriaEgresoService::class);
            $service->eliminar($categoria);

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => 'Categoría eliminada correctamente',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    public function toggleActivo(CategoriaEgreso $categoria)
    {
        $service = app(CategoriaEgresoService::class);
        $service->toggleActivo($categoria);
    }

    public function verHistorial(CategoriaEgreso $categoria)
    {
        $service = app(CategoriaEgresoService::class);
        $this->historialCategoria = $service->obtenerHistorial($categoria);
        $this->categoriaSeleccionada = $categoria;
        $this->mostrarModalHistorial = true;
    }

    public function cerrarModalCrear()
    {
        $this->mostrarModalCrear = false;
        $this->resetFormulario();
    }

    public function cerrarModalEditar()
    {
        $this->mostrarModalEditar = false;
        $this->resetFormulario();
        $this->categoriaSeleccionada = null;
    }

    public function cerrarModalHistorial()
    {
        $this->mostrarModalHistorial = false;
        $this->historialCategoria = [];
        $this->categoriaSeleccionada = null;
    }

    protected function resetFormulario()
    {
        $this->nombre = '';
        $this->descripcion = '';
        $this->color = '#EF4444';
        $this->icono = 'arrow-down-circle';
        $this->motivo_edicion = '';
    }

    #[On('categoria-egreso-creada')]
    #[On('categoria-egreso-actualizada')]
    public function render()
    {
        $query = CategoriaEgreso::orderBy('nombre');

        if ($this->buscar) {
            $query->where('nombre', 'like', "%{$this->buscar}%");
        }

        if ($this->filtro_estado === 'activos') {
            $query->activas();
        } elseif ($this->filtro_estado === 'inactivos') {
            $query->where('activo', false);
        }

        $categorias = $query->paginate(10);

        return view('livewire.caja.categorias.categoria-egreso-list', [
            'categorias' => $categorias,
        ])->layout('layouts.app');
    }
}
