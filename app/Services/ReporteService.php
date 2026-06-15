<?php

namespace App\Services;

use App\Models\Expensa;
use App\Models\Unidad;
use App\Models\PeriodoExpensa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
        // Generar clave de cache única basada en filtros y agrupación
        $cacheKey = $this->generarCacheKey($filtros, $agruparPorPeriodo);

        // Registrar la clave para invalidación posterior
        $this->registrarCacheKey($cacheKey);

        return Cache::remember($cacheKey, 300, function() use ($filtros, $agruparPorPeriodo) {
            $resumido = $this->obtenerReporteResumido($filtros, $agruparPorPeriodo);
            $detallado = $this->obtenerReporteDetallado($filtros, $agruparPorPeriodo);
            $totales = $this->calcularTotalesGenerales($resumido, $detallado);

            return [
                'resumido' => $resumido,
                'detallado' => $detallado,
                'totales' => $totales,
            ];
        });
    }

    /**
     * Generar clave de cache única para el reporte
     */
    protected function generarCacheKey(array $filtros, bool $agruparPorPeriodo): string
    {
        ksort($filtros); // Ordenar array para consistencia
        return 'reporte_consolidado:' . md5(json_encode($filtros) . $agruparPorPeriodo);
    }

    /**
     * Invalidar cache de reportes cuando se modifican expensas
     */
    public function invalidarCacheReportes(): void
    {
        // Eliminar todas las claves de cache de reportes
        // Como no podemos usar tags, limpiamos el cache completo de reportes
        Cache::forget('reportes.periodos');
        Cache::forget('reportes.unidades');

        // Invalidar reportes cacheados por prefijo
        $this->invalidarCachePorPrefijo('reporte_consolidado:');
    }

    /**
     * Invalidar cache por prefijo de clave
     */
    protected function invalidarCachePorPrefijo(string $prefijo): void
    {
        // Para database cache, necesitamos usar una estrategia diferente
        // Podríamos mantener un registro de las claves activas
        if (Cache::getStore() instanceof \Illuminate\Cache\DatabaseStore) {
            // Estrategia para database: eliminar por prefijo
            $cacheKey = 'reportes_keys';
            $keys = Cache::get($cacheKey, []);

            foreach ($keys as $key) {
                if (str_starts_with($key, $prefijo)) {
                    Cache::forget($key);
                }
            }
        }
    }

    /**
     * Registrar clave de cache para invalidación posterior
     */
    protected function registrarCacheKey(string $key): void
    {
        $cacheKey = 'reportes_keys';
        $keys = Cache::get($cacheKey, []);
        $keys[] = $key;
        Cache::put($cacheKey, array_unique($keys), 7200); // 2 horas
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
            ->select([
                'u.id as unidad_id',
                'u.codigo',
                'u.tipo_unidad',
                'u.piso',
                'p.id as periodo_id',
                'p.mes',
                'p.año',
                DB::raw("CONCAT(p.mes, '-', p.año) as periodo_nombre"),
                DB::raw('(e.monto_expensa + e.monto_agua) as total_generado'),
                'e.monto_pagado as total_cobrado',
                'e.monto_pendiente as total_deuda',
                'e.estado',
                // Subquery optimizada para propietario
                DB::raw('(SELECT CONCAT(prop.nombre, " ", prop.apellido)
                         FROM propietario_unidad pu
                         JOIN propietarios prop ON pu.propietario_id = prop.id
                         WHERE pu.unidad_id = u.id
                         AND pu.es_propietario_actual = true
                         AND (pu.fecha_fin IS NULL OR pu.fecha_fin > CURDATE())
                         LIMIT 1
                        ) as propietario_nombre')
            ]);

        // Aplicar filtros
        $this->aplicarFiltros($query, $filtros);

        // Ordenamiento con ordenamiento natural para códigos
        $query->orderByDesc('p.año')
            ->orderByDesc('p.mes')
            ->orderBy('u.tipo_unidad')
            ->orderByRaw("SUBSTRING_INDEX(u.codigo, '-', -1) + 0");

        $results = $query->get();

        // Calcular porcentaje de cobranza y formatear resultados
        return $results->map(function ($row) {
            $generado = (float) $row->total_generado;
            $cobrado = (float) $row->total_cobrado;

            $propietario = $row->propietario_nombre ?? 'Sin asignar';

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

    /**
     * Transformar datos resumidos a formato horizontal (períodos como columnas)
     *
     * @param array $datosResumido
     * @param int|null $añoFiltro Año específico para filtrar (opcional)
     * @return array Formato horizontal con períodos como columnas
     */
    public function transformarResumidoAHorizontal(array $datosResumido, ?int $añoFiltro = null): array
    {
        if (empty($datosResumido)) {
            return [];
        }

        // Filtrar por año si se especifica
        if ($añoFiltro !== null) {
            $datosResumido = collect($datosResumido)
                ->filter(function ($row) use ($añoFiltro) {
                    return isset($row['año']) && $row['año'] == $añoFiltro;
                })
                ->toArray();
        }

        // Obtener todos los períodos únicos ordenados cronológicamente
        $periodos = collect($datosResumido)
            ->sortBy(function ($row) {
                return ($row['año'] ?? 0) * 100 + ($row['mes'] ?? 0);
            })
            ->map(function ($row) {
                return [
                    'id' => $row['periodo_id'],
                    'nombre' => $row['periodo'],
                    'mes' => $row['mes'],
                    'año' => $row['año'],
                ];
            })
            ->unique('id')
            ->values()
            ->toArray();

        // Agrupar por tipo de unidad
        $agrupadoPorTipo = collect($datosResumido)
            ->groupBy('tipo_unidad')
            ->map(function ($rows, $tipoUnidad) use ($periodos) {
                // Crear fila base con el tipo de unidad
                $fila = [
                    'tipo_unidad' => $tipoUnidad,
                    'periodos' => [],
                    'totales' => [
                        'generado' => 0,
                        'cobrado' => 0,
                        'deuda' => 0,
                    ]
                ];

                // Llenar datos de cada período
                foreach ($periodos as $periodo) {
                    $datoPeriodo = $rows->firstWhere('periodo_id', $periodo['id']);

                    $fila['periodos'][] = [
                        'periodo_id' => $periodo['id'],
                        'nombre_periodo' => $periodo['nombre'],
                        'generado' => $datoPeriodo['generado'] ?? 0,
                        'cobrado' => $datoPeriodo['cobrado'] ?? 0,
                        'deuda' => $datoPeriodo['deuda'] ?? 0,
                    ];

                    // Sumar a los totales
                    $fila['totales']['generado'] += $datoPeriodo['generado'] ?? 0;
                    $fila['totales']['cobrado'] += $datoPeriodo['cobrado'] ?? 0;
                    $fila['totales']['deuda'] += $datoPeriodo['deuda'] ?? 0;
                }

                return $fila;
            })
            ->values()
            ->toArray();

        return [
            'periodos' => $periodos,
            'filas' => $agrupadoPorTipo,
        ];
    }

    /**
     * Transformar datos detallados a formato horizontal (períodos como columnas)
     *
     * @param array $datosDetallado
     * @param int|null $añoFiltro Año específico para filtrar (opcional)
     * @return array Formato horizontal con períodos como columnas
     */
    public function transformarDetalladoAHorizontal(array $datosDetallado, ?int $añoFiltro = null): array
    {
        if (empty($datosDetallado)) {
            return [];
        }

        // Filtrar por año si se especifica
        if ($añoFiltro !== null) {
            $datosDetallado = collect($datosDetallado)
                ->filter(function ($row) use ($añoFiltro) {
                    return isset($row['año']) && $row['año'] == $añoFiltro;
                })
                ->toArray();
        }

        // Obtener todos los períodos únicos ordenados cronológicamente
        $periodos = collect($datosDetallado)
            ->sortBy(function ($row) {
                return ($row['año'] ?? 0) * 100 + ($row['mes'] ?? 0);
            })
            ->map(function ($row) {
                return [
                    'id' => $row['periodo_id'],
                    'nombre' => $row['periodo'],
                    'mes' => $row['mes'],
                    'año' => $row['año'],
                ];
            })
            ->unique('id')
            ->values()
            ->toArray();

        // Agrupar por unidad
        $agrupadoPorUnidad = collect($datosDetallado)
            ->groupBy('unidad_id')
            ->map(function ($rows, $unidadId) use ($periodos) {
                $primerRow = $rows->first();

                // Crear fila base con datos de la unidad
                $fila = [
                    'unidad_id' => $unidadId,
                    'unidad' => $primerRow['unidad'],
                    'tipo' => $primerRow['tipo'],
                    'propietario' => $primerRow['propietario'],
                    'periodos' => [],
                    'totales' => [
                        'generado' => 0,
                        'cobrado' => 0,
                        'deuda' => 0,
                    ]
                ];

                // Llenar datos de cada período
                foreach ($periodos as $periodo) {
                    $datoPeriodo = $rows->firstWhere('periodo_id', $periodo['id']);

                    $fila['periodos'][] = [
                        'periodo_id' => $periodo['id'],
                        'nombre_periodo' => $periodo['nombre'],
                        'generado' => $datoPeriodo['generado'] ?? 0,
                        'cobrado' => $datoPeriodo['cobrado'] ?? 0,
                        'deuda' => $datoPeriodo['deuda'] ?? 0,
                        'estado' => $datoPeriodo['estado'] ?? 'Pendiente',
                    ];

                    // Sumar a los totales
                    $fila['totales']['generado'] += $datoPeriodo['generado'] ?? 0;
                    $fila['totales']['cobrado'] += $datoPeriodo['cobrado'] ?? 0;
                    $fila['totales']['deuda'] += $datoPeriodo['deuda'] ?? 0;
                }

                // Calcular porcentaje de cobranza
                $fila['totales']['porcentaje_cobranza'] = $this->calcularPorcentajeCobranza(
                    $fila['totales']['cobrado'],
                    $fila['totales']['generado']
                );

                return $fila;
            })
            ->values()
            ->toArray();

        return [
            'periodos' => $periodos,
            'filas' => $agrupadoPorUnidad,
        ];
    }

    /**
     * Obtener años disponibles en los datos de expensas
     *
     * @return array Años disponibles ordenados descendente
     */
    public function obtenerAñosDisponibles(): array
    {
        return Cache::remember('reportes.años_disponibles', 7200, function() {
            $años = PeriodoExpensa::select('año')
                ->distinct()
                ->orderByDesc('año')
                ->pluck('año')
                ->toArray();

            return $años;
        });
    }

    /**
     * Obtener períodos disponibles filtrados por año
     *
     * @param int|null $año Año específico (opcional)
     * @return array Períodos disponibles
     */
    public function obtenerPeriodosDisponibles(?int $año = null): array
    {
        $query = PeriodoExpensa::orderByDesc('año')
            ->orderByDesc('mes');

        if ($año !== null) {
            $query->where('año', $año);
        }

        return $query->get(['id', 'año', 'mes'])
            ->map(function ($periodo) {
                return [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre_periodo,
                    'mes' => $periodo->mes,
                    'año' => $periodo->año,
                ];
            })
            ->toArray();
    }
}