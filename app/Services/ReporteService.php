<?php

namespace App\Services;

use App\Models\Ingreso;
use App\Models\Egreso;
use App\Models\ConfiguracionEdificio;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ReporteService
{
    protected ConfiguracionEdificio $configuracion;

    public function __construct()
    {
        $config = ConfiguracionEdificio::obtenerConfiguracion() ?? $this->getConfiguracionDefault();
        $this->configuracion = $this->limpiarConfiguracionUtf8($config);
    }

    /**
     * Limpiar caracteres UTF-8 inválidos de la configuración
     */
    protected function limpiarConfiguracionUtf8($configuracion)
    {
        if (is_object($configuracion)) {
            foreach ($configuracion as $key => $value) {
                if (is_string($value)) {
                    $configuracion->$key = $this->limpiarStringUtf8($value);
                }
            }
        }

        return $configuracion;
    }

    /**
     * Generar reporte de ingresos
     *
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param string $formato 'pdf' | 'excel'
     * @return mixed
     */
    public function generarReporteIngresos(string $fechaInicio, string $fechaFin, string $formato = 'pdf')
    {
        try {
            $fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
            $fechaFin = Carbon::parse($fechaFin)->endOfDay();

            // Obtener ingresos del periodo
            $ingresos = Ingreso::activos()
                ->porRangoFechas($fechaInicio, $fechaFin)
                ->with(['categoria', 'propietario', 'creadoPor'])
                ->orderBy('fecha')
                ->orderBy('numero_recibo')
                ->get();

            // Limpiar caracteres UTF-8 inválidos
            $ingresos = $this->limpiarColeccionUtf8($ingresos);

            // Calcular totales
            $totales = $this->calcularTotalesIngresos($ingresos);

            $datos = [
                'configuracion' => $this->configuracion,
                'ingresos' => $ingresos,
                'totales' => $totales,
                'fecha_inicio' => $fechaInicio->format('d/m/Y'),
                'fecha_fin' => $fechaFin->format('d/m/Y'),
                'fecha_generacion' => now()->format('d/m/Y H:i'),
                'titulo' => 'Reporte de Ingresos',
            ];

            if ($formato === 'excel') {
                return $this->generarExcelIngresos($datos);
            } else {
                return $this->generarPDFIngresos($datos);
            }

        } catch (Exception $e) {
            Log::error('Error al generar reporte de ingresos', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'formato' => $formato,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generar reporte de egresos
     *
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param string $formato 'pdf' | 'excel'
     * @return mixed
     */
    public function generarReporteEgresos(string $fechaInicio, string $fechaFin, string $formato = 'pdf')
    {
        try {
            $fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
            $fechaFin = Carbon::parse($fechaFin)->endOfDay();

            // Obtener egresos del periodo
            $egresos = Egreso::activos()
                ->porRangoFechas($fechaInicio, $fechaFin)
                ->with(['categoria', 'creadoPor'])
                ->orderBy('fecha')
                ->orderBy('numero_egreso')
                ->get();

            // Limpiar caracteres UTF-8 inválidos
            $egresos = $this->limpiarColeccionUtf8($egresos);

            // Calcular totales
            $totales = $this->calcularTotalesEgresos($egresos);

            $datos = [
                'configuracion' => $this->configuracion,
                'egresos' => $egresos,
                'totales' => $totales,
                'fecha_inicio' => $fechaInicio->format('d/m/Y'),
                'fecha_fin' => $fechaFin->format('d/m/Y'),
                'fecha_generacion' => now()->format('d/m/Y H:i'),
                'titulo' => 'Reporte de Egresos',
            ];

            if ($formato === 'excel') {
                return $this->generarExcelEgresos($datos);
            } else {
                return $this->generarPDFEgresos($datos);
            }

        } catch (Exception $e) {
            Log::error('Error al generar reporte de egresos', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'formato' => $formato,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generar reporte combinado (ingresos - egresos)
     *
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param string $formato 'pdf' | 'excel'
     * @return mixed
     */
    public function generarReporteCombinado(string $fechaInicio, string $fechaFin, string $formato = 'pdf')
    {
        try {
            $fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
            $fechaFin = Carbon::parse($fechaFin)->endOfDay();

            // Obtener ingresos y egresos
            $ingresos = Ingreso::activos()
                ->porRangoFechas($fechaInicio, $fechaFin)
                ->with(['categoria', 'propietario'])
                ->orderBy('fecha')
                ->get();

            $egresos = Egreso::activos()
                ->porRangoFechas($fechaInicio, $fechaFin)
                ->with(['categoria'])
                ->orderBy('fecha')
                ->get();

            // Limpiar caracteres UTF-8 inválidos
            $ingresos = $this->limpiarColeccionUtf8($ingresos);
            $egresos = $this->limpiarColeccionUtf8($egresos);

            // Calcular totales
            $totalIngresos = $ingresos->sum('importe');
            $totalEgresos = $egresos->sum('importe');
            $balance = $totalIngresos - $totalEgresos;

            $datos = [
                'configuracion' => $this->configuracion,
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'totales' => [
                    'total_ingresos' => $totalIngresos,
                    'total_egresos' => $totalEgresos,
                    'balance' => $balance,
                ],
                'fecha_inicio' => $fechaInicio->format('d/m/Y'),
                'fecha_fin' => $fechaFin->format('d/m/Y'),
                'fecha_generacion' => now()->format('d/m/Y H:i'),
                'titulo' => 'Reporte Financiero Combinado',
            ];

            if ($formato === 'excel') {
                return $this->generarExcelCombinado($datos);
            } else {
                return $this->generarPDFCombinado($datos);
            }

        } catch (Exception $e) {
            Log::error('Error al generar reporte combinado', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'formato' => $formato,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calcular totales de ingresos
     */
    protected function calcularTotalesIngresos($ingresos): array
    {
        return [
            'cantidad' => $ingresos->count(),
            'total_efectivo' => $ingresos->where('metodo_pago', 'efectivo')->sum('importe'),
            'total_transferencia' => $ingresos->where('metodo_pago', 'transferencia')->sum('importe'),
            'total_qr' => $ingresos->where('metodo_pago', 'qr')->sum('importe'),
            'total_otro' => $ingresos->where('metodo_pago', 'otro')->sum('importe'),
            'total_general' => $ingresos->sum('importe'),
        ];
    }

    /**
     * Calcular totales de egresos
     */
    protected function calcularTotalesEgresos($egresos): array
    {
        return [
            'cantidad' => $egresos->count(),
            'total_general' => $egresos->sum('importe'),
            'por_categoria' => $egresos->groupBy('categoria.nombre')->map(function ($items) {
                return $items->sum('importe');
            })->toArray(),
        ];
    }

    /**
     * Generar PDF de ingresos
     */
    protected function generarPDFIngresos(array $datos)
    {
        $pdf = PDF::loadView('reportes.pdf.ingresos', $datos);
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('reporte_ingresos_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Generar PDF de egresos
     */
    protected function generarPDFEgresos(array $datos)
    {
        $pdf = PDF::loadView('reportes.pdf.egresos', $datos);
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('reporte_egresos_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Generar PDF combinado
     */
    protected function generarPDFCombinado(array $datos)
    {
        $pdf = PDF::loadView('reportes.pdf.combinado', $datos);
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('reporte_financiero_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Generar Excel de ingresos
     */
    protected function generarExcelIngresos(array $datos)
    {
        return Excel::download(
            new \App\Exports\IngresosExport($datos),
            'reporte_ingresos_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Generar Excel de egresos
     */
    protected function generarExcelEgresos(array $datos)
    {
        return Excel::download(
            new \App\Exports\EgresosExport($datos),
            'reporte_egresos_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Generar Excel combinado
     */
    protected function generarExcelCombinado(array $datos)
    {
        return Excel::download(
            new \App\Exports\CombinadoExport($datos),
            'reporte_financiero_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Obtener configuración por defecto
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
     * Limpiar caracteres UTF-8 inválidos de una colección
     */
    protected function limpiarColeccionUtf8($coleccion)
    {
        return $coleccion->map(function ($item) {
            return $this->limpiarModeloUtf8($item);
        });
    }

    /**
     * Limpiar caracteres UTF-8 inválidos de un modelo
     */
    protected function limpiarModeloUtf8($modelo)
    {
        foreach ($modelo->getAttributes() as $key => $value) {
            if (is_string($value)) {
                $modelo->{$key} = $this->limpiarStringUtf8($value);
            }
        }

        // Limpiar relaciones cargadas
        foreach ($modelo->getRelations() as $relation => $related) {
            if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
                $modelo->setRelation($relation, $this->limpiarColeccionUtf8($related));
            } elseif ($related instanceof \Illuminate\Database\Eloquent\Model) {
                $modelo->setRelation($relation, $this->limpiarModeloUtf8($related));
            }
        }

        return $modelo;
    }

    /**
     * Limpiar caracteres UTF-8 inválidos de un string
     */
    protected function limpiarStringUtf8($string)
    {
        if (empty($string)) {
            return $string;
        }

        // Convertir a UTF-8 válido
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remover caracteres UTF-8 inválidos
        $string = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $string);

        // Remover caracteres BOM y otros invisibles problemáticos
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        return $string;
    }
}
