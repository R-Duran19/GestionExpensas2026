<?php

namespace App\Services;

use App\Models\ConfiguracionEdificio;
use App\Models\Propietario;
use App\Models\PeriodoExpensa;
use App\Models\Expensa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerarBoletaExpensaPDFService
{
    protected object $configuracion;

    public function __construct()
    {
        $this->configuracion = ConfiguracionEdificio::obtenerConfiguracion() ?? $this->getConfiguracionDefault();
    }

    /**
     * Generar PDF de boleta de expensa
     *
     * @param Propietario $propietario
     * @param PeriodoExpensa $periodo
     * @param string $tipo 'stream' | 'descarga'
     * @return mixed
     */
    public function generar(Propietario $propietario, PeriodoExpensa $periodo, string $tipo = 'stream')
    {
        try {
            // Cargar relaciones necesarias
            $propietario->load(['unidades' => function ($query) use ($periodo) {
                $query->activos()
                      ->with(['expensas' => function ($query) use ($periodo) {
                          $query->where('periodo_id', $periodo->id);
                      }])
                      ->with(['lecturasMedidor' => function ($query) use ($periodo) {
                          $query->where('periodo_id', $periodo->id);
                      }]);
            }]);

            // Obtener expensas del propietario
            $unidadIds = $propietario->unidades->pluck('id');
            $expensas = Expensa::where('periodo_id', $periodo->id)
                              ->whereIn('unidad_id', $unidadIds)
                              ->with(['unidad', 'periodo'])
                              ->get();

            // Calcular totales
            $totales = [
                'subtotal_expensas' => $expensas->sum('monto_expensa'),
                'subtotal_agua' => $expensas->sum('monto_agua'),
                'deuda_anterior' => $expensas->sum('deuda_anterior'),
                'total_a_pagar' => $expensas->sum('monto_total'),
                'total_pagado' => 0,
                'total_pendiente' => $expensas->sum('monto_total'),
                'saldo_a_favor' => 0,
            ];

            // Determinar estado
            if ($totales['total_pendiente'] == 0 && $totales['total_a_pagar'] > 0) {
                $estado = 'pagado';
            } elseif ($totales['total_pagado'] > 0) {
                $estado = 'parcial';
            } elseif ($expensas->isEmpty()) {
                $estado = 'sin_calcular';
            } else {
                $estado = 'pendiente';
            }

            // Preparar datos para la vista
            $datos = [
                'configuracion' => $this->configuracion,
                'propietario' => $propietario,
                'periodo' => $periodo,
                'expensas' => $expensas,
                'totales' => $totales,
                'estado' => $estado,
                'fecha_generacion' => now()->format('d/m/Y H:i'),
            ];

            // Generar PDF
            $pdf = PDF::loadView('expensas.pdf.boleta-expensa', $datos);

            // Configurar PDF (Letter portrait: 8.5" x 11" = 216mm x 279mm)
            $pdf->setPaper('letter', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
                'enable_font_subsetting' => true,
            ]);

            // Generar nombre de archivo
            $nombreArchivo = $this->generarNombreArchivo($propietario, $periodo);

            switch ($tipo) {
                case 'descarga':
                    return $pdf->download($nombreArchivo);

                case 'stream':
                default:
                    return $pdf->stream($nombreArchivo);
            }

        } catch (Exception $e) {
            Log::error('Error al generar PDF de boleta de expensa', [
                'propietario_id' => $propietario->id,
                'periodo_id' => $periodo->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Generar nombre de archivo para el PDF
     */
    protected function generarNombreArchivo(Propietario $propietario, PeriodoExpensa $periodo): string
    {
        $nombre = mb_strtoupper(
            str_replace(
                [' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'],
                ['_', 'A', 'E', 'I', 'O', 'U', 'N'],
                $propietario->nombre . '_' . $propietario->apellido
            )
        );

        $periodoNombre = str_replace([' ', '/'], '_', $periodo->nombre_periodo);

        return "BOLETA_EXPENSA_{$nombre}_{$periodoNombre}.pdf";
    }

    /**
     * Obtener configuración por defecto si no existe en BD
     */
    protected function getConfiguracionDefault(): object
    {
        return (object)[
            'nombre_edificio' => config('app.name', 'Edificio'),
            'direccion' => 'Dirección no configurada',
            'telefono' => '',
            'email' => '',
            'cuit' => '',
            'ciudad' => '',
            'provincia' => '',
            'codigo_postal' => '',
            'logo_url' => null,
            'informacion_adicional' => '',
        ];
    }
}
