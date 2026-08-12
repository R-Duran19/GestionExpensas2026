<?php

namespace App\Livewire\Configuracion;

use App\Services\FactorService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class ConfiguracionFactores extends Component
{
    public $showModal = false;
    public $categoriaSeleccionada = '';
    public $factorActual = 0;
    public $nuevoFactor = '';
    public $motivo = '';

    protected FactorService $factorService;

    public function boot(FactorService $factorService)
    {
        $this->factorService = $factorService;
    }

    public function getFactoresProperty()
    {
        return $this->factorService->obtenerFactoresVigentes();
    }

    public function abrirModalEdicion($categoria)
    {
        try {
            $factores = $this->factores;

            if (!isset($factores[$categoria]) || !$factores[$categoria]) {
                $this->dispatch('alerta', [
                    'tipo' => 'error',
                    'titulo' => 'Error',
                    'mensaje' => "Factor no encontrado para la categoría: {$categoria}",
                ]);
                return;
            }

            $this->categoriaSeleccionada = $categoria;
            $this->factorActual = $factores[$categoria]->factor;
            $this->nuevoFactor = '';
            $this->motivo = '';
            $this->showModal = true;
        } catch (\Exception $e) {
            \Log::error('Error al abrir modal de edición de factor', [
                'categoria' => $categoria,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al abrir el modal: ' . $e->getMessage(),
            ]);
        }
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->reset(['categoriaSeleccionada', 'factorActual', 'nuevoFactor', 'motivo']);
    }

    public function actualizarFactor()
    {
        $this->validate([
            'nuevoFactor' => 'required|numeric|gt:0',
            'motivo' => 'required|string|min:3',
        ]);

        try {
            $this->factorService->actualizarFactor(
                $this->categoriaSeleccionada,
                (float) $this->nuevoFactor,
                $this->motivo
            );

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Factor actualizado',
                'mensaje' => "El factor {$this->categoriaSeleccionada} se actualizó correctamente",
            ]);

            $this->cerrarModal();
        } catch (QueryException $e) {
            // Error de base de datos (duplicados, etc.)
            if ($e->getCode() == 23000) {
                $this->dispatch('alerta', [
                    'tipo' => 'error',
                    'titulo' => 'Factor duplicado',
                    'mensaje' => "Ya existe un factor para esta categoría creado hoy. Espere hasta mañana o actualice el registro existente.",
                ]);
            } else {
                $this->dispatch('alerta', [
                    'tipo' => 'error',
                    'titulo' => 'Error de base de datos',
                    'mensaje' => 'Error al guardar en la base de datos. Código: ' . $e->getCode(),
                ]);
            }
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
        return view('livewire.configuracion.configuracion-factores')
            ->layout('layouts.app');
    }
}
