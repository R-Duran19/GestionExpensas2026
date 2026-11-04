<?php

namespace App\Services;

use App\Models\Expensa;
use App\Models\Ingreso;
use App\Models\LecturaMedidor;
use App\Models\SaldoAFavor;
use App\Models\Unidad;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * [METRICAS 2026-10-30] Panel de métricas del sistema (dashboard admin +
 * portal). TODAS las consultas son de solo lectura y van al vuelo con cache
 * de 10 minutos (el panel no requiere tiempo real; los mutadores ya
 * invalidan cache de reportes, aquí preferimos simplicidad + TTL).
 *
 * Convenciones:
 * - "Mora" = expensa_pendiente + agua_pendiente > 0 (cálculo al vuelo,
 *   igual que el resto del sistema: sin columnas almacenadas).
 * - Antigüedad en MESES = (año_actual*12+mes_actual) - (año_periodo*12+mes_periodo)
 *   tomando el período MÁS ANTIGUO con deuda de la unidad.
 * - Regla maestra del responsable: inquilino si la unidad tiene uno actual,
 *   si no, propietario actual.
 */
class MetricasService
{
    private const TTL_MINUTOS = 10;

    /**
     * Ranking de morosos: unidad, deuda total, período más antiguo con deuda
     * y meses de mora. Ordenado por monto (o antigüedad).
     */
    public function topMorosos(int $limit = 10, string $orden = 'monto'): Collection
    {
        return Cache::remember("metricas.morosos.{$orden}.{$limit}", now()->addMinutes(self::TTL_MINUTOS), function () use ($limit, $orden) {
            $filas = $this->baseMorosos()->take($limit)->values();

            return $this->adjuntarResponsables($filas);
        });
    }

    /**
     * TODAS las unidades con deuda (para la página "Ver más").
     */
    public function morososTodos(): Collection
    {
        return Cache::remember('metricas.morosos.todos', now()->addMinutes(self::TTL_MINUTOS), function () {
            return $this->adjuntarResponsables($this->baseMorosos()->values());
        });
    }

    /**
     * Deuda agrupada por antigüedad: 0-30 días · 1-3 meses · 3-6 · 6+.
     */
    public function bucketsMora(): array
    {
        return Cache::remember('metricas.buckets_mora', now()->addMinutes(self::TTL_MINUTOS), function () {
            $buckets = [
                ['clave' => '0-1m', 'etiqueta' => '0 a 1 mes', 'unidades' => 0, 'monto' => 0.0],
                ['clave' => '1-3m', 'etiqueta' => '1 a 3 meses', 'unidades' => 0, 'monto' => 0.0],
                ['clave' => '3-6m', 'etiqueta' => '3 a 6 meses', 'unidades' => 0, 'monto' => 0.0],
                ['clave' => '6+m', 'etiqueta' => 'Más de 6 meses', 'unidades' => 0, 'monto' => 0.0],
            ];

            foreach ($this->baseMorosos() as $fila) {
                $meses = $fila['meses_mora'];
                $i = match (true) {
                    $meses <= 1 => 0,
                    $meses <= 3 => 1,
                    $meses <= 6 => 2,
                    default => 3,
                };
                $buckets[$i]['unidades']++;
                $buckets[$i]['monto'] += $fila['deuda'];
            }

            return $buckets;
        });
    }

    /**
     * % de morosidad global: deuda pendiente ÷ emisión total.
     */
    public function porcentajeMorosidad(): array
    {
        return Cache::remember('metricas.porcentaje_morosidad', now()->addMinutes(self::TTL_MINUTOS), function () {
            $row = DB::table('expensas')
                ->selectRaw('COALESCE(SUM(monto_total),0) AS emitido, COALESCE(SUM(GREATEST(expensa_pendiente,0)+GREATEST(agua_pendiente,0)),0) AS pendiente')
                ->whereNull('deleted_at')
                ->first();

            $emitido = (float) $row->emitido;
            $pendiente = (float) $row->pendiente;

            return [
                'emitido' => round($emitido, 2),
                'pendiente' => round($pendiente, 2),
                'porcentaje' => $emitido > 0 ? round($pendiente / $emitido * 100, 1) : 0.0,
            ];
        });
    }

