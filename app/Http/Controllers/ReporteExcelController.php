<?php

namespace App\Http\Controllers;

use App\Exports\ReporteGeneralHorizontalExport;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteExcelController extends Controller
{
    protected ReporteService $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    /**
     * Exportar reporte a Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportar(Request $request)
    {
        // Obtener parámetros
        $vista = $request->get('vista', 'resumido'); // 'resumido' o 'detallado'
        $tipoUnidad = $request->get('tipo_unidad', 'todos');
        $periodoId = $request->get('periodo_id', 'todos');
        $unidadId = $request->get('unidad_id', 'todas');
        $agrupacion = $request->get('agrupacion', 'periodo'); // 'periodo' o 'acumulado'
        $filtroAño = $request->get('filtro_año', null);
        $concepto = $request->get('concepto', 'ambos'); // 'ambos', 'expensa' o 'agua'

        // Preparar filtros
        $filtros = [
            'tipo_unidad' => $tipoUnidad,
            'periodo_id' => $periodoId,
            'unidad_id' => $unidadId,
        ];

        $agruparPorPeriodo = $agrupacion === 'periodo';

        // Generar datos del reporte
        $datosReporte = $this->reporteService->generarReporteConsolidado($filtros, $agruparPorPeriodo);

        // Aplicar filtro de concepto (IGUAL que en el frontend):
        // sobrescribe valores y excluye filas sin medidor cuando el concepto es 'agua'.
        $datosOriginales = $this->reporteService->aplicarConcepto($datosReporte[$vista], $concepto);

        // Transformar a formato horizontal (IGUAL que en el frontend)
        $datosHorizontales = $vista === 'resumido'
            ? $this->reporteService->transformarResumidoAHorizontal($datosOriginales, $filtroAño)
            : $this->reporteService->transformarDetalladoAHorizontal($datosOriginales, $filtroAño);

        // Crear exportación horizontal
        $export = new ReporteGeneralHorizontalExport($datosHorizontales, $vista);

        // Generar nombre de archivo
        $nombreArchivo = $this->generarNombreArchivo($vista, $filtros);

        // Descargar archivo usando el facade Excel
        return Excel::download($export, $nombreArchivo);
    }

    /**
     * Generar nombre de archivo para el Excel
     */
    protected function generarNombreArchivo(string $vista, array $filtros): string
    {
        $nombreVista = $vista === 'resumido' ? 'RESUMIDO_HORIZONTAL' : 'DETALLADO_HORIZONTAL';

        $tipoUnidad = $filtros['tipo_unidad'] === 'todos'
            ? 'TODOS'
            : strtoupper($filtros['tipo_unidad']);

        $periodo = $filtros['periodo_id'] === 'todos'
            ? 'TODOS_PERIODOS'
            : 'P' . $filtros['periodo_id'];

        $unidad = $filtros['unidad_id'] === 'todas'
            ? ''
            : '_U' . $filtros['unidad_id'];

        $fecha = now()->format('Y-m-d_H-i');

        return "REPORTE_EXPENSAS_{$nombreVista}_{$tipoUnidad}_{$periodo}{$unidad}_{$fecha}.xlsx";
    }
}