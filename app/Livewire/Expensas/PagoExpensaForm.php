<?php

namespace App\Livewire\Expensas;

use App\Models\ExpensaTotal;
use App\Services\PagoExpensaService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Locked;
use Illuminate\Support\Facades\Auth;

class PagoExpensaForm extends Component
{
    use WithFileUploads;

    // ID de expensa_total (no modificable)
    #[Locked]
    public $expensa_total_id;

    // Datos de expensa
    public $expensaTotal;
    public $propietario;
    public $periodo;

    // Campos del formulario
    #[Validate('required|numeric|min:0.01')]
    public $monto_pago = '';

    #[Validate('required|in:efectivo,transferencia,qr,otro')]
    public $metodo_pago = 'efectivo';

    #[Validate('required|date')]
    public $fecha_pago = '';

    #[Validate('nullable|string|max:100')]
    public $referencia_bancaria = '';

    public $comprobante_foto = null;

    #[Validate('nullable|string|max:1000')]
    public $observaciones = '';

    // Estados de UI
    public $procesando = false;
    public $mostrarInformacion = true;

    protected $messages = [
        'monto_pago.required' => 'El monto del pago es obligatorio',
        'monto_pago.numeric' => 'El monto del pago debe ser un número',
        'monto_pago.min' => 'El monto del pago debe ser mayor a 0',
        'metodo_pago.required' => 'El método de pago es obligatorio',
        'metodo_pago.in' => 'El método de pago seleccionado no es válido',
        'fecha_pago.required' => 'La fecha del pago es obligatoria',
        'fecha_pago.date' => 'La fecha debe ser válida',
        'referencia_bancaria.max' => 'La referencia bancaria no puede exceder los 100 caracteres',
        'observaciones.max' => 'Las observaciones no pueden exceder los 1000 caracteres',
    ];

    protected PagoExpensaService $pagoExpensaService;

    public function boot(PagoExpensaService $pagoExpensaService)
    {
        $this->pagoExpensaService = $pagoExpensaService;
    }

    public function mount($expensaTotal)
    {
        // Obtener expensa total con relaciones
        if (is_numeric($expensaTotal) || is_string($expensaTotal)) {
            $this->expensaTotal = ExpensaTotal::with(['periodo', 'propietario'])
                ->findOrFail($expensaTotal);
        } else {
            $this->expensaTotal = $expensaTotal;
        }

        $this->expensa_total_id = $this->expensaTotal->id;
        $this->propietario = $this->expensaTotal->propietario;
        $this->periodo = $this->expensaTotal->periodo;

        // Establecer fecha actual por defecto
        $this->fecha_pago = now()->format('Y-m-d');

        // Inicializar monto_pago con el monto pendiente
        $this->monto_pago = $this->expensaTotal->monto_pendiente > 0
            ? number_format($this->expensaTotal->monto_pendiente, 2, '.', '')
            : '';
    }

    /**
     * Validar método de pago antes de enviar
     */
    public function updatedMetodoPago($value)
    {
        // Si es efectivo, limpiar referencia bancaria
        if ($value === 'efectivo') {
            $this->referencia_bancaria = '';
        }
    }

    /**
     * Validar que el comprobante se suba para métodos que lo requieren
     */
    protected function validateComprobante(): void
    {
        // Solo requerir comprobante si NO es efectivo
        if ($this->metodo_pago !== 'efectivo' && !$this->comprobante_foto) {
            $this->addError('comprobante_foto', 'El comprobante es obligatorio para ' . ucfirst($this->metodo_pago));
        }
    }

    /**
     * Validar que la referencia bancaria se proporcione para métodos que lo requieren
     */
    protected function validateReferencia(): void
    {
        if ($this->metodo_pago === 'transferencia' && empty($this->referencia_bancaria)) {
            $this->addError('referencia_bancaria', 'La referencia bancaria es obligatoria para transferencias');
        }
    }

    /**
     * Guardar el pago
     */
    public function guardarPago()
    {
        // Validaciones adicionales según método de pago
        $this->validateComprobante();
        $this->validateReferencia();

        // Validar formulario
        $this->validate();

        // Verificar si hay errores de validación adicionales
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        try {
            $this->procesando = true;

            // Preparar datos
            $datosPago = [
                'monto_pago' => $this->monto_pago,
                'metodo_pago' => $this->metodo_pago,
                'fecha_pago' => $this->fecha_pago,
                'referencia_bancaria' => $this->referencia_bancaria,
                'observaciones' => $this->observaciones,
            ];

            // Registrar pago usando el servicio
            $ingreso = $this->pagoExpensaService->registrarPago(
                $this->expensa_total_id,
                $datosPago,
                $this->comprobante_foto
            );

            // Dispatch alerta de éxito
            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Pago registrado exitosamente',
                'mensaje' => "Pago {$ingreso->numero_recibo} de {$this->formatoMoneda($this->monto_pago)} Bs registrado correctamente para {$this->propietario->nombre} {$this->propietario->apellido}",
            ]);

            // Redirigir a la lista de expensas
            return redirect()->route('expensas.list')
                ->with('success', 'Pago registrado exitosamente');

        } catch (\Exception $e) {
            $this->procesando = false;

            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error al registrar pago',
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancelar y volver a la lista
     */
    public function cancelar()
    {
        return redirect()->route('expensas.list');
    }

    /**
     * Obtener el monto pendiente formateado
     */
    public function getMontoPendienteProperty(): string
    {
        return number_format($this->expensaTotal->monto_pendiente, 2, '.', '');
    }

    /**
     * Obtener el monto total formateado
     */
    public function getMontoTotalProperty(): string
    {
        return number_format($this->expensaTotal->monto_total, 2, '.', '');
    }

    /**
     * Obtener el monto pagado formateado
     */
    public function getMontoPagadoProperty(): string
    {
        return number_format($this->expensaTotal->monto_pagado, 2, '.', '');
    }

    /**
     * Verificar si el método de pago requiere comprobante
     */
    public function requiereComprobante(): bool
    {
        return $this->metodo_pago !== 'efectivo';
    }

    /**
     * Verificar si el método de pago requiere referencia
     */
    public function requiereReferencia(): bool
    {
        return $this->metodo_pago === 'transferencia';
    }

    /**
     * Formatear monto a moneda
     */
    protected function formatoMoneda($monto): string
    {
        return number_format($monto, 2, ',', '.');
    }

    /**
     * Obtener color según estado
     */
    public function getEstadoColorProperty(): string
    {
        return match($this->expensaTotal->estado) {
            'pendiente' => 'red',
            'parcial' => 'yellow',
            'pagado' => 'green',
            default => 'gray',
        };
    }

    /**
     * Obtener texto del estado
     */
    public function getEstadoTextoProperty(): string
    {
        return match($this->expensaTotal->estado) {
            'pendiente' => 'Pendiente',
            'parcial' => 'Parcial',
            'pagado' => 'Pagado',
            default => 'Desconocido',
        };
    }

    public function render()
    {
        return view('livewire.expensas.pago-expensa-form')
            ->layout('layouts.app');
    }
}
