<?php

namespace App\Livewire\Periodos;

use App\Models\MedidorGrupo;
use App\Models\PeriodoExpensa;
use App\Services\MedidorService;
use Livewire\Component;

class RegistrarLecturaCompartida extends Component
{
    public $periodoId;

    public $grupoId;

    public $lecturaAnterior = 0;

    public $lecturaActual;

    public $observaciones;

    protected MedidorService $medidorService;

    public function boot(MedidorService $medidorService)
    {
        $this->medidorService = $medidorService;
    }

    public function mount($periodo)
    {
        $this->periodoId = $periodo;
    }

    public function updatedGrupoId($value)
    {
        if (!$value) {
            $this->lecturaAnterior = 0;
            return;
        }

        // Obtener lectura anterior del grupo
        $lecturaAnterior = $this->medidorService->obtenerLecturaAnteriorCompartida($value, $this->periodoId);
        $this->lecturaAnterior = $lecturaAnterior?->lectura_actual ?? 0;
        $this->lecturaActual = null;
    }

    public function guardar()
    {
        $this->validate([
            'grupoId' => 'required|exists:medidor_grupos,id',
            'lecturaActual' => 'required|numeric|min:0',
        ]);

        try {
            $this->medidorService->registrarLecturaCompartida(
                $this->periodoId,
                $this->grupoId,
                $this->lecturaActual,
                $this->observaciones
            );

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Lectura compartida registrada',
                'mensaje' => 'La lectura compartida se registró correctamente para todas las unidades del grupo',
            ]);

            // Limpiar formulario
            $this->reset(['grupoId', 'lecturaActual', 'lecturaAnterior', 'observaciones']);
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    public function getGruposProperty()
    {
        // Obtener IDs de grupos que ya tienen lectura en este período
        $gruposConLectura = \App\Models\LecturaMedidor::where('periodo_id', $this->periodoId)
            ->where('tipo', 'compartido')
            ->pluck('medidor_grupo_id')
            ->toArray();

        return MedidorGrupo::where('activo', true)
            ->whereNotIn('id', $gruposConLectura) // Excluir grupos con lectura registrada
            ->with(['unidades' => function ($q) {
                $q->where('activo', true);
            }])
            ->get()
            ->filter(fn($grupo) => $grupo->unidades->count() > 0)
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->nombre,
                    'unidades_count' => $grupo->unidades->count(),
                    'unidades' => $grupo->unidades->map(fn($u) => $u->nombre_completo)->implode(', '),
                ];
            });
    }

    public function getGrupoSeleccionadoProperty()
    {
        if (!$this->grupoId) {
            return null;
        }

        $grupo = MedidorGrupo::with(['unidades' => function ($q) {
            $q->where('activo', true)->with(['propietarios']);
        }])->find($this->grupoId);

        if (!$grupo) {
            return null;
        }

        return [
            'id' => $grupo->id,
            'nombre' => $grupo->nombre,
            'unidades_count' => $grupo->unidades->count(),
            'unidades' => $grupo->unidades->map(function ($unidad) {
                $propietario = $unidad->propietarioActual;
                return [
                    'nombre' => $unidad->nombre_completo,
                    'codigo' => $unidad->codigo,
                    'propietario' => $propietario?->nombre . ' ' . $propietario?->apellido,
                ];
            }),
        ];
    }

    public function render()
    {
        $periodo = PeriodoExpensa::findOrFail($this->periodoId);

        return view('livewire.periodos.registrar-lectura-compartida', [
            'periodo' => $periodo,
        ])->layout('layouts.app');
    }
}
