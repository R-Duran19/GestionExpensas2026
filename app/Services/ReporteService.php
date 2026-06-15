<?php

namespace App\Services;

use App\Models\Expensa;
use App\Models\Unidad;
use App\Models\PeriodoExpensa;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    /**
     * Generar reporte consolidado con las 3 métricas principales
     *
     * @param array $filtros
     * @param bool $agruparPorPeriodo
     * @return array
     */
    public function generarReporteConsolidado(array $filtros, bool $agruparPorPeriodo = true): array
    {
        $resumido = $this->obtenerReporteResumido($filtros, $agruparPorPeriodo);
        $detallado = $this->obtenerReporteDetallado($filtros, $agruparPorPeriodo);
        $totales = $this->calcularTotalesGenerales($resumido, $detallado);

        return [
            'resumido' => $resumido,
            'detallado' => $detallado,
            'totales' => $totales,
        ];
    }

    /**
     * Obtener reporte resumido (agrupado por tipo de unidad y período)
     *
     * @param array $filtros
     * @param bool $porPeriodo
     * @return array
     */
    public function obtenerReporteResumido(array $filtros, bool $porPeriodo = true): array
    {
        $query = DB::table('expensas as e')
            ->join('unidades as u', 'e.unidad_id', '=', 'u.id')
            ->join('periodos_expensa as p', 'e.periodo_id', '=', 'p.id')
            ->select([
                'u.tipo_unidad',
                DB::raw('COUNT(DISTINCT u.id) as cantidad_unidades'),
                DB::raw('SUM(e.monto_expensa + e.monto_agua) as total_generado'),
                DB::raw('SUM(e.monto_pagado) as total_cobrado'),
                DB::raw('SUM(e.monto_pendiente) as total_deuda'),
                DB::raw('AVG(e.monto_expensa + e.monto_agua) as promedio_generado'),
            ]);

        // Agrupación
        if ($porPeriodo) {
            $query->addSelect([
                'p.id as periodo_id',
                'p.mes',
                'p.año',
                DB::raw("CONCAT(p.mes, '-', p.año) as periodo_nombre"),
            ]);
            $query->groupBy('u.tipo_unidad', 'p.id', 'p.mes', 'p.año');
        } else {
            $query->groupBy('u.tipo_unidad');
        }

        // Aplicar filtros
        $this->aplicarFiltros($query, $filtros);

        // Ordenamiento
        if ($porPeriodo) {
            $query->orderByDesc('p.año')->orderByDesc('p.mes')->orderBy('u.tipo_unidad');
        } else {
            $query->orderBy('u.tipo_unidad');
        }

        $results = $query->get();

        // Calcular porcentaje de cobranza y formatear resultados
        return $results->map(function ($row) {
            $generado = (float) $row->total_generado;
            $cobrado = (float) $row->total_cobrado;

            return [
                'tipo_unidad' => $this->formatearTipoUnidad($row->tipo_unidad),
                'periodo_id' => $row->periodo_id ?? null,
                'periodo' => $row->periodo_nombre ?? 'Todos los períodos',
                'mes' => $row->mes ?? null,
                'año' => $row->año ?? null,
                'unidades' => $row->cantidad_unidades,
                'generado' => $generado,
                'cobrado' => $cobrado,
                'deuda' => (float) $row->total_deuda,
                'promedio' => (float) ($row->promedio_generado ?? 0),
                'porcentaje_cobranza' => $this->calcularPorcentajeCobranza($cobrado, $generado),
            ];
        })->toArray();
    }

    /**
     * Obtener reporte detallado (por unidad individual)
     *
     * @param array $filtros
     * @param bool $porPeriodo
     * @return array
     */
    public function obtenerReporteDetallado(array $filtros, bool $porPeriodo = true): array
    {
        $query = DB::table('expensas as e')
            ->join('unidades as u', 'e.unidad_id', '=', 'u.id')
            ->join('periodos_expensa as p', 'e.periodo_id', '=', 'p.id')
            ->leftJoin('propietario_unidad as pu', function ($join) {
                $join->on('u.id', '=', 'pu.unidad_id')->where('pu.es_propietario_actual', true);
            })
            ->leftJoin('propietarios as prop', 'pu.propietario_id', '=', 'prop.id')
            ->select([
                'u.id as unidad_id',
                'u.codigo',
                'u.tipo_unidad',
                'u.piso',
                'prop.nombre as propietario_nombre',
                'prop.apellido as propietario_apellido',
                'p.id as periodo_id',
                'p.mes',
                'p.año',
                DB::raw("CONCAT(p.mes, '-', p.año) as periodo_nombre"),
                DB::raw('(e.monto_expensa + e.monto_agua) as total_generado'),
                'e.monto_pagado as total_cobrado',
                'e.monto_pendiente as total_deuda',
                'e.estado',
            ]);

        // Aplicar filtros
        $this->aplicarFiltros($query, $filtros);

        // Ordenamiento con ordenamiento natural para códigos
        $query->orderByDesc('p.año')
            ->orderByDesc('p.mes')
            ->orderBy('u.tipo_unidad')
            ->orderByRaw("LENGTH(u.codigo), u.codigo");

        $results = $query->get();

        // Calcular porcentaje de cobranza y formatear resultados
        return $results->map(function ($row) {
            $generado = (float) $row->total_generado;
            $cobrado = (float) $row->total_cobrado;

            $propietario = trim(($row->propietario_nombre ?? '') . ' ' . ($row->propietario_apellido ?? ''));
            if (empty($propietario)) {
                $propietario = 'Sin asignar';
            }

            return [
                'unidad_id' => $row->unidad_id,
                'unidad' => $row->codigo,
                'tipo' => $this->formatearTipoUnidad($row->tipo_unidad),
                'piso' => $row->piso ?? 'N/A',
                'propietario' => $propietario,
                'periodo_id' => $row->periodo_id,
                'periodo' => $row->periodo_nombre,
                'mes' => $row->mes,
                'año' => $row->año,
                'generado' => $generado,
                'cobrado' => $cobrado,
                'deuda' => (float) $row->total_deuda,
                'estado' => $this->formatearEstado($row->estado),
                'porcentaje_cobranza' => $this->calcularPorcentajeCobranza($cobrado, $generado),
            ];
        })->toArray();
    }

    /**
     * Calcular totales generales del reporte
     *
     * @param array $resumido
     * @param array $detallado
     * @return array
     */
    protected function calcularTotalesGenerales(array $resumido, array $detallado): array
    {
        $unidadesUnicas = collect($detallado)->pluck('unidad')->unique()->count();

        $totalGenerado = collect($resumido)->sum('generado');
        $totalCobrado = collect($resumido)->sum('cobrado');
        $totalDeuda = collect($resumido)->sum('deuda');
        $porcentajeCobranza = $this->calcularPorcentajeCobranza($totalCobrado, $totalGenerado);

        return [
            'unidades' => $unidadesUnicas,
            'total_generado' => $totalGenerado,
            'total_cobrado' => $totalCobrado,
            'total_deuda' => $totalDeuda,
            'porcentaje_cobranza' => $porcentajeCobranza,
        ];
    }

    /**
     * Aplicar filtros a la consulta
     *
     * @param $query
     * @param array $filtros
     * @return void
     */
    protected function aplicarFiltros($query, array $filtros): void
    {
        // Filtro por tipo de unidad
        if (isset($filtros['tipo_unidad']) && $filtros['tipo_unidad'] !== 'todos') {
            $query->where('u.tipo_unidad', $filtros['tipo_unidad']);
        }

        // Filtro por período
        if (isset($filtros['periodo_id']) && $filtros['periodo_id'] !== 'todos') {
            $query->where('p.id', $filtros['periodo_id']);
        }

        // Filtro por unidad
        if (isset($filtros['unidad_id']) && $filtros['unidad_id'] !== 'todas') {
            $query->where('u.id', $filtros['unidad_id']);
        }
    }

    /**
     * Calcular porcentaje de cobranza
     *
     * @param float $cobrado
     * @param float $generado
     * @return float
     */
    protected function calcularPorcentajeCobranza(float $cobrado, float $generado): float
    {
        if ($generado <= 0) {
            return 0.0;
        }

        return round(($cobrado / $generado) * 100, 2);
    }

    /**
     * Formatear tipo de unidad para mostrar
     *
     * @param string $tipo
     * @return string
     */
    protected function formatearTipoUnidad(string $tipo): string
    {
        return match ($tipo) {
            'departamento' => 'Departamento',
            'oficina' => 'Oficina',
            'local' => 'Local',
            'parqueo' => 'Parqueo',
            'baulera' => 'Baulera',
            default => ucfirst($tipo),
        };
    }

    /**
     * Formatear estado para mostrar
     *
     * @param string $estado
     * @return string
     */
    protected function formatearEstado(string $estado): string
    {
        return match ($estado) {
            'pendiente' => 'Pendiente',
            'parcial' => 'Parcial',
            'pagado' => 'Pagado',
            default => ucfirst($estado),
        };
    }

    /**
     * Exportar a Excel (placeholder para implementación futura)
     *
     * @param array $data
     * @param string $tipoVista
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarExcel(array $data, string $tipoVista)
    {
        // TODO: Implementar exportación a Excel con Laravel Excel
        // Por ahora retornamos un array simple
        return response()->json([
            'data' => $data,
            'tipo' => $tipoVista,
            'mensaje' => 'Exportación a Excel no implementada aún'
        ]);
    }

    /**
     * Exportar a PDF (placeholder para implementación futura)
     *
     * @param array $data
     * @param string $tipoVista
     * @return \Illuminate\Http\Response
     */
    public function exportarPDF(array $data, string $tipoVista)
    {
        // TODO: Implementar exportación a PDF con DomPDF
        // Por ahora retornamos un array simple
        return response()->json([
            'data' => $data,
            'tipo' => $tipoVista,
            'mensaje' => 'Exportación a PDF no implementada aún'
        ]);
    }
}