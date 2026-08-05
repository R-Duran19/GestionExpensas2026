<?php

namespace App\Livewire\Expensas;

use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use App\Models\Expensa;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BoletaManual extends Component
{
    public $periodoId = null;
    public $unidadesSeleccionadas = [];
    public $search = '';
    public $filtroTipo = '';

    #[Computed()]
    public function periodos()
    {
        return PeriodoExpensa::orderBy('fecha_inicio', 'desc')
            ->get();
    }

    #[Computed()]
    public function periodo()
    {
        if (!$this->periodoId) {
            return null;
        }
        return PeriodoExpensa::find($this->periodoId);
    }

    public function getUnidadesProperty()
    {
        $query = Unidad::with(['propietarioActual', 'expensas' => function($query) {
            if ($this->periodoId) {
                $query->where('periodo_id', $this->periodoId);
            }
        }])->where('activo', true);

        // Filtro por búsqueda
        if ($this->search) {
            $query->where(function($q) {
                $q->where('codigo', 'like', '%' . $this->search . '%')
                  ->orWhereHas('propietarioActual', function($subQ) {
                      $subQ->where('nombre', 'like', '%' . $this->search . '%')
                           ->orWhere('apellido', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Filtro por tipo
        if ($this->filtroTipo) {
            $query->where('tipo_unidad', $this->filtroTipo);
        }

        return $query->orderByRaw(Unidad::ordenPorTipoUnidadRaw('tipo_unidad'))
            ->orderByRaw(Unidad::ordenPorCodigoRaw('codigo', 'ASC'))
            ->get();
    }

    public function seleccionarTodas()
    {
        $this->unidadesSeleccionadas = $this->unidades->pluck('id')->toArray();
    }

    public function deseleccionarTodas()
    {
        $this->unidadesSeleccionadas = [];
    }

    public function toggleTodas()
    {
        $todasIds = $this->unidades->pluck('id')->toArray();
        $todasSeleccionadas = count($this->unidadesSeleccionadas) === count($todasIds) &&
                               empty(array_diff($todasIds, $this->unidadesSeleccionadas));

        if ($todasSeleccionadas) {
            $this->deseleccionarTodas();
        } else {
            $this->seleccionarTodas();
        }
    }

    public function generarPDFs()
    {
        if (empty($this->unidadesSeleccionadas)) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Atención',
                'mensaje' => 'Debes seleccionar al menos una unidad.'
            ]);
            return;
        }

        if (!$this->periodo) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Atención',
                'mensaje' => 'Debes seleccionar un período.'
            ]);
            return;
        }

        // Obtener las unidades seleccionadas
        $unidades = $this->unidades->filter(function($unidad) {
            return in_array($unidad->id, $this->unidadesSeleccionadas);
        });

        // Verificar que las unidades tengan expensas calculadas
        $unidadesSinExpensa = [];
        foreach ($unidades as $unidad) {
            $expensa = $unidad->expensas->first();
            if (!$expensa) {
                $unidadesSinExpensa[] = $unidad->codigo;
            }
        }

        if (!empty($unidadesSinExpensa)) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Atención',
                'mensaje' => 'Las siguientes unidades no tienen expensas calculadas para este período: ' . implode(', ', $unidadesSinExpensa) . '. Solo se generarán las que tienen expensas.'
            ]);
            // Filtrar solo las que tienen expensa
            $unidades = $unidades->filter(function($unidad) {
                return $unidad->expensas->first();
            });
        }

        if ($unidades->isEmpty()) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Atención',
                'mensaje' => 'Ninguna de las unidades seleccionadas tiene expensas calculadas.'
            ]);
            return;
        }

        // Generar URLs de PDF y enviarlas al frontend
        $urlsPDF = [];
        foreach ($unidades as $unidad) {
            $urlsPDF[] = route('expensas.unidad.pdf.mostrar', [
                'periodo' => $this->periodo->id,
                'unidad' => $unidad->id
            ]);
        }

        // Enviar las URLs al frontend para abrirlas
        $this->dispatch('abrirPDFs', urls: $urlsPDF);
    }

    public function render()
    {
        return view('livewire.expensas.boleta-manual');
    }
}
