<?php

namespace App\Livewire\Caja\Egresos;

use App\Models\Egreso;
use App\Models\CategoriaEgreso;
use App\Services\EgresoService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;

class EgresoForm extends Component
{
    use WithFileUploads;

    public $egreso_id = null;
    public $numero_egreso = '';

    #[Validate('required|date')]
    public $fecha = '';

    #[Validate('required|exists:categorias_egresos,id')]
    public $categoria_egreso_id = '';

    #[Validate('required|string|max:255')]
    public $concepto = '';

    #[Validate('nullable|string|max:100')]
    public $numero_comprobante = '';

    #[Validate('required|numeric|min:0.01')]
    public $importe = '';

    #[Validate('nullable|string')]
    public $descripcion = '';

    #[Validate('required|image|max:10240')] // Max 10MB
    public $comprobante_foto = null;

    public $comprobante_foto_actual = null;

    #[Validate('nullable|string|max:255')]
    public $proveedor = '';

    #[Validate('nullable|string')]
    public $observaciones = '';

    // Para crear categoría rápida
    public $mostrarModalCategoria = false;
    public $nombre_categoria_nueva = '';
    public $color_categoria_nueva = '#EF4444';

    public $categorias = [];

    protected $messages = [
        'categoria_egreso_id.required' => 'La categoría es obligatoria',
        'categoria_egreso_id.exists' => 'La categoría seleccionada no es válida',
        'concepto.required' => 'El concepto es obligatorio',
        'importe.required' => 'El importe es obligatorio',
        'importe.numeric' => 'El importe debe ser un número',
        'importe.min' => 'El importe debe ser mayor a 0',
        'comprobante_foto.required' => 'El comprobante es obligatorio',
        'comprobante_foto.image' => 'El comprobante debe ser una imagen',
        'comprobante_foto.max' => 'El comprobante no puede ser mayor a 10MB',
        'fecha.required' => 'La fecha es obligatoria',
        'fecha.date' => 'La fecha debe ser válida',
    ];

    public function mount($egreso = null)
    {
        $this->categorias = CategoriaEgreso::activas()->orderBy('nombre')->get();
        $this->fecha = now()->format('Y-m-d');

        if ($egreso) {
            // Si viene como string (ID), buscar el modelo
            if (is_string($egreso) || is_numeric($egreso)) {
                $egreso = Egreso::findOrFail($egreso);
            }

            $this->egreso_id = $egreso->id;
            $this->numero_egreso = $egreso->numero_egreso;
            $this->fecha = $egreso->fecha->format('Y-m-d');
            $this->categoria_egreso_id = $egreso->categoria_egreso_id;
            $this->concepto = $egreso->concepto;
            $this->numero_comprobante = $egreso->numero_comprobante;
            $this->importe = $egreso->importe;
            $this->descripcion = $egreso->descripcion;
            $this->comprobante_foto_actual = $egreso->comprobante_foto;
            $this->proveedor = $egreso->proveedor;
            $this->observaciones = $egreso->observaciones;
        }
    }

    public function guardar()
    {
        $this->validate();

        // Validar que se suba comprobante
        if (!$this->comprobante_foto && !$this->comprobante_foto_actual) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Atención',
                'mensaje' => 'Es obligatorio adjuntar el comprobante del egreso',
            ]);
            return;
        }

        try {
            $service = app(EgresoService::class);

            $datos = [
                'fecha' => $this->fecha,
                'categoria_egreso_id' => $this->categoria_egreso_id,
                'concepto' => $this->concepto,
                'numero_comprobante' => $this->numero_comprobante,
                'importe' => $this->importe,
                'descripcion' => $this->descripcion,
                'proveedor' => $this->proveedor,
                'observaciones' => $this->observaciones,
            ];

            if ($this->egreso_id) {
                // Actualizar
                $egreso = Egreso::findOrFail($this->egreso_id);
                $service->actualizar($egreso, $datos, $this->comprobante_foto);

                $mensaje = 'Egreso actualizado correctamente';
                $evento = 'egreso-actualizado';
            } else {
                // Crear
                $service->crear($datos, $this->comprobante_foto);

                $mensaje = 'Egreso creado correctamente';
                $evento = 'egreso-creado';

                // Limpiar formulario
                $this->limpiarFormulario();
            }

            $this->dispatch($evento);
            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => $mensaje,
            ]);

            // Redirigir al listado después de la alerta
            $this->dispatch('redirect-to', route('caja.egresos.index'));

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al guardar el egreso: ' . $e->getMessage(),
            ]);
        }
    }

    public function limpiarComprobante()
    {
        $this->comprobante_foto = null;
        $this->comprobante_foto_actual = null;
    }

    /**
     * Cancelar el formulario: borra el archivo temporal subido (si existe) y vuelve al listado.
     * Evita acumular temporales huérfanos en livewire-tmp cuando se cancela sin guardar.
     */
    public function cancelar()
    {
        if ($this->comprobante_foto) {
            try {
                $this->comprobante_foto->delete();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo borrar el temporal al cancelar egreso', [
                    'error' => $e->getMessage(),
                ]);
            }
            $this->comprobante_foto = null;
        }

        return $this->redirect(route('caja.egresos.index'), navigate: true);
    }

    public function abrirModalCategoria()
    {
        $this->mostrarModalCategoria = true;
    }

    public function cerrarModalCategoria()
    {
        $this->mostrarModalCategoria = false;
        $this->nombre_categoria_nueva = '';
        $this->color_categoria_nueva = '#EF4444';
    }

    public function crearCategoriaRapida()
    {
        $this->validate([
            'nombre_categoria_nueva' => 'required|string|max:100',
            'color_categoria_nueva' => 'required|string|max:7',
        ]);

        try {
            $service = app(\App\Services\CategoriaEgresoService::class);
            $categoria = $service->crear([
                'nombre' => $this->nombre_categoria_nueva,
                'color' => $this->color_categoria_nueva,
                'descripcion' => '',
                'icono' => 'arrow-down-circle',
            ]);

            // Asignar automáticamente al egreso actual
            $this->categoria_egreso_id = $categoria->id;

            // Recargar categorías
            $this->categorias = CategoriaEgreso::activas()->orderBy('nombre')->get();

            // Cerrar modal y limpiar
            $this->cerrarModalCategoria();

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => 'Categoría creada correctamente',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al crear la categoría: ' . $e->getMessage(),
            ]);
        }
    }

    protected function limpiarFormulario()
    {
        $this->egreso_id = null;
        $this->numero_egreso = '';
        $this->fecha = now()->format('Y-m-d');
        $this->categoria_egreso_id = '';
        $this->concepto = '';
        $this->numero_comprobante = '';
        $this->importe = '';
        $this->descripcion = '';
        $this->comprobante_foto = null;
        $this->comprobante_foto_actual = null;
        $this->proveedor = '';
        $this->observaciones = '';
    }

    public function render()
    {
        return view('livewire.caja.egresos.egreso-form')
            ->layout('layouts.app');
    }
}
