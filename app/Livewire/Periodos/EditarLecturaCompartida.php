<?php

namespace App\Livewire\Periodos;

use App\Models\LecturaGrupoPeriodo;
use App\Models\LecturaMedidor;
use App\Services\MedidorService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EditarLecturaCompartida extends Component
{

    use AuthorizesRequests;
    public $lecturaGrupoId;
    public $periodoId;
    public $grupoId;

    public $lecturaAnterior = 0;
    public $lecturaActual;
    public $observaciones;

    /**
     * Distribución manual (editable): [unidad_id => consumo]
     */
    public $distribucion = [];

    /** Consumos originales guardados en BD (solo lectura informativa) */
    public $consumosOriginales = [];

    /** IDs de las unidades del grupo (estable, serializable) */
    public $unidadIds = [];

    /** Nombre del grupo (informativo) */
    public $grupoNombre;

    /** Control del modal de confirmación */
    public $mostrarConfirmacion = false;

    /** Mensaje de error in-page */
    public $errorMensaje = null;

    protected MedidorService $medidorService;

    public function boot(MedidorService $medidorService)
    {
        $this->medidorService = $medidorService;
    }

    public function mount($lecturaGrupo)
    {
        $lecturaGrupoModel = LecturaGrupoPeriodo::with(['medidorGrupo.unidades' => function ($q) {
            $q->where('activo', true)->with('propietarios');
        }, 'periodo'])
            ->findOrFail($lecturaGrupo);

        $this->lecturaGrupoId = $lecturaGrupoModel->id;
        $this->periodoId = $lecturaGrupoModel->periodo_id;
        $this->grupoId = $lecturaGrupoModel->medidor_grupo_id;
        // Lectura anterior del registro; si es NULL (registros viejos), se recalcula
        // con la lectura compartida del período anterior del mismo grupo.
        $this->lecturaAnterior = (float) ($lecturaGrupoModel->lectura_anterior
            ?? $this->medidorService->obtenerLecturaAnteriorCompartida($this->grupoId, $this->periodoId)?->lectura_actual
            ?? 0);
        $this->lecturaActual = (float) $lecturaGrupoModel->lectura_actual;
        $this->observaciones = $lecturaGrupoModel->observaciones;
        $this->grupoNombre = $lecturaGrupoModel->medidorGrupo?->nombre;

        // Cargar distribución actual e IDs de unidades
        $lecturasActuales = LecturaMedidor::where('periodo_id', $this->periodoId)
            ->where('medidor_grupo_id', $this->grupoId)
            ->get()
            ->keyBy('unidad_id');

        $this->unidadIds = [];
        foreach ($lecturaGrupoModel->medidorGrupo->unidades as $unidad) {
            $this->unidadIds[] = $unidad->id;
            $consumo = (float) ($lecturasActuales->get($unidad->id)?->consumo_m3 ?? 0);
            $this->distribucion[$unidad->id] = $consumo;
            $this->consumosOriginales[$unidad->id] = $consumo;
        }
    }

    /**
     * Datos informativos de cada unidad (se cargan desde la BD en cada render,
     * evita problemas de serialización de arrays complejos).
     */
    #[Computed]
    public function grupoUnidadesInfo()
    {
        if (empty($this->unidadIds)) {
            return [];
        }

        return \App\Models\Unidad::whereIn('id', $this->unidadIds)
            ->with('propietarios')
            ->get()
            ->map(function ($unidad) {
                $propietario = $unidad->propietarioActual;
                return [
                    'id' => $unidad->id,
                    'nombre' => $unidad->nombre_completo,
                    'codigo' => $unidad->codigo,
                    'propietario' => $propietario ? trim($propietario->nombre . ' ' . $propietario->apellido) : null,
                ];
            })
            ->keyBy('id')
            ->toArray();
    }

    /**
     * Repartir el consumo total equitativamente entre las unidades (server-side).
     * Se llama cuando el usuario confirma desde el botón "Repartir equitativamente".
     */
    public function repartirEquitativo()
    {
        if ($this->lecturaActual === null || $this->lecturaActual === '' || !is_numeric($this->lecturaActual)) {
            return;
        }

        $lecturaNum = (float) $this->lecturaActual;
        if ($lecturaNum < $this->lecturaAnterior) {
            return;
        }

        $cantidad = count($this->unidadIds);
        if ($cantidad === 0) {
            return;
        }

        $consumoTotal = $lecturaNum - (float) $this->lecturaAnterior;
        $porUnidad = round($consumoTotal / $cantidad, 2);

        $nueva = [];
        foreach ($this->unidadIds as $id) {
            $nueva[$id] = $porUnidad;
        }
        $this->distribucion = $nueva;
    }

    #[Computed]
    public function consumoTotal()
    {
        if ($this->lecturaActual === null || $this->lecturaActual === '' || !is_numeric($this->lecturaActual)) {
            return 0;
        }
        $lecturaNum = (float) $this->lecturaActual;
        if ($lecturaNum < $this->lecturaAnterior) {
            return 0;
        }
        return round($lecturaNum - (float) $this->lecturaAnterior, 2);
    }

    #[Computed]
    public function sumaDistribucion()
    {
        return collect($this->distribucion)
            ->map(fn($v) => is_numeric($v) ? (float) $v : 0)
            ->sum();
    }

    #[Computed]
    public function diferenciaDistribucion()
    {
        return round($this->consumoTotal - $this->sumaDistribucion, 2);
    }

    /**
     * Abrir modal de confirmación.
     */
    public function solicitarConfirmacion()
    {
        $this->validate([
            'lecturaActual' => 'required|numeric|min:0',
        ]);

        $this->mostrarConfirmacion = true;
    }

    /**
     * Cerrar modal.
     */
    public function cerrarConfirmacion()
    {
        $this->mostrarConfirmacion = false;
    }

    /**
     * Confirmar y guardar cambios.
     */
    public function confirmarGuardado()
    {
        $this->authorize('update', \App\Models\LecturaMedidor::class);
        $this->mostrarConfirmacion = false;

        try {
            $this->medidorService->actualizarLecturaCompartida(
                $this->lecturaGrupoId,
                (float) $this->lecturaActual,
                $this->distribucion,
                $this->observaciones
            );

            $this->dispatch('lectura-actualizada', tipo: 'compartido')->to(LecturaMedidorList::class);

            return $this->redirect(route('periodos.lecturas', $this->periodoId), navigate: false);
        } catch (\Exception $e) {
            $this->errorMensaje = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.periodos.editar-lectura-compartida')
            ->layout('layouts.app');
    }
}
