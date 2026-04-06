<?php

namespace App\Livewire\Caja\Ingresos;

use App\Models\Ingreso;
use App\Models\CategoriaIngreso;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IngresoList extends Component
{
    use WithPagination;

    public $buscar = '';
    public $filtro_categoria = '';
    public $filtro_metodo = '';
    public $filtro_fecha_desde = '';
    public $filtro_fecha_hasta = '';
    public $sortField = 'fecha';
    public $sortDirection = 'desc';

    public $mostrarModalEliminar = false;
    public $ingresoAEliminar = null;

    public $mostrarModalComprobante = false;
    public $comprobanteUrl = null;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'buscar' => ['except' => ''],
        'filtro_categoria' => ['except' => ''],
        'filtro_metodo' => ['except' => ''],
        'sortField' => ['except' => 'fecha'],
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

    public function confirmarEliminar(Ingreso $ingreso)
    {
        $this->ingresoAEliminar = $ingreso;
        $this->mostrarModalEliminar = true;
    }

    public function eliminar()
    {
        if (!$this->ingresoAEliminar) {
            return;
        }

        try {
            $service = app(\App\Services\IngresoService::class);
            $service->eliminar($this->ingresoAEliminar);

            $this->dispatch('ingreso-eliminado');
            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => 'Ingreso eliminado correctamente',
            ]);

            $this->mostrarModalEliminar = false;
            $this->ingresoAEliminar = null;
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al eliminar el ingreso: ' . $e->getMessage(),
            ]);
        }
    }

    public function cancelarEliminar()
    {
        $this->mostrarModalEliminar = false;
        $this->ingresoAEliminar = null;
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

    #[On('ingreso-creado')]
    #[On('ingreso-actualizado')]
    public function render()
    {
        $query = Ingreso::activos()
            ->with(['categoria', 'unidad', 'creadoPor']);

        // Búsqueda
        if ($this->buscar) {
            $query->where(function ($q) {
                $q->where('numero_recibo', 'like', "%{$this->buscar}%")
                    ->orWhere('descripcion', 'like', "%{$this->buscar}%")
                    ->orWhere('referencia_bancaria', 'like', "%{$this->buscar}%");
            });
        }

        // Filtro por categoría
        if ($this->filtro_categoria) {
            $query->porCategoria($this->filtro_categoria);
        }

        // Filtro por método de pago
        if ($this->filtro_metodo) {
            $query->porMetodoPago($this->filtro_metodo);
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

        $ingresos = $query->paginate(15);

        $categorias = CategoriaIngreso::activas()->orderBy('nombre')->get();

        return view('livewire.caja.ingresos.ingreso-list', [
            'ingresos' => $ingresos,
            'categorias' => $categorias,
        ]);
    }
}
