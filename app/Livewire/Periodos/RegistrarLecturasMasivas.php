<?php

namespace App\Livewire\Periodos;

use App\Models\LecturaMedidor;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use App\Services\MedidorService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class RegistrarLecturasMasivas extends Component
{
    use WithPagination;

    public $periodoId;

    public $search = '';

    public $filtroTipo = '';

    public $filtroEstado = ''; // 'con_lectura', 'sin_lectura', ''

    public $lecturas = [];

    protected MedidorService $medidorService;

    protected $paginationTheme = 'tailwind';

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
    public function unidades()
    {
        // Obtener IDs de unidades que ya tienen lectura individual en este período
        $unidadesConLectura = \App\Models\LecturaMedidor::where('periodo_id', $this->periodoId)
            ->whereHas('unidad', fn($q) => $q->where('tipo_medidor', 'individual'))
            ->pluck('unidad_id')
            ->toArray();

        $query = Unidad::where('activo', true)
            ->where('tiene_medidor', true)
            ->where('tipo_medidor', 'individual') // Solo medidores individuales (excluye grupales)
            ->whereNotIn('id', $unidadesConLectura) // Excluir unidades con lectura registrada
            ->with(['propietarios'])
            // Orden: planta mas alta primero (30, 29, 28... 1), sótanos al final.
            // "Planta 28-29" y "Planta 25 Terraza" toman su primer numero.
            ->orderByRaw(
                "CASE WHEN piso LIKE 'Sotano%' OR piso LIKE 'Sótano%'" .
                " THEN 1000 + CAST(REGEXP_SUBSTR(piso, '[0-9]+') AS SIGNED)" .
                " ELSE -CAST(REGEXP_SUBSTR(piso, '[0-9]+') AS SIGNED) END ASC"
            )
            ->orderByRaw(Unidad::ordenPorTipoUnidadRaw('tipo_unidad'))
            ->orderByRaw(Unidad::ordenPorCodigoRaw('codigo', 'ASC'));

        // Filtro de búsqueda - usar el scope optimizado del modelo
        if ($this->search) {
            $termino = trim($this->search);
            if (!empty($termino)) {
                // Dividir por espacios y filtrar palabras vacías
                $palabras = preg_split('/\s+/', $termino, -1, PREG_SPLIT_NO_EMPTY);
                $palabras = array_filter($palabras, fn($p) => !empty(trim($p)));

                // Si es una sola palabra, búsqueda simple
                if (count($palabras) === 1) {
                    $termino = $palabras[0];
                    $query->where(function ($q) use ($termino) {
                        $q->where('codigo', 'like', "%{$termino}%")
                          ->orWhereHas('propietarioActual', function ($propQ) use ($termino) {
                              $propQ->where('nombre', 'like', "%{$termino}%")
                                    ->orWhere('apellido', 'like', "%{$termino}%");
                          });
                    });
                } else {
                    // Si son múltiples palabras, buscar combinaciones
                    $query->where(function ($q) use ($palabras) {
                        foreach ($palabras as $index => $palabra) {
                            $method = $index === 0 ? 'where' : 'orWhere';
                            $q->$method(function ($subQ) use ($palabra) {
                                $subQ->where('codigo', 'like', "%{$palabra}%")
                                      ->orWhereHas('propietarioActual', function ($propQ) use ($palabra) {
                                          $propQ->where('nombre', 'like', "%{$palabra}%")
                                                ->orWhere('apellido', 'like', "%{$palabra}%");
                                      });
                            });
                        }
                    });
                }
            }
        }

        // Filtro por tipo
        if ($this->filtroTipo) {
            $query->where('tipo_unidad', $this->filtroTipo);
        }

        $unidades = $query->paginate(15);

        // Cargar lecturas anteriores
        $unidades->getCollection()->transform(function ($unidad) {
            $propietario = $unidad->propietarioActual;
            $lecturaAnterior = $this->medidorService->obtenerLecturaAnterior($unidad->id, $this->periodoId);

            return [
                'id' => $unidad->id,
                'codigo' => $unidad->codigo,
                'nombre' => $unidad->nombre_completo,
                'tipo_unidad' => $unidad->tipo_unidad->value,
                'propietario' => $propietario?->nombre . ' ' . $propietario?->apellido,
                'lectura_anterior' => $lecturaAnterior?->lectura_actual ?? 0,
                'tiene_lectura' => false, // Ya filtramos las que tienen lectura
            ];
        });

        return $unidades;
    }

    public function actualizarLectura($unidadId, $valor)
    {
        if (!isset($this->lecturas[$unidadId])) {
            $this->lecturas[$unidadId] = [
                'lectura_actual' => null,
                'observaciones' => '',
            ];
        }
        $this->lecturas[$unidadId]['lectura_actual'] = $valor;
    }

    public function actualizarObservacion($unidadId, $valor)
    {
        if (!isset($this->lecturas[$unidadId])) {
            $this->lecturas[$unidadId] = [
                'lectura_actual' => null,
                'observaciones' => '',
            ];
        }
        $this->lecturas[$unidadId]['observaciones'] = $valor;
    }

    public function guardarTodo()
    {
        // Recorre TODAS las lecturas diligenciadas (incluye otras páginas de la
        // paginación) y solo limpia las que se guardaron, para no perder datos.
        $pendientes = array_filter(
            $this->lecturas,
            fn($l) => $l['lectura_actual'] !== null && $l['lectura_actual'] !== ''
        );

        if (empty($pendientes)) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Sin lecturas',
                'mensaje' => 'No hay lecturas diligenciadas para guardar',
            ]);
            return;
        }

        $unidades = Unidad::whereIn('id', array_keys($pendientes))->get()->keyBy('id');

        $guardadas = 0;
        $errors = [];

        foreach ($pendientes as $unidadId => $lectura) {
            $unidad = $unidades->get($unidadId);

            try {
                $this->medidorService->registrarLecturaIndividual(
                    $this->periodoId,
                    $unidadId,
                    $lectura['lectura_actual'],
                    $lectura['observaciones'] ?? null
                );
                $guardadas++;
                unset($this->lecturas[$unidadId]);
            } catch (\Exception $e) {
                $errors[] = ($unidad?->codigo ?? "Unidad {$unidadId}") . ': ' . $e->getMessage();
            }
        }

        if ($guardadas > 0) {
            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => "{$guardadas} lecturas guardadas",
                'mensaje' => count($errors) > 0
                    ? 'Algunas lecturas tuvieron errores: ' . implode(', ', $errors)
                    : 'Las unidades guardadas ya no aparecerán en la lista',
            ]);

            $this->resetPage();
        } elseif (count($errors) > 0) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error al guardar',
                'mensaje' => implode(', ', $errors),
            ]);
        }
    }

    public function guardarFila($unidadId)
    {
        if (!isset($this->lecturas[$unidadId]) ||
            $this->lecturas[$unidadId]['lectura_actual'] === null ||
            $this->lecturas[$unidadId]['lectura_actual'] === '') {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Dato requerido',
                'mensaje' => 'Debe ingresar la lectura actual',
            ]);
            return;
        }

        try {
            $this->medidorService->registrarLecturaIndividual(
                $this->periodoId,
                $unidadId,
                $this->lecturas[$unidadId]['lectura_actual'],
                $this->lecturas[$unidadId]['observaciones'] ?? null
            );

            $unidad = Unidad::find($unidadId);

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Lectura guardada',
                'mensaje' => 'Se guardó correctamente la lectura de ' . ($unidad?->codigo ?? "unidad {$unidadId}")
                    . (($unidad && $unidad->nombre_completo && $unidad->nombre_completo !== $unidad->codigo)
                        ? ' (' . $unidad->nombre_completo . ')' : '')
                    . '. La unidad ya no aparecerá en la lista.',
            ]);

            // Limpiar y recargar
            unset($this->lecturas[$unidadId]);
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.periodos.registrar-lecturas-masivas')
            ->layout('layouts.app');
    }
}
