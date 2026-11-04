<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * DashboardController
 *
 * Controlador principal del dashboard administrativo.
 *
 * @package App\Http\Controllers
 */
class DashboardController extends Controller
{
    /**
     * Muestra el dashboard principal.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Obtener usuario autenticado
        $user = Auth::user();

        // Actividad reciente del sistema (últimos 10 logs)
        $recentActivity = ActivityLog::with('user')
                                      ->latest()
                                      ->limit(10)
                                      ->get();

        // [METRICAS 2026-10-30] Panel resumido (cada bloque tiene "Ver más"
        // hacia /metricas/*). Queries centralizadas + cache de 10 min.
        $metricas = app(\App\Services\MetricasService::class);

        $metrics = [
            'total_unidades' => \App\Models\Unidad::where('activo', true)->count(),
            'total_propietarios' => \App\Models\Propietario::count(),
            'total_expensas_pendientes' => 0,
            'total_pagos_mes' => 0,
            // Morosidad
            'morosidad' => $metricas->porcentajeMorosidad(),
            'top_morosos' => $metricas->topMorosos(5),
            'buckets_mora' => $metricas->bucketsMora(),
            // Consumo
            'consumo_periodo' => $metricas->consumoPorPeriodo(),
            'top_agua' => $metricas->topConsumidoresAgua(5),
            'anomalias_count' => $metricas->anomaliasConsumo()->count(),
            // Cobranza
            'cobranza' => $metricas->cobranzaPorPeriodo(),
            'canal' => $metricas->canalCobranza(),
            'saldo_favor' => $metricas->saldoFavorFlotante(),
        ];

        return view('dashboard', compact(
            'user',
            'recentActivity',
            'metrics'
        ));
    }
}