    /**
     * Consumo de agua por período: lo que miden los medidores individuales
     * vs lo que factura EPSAS (para detectar pérdidas) + mes pico.
     */
    public function consumoPorPeriodo(): array
    {
        return Cache::remember('metricas.consumo_periodo', now()->addMinutes(self::TTL_MINUTOS), function () {
            $medidores = DB::table('lecturas_medidor')
                ->join('periodos_expensa as pe', 'pe.id', '=', 'lecturas_medidor.periodo_id')
                ->selectRaw("CONCAT(pe.año,'-',LPAD(pe.mes,2,'0')) AS periodo, COALESCE(SUM(consumo_m3),0) AS m3")
                ->groupBy('periodo')
                ->orderBy('periodo')
                ->pluck('m3', 'periodo');

            $epsas = DB::table('facturas_agua_periodo')
                ->join('periodos_expensa as pe', 'pe.id', '=', 'facturas_agua_periodo.periodo_id')
                ->selectRaw("CONCAT(pe.año,'-',LPAD(pe.mes,2,'0')) AS periodo, COALESCE(SUM(consumo_m3),0) AS m3")
                ->groupBy('periodo')
                ->orderBy('periodo')
                ->pluck('m3', 'periodo');

            $periodos = $medidores->keys()->merge($epsas->keys())->unique()->sort()->values();

            $series = $periodos->map(fn ($p) => [
                'periodo' => $p,
                'medidores' => round((float) ($medidores[$p] ?? 0), 2),
                'epsas' => round((float) ($epsas[$p] ?? 0), 2),
            ])->values();

            $pico = $series->sortByDesc('medidores')->first();

            return [
                'series' => $series,
                'pico' => $pico,
                'perdida_ultimo' => (function () use ($series) {
                    $ultimo = $series->last();
                    if (!$ultimo || $ultimo['epsas'] <= 0) return 0.0;
                    return round(max(0, $ultimo['epsas'] - $ultimo['medidores']), 2);
                })(),
            ];
        });
    }

