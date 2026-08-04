<?php

namespace App\Livewire\Inquilinos;

use App\Models\Inquilino;
use App\Models\Unidad;
use App\Services\InquilinoService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AsignarInquilino extends Component
{
    public $inquilino_id = null;
    public $unidad_ids = []; // Array para múltiples unidades
    public $fecha_inicio = '';
    public $monto_alquiler = '';
    public $observaciones = '';

    public $inquilino;
    public $unidadesDisponibles = [];
    public $unidadesYaAsignadas = []; // IDs de unidades ya asignadas

    protected InquilinoService $inquilinoService;

    // Reglas de validación
    protected $rules = [
        'inquilino_id' => 'required|exists:inquilinos,id',
        'unidad_ids' => 'required|array|min:1',
        'unidad_ids.*' => 'exists:unidades,id',
        'fecha_inicio' => 'required|date',
        'monto_alquiler' => 'nullable|numeric|min:0',
        'observaciones' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'inquilino_id.required' => 'El inquilino es obligatorio',
        'inquilino_id.exists' => 'El inquilino no existe',
        'unidad_ids.required' => 'Debe seleccionar al menos una unidad',
        'unidad_ids.min' => 'Debe seleccionar al menos una unidad',
        'unidad_ids.*.exists' => 'Una de las unidades seleccionadas no existe',
        'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
        'fecha_inicio.date' => 'La fecha de inicio no es válida',
        'monto_alquiler.numeric' => 'El monto de alquiler debe ser un número',
        'monto_alquiler.min' => 'El monto de alquiler no puede ser negativo',
    ];

    public function boot(InquilinoService $inquilinoService)
    {
        $this->inquilinoService = $inquilinoService;
    }

    public function mount($inquilino = null)
    {
        if ($inquilino) {
            $this->inquilino = Inquilino::findOrFail($inquilino);
            $this->inquilino_id = $inquilino;
        }

        $this->fecha_inicio = now()->format('Y-m-d');
        $this->cargarUnidadesDisponibles();
    }

    protected function cargarUnidadesDisponibles()
    {
        // IDs de unidades que ya tienen un inquilino actual (cualquier inquilino)
        $unidadesConInquilinoIds = DB::table('inquilino_unidad')
            ->where('es_inquilino_actual', true)
            ->pluck('unidad_id')
            ->toArray();

        // Cargar unidades activas que NO tienen inquilino actual
        $query = Unidad::activos()->whereNotIn('id', $unidadesConInquilinoIds);

        // Si hay un inquilino seleccionado, marcar las que ya tiene asignadas
        // (ya no deberían aparecer como disponibles porque tienen inquilino actual = él mismo)
        if ($this->inquilino_id) {
            $inquilino = Inquilino::find($this->inquilino_id);
            if ($inquilino) {
                $this->unidadesYaAsignadas = $inquilino->unidadesActuales->pluck('id')->toArray();
            }
        }

        $this->unidadesDisponibles = $query->orderBy('codigo')->get();
    }

    /**
     * Cuando cambia el inquilino seleccionado
     */
    public function updatedInquilinoId()
    {
        $this->inquilino = Inquilino::find($this->inquilino_id);
        $this->unidad_ids = []; // Limpiar unidades seleccionadas
        $this->cargarUnidadesDisponibles();
    }

    /**
     * Asignar inquilino a las unidades seleccionadas
     */
    public function asignar()
    {
        $this->validate();

        $inquilino = Inquilino::findOrFail($this->inquilino_id);

        try {
            // Usar el método para asignar múltiples unidades
            $resultado = $this->inquilinoService->asignarMultiplesUnidades(
                $inquilino,
                $this->unidad_ids,
                $this->fecha_inicio,
                $this->monto_alquiler ? floatval($this->monto_alquiler) : null,
                $this->observaciones
            );

            $mensaje = "Unidades asignadas: " . implode(', ', $resultado['asignadas']);
            if (!empty($resultado['errores'])) {
                $mensaje .= ". Errores: " . implode('; ', $resultado['errores']);
            }

            $this->dispatch('toast-success', message: $mensaje);

            // Limpiar campos
            $this->unidad_ids = [];
            $this->monto_alquiler = '';
            $this->observaciones = '';
            $this->fecha_inicio = now()->format('Y-m-d');

            // Recargar unidades disponibles
            $this->cargarUnidadesDisponibles();

        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: $e->getMessage());
        }
    }

    /**
     * Cancelar y volver
     */
    public function cancelar()
    {
        if ($this->inquilino_id) {
            return redirect()->route('inquilinos.show', $this->inquilino_id);
        }
        return redirect()->route('inquilinos.index');
    }

    /**
     * Ir al detalle del inquilino
     */
    public function verInquilino()
    {
        if ($this->inquilino_id) {
            return redirect()->route('inquilinos.show', $this->inquilino_id);
        }
    }

    /**
     * Obtener nombre del inquilino
     */
    public function getNombreInquilinoProperty(): string
    {
        if ($this->inquilino) {
            return $this->inquilino->nombre_completo;
        }

        $inquilino = Inquilino::find($this->inquilino_id);
        return $inquilino ? $inquilino->nombre_completo : '';
    }

    /**
     * Obtener unidades actuales del inquilino
     */
    public function getUnidadesInquilinoProperty()
    {
        if ($this->inquilino_id) {
            $inquilino = Inquilino::find($this->inquilino_id);
            return $inquilino ? $inquilino->unidadesActuales : collect();
        }
        return collect();
    }

    /**
     * Desasignar una unidad del inquilino
     */
    public function desasignarUnidad($unidadId)
    {
        try {
            $unidad = Unidad::findOrFail($unidadId);
            $inquilino = Inquilino::findOrFail($this->inquilino_id);

            $this->inquilinoService->desasignarUnidad($unidad, $inquilino);

            $this->dispatch('toast-success', message: "Unidad {$unidad->codigo} desasignada correctamente");

            // Recargar unidades disponibles y asignadas
            $this->cargarUnidadesDisponibles();

        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.inquilinos.asignar-inquilino')
            ->layout('layouts.app');
    }
}
