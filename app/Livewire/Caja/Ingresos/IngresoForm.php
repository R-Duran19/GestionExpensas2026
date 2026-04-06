<?php

namespace App\Livewire\Caja\Ingresos;

use App\Models\Ingreso;
use App\Models\CategoriaIngreso;
use App\Models\Unidad;
use App\Services\IngresoService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;

class IngresoForm extends Component
{
    use WithFileUploads;

    public $ingreso_id = null;
    public $numero_recibo = '';

    #[Validate('required|date')]
    public $fecha = '';

    #[Validate('required|exists:categorias_ingresos,id')]
    public $categoria_ingreso_id = '';

    #[Validate('required|numeric|min:0.01')]
    public $importe = '';

    #[Validate('required|in:efectivo,transferencia,qr,otro')]
    public $metodo_pago = 'efectivo';

    #[Validate('nullable|string|max:255')]
    public $descripcion = '';

    public $comprobante_foto = null;
    public $comprobante_foto_actual = null;

    #[Validate('nullable|string|max:100')]
    public $referencia_bancaria = '';

    public $unidad_id = null;

    #[Validate('nullable|string')]
    public $observaciones = '';

    // Para crear categoría rápida
    public $mostrarModalCategoria = false;
    public $nombre_categoria_nueva = '';
    public $color_categoria_nueva = '#10B981';

    public $categorias = [];
    public $unidades = [];

    protected $messages = [
        'categoria_ingreso_id.required' => 'La categoría es obligatoria',
        'categoria_ingreso_id.exists' => 'La categoría seleccionada no es válida',
        'importe.required' => 'El importe es obligatorio',
        'importe.numeric' => 'El importe debe ser un número',
        'importe.min' => 'El importe debe ser mayor a 0',
        'metodo_pago.required' => 'El método de pago es obligatorio',
        'metodo_pago.in' => 'El método de pago seleccionado no es válido',
        'fecha.required' => 'La fecha es obligatoria',
        'fecha.date' => 'La fecha debe ser válida',
    ];

    public function mount($ingreso = null)
    {
        $this->categorias = CategoriaIngreso::activas()->orderBy('nombre')->get();
        $this->unidades = Unidad::orderBy('codigo')->get();
        $this->fecha = now()->format('Y-m-d');

        if ($ingreso) {
            // Si viene como string (ID), buscar el modelo
            if (is_string($ingreso) || is_numeric($ingreso)) {
                $ingreso = Ingreso::findOrFail($ingreso);
            }

            $this->ingreso_id = $ingreso->id;
            $this->numero_recibo = $ingreso->numero_recibo;
            $this->fecha = $ingreso->fecha->format('Y-m-d');
            $this->categoria_ingreso_id = $ingreso->categoria_ingreso_id;
            $this->importe = $ingreso->importe;
            $this->metodo_pago = $ingreso->metodo_pago;
            $this->descripcion = $ingreso->descripcion;
            $this->comprobante_foto_actual = $ingreso->comprobante_foto;
            $this->referencia_bancaria = $ingreso->referencia_bancaria;
            $this->unidad_id = $ingreso->unidad_id;
            $this->observaciones = $ingreso->observaciones;
        }
    }

    public function updatedMetodoPago($value)
    {
        // Si es efectivo, limpiar referencia bancaria
        if ($value === 'efectivo') {
            $this->referencia_bancaria = '';
            if ($this->comprobante_foto_actual === null && $this->comprobante_foto === null) {
                // No requerir comprobante para efectivo
            }
        }
    }

    public function guardar()
    {
        $this->validate();

        // Validar que se suba comprobante si NO es efectivo
        if ($this->metodo_pago !== 'efectivo' && !$this->comprobante_foto && !$this->comprobante_foto_actual) {
            $this->dispatch('alerta', [
                'tipo' => 'warning',
                'titulo' => 'Atención',
                'mensaje' => 'Para pagos con transferencia o QR es necesario adjuntar el comprobante',
            ]);
            return;
        }

        try {
            $service = app(IngresoService::class);

            $datos = [
                'fecha' => $this->fecha,
                'categoria_ingreso_id' => $this->categoria_ingreso_id,
                'importe' => $this->importe,
                'metodo_pago' => $this->metodo_pago,
                'descripcion' => $this->descripcion,
                'referencia_bancaria' => $this->referencia_bancaria,
                'unidad_id' => $this->unidad_id,
                'observaciones' => $this->observaciones,
            ];

            if ($this->ingreso_id) {
                // Actualizar
                $ingreso = Ingreso::findOrFail($this->ingreso_id);
                $service->actualizar($ingreso, $datos, $this->comprobante_foto);

                $mensaje = 'Ingreso actualizado correctamente';
                $evento = 'ingreso-actualizado';
            } else {
                // Crear
                $service->crear($datos, $this->comprobante_foto);

                $mensaje = 'Ingreso creado correctamente';
                $evento = 'ingreso-creado';

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
            $this->dispatch('redirect-to', route('caja.ingresos.index'));

        } catch (\Exception $e) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Error al guardar el ingreso: ' . $e->getMessage(),
            ]);
        }
    }

    public function limpiarComprobante()
    {
        $this->comprobante_foto = null;
        $this->comprobante_foto_actual = null;
    }

    public function abrirModalCategoria()
    {
        $this->mostrarModalCategoria = true;
    }

    public function cerrarModalCategoria()
    {
        $this->mostrarModalCategoria = false;
        $this->nombre_categoria_nueva = '';
        $this->color_categoria_nueva = '#10B981';
    }

    public function crearCategoriaRapida()
    {
        $this->validate([
            'nombre_categoria_nueva' => 'required|string|max:100',
            'color_categoria_nueva' => 'required|string|max:7',
        ]);

        try {
            $service = app(\App\Services\CategoriaIngresoService::class);
            $categoria = $service->crear([
                'nombre' => $this->nombre_categoria_nueva,
                'color' => $this->color_categoria_nueva,
                'descripcion' => '',
                'icono' => 'dollar-sign',
            ]);

            // Asignar automáticamente al ingreso actual
            $this->categoria_ingreso_id = $categoria->id;

            // Recargar categorías
            $this->categorias = CategoriaIngreso::activas()->orderBy('nombre')->get();

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
        $this->ingreso_id = null;
        $this->numero_recibo = '';
        $this->fecha = now()->format('Y-m-d');
        $this->categoria_ingreso_id = '';
        $this->importe = '';
        $this->metodo_pago = 'efectivo';
        $this->descripcion = '';
        $this->comprobante_foto = null;
        $this->comprobante_foto_actual = null;
        $this->referencia_bancaria = '';
        $this->unidad_id = null;
        $this->observaciones = '';
    }

    public function render()
    {
        return view('livewire.caja.ingresos.ingreso-form');
    }
}
