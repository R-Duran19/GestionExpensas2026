<?php

namespace App\Livewire\Caja\Egresos;

use App\Models\Egreso;
use App\Models\CategoriaEgreso;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EgresoList extends Component
{

    use AuthorizesRequests;
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
        $this->authorize('delete', \App\Models\Egreso::class);
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

    public function verComprobante($egresoId)
    {
        // [SEGURIDAD-09 / Fase 1.2] Recibe el ID del egreso (antes recibia la ruta).
        // Usa la ruta protegida auth+admin+throttle en vez de Storage::url() publico.
        $egreso = Egreso::findOrFail($egresoId);

        if (!$egreso->comprobante_foto || !$egreso->tieneComprobante()) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'El comprobante no existe o no pudo ser localizado.',
            ]);
            return;
        }

        $this->comprobanteUrl = route('comprobante.egreso', $egreso);
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
