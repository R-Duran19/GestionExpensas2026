<?php

namespace App\Livewire\Caja\Egresos;

use App\Models\Egreso;
use App\Models\CategoriaEgreso;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EgresoList extends Component
{
    use WithPagination;

    public $buscar = '';
    public $filtro_categoria = '';
    public $filtro_proveedor = '';
    public $filtro_fecha_desde = '';
    public $filtro_fecha_hasta = '';
    public $sortField = 'numero_egreso';
    public $sortDirection = 'desc';

    public $mostrarModalEliminar = false;
    public $egresoAEliminar = null;

    public $mostrarModalComprobante = false;
    public $comprobanteUrl = null;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'buscar' => ['except' => ''],
        'filtro_categoria' => ['except' => ''],
        'filtro_proveedor' => ['except' => ''],
        'sortField' => ['except' => 'numero_egreso'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function confirmarEliminar(Egreso $egreso)
    {
        $this->egresoAEliminar = $egreso;
        $this->mostrarModalEliminar = true;
    }

    public function eliminar()
    {
        if (!$this->egresoAEliminar) {
            return;
        }

        try {
            $service = app(\App\Services\EgresoService::class);
            $service->eliminar($this->egresoAEliminar);

            $this->dispatch('egreso-eliminado');
            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => 'Egreso eliminado correctamente',
            ]);

            $this->mostrarModalEliminar = false;
            $this->egresoAEliminar = null;
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al eliminar el egreso: ' . $e->getMessage(),
            ]);
        }
    }

    public function cancelarEliminar()
    {
        $this->mostrarModalEliminar = false;
        $this->egresoAEliminar = null;
    }

    public function verComprobante($ruta)
    {
        $this->comprobanteUrl = Storage::url($ruta);
        $this->mostrarModalComprobante = true;
    }

    public function cerrarModalComprobante()
    {
        $this->mostrarModalComprobante = false;
        $this->comprobanteUrl = null;
    }

    #[On('egreso-creado')]
    #[On('egreso-actualizado')]
    public function render()
    {
        $query = Egreso::activos()
            ->with(['categoria', 'creadoPor']);

        // Búsqueda
        if ($this->buscar) {
            $query->where(function ($q) {
                $q->where('numero_egreso', 'like', "%{$this->buscar}%")
                    ->orWhere('concepto', 'like', "%{$this->buscar}%")
                    ->orWhere('numero_comprobante', 'like', "%{$this->buscar}%")
                    ->orWhere('proveedor', 'like', "%{$this->buscar}%");
            });
        }

        // Filtro por categoría
        if ($this->filtro_categoria) {
            $query->porCategoria($this->filtro_categoria);
        }

        // Filtro por proveedor
        if ($this->filtro_proveedor) {
            $query->porProveedor($this->filtro_proveedor);
        }

        // Filtro por rango de fechas
        if ($this->filtro_fecha_desde && $this->filtro_fecha_hasta) {
            $query->porRangoFechas($this->filtro_fecha_desde, $this->filtro_fecha_hasta);
        } elseif ($this->filtro_fecha_desde) {
            $query->whereDate('fecha', '>=', $this->filtro_fecha_desde);
        } elseif ($this->filtro_fecha_hasta) {
            $query->whereDate('fecha', '<=', $this->filtro_fecha_hasta);
        }

        // Ordenamiento
        $query->orderBy($this->sortField, $this->sortDirection);

        $egresos = $query->paginate(15);

        $categorias = CategoriaEgreso::activas()->orderBy('nombre')->get();

        return view('livewire.caja.egresos.egreso-list', [
            'egresos' => $egresos,
            'categorias' => $categorias,
        ])->layout('layouts.app');
    }
}
