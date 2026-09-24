<?php

namespace App\Livewire\Periodos;

use App\Models\LecturaMedidor;
use App\Services\MedidorService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EditarLecturaIndividual extends Component
{

    use AuthorizesRequests;
    public $lecturaId;
    public $periodoId;

    public $lecturaAnterior = 0;
    public $lecturaActual;
    public $observaciones;

    /** Control del modal de confirmación */
    public $mostrarConfirmacion = false;

    /** Mensaje de error in-page (si ocurre uno al guardar) */
    public $errorMensaje = null;

    /** Datos informativos de la unidad (no editables) */
    public $unidadNombre;
    public $unidadCodigo;
    public $propietario;

    protected MedidorService $medidorService;

    public function boot(MedidorService $medidorService)
    {
        $this->medidorService = $medidorService;
    }

    public function mount($lectura)
    {
        $lecturaModel = LecturaMedidor::with(['unidad.propietarios', 'periodo'])
            ->whereNull('medidor_grupo_id')
            ->findOrFail($lectura);

        if ($lecturaModel->medidor_grupo_id !== null) {
            abort(404, 'Esta lectura no es individual.');
        }

        $this->lecturaId = $lecturaModel->id;
        $this->periodoId = $lecturaModel->periodo_id;
        // Lectura anterior del registro; si es NULL (registros viejos), se recalcula
        // con la lectura del período anterior de la misma unidad.
        $this->lecturaAnterior = (float) ($lecturaModel->lectura_anterior
            ?? $this->medidorService->obtenerLecturaAnterior($lecturaModel->unidad_id, $lecturaModel->periodo_id)?->lectura_actual
            ?? 0);
        $this->lecturaActual = (float) $lecturaModel->lectura_actual;
        $this->observaciones = $lecturaModel->observaciones;

        $unidad = $lecturaModel->unidad;
        $this->unidadNombre = $unidad?->nombre_completo;
        $this->unidadCodigo = $unidad?->codigo;
        $propietario = $unidad?->propietarioActual;
        $this->propietario = $propietario ? trim($propietario->nombre . ' ' . $propietario->apellido) : null;
    }

    /**
     * Calcula el consumo en vivo (lectura actual - anterior).
     */
    #[Computed]
    public function consumoCalculado(): float
    {
        if ($this->lecturaActual === null || $this->lecturaActual === '' || $this->lecturaActual < $this->lecturaAnterior) {
            return 0;
        }
        return round((float) $this->lecturaActual - (float) $this->lecturaAnterior, 2);
    }

    /**
     * Abre el modal de confirmación tras la validación del formulario.
     */
    public function solicitarConfirmacion()
    {
        $this->validate([
            'lecturaActual' => 'required|numeric|min:0',
        ]);

        $this->mostrarConfirmacion = true;
    }

    /**
     * Cierra el modal de confirmación.
     */
    public function cerrarConfirmacion()
    {
        $this->mostrarConfirmacion = false;
    }

    /**
     * Confirma y ejecuta la actualización.
     */
    public function confirmarGuardado()
    {
        $this->authorize('update', \App\Models\LecturaMedidor::class);
        $this->mostrarConfirmacion = false;

        try {
            $this->medidorService->actualizarLecturaIndividual(
                $this->lecturaId,
                (float) $this->lecturaActual,
                $this->observaciones
            );

            // Notificar a la lista para mostrar banner de éxito
            $this->dispatch('lectura-actualizada', tipo: 'individual')->to(LecturaMedidorList::class);

            return $this->redirect(route('periodos.lecturas', $this->periodoId), navigate: false);
        } catch (\Exception $e) {
            $this->errorMensaje = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.periodos.editar-lectura-individual')
            ->layout('layouts.app');
    }
}
