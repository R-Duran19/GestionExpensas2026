<?php

namespace App\Livewire\Configuracion;

use App\Models\FacturaAguaConfig;
use App\Services\FacturaAguaService;
use Livewire\Component;

class ConfiguracionFacturasAgua extends Component
{
    public $showModal = false;
    public $facturaId = null;
    public $facturaTipo = '';
    public $numeroActual = '';
    public $nuevoNumero = '';
    public $motivo = '';

    protected FacturaAguaService $facturaAguaService;

    public function boot(FacturaAguaService $facturaAguaService)
    {
        $this->facturaAguaService = $facturaAguaService;
    }

    public function getFacturasProperty()
    {
        return $this->facturaAguaService->obtenerFacturasConfiguradas();
    }

    public function abrirModalEdicion($facturaId)
    {
        $factura = FacturaAguaConfig::find($facturaId);

        if (!$factura) {
            $this->dispatch('alerta', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Factura no encontrada',
            ]);
            return;
        }

        $this->facturaId = $facturaId;
        $this->facturaTipo = $factura->tipo_label;
        $this->numeroActual = $factura->numero_factura;
        $this->nuevoNumero = '';
        $this->motivo = '';
        $this->showModal = true;
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->reset(['facturaId', 'facturaTipo', 'numeroActual', 'nuevoNumero', 'motivo']);
    }

    public function actualizarNumeroFactura()
    {
        $this->validate([
            'nuevoNumero' => 'required|string|min:1',
            'motivo' => 'required|string|min:3',
        ]);

        try {
            $this->facturaAguaService->actualizarNumeroFactura(
                $this->facturaId,
                $this->nuevoNumero,
                $this->motivo
            );

            $this->dispatch('alerta', [
                'tipo' => 'success',
                'titulo' => 'Número de factura actualizado',
                'mensaje' => "El número de factura {$this->facturaTipo} se actualizó correctamente",
            ]);

            $this->cerrarModal();
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
        return view('livewire.configuracion.configuracion-facturas-agua');
    }
}
