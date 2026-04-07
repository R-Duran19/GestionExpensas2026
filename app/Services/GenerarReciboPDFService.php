<?php

namespace App\Services;

use App\Models\Ingreso;
use App\Models\ConfiguracionEdificio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerarReciboPDFService
{
    protected ConfiguracionEdificio $configuracion;

    public function __construct()
    {
        $this->configuracion = ConfiguracionEdificio::obtenerConfiguracion() ?? $this->getConfiguracionDefault();
    }

    /**
     * Generar PDF de un recibo
     *
     * @param Ingreso $ingreso
     * @param string $tipo 'stream' | 'descarga' | 'guardar'
     * @return mixed
     */
    public function generar(Ingreso $ingreso, string $tipo = 'stream')
    {
        try {
            // Cargar relaciones necesarias
            $ingreso->load(['expensaTotal.periodo', 'propietario', 'categoria', 'creadoPor']);

            // Preparar datos para la vista
            $datos = $this->prepararDatos($ingreso);

            // Generar PDF
            $pdf = PDF::loadView('recibos.pdf.recibo-ticket', $datos);

            // Configurar PDF
            $pdf->setPaper([0, 0, 226.77, 850.39], 'portrait'); // 80mm ancho x ~300mm alto
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
                'enable_font_subsetting' => true,
            ]);

            // Generar nombre de archivo
            $nombreArchivo = $this->generarNombreArchivo($ingreso);

            switch ($tipo) {
                case 'descarga':
                    return $pdf->download($nombreArchivo);

                case 'guardar':
                    return $this->guardarPDF($pdf, $nombreArchivo, $ingreso);

                case 'stream':
                default:
                    return $pdf->stream($nombreArchivo);
            }

        } catch (Exception $e) {
            Log::error('Error al generar PDF de recibo', [
                'ingreso_id' => $ingreso->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Preparar datos para la vista del PDF
     */
    protected function prepararDatos(Ingreso $ingreso): array
    {
        $datos = [
            'configuracion' => $this->configuracion,
            'ingreso' => $ingreso,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'metodos_pago' => [
                'efectivo' => 'Efectivo',
                'transferencia' => 'Transferencia/Depósito',
                'qr' => 'QR',
                'otro' => 'Otro',
            ],
        ];

        // Si es un pago de expensa, agregar datos específicos
        if ($ingreso->expensa_total_id) {
            $datos['propietario'] = $ingreso->propietario;
            $datos['expensa_total'] = $ingreso->expensaTotal;
            $datos['periodo'] = $ingreso->expensaTotal->periodo;
        }

        return $datos;
    }

    /**
     * Generar nombre de archivo para el PDF
     */
    protected function generarNombreArchivo(Ingreso $ingreso): string
    {
        $fecha = $ingreso->fecha->format('Ymd');
        $numero = str_replace(['-', '/'], '_', $ingreso->numero_recibo);

        return "RECIBO_{$fecha}_{$numero}.pdf";
    }

    /**
     * Guardar PDF en storage
     */
    protected function guardarPDF($pdf, string $nombreArchivo, Ingreso $ingreso): string
    {
        $contenido = $pdf->output();

        // Crear directorio si no existe
        $directorio = 'recibos-pdf/' . $ingreso->fecha->format('Y/m');
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
     * Generar PDF para múltiples recibos
     *
     * @param array $ingresos
     * @return mixed
     */
    public function generarMultiple(array $ingresos)
    {
        try {
            $datos = [
                'configuracion' => $this->configuracion,
                'ingresos' => $ingresos,
                'fecha_generacion' => now()->format('d/m/Y H:i'),
            ];

            $pdf = PDF::loadView('recibos.pdf.recibo-multiple', $datos);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('RECIBOS_MULTIPLES_' . now()->format('YmdHis') . '.pdf');

        } catch (Exception $e) {
            Log::error('Error al generar PDF múltiple', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
