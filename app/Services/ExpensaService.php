<?php

namespace App\Services;

use App\Models\Expensa;
use App\Models\Unidad;
use App\Models\PeriodoExpensa;
use App\Models\LecturaMedidor;
use App\Models\FacturaAguaPeriodo;
use App\Models\SaldoAFavor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpensaService
{
    /**
     * Generar todas las expensas para un período
     *
     * @param int $periodoId
     * @return array{exitosas: int, errores: array, detalles: array}
     */
    public function generarExpensas(int $periodoId): array
    {
        $periodo = PeriodoExpensa::findOrFail($periodoId);

        // Validar que el período esté abierto
        if ($periodo->estado !== 'abierto') {
            throw new \Exception("El período debe estar abierto para calcular expensas");
        }

        // Validar que el período tenga los factores de agua calculados
        if (is_null($periodo->factor_domiciliario) || is_null($periodo->factor_comercial)) {
            throw new \Exception("El período {$periodo->nombre_periodo} no tiene los factores de agua calculados. Debe calcular los factores antes de generar las expensas.");
        }

        $unidades = Unidad::activos()->get();

        $resultados = [
            'exitosas' => 0,
            'errores' => [],
            'detalles' => []
        ];

        DB::beginTransaction();

        try {
            foreach ($unidades as $unidad) {
                try {
                    // Usar bloqueo pesimista para evitar condiciones de carrera
                    $expensa = $this->generarExpensaUnidadConBloqueo($periodoId, $unidad->id);
                    $resultados['exitosas']++;
                    $resultados['detalles'][] = [
                        'unidad' => $unidad->nombre_completo,
                        'monto_total' => $expensa->monto_total,
                        'expensa_pendiente' => $expensa->expensa_pendiente,
                        'agua_pendiente' => $expensa->agua_pendiente,
                    ];
                } catch (\Exception $e) {
                    $resultados['errores'][] = [
                        'unidad' => $unidad->nombre_completo,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $resultados;
    }

    /**
     * Generar expensa para una unidad con bloqueo pesimista
     *
     * @param int $periodoId
     * @param int $unidadId
     * @return Expensa
     */
    protected function generarExpensaUnidadConBloqueo(int $periodoId, int $unidadId): Expensa
    {
        // Bloqueo pesimista en unidad para evitar condiciones de carrera
        $unidad = Unidad::lockForUpdate()->findOrFail($unidadId);

        return $this->generarExpensaUnidad($periodoId, $unidad);
    }

    /**
     * Generar expensa para una unidad específica
     *
     * @param int $periodoId
     * @param Unidad $unidad
     * @return Expensa
     */
    public function generarExpensaUnidad(int $periodoId, Unidad $unidad): Expensa
    {
        $periodo = PeriodoExpensa::findOrFail($periodoId);

        // 1. Calcular montos de emisión (no cambian si el periodo está cerrado)
        $montoExpensa = $this->calcularMontoExpensa($unidad);
        $montoAgua = $unidad->tiene_medidor
            ? $this->calcularMontoAgua($periodoId, $unidad->id)
            : 0;

        // 2. Obtener deuda histórica (calculado al vuelo, sin columna deuda_anterior)
        $deudaHistorica = $this->calcularDeudaHistorica($unidad->id, $periodoId);

        // 3. Obtener y aplicar saldos a favor disponibles
        $saldoAFavorDisponible = $this->obtenerSaldoAFavorDisponible($unidad->id);
        $saldoAFavorAplicado = 0;

        if ($saldoAFavorDisponible > 0 && $deudaHistorica > 0) {
            // Aplicar saldo a favor a la deuda histórica primero
            $saldoAplicar = min($saldoAFavorDisponible, $deudaHistorica);
            $deudaHistorica -= $saldoAplicar;
            $saldoAFavorAplicado = $saldoAplicar;

            // Consumir saldos a favor
            $this->consumirSaldosAFavor($unidad->id, $saldoAplicar);
        }

        // 4. Redondear montos de emisión primero
        $montoExpensa = $this->redondear($montoExpensa);
        $montoAgua = $this->redondear($montoAgua);

        // 5. Calcular total de emisión CON los montos ya redondeados
        $montoTotal = $montoExpensa + $montoAgua;

        // 6. Crear o actualizar expensa
        $expensa = Expensa::updateOrCreate(
            [
                'periodo_id' => $periodoId,
                'unidad_id' => $unidad->id,
            ],
            [
                'monto_expensa' => $montoExpensa,
                'monto_agua' => $montoAgua,
                'monto_total' => $montoTotal,
                // Al crear, todo está pendiente (los saldos pendientes son solo del periodo actual)
                'expensa_pendiente' => $montoExpensa,
                'agua_pendiente' => $montoAgua,
                'periodo_cerrado' => false,
            ]
        );

        Log::info('Expensa generada', [
            'periodo' => $periodo->nombre_periodo,
            'unidad' => $unidad->nombre_completo,
            'monto_total' => $montoTotal,
            'saldo_a_favor_aplicado' => $saldoAFavorAplicado,
            'deuda_historica_antes' => $this->calcularDeudaHistorica($unidad->id, $periodoId) + $saldoAFavorAplicado,
            'deuda_historica_despues' => $this->calcularDeudaHistorica($unidad->id, $periodoId),
        ]);

        return $expensa;
    }

    /**
     * Calcular deuda histórica al vuelo (sin columna estática)
     *
     * @param int $unidadId
     * @param int $periodoIdActual
     * @return float
     */
    protected function calcularDeudaHistorica(int $unidadId, int $periodoIdActual): float
    {
        $periodoActual = PeriodoExpensa::find($periodoIdActual);

        // Buscar expensas de períodos ANTERIORES
        $expensasAnteriores = Expensa::where('unidad_id', $unidadId)
            ->whereHas('periodo', function ($query) use ($periodoActual) {
                $query->where(function ($q) use ($periodoActual) {
                    $q->where('año', '<', $periodoActual->año)
                      ->orWhere(function ($q2) use ($periodoActual) {
                          $q2->where('año', '=', $periodoActual->año)
                             ->where('mes', '<', $periodoActual->mes);
                      });
                });
            })
            ->get();

        // Sumar saldos pendientes (cálculo al vuelo)
        $deudaTotal = $expensasAnteriores->sum('expensa_pendiente')
                    + $expensasAnteriores->sum('agua_pendiente');

        return max(0, $deudaTotal);
    }

    /**
     * Obtener saldo a favor disponible de una unidad
     *
     * @param int $unidadId
     * @return float
     */
    protected function obtenerSaldoAFavorDisponible(int $unidadId): float
    {
        return SaldoAFavor::deUnidad($unidadId)
            ->disponibles()
            ->sum('monto_saldo');
    }

    /**
     * Consumir saldos a favor de una unidad (FIFO)
     *
     * @param int $unidadId
     * @param float $montoAConsumir
     * @return void
     */
    protected function consumirSaldosAFavor(int $unidadId, float $montoAConsumir): void
    {
        $saldos = SaldoAFavor::deUnidad($unidadId)
            ->disponibles()
            ->orderBy('created_at', 'asc') // FIFO: consumir los más antiguos primero
            ->lockForUpdate()
            ->get();

        $montoRestante = $montoAConsumir;

        foreach ($saldos as $saldo) {
            if ($montoRestante <= 0) {
                break;
            }

            $montoAConsumirSaldo = min($montoRestante, $saldo->monto_saldo);
            $saldo->aplicarParcialmente($montoAConsumirSaldo);
            $montoRestante -= $montoAConsumirSaldo;

            Log::info('Saldo a favor consumido', [
                'unidad_id' => $unidadId,
                'saldo_id' => $saldo->id,
                'monto_consumido' => $montoAConsumirSaldo,
                'saldo_restante' => $saldo->monto_saldo,
            ]);
        }
    }

    /**
     * Calcular el monto de expensa de departamento/oficina
     * Fórmula: m² × factor de categoría
     *
     * @param Unidad $unidad
     * @return float
     */
    protected function calcularMontoExpensa(Unidad $unidad): float
    {
        $metrosCuadrados = $unidad->metros_cuadrados;
        $factor = $unidad->factor_prorrateo;

        return $metrosCuadrados * $factor;
    }

    /**
     * Calcular el monto de agua para una unidad
     * Fórmula: consumo_m3 × factor_agua (según categoría de la unidad)
     *
     * @param int $periodoId
     * @param int $unidadId
     * @return float
     */
    protected function calcularMontoAgua(int $periodoId, int $unidadId): float
    {
        $periodo = PeriodoExpensa::findOrFail($periodoId);
        $unidad = Unidad::findOrFail($unidadId);

        if (is_null($periodo->factor_domiciliario) || is_null($periodo->factor_comercial)) {
            throw new \Exception("El período {$periodo->nombre_periodo} no tiene los factores de agua calculados");
        }

        $lectura = LecturaMedidor::where('periodo_id', $periodoId)
            ->where('unidad_id', $unidadId)
            ->first();

        if (!$lectura) {
            return 0;
        }

        $consumoM3 = $lectura->consumo_m3;
        $factorAgua = $unidad->categoria_unidad === \App\Enums\CategoriaUnidad::DOMICILIARIA
            ? $periodo->factor_domiciliario
            : $periodo->factor_comercial;

        return $consumoM3 * $factorAgua;
    }

    /**
     * Calcular el factor de agua del período
     * Fórmula: Suma(importes facturas) / Suma(consumos facturas)
     *
     * @param int $periodoId
     * @return float
     */
    public function calcularFactorAgua(int $periodoId): float
    {
        $facturas = FacturaAguaPeriodo::where('periodo_id', $periodoId)->get();

        if ($facturas->isEmpty()) {
            throw new \Exception("No hay facturas de agua registradas para este período");
        }

        $totalImporte = $facturas->sum('importe');
        $totalConsumo = $facturas->sum('consumo_m3');

        $factorAgua = $totalConsumo > 0
            ? ($totalImporte / $totalConsumo)
            : 0.0;

        // Guardar el factor en el período
        $periodo = PeriodoExpensa::find($periodoId);
        $periodo->factor_domiciliario = $factorAgua;
        $periodo->factor_comercial = $factorAgua; // Mismo factor para ambos
        $periodo->factores_calculados_at = now();
        $periodo->factores_calculados_por = auth()->id();
        $periodo->save();

        return $factorAgua;
    }

    /**
     * Redondear monto según reglas del negocio
     * < 0.50 → redondear hacia ABAJO
     * >= 0.50 → redondear hacia ARRIBA
     *
     * @param float $monto
     * @return float
     */
    public function redondear(float $monto): float
    {
        $decimal = $monto - floor($monto);

        if ($decimal < 0.50) {
            return floor($monto);
        } else {
            return ceil($monto);
        }
    }

    /**
     * Cerrar un período (hace inmutables los montos de emisión)
     *
     * @param int $periodoId
     * @return void
     */
    public function cerrarPeriodo(int $periodoId): void
    {
        DB::transaction(function () use ($periodoId) {
            $periodo = PeriodoExpensa::findOrFail($periodoId);

            if ($periodo->estado === 'cerrado') {
                throw new \Exception("El período ya está cerrado");
            }

            // Marcar expensas como inmutables
            Expensa::where('periodo_id', $periodoId)
                ->update(['periodo_cerrado' => true]);

            // Cerrar período
            $periodo->update([
                'estado' => 'cerrado',
                'fecha_cierre' => now(),
                'cerrado_por' => auth()->id(),
            ]);

            Log::info('Período cerrado', [
                'periodo' => $periodo->nombre_periodo,
                'cerrado_por' => auth()->id(),
            ]);
        });
    }

    /**
     * Reabrir un período (permite modificar montos de emisión)
     *
     * @param int $periodoId
     * @param string|null $motivo
     * @return void
     */
    public function reabrirPeriodo(int $periodoId, ?string $motivo = null): void
    {
        DB::transaction(function () use ($periodoId, $motivo) {
            $periodo = PeriodoExpensa::findOrFail($periodoId);

            if ($periodo->estado === 'abierto') {
                throw new \Exception("El período ya está abierto");
            }

            // Desmarcar expensas como inmutables
            Expensa::where('periodo_id', $periodoId)
                ->update(['periodo_cerrado' => false]);

            // Reabrir período
            $periodo->update([
                'estado' => 'abierto',
                'fecha_cierre' => null,
                'motivo_reapertura' => $motivo,
                'cerrado_por' => null,
            ]);

            Log::info('Período reabierto', [
                'periodo' => $periodo->nombre_periodo,
                'motivo' => $motivo,
            ]);
        });
    }

    /**
     * Recalcular todas las expensas de un período
     *
     * @param int $periodoId
     * @return array
     */
    public function recalcularExpensas(int $periodoId): array
    {
        $periodo = PeriodoExpensa::findOrFail($periodoId);

        if ($periodo->estado === 'cerrado') {
            throw new \Exception("No se puede recalcular un período cerrado. Reabra el período primero.");
        }

        // Eliminar expensas existentes del período
        Expensa::where('periodo_id', $periodoId)->delete();

        // Generar nuevamente
        return $this->generarExpensas($periodoId);
    }

    /**
     * Obtener resumen de deuda de una unidad
     *
     * @param int $unidadId
     * @return array
     */
    public function obtenerResumenDeudaUnidad(int $unidadId): array
    {
        $unidad = Unidad::findOrFail($unidadId);

        // Obtener todas las expensas
        $expensas = Expensa::where('unidad_id', $unidadId)
            ->with('periodo')
            ->orderBy('periodo_id', 'desc')
            ->get();

        // Calcular totales
        $totalExpensaPendiente = $expensas->sum('expensa_pendiente');
        $totalAguaPendiente = $expensas->sum('agua_pendiente');
        $totalDeuda = $totalExpensaPendiente + $totalAguaPendiente;

        // Obtener saldo a favor
        $saldoAFavor = $this->obtenerSaldoAFavorDisponible($unidadId);

        // Agrupar por estado
        $pendientes = $expensas->filter(fn($e) => $e->expensa_pendiente > 0 || $e->agua_pendiente > 0);
        $pagadas = $expensas->filter(fn($e) => $e->expensa_pendiente == 0 && $e->agua_pendiente == 0);

        return [
            'unidad' => $unidad->nombre_completo,
            'deuda_total' => $totalDeuda,
            'deuda_expensa' => $totalExpensaPendiente,
            'deuda_agua' => $totalAguaPendiente,
            'saldo_a_favor' => $saldoAFavor,
            'neta_pagar' => max(0, $totalDeuda - $saldoAFavor),
            'expensas_pendientes' => $pendientes->count(),
            'expensas_pagadas' => $pagadas->count(),
            'expensas' => $expensas,
        ];
    }

    /**
     * Obtener deuda total de una unidad (al vuelo)
     *
     * @param int $unidadId
     * @param int|null $periodoId (si se especifica, solo calcula deuda hasta ese período)
     * @return array{deuda_total: float, deuda_expensa: float, deuda_agua: float, detalles: array}
     */
    public function obtenerDeudaUnidad(int $unidadId, ?int $periodoId = null): array
    {
        $query = Expensa::where('unidad_id', $unidadId);

        if ($periodoId) {
            $query->where('periodo_id', '<=', $periodoId);
        }

        $expensas = $query->get();

        $deudaExpensa = $expensas->sum('expensa_pendiente');
        $deudaAgua = $expensas->sum('agua_pendiente');
        $deudaTotal = $deudaExpensa + $deudaAgua;

        // Obtener detalle por período
        $detalles = $expensas->map(function ($expensa) {
            return [
                'periodo' => $expensa->periodo->nombre_periodo,
                'expensa_pendiente' => $expensa->expensa_pendiente,
                'agua_pendiente' => $expensa->agua_pendiente,
                'total_pendiente' => $expensa->total_pendiente,
                'periodo_cerrado' => $expensa->periodo_cerrado,
            ];
        });

        return [
            'deuda_total' => $deudaTotal,
            'deuda_expensa' => $deudaExpensa,
            'deuda_agua' => $deudaAgua,
            'detalles' => $detalles,
        ];
    }

    /**
     * Validar que el período esté completo para calcular expensas
     *
     * @param int $periodoId
     * @return array{valido: bool, errores: array, advertencias: array}
     */
    public function validarPeriodoCompleto(int $periodoId): array
    {
        $errores = [];
        $advertencias = [];

        // Verificar que hay facturas de agua
        $facturas = FacturaAguaPeriodo::where('periodo_id', $periodoId)->count();
        if ($facturas === 0) {
            $errores[] = "No hay facturas de agua registradas";
        }

        // Verificar que hay unidades activas
        $unidades = Unidad::activos()->count();
        if ($unidades === 0) {
            $errores[] = "No hay unidades activas";
        }

        // Advertir si faltan lecturas (NO es bloqueante)
        $unidadesConMedidor = Unidad::activos()->conMedidor()->count();
        $lecturas = LecturaMedidor::where('periodo_id', $periodoId)->count();

        if ($unidadesConMedidor > 0 && $lecturas < $unidadesConMedidor) {
            $faltantes = $unidadesConMedidor - $lecturas;
            $advertencias[] = "Faltan lecturas de medidor ({$lecturas} de {$unidadesConMedidor}). Las unidades sin lectura tendrán consumo de agua en 0. Podrás recalcular luego.";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'advertencias' => $advertencias
        ];
    }
}
