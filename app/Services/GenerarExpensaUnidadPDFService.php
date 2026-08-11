<?php

namespace App\Services;

use App\Models\ConfiguracionEdificio;
use App\Models\Unidad;
use App\Models\PeriodoExpensa;
use App\Models\Expensa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerarExpensaUnidadPDFService
{
    protected object $configuracion;

    public function __construct()
    {
        $this->configuracion = ConfiguracionEdificio::obtenerConfiguracion() ?? $this->getConfiguracionDefault();
    }

    /**
     * Generar PDF de expensa de una unidad específica
     *
     * @param Unidad $unidad
     * @param PeriodoExpensa $periodo
     * @param string $tipo 'stream' | 'descarga'
     * @return mixed
     */
    public function generar(Unidad $unidad, PeriodoExpensa $periodo, string $tipo = 'stream')
    {
        try {
            // Cargar relaciones necesarias (solo propietarioActual e inquilinoActual son relaciones)
            // tipo_unidad y categoria_unidad son enums, no relaciones
            $unidad->load(['propietarioActual', 'inquilinoActual']);

            // Obtener expensa de esta unidad para el período
            $expensa = Expensa::where('periodo_id', $periodo->id)
                              ->where('unidad_id', $unidad->id)
                              ->first();

            // Obtener lectura de medidor si aplica
            $lectura = null;
            $factorAgua = 0;

            if ($unidad->tiene_medidor) {
                $lectura = $unidad->lecturasMedidor()
                                   ->where('periodo_id', $periodo->id)
                                   ->first();

                $factorAgua = $unidad->categoria_unidad === \App\Enums\CategoriaUnidad::DOMICILIARIA
                    ? $periodo->factor_domiciliario
                    : $periodo->factor_comercial;
            }

            // Calcular totales
            $totales = [
                'subtotal_expensas' => $expensa ? $expensa->monto_expensa : 0,
                'subtotal_agua' => $expensa ? $expensa->monto_agua : 0,
                'deuda_anterior' => $expensa ? $expensa->deuda_anterior : 0,
                'total_a_pagar' => $expensa ? $expensa->monto_total : 0,
            ];

            // Determinar estado
            if ($expensa) {
                $pendiente = $expensa->expensa_pendiente + $expensa->agua_pendiente;
                if ($pendiente <= 0.01 && $totales['total_a_pagar'] > 0) {
                    $estado = 'pagado';
                } elseif ($pendiente < $totales['total_a_pagar']) {
                    $estado = 'parcial';
                } else {
                    $estado = 'pendiente';
                }
            } else {
                $estado = 'sin_calcular';
            }

            // Preparar datos para la vista
            $datos = [
                'configuracion' => $this->configuracion,
                'unidad' => $unidad,
                'periodo' => $periodo,
                'expensa' => $expensa,
                'lectura' => $lectura,
                'factor_agua' => $factorAgua,
                'totales' => $totales,
                'estado' => $estado,
                'fecha_generacion' => now()->format('d/m/Y H:i'),
            ];

            // Generar PDF
            $pdf = PDF::loadView('expensas.pdf.expensa-unidad', $datos);

            // Configurar PDF (Letter portrait: 8.5" x 11" = 216mm x 279mm)
            $pdf->setPaper('letter', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
                'enable_font_subsetting' => true,
            ]);

            // Generar nombre de archivo
            $nombreArchivo = $this->generarNombreArchivo($unidad, $periodo);

            switch ($tipo) {
                case 'descarga':
                    return $pdf->download($nombreArchivo);

                case 'stream':
                default:
                    return $pdf->stream($nombreArchivo);

            }

        } catch (Exception $e) {
            Log::error('Error al generar PDF de expensa de unidad', [
                'unidad_id' => $unidad->id,
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
    protected function generarNombreArchivo(Unidad $unidad, PeriodoExpensa $periodo): string
    {
        $tipoUnidad = strtoupper($unidad->tipo_unidad->getLabel());
        $periodoNombre = str_replace([' ', '/'], '_', $periodo->nombre_periodo);

        return "EXPENSA_{$tipoUnidad}_{$unidad->codigo}_{$periodoNombre}.pdf";
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
