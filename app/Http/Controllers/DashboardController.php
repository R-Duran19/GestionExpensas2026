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

        // Métricas básicas (placeholder para FASE 6: Reportes)
        $metrics = [
            'total_unidades' => 0, // Se actualizará en FASE 1.2
            'total_propietarios' => 0, // Se actualizará en FASE 1.2
            'total_expensas_pendientes' => 0, // Se actualizará en FASE 3
            'total_pagos_mes' => 0, // Se actualizará en FASE 5
        ];

        return view('dashboard', compact(
            'user',
            'recentActivity',
            'metrics'
        ));
    }
}