    /**
     * Top de unidades por consumo de agua histórico.
     */
    public function topConsumidoresAgua(int $limit = 10): Collection
    {
        return Cache::remember("metricas.top_agua.{$limit}", now()->addMinutes(self::TTL_MINUTOS), function () use ($limit) {
            return DB::table('lecturas_medidor')
                ->join('unidades as u', 'u.id', '=', 'lecturas_medidor.unidad_id')
                ->selectRaw('lecturas_medidor.unidad_id, u.codigo, u.categoria_unidad,
                    COALESCE(SUM(consumo_m3),0) AS total_m3,
                    COALESCE(AVG(consumo_m3),0) AS promedio_m3,
                    MAX(lecturas_medidor.periodo_id) AS ultimo_periodo_id')
                ->where('u.activo', true)
                ->groupBy('lecturas_medidor.unidad_id', 'u.codigo', 'u.categoria_unidad')
                ->orderByDesc('total_m3')
                ->limit($limit)
                ->get()
                ->map(function ($fila) {
                    // Último consumo real (del período más reciente con lectura)
                    $ultimo = DB::table('lecturas_medidor')
                        ->where('unidad_id', $fila->unidad_id)
                        ->orderByDesc('periodo_id')
                        ->value('consumo_m3');

                    return [
                        'unidad_id' => $fila->unidad_id,
                        'codigo' => $fila->codigo,
                        'categoria' => $fila->categoria_unidad,
                        'total_m3' => round((float) $fila->total_m3, 2),
                        'promedio_m3' => round((float) $fila->promedio_m3, 2),
                        'ultimo_m3' => round((float) $ultimo, 2),
                    ];
                });
        });
    }

    /**
     * Anomalías de consumo: unidades cuyo ÚLTIMO consumo supera su promedio
     * histórico por un margen (posibles fugas). Mínimo 3 lecturas para juzgar.
     */
    public function anomaliasConsumo(float $umbral = 1.5): Collection
    {
        return Cache::remember("metricas.anomalias.{$umbral}", now()->addMinutes(self::TTL_MINUTOS), function () use ($umbral) {
            $filas = DB::table('lecturas_medidor')
                ->join('unidades as u', 'u.id', '=', 'lecturas_medidor.unidad_id')
                ->selectRaw('lecturas_medidor.unidad_id, u.codigo, COUNT(*) AS lecturas,
                    COALESCE(AVG(consumo_m3),0) AS promedio_m3')
                ->where('u.activo', true)
                ->groupBy('lecturas_medidor.unidad_id', 'u.codigo')
                ->havingRaw('COUNT(*) >= 3')
                ->get();

            return $filas->map(function ($fila) use ($umbral) {
                $ultimo = (float) DB::table('lecturas_medidor')
                    ->where('unidad_id', $fila->unidad_id)
                    ->orderByDesc('periodo_id')
                    ->value('consumo_m3');

                $promedio = (float) $fila->promedio_m3;
                if ($promedio <= 0) return null;

                // Umbral mínimo significativo: sin esto, unidades con promedio
                // de 0.02 m3 (bauleras/locales cerrados) salen como "anómalas".
                if ($promedio < 1 || $ultimo < 2) return null;

                $ratio = $ultimo / $promedio;
                if ($ratio < $umbral) return null;

                return [
                    'unidad_id' => $fila->unidad_id,
                    'codigo' => $fila->codigo,
                    'promedio_m3' => round($promedio, 2),
                    'ultimo_m3' => round($ultimo, 2),
                    'ratio' => round($ratio, 2),
                ];
            })->filter()->sortByDesc('ratio')->values();
        });
    }

    /**
     * Consumo agregado por categoría de unidad.
     */
    public function consumoPorCategoria(): Collection
    {
        return Cache::remember('metricas.consumo_categoria', now()->addMinutes(self::TTL_MINUTOS), function () {
            return DB::table('lecturas_medidor')
                ->join('unidades as u', 'u.id', '=', 'lecturas_medidor.unidad_id')
                ->selectRaw('u.categoria_unidad AS categoria, COALESCE(SUM(consumo_m3),0) AS total_m3,
                    COUNT(DISTINCT lecturas_medidor.unidad_id) AS unidades')
                ->groupBy('u.categoria_unidad')
                ->orderByDesc('total_m3')
                ->get()
                ->map(fn ($f) => [
                    'categoria' => $f->categoria,
                    'total_m3' => round((float) $f->total_m3, 2),
                    'unidades' => (int) $f->unidades,
                ]);
        });
    }

    /**
     * Cobranza por período: emitido vs cobrado (al vuelo desde pendientes).
     */
    public function cobranzaPorPeriodo(): array
    {
        return Cache::remember('metricas.cobranza_periodo', now()->addMinutes(self::TTL_MINUTOS), function () {
            $filas = DB::table('expensas')
                ->join('periodos_expensa as pe', 'pe.id', '=', 'expensas.periodo_id')
                ->selectRaw("CONCAT(pe.año,'-',LPAD(pe.mes,2,'0')) AS periodo,
                    COALESCE(SUM(monto_total),0) AS emitido,
                    COALESCE(SUM(monto_total - GREATEST(expensa_pendiente,0) - GREATEST(agua_pendiente,0)),0) AS cobrado")
                ->whereNull('expensas.deleted_at')
                ->groupBy('periodo')
                ->orderBy('periodo')
                ->get()
                ->map(fn ($f) => [
                    'periodo' => $f->periodo,
                    'emitido' => round((float) $f->emitido, 2),
                    'cobrado' => round(max(0, (float) $f->cobrado), 2),
                ]);

            $ultimo = $filas->last();

            return [
                'series' => $filas->values(),
                'ultimo_porcentaje' => ($ultimo && $ultimo['emitido'] > 0)
                    ? round($ultimo['cobrado'] / $ultimo['emitido'] * 100, 1)
                    : 0.0,
            ];
        });
    }

    /**
     * Portal QR vs caja manual (total histórico + por mes reciente).
     */
    public function canalCobranza(): array
    {
        return Cache::remember('metricas.canal_cobranza', now()->addMinutes(self::TTL_MINUTOS), function () {
            $totales = Ingreso::selectRaw("COALESCE(SUM(CASE WHEN metodo_pago = 'qr' THEN monto_total ELSE 0 END),0) AS qr,
                COALESCE(SUM(CASE WHEN metodo_pago <> 'qr' OR metodo_pago IS NULL THEN monto_total ELSE 0 END),0) AS otros")
                ->first();

            return [
                'qr' => round((float) $totales->qr, 2),
                'otros' => round((float) $totales->otros, 2),
            ];
        });
    }

    /**
     * Saldos a favor flotantes (crédito sin consumir de los copropietarios).
     */
    public function saldoFavorFlotante(): float
    {
        return Cache::remember('metricas.saldo_favor', now()->addMinutes(self::TTL_MINUTOS), function () {
            return round((float) SaldoAFavor::whereIn('estado', ['disponible', 'parcialmente_consumido'])
                ->sum('monto_saldo'), 2);
        });
    }

    /**
     * Histórico de consumo de UNA unidad (para el portal y el drill-down
     * admin). Incluye el promedio del edificio por período como referencia.
     */
    public function consumoHistoricoUnidad(int $unidadId): array
    {
        return Cache::remember("metricas.consumo_unidad.{$unidadId}", now()->addMinutes(self::TTL_MINUTOS), function () use ($unidadId) {
            $propios = DB::table('lecturas_medidor')
                ->join('periodos_expensa as pe', 'pe.id', '=', 'lecturas_medidor.periodo_id')
                ->where('lecturas_medidor.unidad_id', $unidadId)
                ->selectRaw("CONCAT(pe.año,'-',LPAD(pe.mes,2,'0')) AS periodo, COALESCE(SUM(consumo_m3),0) AS m3")
                ->groupBy('periodo')
                ->orderBy('periodo')
                ->get();

            // Promedio por unidad del edificio en esos mismos períodos (referencia)
            $edificio = DB::table('lecturas_medidor')
                ->join('periodos_expensa as pe', 'pe.id', '=', 'lecturas_medidor.periodo_id')
                ->selectRaw("CONCAT(pe.año,'-',LPAD(pe.mes,2,'0')) AS periodo,
                    COALESCE(SUM(consumo_m3) / NULLIF(COUNT(DISTINCT lecturas_medidor.unidad_id),0),0) AS promedio")
                ->whereIn('lecturas_medidor.periodo_id', $propios->pluck('periodo_id'))
                ->groupBy('periodo')
                ->orderBy('periodo')
                ->pluck('promedio', 'periodo');

            $serie = $propios->map(fn ($f) => [
                'periodo' => $f->periodo,
                'm3' => round((float) $f->m3, 2),
                'promedio_edificio' => round((float) ($edificio[$f->periodo] ?? 0), 2),
            ])->values();

            $ultimo = $serie->last();

            return [
                'serie' => $serie,
                'ultimo_m3' => $ultimo['m3'] ?? 0,
                'promedio_propio' => round($serie->avg('m3'), 2),
                'vs_edificio_ultimo' => ($ultimo && ($ultimo['promedio_edificio'] ?? 0) > 0)
                    ? round($ultimo['m3'] / $ultimo['promedio_edificio'], 2)
                    : null,
            ];
        });
    }

    /**
     * Timeline por período (Pagado/Parcial/Pendiente) de las unidades de un
     * usuario del portal — para la vista del propietario/inquilino.
     */
    public function timelineUnidades(array $unidadIds): Collection
    {
        if (empty($unidadIds)) {
            return collect();
        }

        return Expensa::query()
            ->join('periodos_expensa as pe', 'pe.id', '=', 'expensas.periodo_id')
            ->whereIn('expensas.unidad_id', $unidadIds)
            ->whereNull('expensas.deleted_at')
            ->selectRaw('pe.id AS periodo_id, pe.año, pe.mes, expensas.unidad_id,
                expensas.monto_total, expensas.expensa_pendiente, expensas.agua_pendiente')
            ->orderBy('pe.año')->orderBy('pe.mes')
            ->get()
            ->groupBy(fn ($e) => $e->año . '-' . str_pad($e->mes, 2, '0', STR_PAD_LEFT))
            ->map(function ($grupo, $periodo) {
                $pendiente = $grupo->sum(fn ($e) => max(0, (float) $e->expensa_pendiente) + max(0, (float) $e->agua_pendiente));
                $emitido = $grupo->sum('monto_total');

                $estado = $pendiente <= 0 ? 'pagado' : ($pendiente < $emitido ? 'parcial' : 'pendiente');

                return [
                    'periodo' => $periodo,
                    'emitido' => round($emitido, 2),
                    'pendiente' => round($pendiente, 2),
                    'estado' => $estado,
                ];
            })
            ->values();
    }

    /* ------------------------------------------------------------------ */

    /**
     * Base del ranking de morosos: una fila por unidad con deuda, con el
     * período más antiguo con deuda y meses de mora calculados.
     */
    private function baseMorosos(): Collection
    {
        return DB::table('expensas')
            ->join('unidades as u', 'u.id', '=', 'expensas.unidad_id')
            ->join('periodos_expensa as pe', 'pe.id', '=', 'expensas.periodo_id')
            ->whereNull('expensas.deleted_at')
            ->where('u.activo', true)
            ->where(function ($q) {
                $q->where('expensas.expensa_pendiente', '>', 0)
                  ->orWhere('expensas.agua_pendiente', '>', 0);
            })
            ->selectRaw('expensas.unidad_id, u.codigo,
                SUM(GREATEST(expensas.expensa_pendiente,0) + GREATEST(expensas.agua_pendiente,0)) AS deuda,
                MIN(pe.año*100 + pe.mes) AS primer_periodo')
            ->groupBy('expensas.unidad_id', 'u.codigo')
            ->orderByDesc('deuda')
            ->get()
            ->map(function ($f) {
                $ahora = (int) now()->format('Y') * 100 + (int) now()->format('n');
                $primer = (int) $f->primer_periodo;
                $mesesMora = floor(($ahora - $primer) / 100) * 12 + (($ahora % 100) - ($primer % 100));

                return [
                    'unidad_id' => $f->unidad_id,
                    'codigo' => $f->codigo,
                    'deuda' => round((float) $f->deuda, 2),
                    'primer_periodo' => substr($primer, 0, 4) . '-' . str_pad(substr($primer, 4), 2, '0', STR_PAD_LEFT),
                    'meses_mora' => max(0, $mesesMora),
                ];
            })
            ->sortByDesc('deuda')
            ->values();
    }

    /**
     * Regla maestra: responsable = inquilino actual si existe, si no el
     * propietario actual. Adjunta nombre + tipo a cada fila de morosos.
     */
    private function adjuntarResponsables(Collection $filas): Collection
    {
        $unidades = Unidad::with(['propietarioActual', 'inquilinoActual'])
            ->whereIn('id', $filas->pluck('unidad_id'))
            ->get()
            ->keyBy('id');

        return $filas->map(function ($fila) use ($unidades) {
            $unidad = $unidades->get($fila['unidad_id']);
            $fila['responsable'] = '—';
            $fila['tipo_responsable'] = '—';

            if ($unidad) {
                $inquilino = $unidad->getRelation('inquilinoActual')->first();
                $propietario = $unidad->getRelation('propietarioActual')->first();

                if ($inquilino) {
                    $fila['responsable'] = $inquilino->nombre_completo;
                    $fila['tipo_responsable'] = 'inquilino';
                } elseif ($propietario) {
                    $fila['responsable'] = $propietario->nombre_completo;
                    $fila['tipo_responsable'] = 'propietario';
                }
            }

            return $fila;
        });
    }
}
