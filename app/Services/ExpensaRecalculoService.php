<?php

namespace App\Services;

use App\Models\Expensa;
use App\Models\ExpensaTotal;
use App\Models\PeriodoExpensa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpensaRecalculoService
{
    /**
     * Recalcular todos los campos derivados de expensas para un período
     * Esta función se ejecuta después de importar datos históricos y pagos
     *
     * @param int $periodoId
     * @return array{expensas_recalculadas: int, totales_recalculados: int}
     */
    public function recalcularPeriodo(int $periodoId): array
    {
        $periodo = PeriodoExpensa::findOrFail($periodoId);

        DB::beginTransaction();

        try {
            // 1. Recalcular expensas individuales
            $expensasRecalculadas = $this->recalcularExpensasIndividuales($periodoId);

            // 2. Recalcular expensas totales por propietario
            $totalesRecalculados = $this->recalcularExpensasTotales($periodoId);

            DB::commit();

            return [
                'expensas_recalculadas' => $expensasRecalculadas,
                'totales_recalculados' => $totalesRecalculados,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al recalcular período {$periodoId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recalcular campos derivados de expensas individuales
     *
     * @param int $periodoId
     * @return int número de expensas recalculadas
     */
    private function recalcularExpensasIndividuales(int $periodoId): int
    {
        $expensas = Expensa::where('periodo_id', $periodoId)->get();

        $recalculadas = 0;

        foreach ($expensas as $expensa) {
            // Recalcular monto_pendiente
            $montoPendiente = $expensa->monto_total - $expensa->monto_pagado;

            // Determinar estado según los montos
            if ($montoPendiente <= 0.01) { // Margen para errores de redondeo
                $estado = 'pagado';
                $montoPendiente = 0;
            } elseif ($expensa->monto_pagado > 0) {
                $estado = 'parcial';
            } else {
                $estado = 'pendiente';
            }

            // Actualizar expensa
            $expensa->update([
                'monto_pendiente' => $montoPendiente,
                'estado' => $estado,
            ]);

            $recalculadas++;
        }

        return $recalculadas;
    }

    /**
     * Recalcular expensas totales por propietario
     *
     * @param int $periodoId
     * @return int número de totales recalculados
     */
    private function recalcularExpensasTotales(int $periodoId): int
    {
        $periodo = PeriodoExpensa::findOrFail($periodoId);

        // Obtener todas las expensas del período
        $expensas = Expensa::where('periodo_id', $periodoId)
            ->whereHas('unidad', function($query) {
                $query->where('activo', true);
            })
            ->get();

        // Agrupar expensas por propietario
        $expensasPorPropietario = [];
        foreach ($expensas as $expensa) {
            $propietario = $expensa->unidad->propietarioActual;
            if (!$propietario) {
                continue;
            }

            $propietarioId = $propietario->id;
            if (!isset($expensasPorPropietario[$propietarioId])) {
                $expensasPorPropietario[$propietarioId] = [
                    'propietario' => $propietario,
                    'monto_total' => 0,
                    'monto_pagado' => 0,
                    'monto_pendiente' => 0,
                ];
            }

            $expensasPorPropietario[$propietarioId]['monto_total'] += $expensa->monto_total;
            $expensasPorPropietario[$propietarioId]['monto_pagado'] += $expensa->monto_pagado;
            $expensasPorPropietario[$propietarioId]['monto_pendiente'] += $expensa->monto_pendiente;
        }

        // Actualizar o crear expensas totales
        $recalculados = 0;
        foreach ($expensasPorPropietario as $propietarioId => $datos) {
            $montoTotal = $datos['monto_total'];
            $montoPagado = $datos['monto_pagado'];
            $montoPendiente = $datos['monto_pendiente'];

            // Determinar estado
            if ($montoPendiente <= 0.01) {
                $estado = 'pagado';
                $montoPendiente = 0;
            } elseif ($montoPagado > 0) {
                $estado = 'parcial';
            } else {
                $estado = 'pendiente';
            }

            // Actualizar o crear expensa total
            ExpensaTotal::updateOrCreate(
                [
                    'periodo_id' => $periodoId,
                    'propietario_id' => $propietarioId,
                ],
                [
                    'monto_total' => $montoTotal,
                    'monto_pagado' => $montoPagado,
                    'monto_pendiente' => $montoPendiente,
                    'estado' => $estado,
                    'observaciones' => 'Recalculado después de importación histórica',
                ]
            );

            $recalculados++;
        }

        return $recalculados;
    }

    /**
     * Recalcular solo una expensa específica (útil para debug)
     *
     * @param int $expensaId
     * @return bool
     */
    public function recalcularExpensa(int $expensaId): bool
    {
        $expensa = Expensa::findOrFail($expensaId);

        // Recalcular monto_pendiente
        $montoPendiente = $expensa->monto_total - $expensa->monto_pagado;

        // Determinar estado
        if ($montoPendiente <= 0.01) {
            $estado = 'pagado';
            $montoPendiente = 0;
        } elseif ($expensa->monto_pagado > 0) {
            $estado = 'parcial';
        } else {
            $estado = 'pendiente';
        }

        $expensa->update([
            'monto_pendiente' => $montoPendiente,
            'estado' => $estado,
        ]);

        return true;
    }
}