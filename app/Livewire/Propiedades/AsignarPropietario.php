<?php

namespace App\Livewire\Propiedades;

use App\Models\Propietario;
use App\Models\Unidad;
use App\Services\UnidadService;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AsignarPropietario extends Component
{

    use AuthorizesRequests;
    // Formulario
    public $propietario_id = '';
    public $unidades_seleccionadas = []; // Array de IDs para selección múltiple
    public $fecha_inicio = '';
    public $motivo = '';
    public $activar_unidad = true;

    // Búsqueda
    public $busqueda_propietario = '';
    public $busqueda_unidad = '';

    // Resultados de búsqueda
    public $propietarios_encontrados = [];
    public $unidades_encontradas = [];

    // Propietario seleccionado
    public $propietario_seleccionado = null;

    protected UnidadService $unidadService;

    public function boot(UnidadService $unidadService)
    {
        $this->unidadService = $unidadService;
        $this->fecha_inicio = now()->format('Y-m-d');

        // Inicializar arrays para evitar problemas de inicialización
        $this->unidades_encontradas = [];
        $this->propietarios_encontrados = [];
    }

    public function render()
    {
        return view('livewire.propiedades.asignar-propietario')
            ->layout('layouts.app');
    }

    /**
     * Buscar propietarios
     */
    public function updatedBusquedaPropietario($valor)
    {
        if (strlen($valor) < 2) {
            $this->propietarios_encontrados = [];
            return;
        }

        $this->propietarios_encontrados = Propietario::activos()
            ->buscar($valor)
            ->limit(10)
            ->get()
            ->map(function ($prop) {
                return [
                    'id' => $prop->id,
                    'nombre_completo' => $prop->nombre_completo,
                    'ci' => $prop->ci,
                    'unidades_actuales' => $prop->unidadesActuales->count(),
                ];
            })
            ->toArray();
    }

    /**
     * Seleccionar propietario
     */
    public function seleccionarPropietario($propietarioId)
    {
        $this->propietario_id = $propietarioId;
        $this->propietario_seleccionado = Propietario::find($propietarioId);
        $this->busqueda_propietario = $this->propietario_seleccionado->nombre_completo;

        // Limpiar los resultados encontrados para cerrar el dropdown
        $this->propietarios_encontrados = [];
    }

    /**
     * Buscar unidades
     */
    public function updatedBusquedaUnidad($valor)
    {
        if (strlen($valor) < 2) {
            $this->unidades_encontradas = [];
            return;
        }

        try {
            $unidades = Unidad::buscar($valor)
                ->limit(10)
                ->get();

            $this->unidades_encontradas = $unidades->map(function ($unidad) {
                $tienePropietario = false;
                $propietarioActualNombre = null;

                try {
                    $tienePropietario = $unidad->tienePropietarioActual();
                    if ($tienePropietario) {
                        $propietario = $unidad->propietarioActual;
                        if ($propietario) {
                            $propietarioActualNombre = $propietario->nombre_completo;
                        }
                    }
                } catch (\Exception $e) {
                    // Si falla la consulta de propietario, continuar sin ella
                }

                return [
                    'id' => $unidad->id,
                    'codigo' => $unidad->codigo,
                    'nombre_completo' => $unidad->nombre_completo,
                    'tipo' => $unidad->tipo_unidad->value,
                    'piso' => $unidad->piso,
                    'm2' => $unidad->metros_cuadrados,
                    'tiene_propietario' => $tienePropietario,
                    'propietario_actual' => $propietarioActualNombre,
                ];
            })->toArray();
        } catch (\Exception $e) {
            $this->unidades_encontradas = [];
        }
    }

    /**
     * Seleccionar unidad (agregar a la lista)
     */
    public function seleccionarUnidad($unidadId)
    {
        // Verificar si ya está seleccionada
        if (!in_array($unidadId, $this->unidades_seleccionadas)) {
            $this->unidades_seleccionadas[] = $unidadId;
        }

        // Limpiar la búsqueda y los resultados para cerrar el dropdown
        $this->busqueda_unidad = '';
        $this->unidades_encontradas = [];
    }

    /**
     * Quitar unidad de la selección
     */
    public function quitarUnidad($unidadId)
    {
        $this->unidades_seleccionadas = array_filter(
            $this->unidades_seleccionadas,
            fn($id) => $id !== $unidadId
        );
        // Reindexar array
        $this->unidades_seleccionadas = array_values($this->unidades_seleccionadas);
    }

    /**
     * Asignar propietario a unidades (puede ser múltiple)
     */
    public function asignar()
    {
        $this->authorize('update', \App\Models\Unidad::class);
        $this->validate([
            'propietario_id' => 'required|exists:propietarios,id',
            'unidades_seleccionadas' => 'required|array|min:1',
            'unidades_seleccionadas.*' => 'exists:unidades,id',
            'fecha_inicio' => 'required|date',
        ], [
            'propietario_id.required' => 'Debe seleccionar un propietario',
            'unidades_seleccionadas.required' => 'Debe seleccionar al menos una unidad',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
        ]);

        try {
            $prop = Propietario::find($this->propietario_id);
            $unidades = Unidad::whereIn('id', $this->unidades_seleccionadas)->get();
            $asignadas = [];
            $yaAsignadas = [];

            foreach ($unidades as $unidad) {
                // Verificar si la unidad ya tiene este propietario
                if ($unidad->propietarioActual()->where('propietarios.id', $prop->id)->exists()) {
                    $yaAsignadas[] = $unidad->codigo;
                    continue;
                }

                // Asignar
                $this->unidadService->asignarPropietario(
                    $unidad,
                    $prop,
                    $this->fecha_inicio,
                    $this->motivo ?: null
                );

                $asignadas[] = $unidad->codigo;
            }

            // Mensaje de resultado
            if (count($asignadas) > 0) {
                $mensaje = sprintf(
                    '%s asignada%s correctamente a %s: %s',
                    $prop->nombre_completo,
                    count($asignadas) > 1 ? 's' : '',
                    count($asignadas) > 1 ? 'las unidades' : 'la unidad',
                    implode(', ', $asignadas)
                );

                if (count($yaAsignadas) > 0) {
                    $mensaje .= sprintf(
                        '. (Ya tenía: %s)',
                        implode(', ', $yaAsignadas)
                    );
                }

                $this->dispatch('toast-success', ['message' => $mensaje]);

                // Guardar mensaje para asignaciones recientes
                session()->flash('asignacion_reciente', sprintf(
                    '%s → %s',
                    $prop->nombre_completo,
                    implode(', ', $asignadas)
                ));
            } else {
                $this->dispatch('toast-error', ['message' => 'Todas las unidades seleccionadas ya tenían este propietario']);
            }

            // Limpiar formulario
            $this->limpiarFormulario();

        } catch (\Exception $e) {
            $this->dispatch('toast-error', ['message' => 'Error al asignar: ' . $e->getMessage()]);
        }
    }

    /**
     * Limpiar formulario
     */
    public function limpiarFormulario()
    {
        $this->reset([
            'propietario_id',
            'unidades_seleccionadas',
            'busqueda_propietario',
            'busqueda_unidad',
            'motivo',
        ]);

        $this->propietario_seleccionado = null;
        $this->propietarios_encontrados = [];
        $this->unidades_encontradas = [];
        $this->fecha_inicio = now()->format('Y-m-d');
    }

    /**
     * Ver información del propietario seleccionado
     */
    public function getInfoPropietarioProperty(): ?array
    {
        if (!$this->propietario_seleccionado) {
            return null;
        }

        return [
            'nombre' => $this->propietario_seleccionado->nombre_completo,
            'ci' => $this->propietario_seleccionado->ci,
            'telefono' => $this->propietario_seleccionado->telefono,
            'email' => $this->propietario_seleccionado->email,
            'unidades_actuales' => $this->propietario_seleccionado->unidadesActuales->count(),
        ];
    }

    /**
     * Obtener información de las unidades seleccionadas
     */
    public function getUnidadesSeleccionadasInfoProperty(): array
    {
        if (empty($this->unidades_seleccionadas)) {
            return [];
        }

        return Unidad::whereIn('id', $this->unidades_seleccionadas)
            ->get()
            ->map(function ($unidad) {
                $propietarioActual = $unidad->propietarioActual;
                return [
                    'id' => $unidad->id,
                    'codigo' => $unidad->codigo,
                    'tipo' => $unidad->tipo_unidad->getLabel(),
                    'piso' => $unidad->piso,
                    'm2' => $unidad->metros_cuadrados,
                    'categoria' => $unidad->categoria_unidad->getLabel(),
                    'tiene_medidor' => $unidad->tiene_medidor,
                    'propietario_actual' => $propietarioActual ? $propietarioActual->nombre_completo : 'Sin asignar',
                ];
            })
            ->toArray();
    }
}
