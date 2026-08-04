<?php

namespace App\Livewire\Periodos;

use App\Models\LecturaGrupoPeriodo;
use App\Models\LecturaMedidor;
use App\Models\PeriodoExpensa;
use App\Services\MedidorService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class LecturaMedidorList extends Component
{
    public $periodoId;

    public $filtroTipo = '';

    /** Término de búsqueda (nombre de unidad, código o propietario) */
    public $search = '';

    /** Banner de éxito tras edición */
    public $bannerExito = null;

    protected MedidorService $medidorService;

    public function boot(MedidorService $medidorService)
    {
        $this->medidorService = $medidorService;
    }

    public function mount($periodo)
    {
        $this->periodoId = $periodo;
    }

    #[On('lectura-actualizada')]
    public function mostrarBannerExito($tipo = null)
    {
        $this->bannerExito = $tipo === 'compartido'
            ? 'Lectura compartida actualizada correctamente.'
            : 'Lectura actualizada correctamente.';
    }

    public function limpiarBusqueda()
    {
        $this->search = '';
    }

    public function limpiarBanner()
    {
        $this->bannerExito = null;
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
            $query->whereNull('medidor_grupo_id');
        } elseif ($this->filtroTipo === 'compartido') {
            $query->whereNotNull('medidor_grupo_id');
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
                if ($lectura->medidor_grupo_id !== null) {
                    $grupoId = $lectura->medidor_grupo_id;

                    if (!isset($lecturasAgrupadas[$grupoId])) {
                        // Obtener datos de la lectura del grupo
                        $lecturaGrupo = $lecturasGrupos->get($grupoId);

                        // Crear entrada agrupada con datos de la tabla lectura_grupos_periodos
                        $lecturasAgrupadas[$grupoId] = [
                            'id' => 'grupo_' . $grupoId,
                            'tipo' => 'compartido',
                            'grupo_id' => $grupoId,
                            'lectura_grupo_id' => $lecturaGrupo?->id,
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
            $resultado = collect(array_values($lecturasAgrupadas))->concat($lecturasIndividuales);
        } else {
            // Si el filtro es solo "individual", devolver normales
            $resultado = $lecturas->map(function ($lectura) {
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

        // Aplicar filtro de búsqueda
        $search = trim($this->search);
        if ($search !== '') {
            $palabras = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            $palabras = array_filter($palabras, fn($p) => !empty(trim($p)));

            if (!empty($palabras)) {
                $resultado = $resultado->filter(function ($item) use ($palabras) {
                    // Construir el "haystack" según el tipo
                    if (($item['tipo'] ?? null) === 'compartido') {
                        $campos = [$item['grupo_nombre'] ?? ''];
                        foreach ($item['unidades'] ?? [] as $u) {
                            $campos[] = $u['nombre_unidad'] ?? '';
                            $campos[] = $u['codigo'] ?? '';
                            $campos[] = $u['propietario'] ?? '';
                        }
                    } else {
                        $campos = [
                            $item['nombre_unidad'] ?? '',
                            $item['codigo'] ?? '',
                            $item['propietario'] ?? '',
                        ];
                    }

                    $haystack = strtolower(implode(' ', $campos));

                    foreach ($palabras as $palabra) {
                        if (!str_contains($haystack, strtolower($palabra))) {
                            return false;
                        }
                    }
                    return true;
                })->values();
            }
        }

        return $resultado;
    }

    public function render()
    {
        return view('livewire.periodos.lectura-medidor-list')
            ->layout('layouts.app');
    }
}
