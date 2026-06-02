<?php

namespace App\Livewire\Propiedades;

use App\Enums\CategoriaUnidad;
use App\Enums\TipoUnidad;
use App\Models\Propietario;
use App\Models\Unidad;
use App\Services\UnidadService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UnidadEditConPropietario extends Component
{
    #[Locked] public $unidad_id;

    // Datos de la unidad
    public $codigo = '';
    public $tipo_unidad = '';
    public $piso = '';
    public $metros_cuadrados = '';
    public $categoria_unidad = '';
    public $tiene_medidor = true;
    public $observaciones = '';
    public $activo = false;

    // Gestión de propietario
    public $propietario_actual = null;
    public $busqueda_propietario = '';
    public $buscando_propietario = false;
    public $propietarios_encontrados = [];
    public $propietario_seleccionado_id = null;
    public $fecha_inicio_propietario = '';

    // UI state
    public $mostrar_confirmacion_desvincular = false;

    protected UnidadService $unidadService;

    // Reglas de validación
    public $rules = [
        'codigo' => 'required|string|max:20',
        'tipo_unidad' => 'required|in:departamento,oficina,local,parqueo,baulera',
        'piso' => 'required|string|max:50',
        'metros_cuadrados' => 'required|numeric|min:0|max:999999.99',
        'categoria_unidad' => 'required|in:domiciliaria,comercial',
        'tiene_medidor' => 'boolean',
        'observaciones' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'codigo.required' => 'El código es obligatorio',
        'tipo_unidad.required' => 'El tipo de unidad es obligatorio',
        'piso.required' => 'La ubicación (piso) es obligatoria',
        'metros_cuadrados.required' => 'Los metros cuadrados son obligatorios',
        'categoria_unidad.required' => 'La categoría es obligatoria',
    ];

    public function boot(UnidadService $unidadService)
    {
        $this->unidadService = $unidadService;
        $this->fecha_inicio_propietario = now()->format('Y-m-d');
    }

    public function mount($unidad)
    {
        if (is_numeric($unidad) || is_string($unidad)) {
            $unidad = Unidad::findOrFail($unidad);
        }

        $this->unidad_id = $unidad->id;
        $this->cargarDatosUnidad($unidad);
        $this->cargarPropietarioActual($unidad);

        \Log::info('Montando UnidadEditConPropietario para unidad: ' . $unidad->codigo);
        if ($this->propietario_actual) {
            \Log::info('Propietario actual cargado: ' . $this->propietario_actual['nombre_completo']);
        }
    }

    private function cargarDatosUnidad(Unidad $unidad)
    {
        $this->codigo = $unidad->codigo;
        $this->tipo_unidad = $unidad->tipo_unidad->value;
        $this->piso = $unidad->piso;
        $this->metros_cuadrados = $unidad->metros_cuadrados;
        $this->categoria_unidad = $unidad->categoria_unidad->value;
        $this->tiene_medidor = $unidad->tiene_medidor;
        $this->observaciones = $unidad->observaciones ?? '';
        $this->activo = $unidad->activo;
    }

    private function cargarPropietarioActual(Unidad $unidad)
    {
        if ($unidad->tienePropietarioActual()) {
            $propietario = $unidad->propietarioActual;
            $this->propietario_actual = [
                'id' => $propietario->id,
                'nombre_completo' => $propietario->nombre_completo,
                'ci' => $propietario->ci,
                'telefono' => $propietario->telefono,
                'email' => $propietario->email,
                'fecha_inicio' => $propietario->pivot->fecha_inicio,
            ];
        }
    }

    public function updatedTipoUnidad($valor)
    {
        if (!$valor) {
            return;
        }

        $tipo = TipoUnidad::from($valor);
        $categoriaPermitida = $tipo->getCategoriaPermitida();
        $tieneMedidor = $tipo->getTieneMedidor();

        $this->categoria_unidad = $categoriaPermitida->value;
        $this->tiene_medidor = $tieneMedidor;
    }

    public function actualizarUnidad()
    {
        $this->validate();

        try {
            $unidad = Unidad::find($this->unidad_id);

            if (!$unidad) {
                $this->dispatch('toast-error', message: 'Unidad no encontrada');
                return;
            }

            // Verificar compatibilidad tipo-categoría
            $tipo = TipoUnidad::from($this->tipo_unidad);
            $categoria = CategoriaUnidad::from($this->categoria_unidad);
            $categoriaPermitida = $tipo->getCategoriaPermitida();

            if ($categoria !== $categoriaPermitida) {
                $this->dispatch('toast-error', message: sprintf(
                    'El tipo %s no puede ser categoría %s. Debe ser %s.',
                    $tipo->getLabel(),
                    $categoria->getLabel(),
                    $categoriaPermitida->getLabel()
                ));
                return;
            }

            $unidad->update([
                'codigo' => strtoupper($this->codigo),
                'tipo_unidad' => $this->tipo_unidad,
                'piso' => $this->piso,
                'metros_cuadrados' => $this->metros_cuadrados,
                'categoria_unidad' => $this->categoria_unidad,
                'tiene_medidor' => $this->tiene_medidor,
                'observaciones' => $this->observaciones ?: null,
            ]);

            $this->activo = $unidad->activo;

            $this->dispatch('toast-success', message: 'Unidad actualizada correctamente');

        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function updatedBusquedaPropietario($valor)
    {
        // Limpiar espacios en blanco
        $valor = trim($valor);

        if (strlen($valor) < 2) {
            $this->propietarios_encontrados = [];
            $this->buscando_propietario = false;
            return;
        }

        $this->buscando_propietario = true;

        $this->propietarios_encontrados = Propietario::activos()
            ->buscar($valor)
            ->orderBy('nombre')
            ->limit(10)
            ->get()
            ->map(function ($prop) {
                return [
                    'id' => $prop->id,
                    'nombre_completo' => $prop->nombre_completo,
                    'ci' => $prop->ci,
                ];
            })
            ->toArray();

        $this->buscando_propietario = false;
    }

    public function seleccionarPropietario($propietarioId)
    {
        \Log::info('Seleccionar propietario ID: ' . $propietarioId);

        $this->propietario_seleccionado_id = $propietarioId;
        $propietario = Propietario::find($propietarioId);

        if ($propietario) {
            $this->busqueda_propietario = $propietario->nombre_completo;
            $this->propietarios_encontrados = [];
            $this->buscando_propietario = false;

            \Log::info('Propietario seleccionado: ' . $propietario->nombre_completo);
        } else {
            \Log::error('No se encontró propietario con ID: ' . $propietarioId);
        }
    }

    public function vincularPropietario()
    {
        \Log::info('Intentando vincular propietario. ID seleccionado: ' . $this->propietario_seleccionado_id);
        \Log::info('Fecha inicio: ' . $this->fecha_inicio_propietario);

        // Validar manualmente con mensajes claros
        if (!$this->propietario_seleccionado_id) {
            \Log::warning('No hay propietario seleccionado');
            $this->dispatch('toast-error', message: 'Debe seleccionar un propietario de la lista de búsqueda');
            return;
        }

        if (!$this->fecha_inicio_propietario) {
            \Log::warning('No hay fecha de inicio');
            $this->dispatch('toast-error', message: 'La fecha de inicio es obligatoria');
            return;
        }

        try {
            $unidad = Unidad::find($this->unidad_id);
            $propietario = Propietario::find($this->propietario_seleccionado_id);

            \Log::info('Unidad: ' . ($unidad ? $unidad->codigo : 'No encontrada'));
            \Log::info('Propietario: ' . ($propietario ? $propietario->nombre_completo : 'No encontrado'));

            if (!$unidad || !$propietario) {
                $this->dispatch('toast-error', message: 'Unidad o propietario no encontrado');
                return;
            }

            $this->unidadService->asignarPropietario(
                $unidad,
                $propietario,
                $this->fecha_inicio_propietario,
                'Asignación desde edición de unidad'
            );

            $this->activo = $unidad->activo;
            $this->cargarPropietarioActual($unidad);

            $this->limpiarBusquedaPropietario();

            \Log::info('Propietario asignado exitosamente');

            $this->dispatch('toast-success', message: sprintf(
                'Propietario %s asignado correctamente',
                $propietario->nombre_completo
            ));

        } catch (\Exception $e) {
            \Log::error('Error al asignar propietario: ' . $e->getMessage());

            // Verificar si es un error de duplicado
            if (str_contains($e->getMessage(), 'ya está asignado como propietario actual')) {
                $this->dispatch('toast-error', message: $e->getMessage());
            } elseif (str_contains($e->getMessage(), 'ya existe una asignación')) {
                $this->dispatch('toast-error', message: $e->getMessage());
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->dispatch('toast-error', message: 'Este propietario ya está asignado a esta unidad con esa fecha de inicio');
            } else {
                $this->dispatch('toast-error', message: 'Error al asignar propietario: ' . $e->getMessage());
            }
        }
    }

    public function confirmarDesvincularPropietario()
    {
        \Log::info('confirmarDesvincularPropietario llamado');
        $this->mostrar_confirmacion_desvincular = true;
        \Log::info('mostrar_confirmacion_desvincular: ' . ($this->mostrar_confirmacion_desvincular ? 'true' : 'false'));
    }

    public function desvincularPropietario()
    {
        \Log::info('desvincularPropietario llamado');

        try {
            $unidad = Unidad::find($this->unidad_id);

            \Log::info('Unidad: ' . ($unidad ? $unidad->codigo : 'No encontrada'));

            if (!$unidad || !$unidad->tienePropietarioActual()) {
                $this->dispatch('toast-error', message: 'La unidad no tiene propietario actual');
                return;
            }

            $propietario = $unidad->propietarioActual;
            $nombrePropietario = $propietario->nombre_completo;

            \Log::info('Desvinculando propietario: ' . $nombrePropietario);

            // Actualizar tabla pivot
            \DB::table('propietario_unidad')
                ->where('unidad_id', $unidad->id)
                ->where('es_propietario_actual', true)
                ->update([
                    'es_propietario_actual' => false,
                    'fecha_fin' => now(),
                    'updated_at' => now(),
                ]);

            // Desactivar unidad
            $this->unidadService->desactivar($unidad);

            $this->activo = false;
            $this->propietario_actual = null;
            $this->mostrar_confirmacion_desvincular = false;

            \Log::info('Propietario desvinculado exitosamente');

            $this->dispatch('toast-success', message: sprintf(
                'Propietario %s desvinculado. Unidad desactivada.',
                $nombrePropietario
            ));

        } catch (\Exception $e) {
            \Log::error('Error al desvincular: ' . $e->getMessage());
            $this->dispatch('toast-error', message: 'Error al desvincular: ' . $e->getMessage());
        }
    }

    public function cancelarDesvinculacion()
    {
        \Log::info('cancelarDesvinculacion llamado');
        $this->mostrar_confirmacion_desvincular = false;
        \Log::info('Modal cerrado');
    }

    public function limpiarBusquedaPropietario()
    {
        $this->reset(['busqueda_propietario', 'propietario_seleccionado_id', 'fecha_inicio_propietario']);
        $this->fecha_inicio_propietario = now()->format('Y-m-d');
        $this->propietarios_encontrados = [];
        $this->buscando_propietario = false;

        \Log::info('Búsqueda de propietario limpiada');
    }

    public function getTiposUnidadProperty(): array
    {
        return TipoUnidad::toSelectArray();
    }

    public function getCategoriasProperty(): array
    {
        return CategoriaUnidad::toSelectArray();
    }

    public function getTituloProperty(): string
    {
        return sprintf('Editar Unidad: %s', $this->codigo);
    }

    public function render()
    {
        return view('livewire.propiedades.unidad-edit-con-propietario')
            ->layout('layouts.app');
    }
}