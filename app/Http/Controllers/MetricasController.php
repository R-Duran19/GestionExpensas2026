<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use App\Services\MetricasService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * [METRICAS 2026-10-30] Páginas de detalle del panel de métricas ("Ver más"
 * desde el dashboard). Solo admin.
 */
class MetricasController extends Controller
{
    public function morosidad(MetricasService $metricas): View
    {
        $morosos = $metricas->morososTodos();
        $buckets = $metricas->bucketsMora();
        $porcentaje = $metricas->porcentajeMorosidad();

        // Orden alterno: por antigüedad de mora
        $masAntiguos = $morosos->sortByDesc('meses_mora')->take(15)->values();

        return view('metricas.morosidad', compact('morosos', 'buckets', 'porcentaje', 'masAntiguos'));
    }

    public function consumo(MetricasService $metricas): View
    {
        $consumoPeriodo = $metricas->consumoPorPeriodo();
        $topAgua = $metricas->topConsumidoresAgua(50);
        $anomalias = $metricas->anomaliasConsumo();
        $porCategoria = $metricas->consumoPorCategoria();

        return view('metricas.consumo', compact(
            'consumoPeriodo', 'topAgua', 'anomalias', 'porCategoria'
        ));
    }

    public function consumoUnidad(Unidad $unidad, MetricasService $metricas): View
    {
        $historico = $metricas->consumoHistoricoUnidad($unidad->id);

        return view('metricas.consumo-unidad', compact('unidad', 'historico'));
    }
}
