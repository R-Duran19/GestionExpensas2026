<?php

namespace App\Livewire\Propietarios;

use App\Models\Propietario;
use App\Models\Unidad;
use App\Services\UnidadService;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PropietarioEditConUnidades extends Component
{

    use AuthorizesRequests;
    #[Locked]
    public $propietario_id;

    // Datos del propietario
    public $nombre = '';

    public $apellido = '';

    public $ci = '';

    public $telefono = '';

    public $email = '';

    public $observaciones = '';

    // Gestión de unidades
    public $unidades_actuales = [];

    public $busqueda_unidad = '';

    public $buscando_unidad = false;

    public $unidades_encontradas = [];

    public $unidad_seleccionada_id = null;

    public $fecha_inicio_unidad = '';

    // UI state
    public $mostrar_confirmacion_desvincular = false;

    public $unidad_a_desvincular_id = null;

    protected UnidadService $unidadService;

    // Reglas de validación
    public $rules = [
        'nombre' => 'required|string|max:100',
        'apellido' => 'required|string|max:100',
        'ci' => 'required|string|max:20',
        'telefono' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:100',
        'observaciones' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'nombre.required' => 'El nombre es obligatorio',
        'apellido.required' => 'El apellido es obligatorio',
        'ci.required' => 'El CI es obligatorio',
        'telefono.required' => 'El teléfono es obligatorio',
        'email.email' => 'El email no es válido',
    ];

    public function boot(UnidadService $unidadService)
    {
        $this->unidadService = $unidadService;
        $this->fecha_inicio_unidad = now()->format('Y-m-d');
    }

    public function mount($propietario)
    {
        if (is_numeric($propietario) || is_string($propietario)) {
            $propietario = Propietario::findOrFail($propietario);
        }

        $this->propietario_id = $propietario->id;
        $this->cargarDatosPropietario($propietario);
        $this->cargarUnidadesActuales($propietario);
    }

    private function cargarDatosPropietario(Propietario $propietario)
    {
        $this->nombre = $propietario->nombre;
        $this->apellido = $propietario->apellido;
        $this->ci = $propietario->ci;
        $this->telefono = $propietario->telefono;
        $this->email = $propietario->email ?? '';
        $this->observaciones = $propietario->observaciones ?? '';
    }

    private function cargarUnidadesActuales(Propietario $propietario)
    {
        $unidades = $propietario->unidadesActuales;

        $this->unidades_actuales = collect($unidades)->map(function ($unidad) {
            $fechaInicio = null;
            if ($unidad->pivot->fecha_inicio) {
                $fechaInicio = is_string($unidad->pivot->fecha_inicio)
                    ? \Carbon\Carbon::parse($unidad->pivot->fecha_inicio)->format('d/m/Y')
                    : $unidad->pivot->fecha_inicio->format('d/m/Y');
            }

            return [
                'id' => $unidad->id,
                'codigo' => $unidad->codigo,
                'nombre_completo' => $unidad->nombre_completo,
                'tipo' => $unidad->tipo_unidad->value,
                'tipo_label' => $unidad->tipo_unidad->getLabel(),
                'piso' => $unidad->piso,
                'metros_cuadrados' => $unidad->metros_cuadrados,
                'categoria' => $unidad->categoria_unidad->value,
                'categoria_label' => $unidad->categoria_unidad->getLabel(),
                'activo' => $unidad->activo,
                'fecha_inicio' => $fechaInicio,
            ];
        })->toArray();
    }

    public function actualizarPropietario()
    {
        $this->authorize('update', \App\Models\Propietario::class);
        $this->validate();

        try {
            $propietario = Propietario::find($this->propietario_id);

            if (! $propietario) {
                $this->dispatch('toast-error', message: 'Propietario no encontrado');

                return;
            }

            $propietario->update([
                'nombre' => ucwords(strtolower($this->nombre)),
                'apellido' => ucwords(strtolower($this->apellido)),
                'ci' => strtoupper($this->ci),
                'telefono' => $this->telefono,
                'email' => $this->email ?: null,
                'observaciones' => $this->observaciones ?: null,
            ]);

            $this->dispatch('toast-success', message: 'Propietario actualizado correctamente');

        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: 'Error al actualizar: '.$e->getMessage());
        }
    }

    public function updatedBusquedaUnidad($valor)
    {
        // Limpiar espacios en blanco
        $valor = trim($valor);

        if (strlen($valor) < 2) {
            $this->unidades_encontradas = [];
            $this->buscando_unidad = false;

            return;
        }

        $this->buscando_unidad = true;

        try {
            // Buscar unidades que NO tengan propietario actual
            $unidades = Unidad::buscar($valor)
                ->where('activo', false)
                ->whereDoesntHave('propietarioActual')
                ->orderByRaw(Unidad::ordenPorTipoUnidadRaw('tipo_unidad'))
                ->orderByRaw(Unidad::ordenPorCodigoRaw('codigo', 'ASC'))
                ->limit(10)
                ->get();

            $this->unidades_encontradas = $unidades->map(function ($unidad) {
                return [
                    'id' => $unidad->id,
                    'codigo' => $unidad->codigo,
                    'nombre_completo' => $unidad->nombre_completo,
                    'tipo' => $unidad->tipo_unidad->getLabel(),
                    'piso' => $unidad->piso,
                    'm2' => $unidad->metros_cuadrados,
                ];
            })->toArray();
        } catch (\Exception $e) {
            $this->unidades_encontradas = [];
        }

        $this->buscando_unidad = false;
    }

    public function seleccionarUnidad($unidadId)
    {
        \Log::info('Seleccionar unidad ID: '.$unidadId);

        $this->unidad_seleccionada_id = $unidadId;
        $unidad = Unidad::find($unidadId);

        if ($unidad) {
            $this->busqueda_unidad = $unidad->codigo.' - '.$unidad->nombre_completo;
            $this->unidades_encontradas = [];
            $this->buscando_unidad = false;

            \Log::info('Unidad seleccionada: '.$unidad->codigo);
        } else {
            \Log::error('No se encontró unidad con ID: '.$unidadId);
        }
    }

    public function vincularUnidad()
    {
        \Log::info('Intentando vincular unidad. ID seleccionado: '.$this->unidad_seleccionada_id);
        \Log::info('Fecha inicio: '.$this->fecha_inicio_unidad);

        // Validar manualmente con mensajes claros
        if (! $this->unidad_seleccionada_id) {
            \Log::warning('No hay unidad seleccionada');
            $this->dispatch('toast-error', message: 'Debe seleccionar una unidad de la lista de búsqueda');

            return;
        }

        if (! $this->fecha_inicio_unidad) {
            \Log::warning('No hay fecha de inicio');
            $this->dispatch('toast-error', message: 'La fecha de inicio es obligatoria');

            return;
        }

        try {
            $propietario = Propietario::find($this->propietario_id);
            $unidad = Unidad::find($this->unidad_seleccionada_id);

            \Log::info('Propietario: '.($propietario ? $propietario->nombre_completo : 'No encontrado'));
            \Log::info('Unidad: '.($unidad ? $unidad->codigo : 'No encontrada'));

            if (! $propietario || ! $unidad) {
                $this->dispatch('toast-error', message: 'Propietario o unidad no encontrado');

                return;
            }

            $this->unidadService->asignarPropietario(
                $unidad,
                $propietario,
                $this->fecha_inicio_unidad,
                'Asignación desde edición de propietario'
            );

            $this->cargarUnidadesActuales($propietario);
            $this->limpiarBusquedaUnidad();

            \Log::info('Unidad vinculada exitosamente');

            $this->dispatch('toast-success', message: sprintf(
                'Unidad %s asignada correctamente a %s',
                $unidad->codigo,
                $propietario->nombre_completo
            ));

        } catch (\Exception $e) {
            \Log::error('Error al asignar unidad: '.$e->getMessage());

            // Verificar si es un error de duplicado
            if (str_contains($e->getMessage(), 'ya está asignado como propietario actual')) {
                $this->dispatch('toast-error', message: $e->getMessage());
            } elseif (str_contains($e->getMessage(), 'ya existe una asignación')) {
                $this->dispatch('toast-error', message: $e->getMessage());
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->dispatch('toast-error', message: 'Esta unidad ya está asignada a este propietario con esa fecha de inicio');
            } else {
                $this->dispatch('toast-error', message: 'Error al asignar unidad: '.$e->getMessage());
            }
        }
    }

    public function confirmarDesvincularUnidad($unidadId)
    {
        $this->unidad_a_desvincular_id = $unidadId;
        $this->mostrar_confirmacion_desvincular = true;
    }

    public function desvincularUnidad()
    {
        try {
            $unidad = Unidad::find($this->unidad_a_desvincular_id);
            $propietario = Propietario::find($this->propietario_id);

            if (! $unidad || ! $propietario) {
                $this->dispatch('toast-error', message: 'Unidad o propietario no encontrado');

                return;
            }

            // Verificar que el propietario sea el actual de esta unidad
            if (! $unidad->propietarioActual()->where('propietarios.id', $propietario->id)->exists()) {
                $this->dispatch('toast-error', message: 'Este propietario no es el actual de esta unidad');

                return;
            }

            $codigoUnidad = $unidad->codigo;

            // Actualizar tabla pivot
            \DB::table('propietario_unidad')
                ->where('unidad_id', $unidad->id)
                ->where('propietario_id', $propietario->id)
                ->where('es_propietario_actual', true)
                ->update([
                    'es_propietario_actual' => false,
                    'fecha_fin' => now(),
                    'updated_at' => now(),
                ]);

            // Desactivar unidad
            $this->unidadService->desactivar($unidad);

            // Recargar unidades
            $this->cargarUnidadesActuales($propietario);

            $this->unidad_a_desvincular_id = null;
            $this->mostrar_confirmacion_desvincular = false;

            $this->dispatch('toast-success', message: sprintf(
                'Unidad %s desvinculada correctamente',
                $codigoUnidad
            ));

        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: 'Error al desvincular: '.$e->getMessage());
        }
    }

    public function cancelarDesvinculacion()
    {
        $this->mostrar_confirmacion_desvincular = false;
        $this->unidad_a_desvincular_id = null;
    }

    public function limpiarBusquedaUnidad()
    {
        $this->reset(['busqueda_unidad', 'unidad_seleccionada_id', 'fecha_inicio_unidad']);
        $this->fecha_inicio_unidad = now()->format('Y-m-d');
        $this->unidades_encontradas = [];
        $this->buscando_unidad = false;

        \Log::info('Búsqueda de unidad limpiada');
    }

    public function getTituloProperty(): string
    {
        return sprintf('Editar Propietario: %s %s', $this->nombre, $this->apellido);
    }

    public function getNombreCompletoProperty(): string
    {
        return trim($this->nombre.' '.$this->apellido);
    }

    public function render()
    {
        return view('livewire.propietarios.propietario-edit-con-unidades')
            ->layout('layouts.app');
    }
}
