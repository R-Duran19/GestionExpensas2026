<?php

namespace App\Livewire\Configuracion;

use App\Models\Unidad;
use App\Services\MedidorGrupoService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class MedidorGrupoForm extends Component
{
    public $grupoId = null;
    public $nombre = '';
    public $tipo_distribucion = 'equitativa';
    public $activo = '1';
    public $observaciones = '';

    public $unidadesSeleccionadas = [];
    public $unidadesDisponibles = [];

    protected MedidorGrupoService $medidorGrupoService;

    public function boot(MedidorGrupoService $medidorGrupoService)
    {
        $this->medidorGrupoService = $medidorGrupoService;
    }

    public function mount($grupo = null)
    {
        // Si viene $grupo, es edición, convertimos a entero
        if ($grupo) {
            $this->grupoId = (int) $grupo;
        }

        if ($this->grupoId) {
            $grupoModel = \App\Models\MedidorGrupo::with(['unidades'])->find($this->grupoId);

            if (!$grupoModel) {
                session()->flash('error', 'Grupo no encontrado');
                return redirect()->route('configuracion.medidores-grupos.index');
            }

            $this->nombre = $grupoModel->nombre;
            $this->tipo_distribucion = $grupoModel->tipo_distribucion;
            $this->activo = $grupoModel->activo ? '1' : '0';
            $this->observaciones = $grupoModel->observaciones ?? '';
            $this->unidadesSeleccionadas = $grupoModel->unidades->pluck('id')->toArray();
        }

        $this->cargarUnidadesDisponibles();
    }

    public function cargarUnidadesDisponibles()
    {
        $unidades = $this->medidorGrupoService->obtenerUnidadesDisponibles($this->grupoId);

        $this->unidadesDisponibles = $unidades->map(function ($unidad) {
            return [
                'id' => $unidad->id,
                'nombre' => $unidad->nombre_completo,
                'codigo' => $unidad->codigo,
                'seleccionada' => in_array($unidad->id, $this->unidadesSeleccionadas),
            ];
        })->toArray();
    }

    public function toggleUnidad($unidadId)
    {
        $index = array_search($unidadId, $this->unidadesSeleccionadas);

        if ($index !== false) {
            unset($this->unidadesSeleccionadas[$index]);
            $this->unidadesSeleccionadas = array_values($this->unidadesSeleccionadas);
        } else {
            $this->unidadesSeleccionadas[] = $unidadId;
        }

        $this->cargarUnidadesDisponibles();
    }

    public function guardar()
    {
        $this->validate([
            'nombre' => 'required|string|min:3|max:100',
            'tipo_distribucion' => 'required|in:equitativa',
            'observaciones' => 'nullable|string|max:500',
        ]);

        try {
            $data = [
                'nombre' => $this->nombre,
                'tipo_distribucion' => $this->tipo_distribucion,
                'activo' => $this->activo === '1' || $this->activo === true,
                'observaciones' => $this->observaciones ?: null,
            ];

            if ($this->grupoId) {
                $grupo = $this->medidorGrupoService->actualizarGrupo($this->grupoId, $data);

                // Actualizar unidades asignadas
                $this->sincronizarUnidades($grupo->id);
            } else {
                $grupo = $this->medidorGrupoService->crearGrupo($data);

                // Asignar unidades seleccionadas
                foreach ($this->unidadesSeleccionadas as $unidadId) {
                    $this->medidorGrupoService->asignarUnidad($grupo->id, $unidadId);
                }
            }

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => $this->grupoId ? 'Grupo actualizado' : 'Grupo creado',
                'mensaje' => "El grupo '{$this->nombre}' se guardó correctamente",
            ]);

            return redirect()->route('configuracion.medidores-grupos.index');
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    private function sincronizarUnidades($grupoId)
    {
        $grupo = \App\Models\MedidorGrupo::find($grupoId);

        // Obtener unidades actuales
        $unidadesActuales = $grupo->unidades->pluck('id')->toArray();

        // Desasignar unidades que ya no están seleccionadas
        foreach ($unidadesActuales as $unidadId) {
            if (!in_array($unidadId, $this->unidadesSeleccionadas)) {
                $this->medidorGrupoService->desasignarUnidad($grupoId, $unidadId);
            }
        }

        // Asignar nuevas unidades
        foreach ($this->unidadesSeleccionadas as $unidadId) {
            if (!in_array($unidadId, $unidadesActuales)) {
                $this->medidorGrupoService->asignarUnidad($grupoId, $unidadId);
            }
        }
    }

    public function render()
    {
        return view('livewire.configuracion.medidor-grupo-form')
            ->layout('layouts.app');
    }
}
