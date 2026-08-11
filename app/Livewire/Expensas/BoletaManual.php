<?php

namespace App\Livewire\Expensas;

use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use App\Models\Expensa;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class BoletaManual extends Component
{
    use WithPagination;

    public $periodoId = null;
    public $unidadesSeleccionadas = [];
    public $search = '';
    public $filtroTipo = '';
    public $pdfGenerado = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filtroTipo' => ['except' => ''],
    ];

    #[Computed()]
    public function periodos()
    {
        return PeriodoExpensa::orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
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
        $query = Unidad::with([
            'propietarioActual',
            'inquilinoActual',
            'expensas' => function ($query) {
                if ($this->periodoId) {
                    $query->where('periodo_id', $this->periodoId);
                }
            },
        ])->where('activo', true);

        // Filtro por búsqueda (código, propietario o inquilino)
        if ($this->search) {
            $termino = trim($this->search);
            if ($termino !== '') {
                $query->where(function ($q) use ($termino) {
                    $q->where('codigo', 'like', '%' . $termino . '%')
                      ->orWhereHas('propietarioActual', function ($subQ) use ($termino) {
                          $subQ->where('nombre', 'like', '%' . $termino . '%')
                               ->orWhere('apellido', 'like', '%' . $termino . '%');
                      })
                      ->orWhereHas('inquilinoActual', function ($subQ) use ($termino) {
                          $subQ->where('nombre', 'like', '%' . $termino . '%')
                               ->orWhere('apellido', 'like', '%' . $termino . '%');
                      });
                });
            }
        }

        // Filtro por tipo
        if ($this->filtroTipo) {
            $query->where('tipo_unidad', $this->filtroTipo);
        }

        return $query->orderByRaw(Unidad::ordenPorTipoUnidadRaw('tipo_unidad'))
            ->orderByRaw(Unidad::ordenPorCodigoRaw('codigo', 'ASC'))
            ->paginate(20);
    }

    public function seleccionarTodas()
    {
        // Solo las de la página actual (paginación)
        $idsPagina = $this->unidades->pluck('id')->all();
        $this->unidadesSeleccionadas = array_values(
            array_unique(array_merge($this->unidadesSeleccionadas, $idsPagina))
        );
    }

    public function deseleccionarTodas()
    {
        // Solo las de la página actual
        $idsPagina = $this->unidades->pluck('id')->all();
        $this->unidadesSeleccionadas = array_values(
            array_diff($this->unidadesSeleccionadas, $idsPagina)
        );
    }

    public function toggleTodas()
    {
        $idsPagina = $this->unidades->pluck('id')->all();
        $todasSeleccionadas = !empty($idsPagina) && empty(array_diff($idsPagina, $this->unidadesSeleccionadas));

        if ($todasSeleccionadas) {
            $this->deseleccionarTodas();
        } else {
            $this->seleccionarTodas();
        }
    }

    public function limpiarSeleccion()
    {
        $this->unidadesSeleccionadas = [];
    }

    public function generarPDFs()
    {
        $this->pdfGenerado = null;

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

        // Query directa por IDs seleccionados para identificar cuáles tienen expensa
        $expensas = Expensa::where('periodo_id', $this->periodo->id)
            ->whereIn('unidad_id', $this->unidadesSeleccionadas)
            ->pluck('unidad_id');

        $unidadesConExpensa = $expensas->all();

        if (empty($unidadesConExpensa)) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Atención',
                'mensaje' => 'Ninguna de las unidades seleccionadas tiene expensas calculadas para este período.'
            ]);
            return;
        }

        // Unidades seleccionadas sin expensa (para avisar)
        $unidadesSinExpensa = array_diff($this->unidadesSeleccionadas, $unidadesConExpensa);

        // Generar URL al PDF consolidado (una boleta por página)
        $this->pdfGenerado = [
            'url' => route('expensas.boletas.imprimir.mostrar', [
                'periodo' => $this->periodo->id,
                'unidades' => implode(',', $unidadesConExpensa),
            ]),
            'descargar' => route('expensas.boletas.imprimir.descargar', [
                'periodo' => $this->periodo->id,
                'unidades' => implode(',', $unidadesConExpensa),
            ]),
            'cantidad' => count($unidadesConExpensa),
            'periodo' => $this->periodo->nombre_periodo,
        ];

        $mensaje = 'Boleta lista: ' . count($unidadesConExpensa) . ' unidades. Usá los botones Ver/Descargar abajo.';
        $tipo = 'success';
        if (!empty($unidadesSinExpensa)) {
            // Mostrar códigos de las que no tienen expensa
            $codigosSin = Unidad::whereIn('id', $unidadesSinExpensa)->pluck('codigo')->all();
            $mensaje = 'Se generó PDF con ' . count($unidadesConExpensa) . ' unidades. Sin expensa: ' . implode(', ', $codigosSin) . '.';
            $tipo = 'warning';
        }

        $this->dispatch('alerta', [
            'tipo' => $tipo,
            'titulo' => 'Boletas listas',
            'mensaje' => $mensaje,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo()
    {
        $this->resetPage();
    }

    public function updatingPeriodoId()
    {
        $this->resetPage();
        $this->unidadesSeleccionadas = [];
        $this->pdfGenerado = null;
    }

    public function render()
    {
        return view('livewire.expensas.boleta-manual');
    }
}
