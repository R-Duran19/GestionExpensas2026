<?php

namespace App\Livewire\Periodos;

use App\Models\FacturaAguaConfig;
use App\Models\FacturaAguaPeriodo;
use App\Models\PeriodoExpensa;
use Livewire\Component;
use Livewire\Attributes\Locked;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RegistrarFacturasAgua extends Component
{

    use AuthorizesRequests;
    #[Locked]
    public $periodoId;

    public $facturas = [];

    public $mostrarResumen = false;

    public $factorDomiciliario = 0;
    public $factorComercial = 0;

    public function mount($periodo)
    {
        $this->periodoId = $periodo;

        $periodoModel = PeriodoExpensa::findOrFail($periodo);

        // Si el período ya tiene factores calculados, cargarlos
        if ($periodoModel->tiene_factores_calculados) {
            $this->factorDomiciliario = $periodoModel->factor_domiciliario;
            $this->factorComercial = $periodoModel->factor_comercial;
            $this->mostrarResumen = true;
        }

        // Cargar facturas ya registradas si existen
        $facturasRegistradas = FacturaAguaPeriodo::where('periodo_id', $periodo)
            ->get()
            ->keyBy('factura_agua_config_id');

        // Obtener configuraciones de facturas
        $configs = FacturaAguaConfig::all();

        foreach ($configs as $config) {
            $registrada = $facturasRegistradas->get($config->id);

            $this->facturas[$config->id] = [
                'config_id' => $config->id,
                'tipo' => $config->tipo,
                'categoria' => $config->categoria,
                'numero_factura' => $registrada?->numero_factura ?? $config->numero_factura,
                'importe' => $registrada?->importe ?? null,
                'consumo_m3' => $registrada?->consumo_m3 ?? null,
                'fecha_factura' => $registrada?->fecha_factura?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'observaciones' => $registrada?->observaciones ?? null,
                'registrada' => !is_null($registrada),
            ];
        }
    }

    public function guardarFacturas()
    {
        $this->authorize('create', \App\Models\FacturaAguaPeriodo::class);
        $this->validate([
            'facturas.*.numero_factura' => 'required|string|max:50',
            'facturas.*.importe' => 'required|numeric|min:0',
            'facturas.*.consumo_m3' => 'required|numeric|min:0',
            'facturas.*.fecha_factura' => 'required|date',
        ]);

        try {
            $periodo = PeriodoExpensa::findOrFail($this->periodoId);

            if ($periodo->estado !== 'abierto') {
                throw new \Exception('El período está cerrado. No se pueden registrar facturas.');
            }

            foreach ($this->facturas as $factura) {
                FacturaAguaPeriodo::updateOrCreate(
                    [
                        'periodo_id' => $this->periodoId,
                        'factura_agua_config_id' => $factura['config_id'],
                    ],
                    [
                        'numero_factura' => $factura['numero_factura'],
                        'categoria' => $factura['categoria'],
                        'importe' => $factura['importe'],
                        'consumo_m3' => $factura['consumo_m3'],
                        'fecha_factura' => $factura['fecha_factura'],
                        'observaciones' => $factura['observaciones'],
                        'registrado_por' => auth()->id(),
                    ]
                );
            }

            // Verificar si están las 3 facturas completas para guardar factores
            $facturasRegistradas = FacturaAguaPeriodo::where('periodo_id', $this->periodoId)->count();
            if ($facturasRegistradas === 3) {
                $this->guardarFactoresEnPeriodo();
            }

            // Calcular factores para mostrar
            $this->calcularFactores();

            // Redirigir al detalle del período
            return $this->redirectRoute('periodos.show', $this->periodoId);
        } catch (\Exception $e) {
            $this->dispatch('alerta',
                tipo: 'error',
                titulo: 'Error',
                mensaje: $e->getMessage(),
            );
        }
    }

    public function calcularFactores()
    {
        // Si el período ya tiene factores calculados, usar esos
        $periodo = PeriodoExpensa::findOrFail($this->periodoId);
        if ($periodo->tiene_factores_calculados) {
            $this->factorDomiciliario = $periodo->factor_domiciliario;
            $this->factorComercial = $periodo->factor_comercial;
            $this->mostrarResumen = true;
            return;
        }

        // Si no, calcularlos al vuelo (temporal)
        $domiciliarias = collect($this->facturas)->filter(fn($f) => $f['categoria'] === 'domiciliaria');
        $comercial = collect($this->facturas)->filter(fn($f) => $f['categoria'] === 'comercial')->first();

        $totalImporteDom = $domiciliarias->sum('importe');
        $totalConsumoDom = $domiciliarias->sum('consumo_m3');

        $this->factorDomiciliario = $totalConsumoDom > 0
            ? round($totalImporteDom / $totalConsumoDom, 8)
            : 0.0;

        $this->factorComercial = ($comercial && $comercial['consumo_m3'] > 0)
            ? round($comercial['importe'] / $comercial['consumo_m3'], 8)
            : 0.0;

        $this->mostrarResumen = true;
    }

    private function guardarFactoresEnPeriodo()
    {
        $domiciliarias = collect($this->facturas)->filter(fn($f) => $f['categoria'] === 'domiciliaria');
        $comercial = collect($this->facturas)->filter(fn($f) => $f['categoria'] === 'comercial')->first();

        $totalImporteDom = $domiciliarias->sum('importe');
        $totalConsumoDom = $domiciliarias->sum('consumo_m3');

        $factorDomiciliario = $totalConsumoDom > 0
            ? round($totalImporteDom / $totalConsumoDom, 8)
            : 0.0;

        $factorComercial = ($comercial && $comercial['consumo_m3'] > 0)
            ? round($comercial['importe'] / $comercial['consumo_m3'], 8)
            : 0.0;

        PeriodoExpensa::where('id', $this->periodoId)->update([
            'factor_domiciliario' => $factorDomiciliario,
            'factor_comercial' => $factorComercial,
            'factores_calculados_at' => now(),
            'factores_calculados_por' => auth()->id(),
        ]);
    }

    public function continuar()
    {
        return redirect()->route('periodos.lecturas', $this->periodoId);
    }

    public function render()
    {
        $periodo = PeriodoExpensa::findOrFail($this->periodoId);

        return view('livewire.periodos.registrar-facturas-agua', [
            'periodo' => $periodo,
        ])->layout('layouts.app');
    }
}
