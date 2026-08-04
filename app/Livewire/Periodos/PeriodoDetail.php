<?php

namespace App\Livewire\Periodos;

use App\Enums\TipoUnidad;
use App\Models\LecturaGrupoPeriodo;
use App\Models\LecturaMedidor;
use App\Models\PeriodoExpensa;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class PeriodoDetail extends Component
{
    use WithPagination;

    #[Locked]
    public $periodoId;

    public $periodo;

    public $filtroTipo = '';

    public $search = '';

    protected $paginationTheme = 'tailwind';

    public function mount($periodo)
    {
        $this->periodoId = $periodo;
        $this->periodo = PeriodoExpensa::with([
            'cerradoPor',
            'facturasAgua',
            'lecturasMedidor',
        ])->findOrFail($periodo);
    }

    public function updatingFiltroTipo()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    protected function propietarioDe($unidad): string
    {
        $prop = $unidad->propietarios->first(fn ($p) => $p->pivot && $p->pivot->es_propietario_actual);

        return trim(($prop?->nombre ?? '').' '.($prop?->apellido ?? ''));
    }

    protected function buildLecturaRows()
    {
        $periodoId = $this->periodoId;

        // Lecturas individuales (medidor_grupo_id IS NULL) de unidades con medidor
        $lecturasIndividuales = LecturaMedidor::where('periodo_id', $periodoId)
            ->whereNull('medidor_grupo_id')
            ->with(['unidad.propietarios'])
            ->get()
            ->filter(fn ($l) => $l->unidad && $l->unidad->tiene_medidor);

        // Lecturas distribuidas (filas por unidad dentro de un grupo)
        $lecturasDistribuidas = LecturaMedidor::where('periodo_id', $periodoId)
            ->whereNotNull('medidor_grupo_id')
            ->with(['unidad.propietarios'])
            ->get()
            ->groupBy('medidor_grupo_id');

        // Lecturas a nivel de grupo (valores reales del medidor compartido)
        $lecturasGrupos = LecturaGrupoPeriodo::where('periodo_id', $periodoId)
            ->with('medidorGrupo')
            ->get()
            ->keyBy('medidor_grupo_id');

        $rows = collect();

        foreach ($lecturasIndividuales as $lectura) {
            $unidad = $lectura->unidad;
            $rows->push([
                'tipo' => 'individual',
                'id' => 'ind_'.$lectura->id,
                'codigo' => $unidad->codigo,
                'nombre_unidad' => $unidad->nombre_completo,
                'tipo_unidad_value' => $unidad->tipo_unidad->value,
                'tipo_unidad_label' => $unidad->tipo_unidad->getLabel(),
                'propietario' => $this->propietarioDe($unidad),
                'lectura_anterior' => (float) ($lectura->lectura_anterior ?? 0),
                'lectura_actual' => (float) ($lectura->lectura_actual ?? 0),
                'consumo_m3' => (float) ($lectura->consumo_m3 ?? 0),
                'fecha' => $lectura->created_at,
                'observaciones' => $lectura->observaciones,
                'sort_key' => $unidad->codigo,
            ]);
        }

        foreach ($lecturasGrupos as $grupoId => $lecturaGrupo) {
            $grupo = $lecturaGrupo->medidorGrupo;
            $distribuidas = $lecturasDistribuidas->get($grupoId, collect());

            $unidadesData = [];
            foreach ($distribuidas as $dist) {
                $unidad = $dist->unidad;
                if (! $unidad) {
                    continue;
                }
                $unidadesData[] = [
                    'codigo' => $unidad->codigo,
                    'nombre_unidad' => $unidad->nombre_completo,
                    'tipo_unidad_value' => $unidad->tipo_unidad->value,
                    'tipo_unidad_label' => $unidad->tipo_unidad->getLabel(),
                    'propietario' => $this->propietarioDe($unidad),
                    'consumo_m3' => (float) ($dist->consumo_m3 ?? 0),
                ];
            }

            usort($unidadesData, fn ($a, $b) => strnatcasecmp($a['codigo'] ?? '', $b['codigo'] ?? ''));

            $rows->push([
                'tipo' => 'grupo',
                'id' => 'grupo_'.$grupoId,
                'grupo_id' => $grupoId,
                'grupo_nombre' => $grupo?->nombre ?? ('Grupo '.$grupoId),
                'unidades' => $unidadesData,
                'tipo_unidad_values' => array_values(array_unique(array_column($unidadesData, 'tipo_unidad_value'))),
                'lectura_anterior' => (float) ($lecturaGrupo->lectura_anterior ?? 0),
                'lectura_actual' => (float) ($lecturaGrupo->lectura_actual ?? 0),
                'consumo_total' => (float) ($lecturaGrupo->consumo_total ?? 0),
                'fecha' => $lecturaGrupo->created_at,
                'observaciones' => $lecturaGrupo->observaciones,
                'sort_key' => $grupo?->nombre ?? ('Grupo '.$grupoId),
            ]);
        }

        // Filtro por tipo de unidad
        if ($this->filtroTipo !== '') {
            $filtro = $this->filtroTipo;
            $rows = $rows->filter(function ($row) use ($filtro) {
                if ($row['tipo'] === 'individual') {
                    return $row['tipo_unidad_value'] === $filtro;
                }

                return in_array($filtro, $row['tipo_unidad_values'] ?? []);
            })->values();
        }

        // Buscador (codigo o propietario, y nombre del grupo)
        $term = trim($this->search);
        if ($term !== '') {
            $t = mb_strtolower($term);
            $rows = $rows->filter(function ($row) use ($t) {
                if ($row['tipo'] === 'individual') {
                    return str_contains(mb_strtolower($row['codigo']), $t)
                        || str_contains(mb_strtolower($row['propietario']), $t)
                        || str_contains(mb_strtolower($row['nombre_unidad']), $t);
                }
                if (str_contains(mb_strtolower($row['grupo_nombre']), $t)) {
                    return true;
                }
                foreach ($row['unidades'] as $u) {
                    if (str_contains(mb_strtolower($u['codigo']), $t)
                        || str_contains(mb_strtolower($u['propietario']), $t)
                        || str_contains(mb_strtolower($u['nombre_unidad']), $t)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        // Orden ascendente natural (LOC-1, LOC-2, LOC-10 ...)
        return $rows->sortBy('sort_key', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    public function render()
    {
        $rows = $this->buildLecturaRows();

        $perPage = 15;
        $page = max(1, (int) $this->getPage('page'));
        $items = $rows->forPage($page, $perPage)->values();

        $lecturas = new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        $tipoOpciones = [
            '' => 'Todos los tipos',
            TipoUnidad::DEPARTAMENTO->value => TipoUnidad::DEPARTAMENTO->getLabel(),
            TipoUnidad::LOCAL->value => TipoUnidad::LOCAL->getLabel(),
            TipoUnidad::OFICINA->value => TipoUnidad::OFICINA->getLabel(),
        ];

        return view('livewire.periodos.periodo-detail', [
            'lecturas' => $lecturas,
            'tipoOpciones' => $tipoOpciones,
        ])->layout('layouts.app');
    }
}
