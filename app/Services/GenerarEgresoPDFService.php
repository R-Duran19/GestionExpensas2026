<?php

namespace App\Services;

use App\Models\Egreso;
use App\Models\ConfiguracionEdificio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerarEgresoPDFService
{
    protected ConfiguracionEdificio $configuracion;

    public function __construct()
    {
        $this->configuracion = ConfiguracionEdificio::obtenerConfiguracion() ?? $this->getConfiguracionDefault();
    }

    /**
     * Generar PDF de un egreso
     *
     * @param Egreso $egreso
     * @param string $tipo 'stream' | 'descarga' | 'guardar'
     * @return mixed
     */
    public function generar(Egreso $egreso, string $tipo = 'stream')
    {
        try {
            // Cargar relaciones necesarias
            $egreso->load(['categoria', 'creadoPor']);

            // Preparar datos para la vista
            $datos = $this->prepararDatos($egreso);

            // Generar PDF
            $pdf = PDF::loadView('recibos.pdf.egreso-ticket', $datos);

            // Configurar PDF
            $pdf->setPaper([0, 0, 226.77, 850.39], 'portrait'); // 80mm ancho x ~300mm alto
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
                'enable_font_subsetting' => true,
            ]);

            // Generar nombre de archivo
            $nombreArchivo = $this->generarNombreArchivo($egreso);

            switch ($tipo) {
                case 'descarga':
                    return $pdf->download($nombreArchivo);

                case 'guardar':
                    return $this->guardarPDF($pdf, $nombreArchivo, $egreso);

                case 'stream':
                default:
                    return $pdf->stream($nombreArchivo);
            }

        } catch (Exception $e) {
            Log::error('Error al generar PDF de egreso', [
                'egreso_id' => $egreso->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Preparar datos para la vista del PDF
     */
    protected function prepararDatos(Egreso $egreso): array
    {
        return [
            'configuracion' => $this->configuracion,
            'egreso' => $egreso,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Generar nombre de archivo para el PDF
     */
    protected function generarNombreArchivo(Egreso $egreso): string
    {
        $fecha = $egreso->fecha->format('Ymd');
        $numero = str_replace(['-', '/'], '_', $egreso->numero_egreso);

        return "EGRESO_{$fecha}_{$numero}.pdf";
    }

    /**
     * Guardar PDF en storage
     */
    protected function guardarPDF($pdf, string $nombreArchivo, Egreso $egreso): string
    {
        $contenido = $pdf->output();

        // Crear directorio si no existe
        $directorio = 'egresos-pdf/' . $egreso->fecha->format('Y/m');
        if (!Storage::exists($directorio)) {
            Storage::makeDirectory($directorio, 0755, true);
        }

        // Guardar archivo
        $ruta = $directorio . '/' . $nombreArchivo;
        Storage::put($ruta, $contenido);

        return $ruta;
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

    /**
     * Generar PDF para múltiples egresos
     *
     * @param array $egresos
     * @return mixed
     */
    public function generarMultiple(array $egresos)
    {
        try {
            $datos = [
                'configuracion' => $this->configuracion,
                'egresos' => $egresos,
                'fecha_generacion' => now()->format('d/m/Y H:i'),
            ];

            $pdf = PDF::loadView('recibos.pdf.egreso-multiple', $datos);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('EGRESOS_MULTIPLES_' . now()->format('YmdHis') . '.pdf');

        } catch (Exception $e) {
            Log::error('Error al generar PDF múltiple', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
