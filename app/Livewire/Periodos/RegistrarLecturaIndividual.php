<?php

namespace App\Livewire\Periodos;

use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use App\Services\MedidorService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class RegistrarLecturaIndividual extends Component
{
    public $periodoId;

    public $unidadId;

    public $lecturaAnterior = 0;

    public $lecturaActual;

    public $observaciones;

    public $searchUnidad = '';

    protected MedidorService $medidorService;

    public function boot(MedidorService $medidorService)
    {
        $this->medidorService = $medidorService;
    }

    public function mount($periodo)
    {
        $this->periodoId = $periodo;
    }

    public function seleccionarUnidad($unidadId)
    {
        $this->unidadId = $unidadId;
        $this->updatedUnidadId($unidadId);
    }

    public function limpiarUnidad()
    {
        $this->unidadId = null;
        $this->searchUnidad = '';
        $this->lecturaAnterior = 0;
        $this->lecturaActual = null;
        $this->observaciones = null;
    }

    public function updatedUnidadId($value)
    {
        if (!$value) {
            $this->lecturaAnterior = 0;
            return;
        }

        // Obtener lectura anterior
        $lecturaAnterior = $this->medidorService->obtenerLecturaAnterior($value, $this->periodoId);
        $this->lecturaAnterior = $lecturaAnterior?->lectura_actual ?? 0;
        $this->lecturaActual = null;
    }

    public function guardar()
    {
        $this->validate([
            'unidadId' => 'required|exists:unidades,id',
            'lecturaActual' => 'required|numeric|min:0',
        ]);

        try {
            $this->medidorService->registrarLecturaIndividual(
                $this->periodoId,
                $this->unidadId,
                $this->lecturaActual,
                $this->observaciones
            );

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Lectura registrada',
                'mensaje' => 'La lectura individual se registró correctamente',
            ]);

            // Limpiar formulario
            $this->reset(['unidadId', 'lecturaActual', 'lecturaAnterior', 'observaciones']);
        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    #[Computed]
    public function unidades()
    {
        // Obtener IDs de unidades que ya tienen lectura en este período
        $unidadesConLectura = \App\Models\LecturaMedidor::where('periodo_id', $this->periodoId)
            ->whereHas('unidad', fn($q) => $q->where('tipo_medidor', 'individual'))
            ->pluck('unidad_id')
            ->toArray();

        return Unidad::where('activo', true)
            ->where('tiene_medidor', true)
            ->where('tipo_medidor', 'individual') // Solo medidores individuales
            ->whereNotIn('id', $unidadesConLectura) // Excluir unidades con lectura registrada
            ->with(['propietarios'])
            ->get()
            ->map(function ($unidad) {
                $propietario = $unidad->propietarioActual;
                return [
                    'id' => $unidad->id,
                    'nombre' => $unidad->nombre_completo,
                    'codigo' => $unidad->codigo,
                    'propietario' => $propietario?->nombre . ' ' . $propietario?->apellido,
                ];
            });
    }

    #[Computed]
    public function unidadesFiltradas()
    {
        if (empty($this->searchUnidad)) {
            return collect();
        }

        return $this->unidades->filter(function ($unidad) {
            $search = strtolower($this->searchUnidad);
            return str_contains(strtolower($unidad['codigo']), $search) ||
                   str_contains(strtolower($unidad['nombre']), $search) ||
                   str_contains(strtolower($unidad['propietario']), $search);
        })->values();
    }

    public function render()
    {
        $periodo = PeriodoExpensa::findOrFail($this->periodoId);

        return view('livewire.periodos.registrar-lectura-individual', [
            'periodo' => $periodo,
        ])->layout('layouts.app');
    }
}
