<?php

namespace App\Livewire\Periodos;

use App\Models\LecturaGrupoPeriodo;
use App\Models\LecturaMedidor;
use App\Models\PeriodoExpensa;
use App\Services\MedidorService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class LecturaMedidorList extends Component
{
    public $periodoId;

    public $filtroTipo = '';

    protected MedidorService $medidorService;

    public function boot(MedidorService $medidorService)
    {
        $this->medidorService = $medidorService;
    }

    public function mount($periodo)
    {
        $this->periodoId = $periodo;
    }

    #[Computed]
    public function periodo()
    {
        return PeriodoExpensa::findOrFail($this->periodoId);
    }

    #[Computed]
    public function progreso()
    {
        return $this->medidorService->obtenerProgresoLecturas($this->periodoId);
    }

    #[Computed]
    public function lecturas()
    {
        $query = LecturaMedidor::where('periodo_id', $this->periodoId)
            ->with(['unidad.propietarios', 'medidorGrupo']);

        if ($this->filtroTipo === 'individual') {
            $query->where('tipo', 'individual');
        } elseif ($this->filtroTipo === 'compartido') {
            $query->where('tipo', 'compartido');
        }

        $lecturas = $query->get();

        // Si el filtro es "compartido" o "todos", agrupar las lecturas compartidas
        if ($this->filtroTipo !== 'individual') {
            $lecturasAgrupadas = [];
            $lecturasIndividuales = [];

            // Obtener lecturas de grupos (tabla nueva) para este período
            $lecturasGrupos = LecturaGrupoPeriodo::where('periodo_id', $this->periodoId)
                ->with('medidorGrupo')
                ->get()
                ->keyBy('medidor_grupo_id');

            foreach ($lecturas as $lectura) {
                if ($lectura->tipo === 'compartido') {
                    $grupoId = $lectura->medidor_grupo_id;

                    if (!isset($lecturasAgrupadas[$grupoId])) {
                        // Obtener datos de la lectura del grupo
                        $lecturaGrupo = $lecturasGrupos->get($grupoId);

                        // Crear entrada agrupada con datos de la tabla lectura_grupos_periodos
                        $lecturasAgrupadas[$grupoId] = [
                            'id' => 'grupo_' . $grupoId,
                            'tipo' => 'compartido',
                            'grupo_id' => $grupoId,
                            'grupo_nombre' => $lectura->medidorGrupo->nombre,
                            'lectura_anterior' => $lecturaGrupo?->lectura_anterior ?? 0,
                            'lectura_actual' => $lecturaGrupo?->lectura_actual ?? 0,
                            'consumo_total' => $lecturaGrupo?->consumo_total ?? 0,
                            'observaciones' => $lecturaGrupo?->observaciones,
                            'unidades' => [],
                        ];
                    }

                    // Agregar unidad a la lista
                    $unidad = $lectura->unidad;
                    $propietario = $unidad->propietarioActual;
                    $lecturasAgrupadas[$grupoId]['unidades'][] = [
                        'id' => $lectura->id,
                        'unidad_id' => $unidad->id,
                        'nombre_unidad' => $unidad->nombre_completo,
                        'codigo' => $unidad->codigo,
                        'propietario' => $propietario?->nombre . ' ' . $propietario?->apellido,
                        'consumo_asignado' => $lectura->consumo_m3,
                    ];
                } else {
                    // Lecturas individuales normales
                    $unidad = $lectura->unidad;
                    $propietario = $unidad->propietarioActual;

                    $lecturasIndividuales[] = [
                        'id' => $lectura->id,
                        'unidad_id' => $unidad->id,
                        'nombre_unidad' => $unidad->nombre_completo,
                        'codigo' => $unidad->codigo,
                        'tipo' => 'individual',
                        'categoria' => $unidad->categoria_unidad->getLabel(),
                        'propietario' => $propietario?->nombre . ' ' . $propietario?->apellido,
                        'lectura_anterior' => $lectura->lectura_anterior,
                        'lectura_actual' => $lectura->lectura_actual,
                        'consumo_m3' => $lectura->consumo_m3,
                        'observaciones' => $lectura->observaciones,
                    ];
                }
            }

            // Combinar: primero las compartidas agrupadas, luego las individuales
            return collect(array_values($lecturasAgrupadas))->concat($lecturasIndividuales);
        }

        // Si el filtro es solo "individual", devolver normales
        return $lecturas->map(function ($lectura) {
            $unidad = $lectura->unidad;
            $propietario = $unidad->propietarioActual;

            return [
                'id' => $lectura->id,
                'unidad_id' => $unidad->id,
                'nombre_unidad' => $unidad->nombre_completo,
                'codigo' => $unidad->codigo,
                'tipo' => 'individual',
                'categoria' => $unidad->categoria_unidad->getLabel(),
                'propietario' => $propietario?->nombre . ' ' . $propietario?->apellido,
                'lectura_anterior' => $lectura->lectura_anterior,
                'lectura_actual' => $lectura->lectura_actual,
                'consumo_m3' => $lectura->consumo_m3,
                'observaciones' => $lectura->observaciones,
            ];
        });
    }

    public function render()
    {
        return view('livewire.periodos.lectura-medidor-list')
            ->layout('layouts.app');
    }
}
