<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Expensa;
use App\Models\IngresoDetalle;
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

        $this->invalidarCacheReportes();

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

        // [REMEDIACIÓN-2.1] Guard interno de estado: la inmutabilidad del
        // período cerrado se aplica AQUÍ (fuente única), no en cada caller.
        // Antes dos callers wire-reachable regeneraban expensas de períodos
        // cerrados reseteando pendientes y desmarcando la inmutabilidad.
        if ($periodo->estado !== 'abierto') {
            throw new \Exception("El período {$periodo->nombre_periodo} está cerrado: no se pueden generar/regenerar sus expensas. Reabra el período primero.");
        }

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
        // [REMEDIACIÓN-2.1] En UPDATE nunca resetear pendientes a la emisión:
        // se recalculan desde lo efectivamente pagado (ingreso_detalles), tal
        // como lo hace el ajuste temporal. Antes, regenerar un período con
        // pagos parciales borraba el estado de pago (doble cobro).
        $expensa = Expensa::where('periodo_id', $periodoId)
            ->where('unidad_id', $unidad->id)
            ->first();

        if ($expensa) {
            $pagado = \App\Models\IngresoDetalle::where('expensa_id', $expensa->id)
                ->selectRaw('COALESCE(SUM(monto_expensa_aplicado), 0) AS pagado_expensa, COALESCE(SUM(monto_agua_aplicado), 0) AS pagado_agua')
                ->first();

            $expensa->update([
                'monto_expensa' => $montoExpensa,
                'monto_agua' => $montoAgua,
                'monto_total' => $montoTotal,
                'expensa_pendiente' => max(0, $montoExpensa - (float) $pagado->pagado_expensa),
                'agua_pendiente' => max(0, $montoAgua - (float) $pagado->pagado_agua),
                // periodo_cerrado NO se toca en update: es marca de inmutabilidad
            ]);
        } else {
            // Al crear, todo está pendiente (los saldos pendientes son solo del periodo actual)
            $expensa = Expensa::create([
                'periodo_id' => $periodoId,
                'unidad_id' => $unidad->id,
                'monto_expensa' => $montoExpensa,
                'monto_agua' => $montoAgua,
                'monto_total' => $montoTotal,
                'expensa_pendiente' => $montoExpensa,
                'agua_pendiente' => $montoAgua,
                'periodo_cerrado' => false,
            ]);
        }

        // [FIX B — OPCIÓN 3 2026-10-15] Si aún queda saldo a favor disponible
        // (la unidad estaba al día y la deuda histórica no lo consumió), se
        // aplica a la expensa NUEVA como pago automático, registrado como
        // ingreso_detalle del ingreso que generó el saldo (trazabilidad
        // contable completa). Así el mes siguiente "nace pagado" o pagado
        // parcial según cubra el saldo.
        $this->aplicarSaldoAFavorANuevaExpensa($expensa);

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
     * [FIX B — OPCIÓN 3 2026-10-15] Aplicar el saldo a favor disponible de la
     * unidad a la expensa recién generada (pago automático).
     *
     * Se registra como ingreso_detalle(s) del ingreso que generó cada saldo
     * (misma trazabilidad que un pago normal), se consume el saldo FIFO con
     * sus estados (disponible → parcialmente_consumido → consumido) y se
     * recomputan los pendientes desde los detalles. Es seguro ante
     * regeneración: el pendiente SIEMPRE se recalcula desde ingreso_detalles.
     */
    protected function aplicarSaldoAFavorANuevaExpensa(Expensa $expensa): void
    {
        DB::transaction(function () use ($expensa) {
            $saldoDisponible = $this->obtenerSaldoAFavorDisponible($expensa->unidad_id);
            if ($saldoDisponible <= 0) {
                return;
            }

            $expensa->refresh();

            // Primero cubre expensa, luego agua (convención del sistema)
            $porExpensa = (float) min($saldoDisponible, max(0, (float) $expensa->expensa_pendiente));
            $porAgua = (float) min(round($saldoDisponible - $porExpensa, 2), max(0, (float) $expensa->agua_pendiente));
            $totalAplicar = round($porExpensa + $porAgua, 2);

            if ($totalAplicar <= 0) {
                return;
            }

            // Incluye parcialmente_consumidos: aún tienen monto_saldo aplicable
            $saldos = SaldoAFavor::deUnidad($expensa->unidad_id)
                ->whereIn('estado', ['disponible', 'parcialmente_consumido'])
                ->where('monto_saldo', '>', 0)
                ->orderBy('created_at', 'asc') // FIFO
                ->lockForUpdate()
                ->get();

            $faltaExpensa = $porExpensa;
            $faltaAgua = $porAgua;

            foreach ($saldos as $saldo) {
                if ($faltaExpensa <= 0 && $faltaAgua <= 0) {
                    break;
                }

                $delSaldoExpensa = (float) min($faltaExpensa, $saldo->monto_saldo);
                $delSaldoAgua = (float) min($faltaAgua, round($saldo->monto_saldo - $delSaldoExpensa, 2));
                $aplicado = round($delSaldoExpensa + $delSaldoAgua, 2);

                if ($aplicado <= 0) {
                    continue;
                }

                IngresoDetalle::create([
                    'ingreso_id' => $saldo->ingreso_id,
                    'expensa_id' => $expensa->id,
                    'concepto' => ($delSaldoExpensa > 0 && $delSaldoAgua > 0) ? 'ambos' : ($delSaldoAgua > 0 ? 'agua' : 'expensa'),
                    'monto_aplicado' => $aplicado,
                    'monto_expensa_aplicado' => $delSaldoExpensa,
                    'monto_agua_aplicado' => $delSaldoAgua,
                ]);

                $saldo->aplicarParcialmente($aplicado);

                $faltaExpensa = round($faltaExpensa - $delSaldoExpensa, 2);
                $faltaAgua = round($faltaAgua - $delSaldoAgua, 2);
            }

            // Recomputar pendientes desde los detalles (misma fórmula del update)
            $pagado = IngresoDetalle::where('expensa_id', $expensa->id)
                ->selectRaw('COALESCE(SUM(monto_expensa_aplicado), 0) AS pe, COALESCE(SUM(monto_agua_aplicado), 0) AS pa')
                ->first();

            $expensa->update([
                'expensa_pendiente' => max(0, (float) $expensa->monto_expensa - (float) $pagado->pe),
                'agua_pendiente' => max(0, (float) $expensa->monto_agua - (float) $pagado->pa),
            ]);

            Log::info('Saldo a favor aplicado a expensa nueva', [
                'expensa_id' => $expensa->id,
                'unidad_id' => $expensa->unidad_id,
                'periodo_id' => $expensa->periodo_id,
                'aplicado_expensa' => $porExpensa,
                'aplicado_agua' => $porAgua,
                'pendiente_expensa' => $expensa->fresh()->expensa_pendiente,
                'pendiente_agua' => $expensa->fresh()->agua_pendiente,
            ]);
        });
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
    public function cerrarPeriodo(int $periodoId, ?string $motivo = null): void
    {
        DB::transaction(function () use ($periodoId, $motivo) {
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

            // Auditoría: el motivo del cierre (opcional) vive en activity_logs,
            // NO en una columna del período (decisión 2026-10-15: sin esquema nuevo).
            try {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'cierre_periodo',
                    'model_type' => PeriodoExpensa::class,
                    'model_id' => $periodo->id,
                    'description' => 'Cierre del período ' . $periodo->nombre_periodo . ($motivo ? ': ' . $motivo : ''),
                    'properties' => ['motivo' => $motivo, 'fecha_cierre' => now()->toDateTimeString()],
                ]);
            } catch (\Exception $e) {
                // El log de auditoría no debe romper el cierre
            }

            Log::info('Período cerrado', [
                'periodo' => $periodo->nombre_periodo,
                'cerrado_por' => auth()->id(),
                'motivo' => $motivo,
            ]);
        });

        $this->invalidarCacheReportes();
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

        $this->invalidarCacheReportes();
    }

    /**
     * Invalida el cache de reportes (cálculos afectan Generado/Deuda)
     */
    private function invalidarCacheReportes(): void
    {
        app(ReporteService::class)->invalidarCacheReportes();
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

        // [REMEDIACIÓN-2.1] No destruir expensas con pagos registrados: el
        // delete en cascada borraba ingreso_detalles (la evidencia de la
        // aplicación del pago) mientras el recibo sobrevivía — deuda doble.
        $hayPagos = \App\Models\IngresoDetalle::whereHas('expensa', function ($q) use ($periodoId) {
            $q->where('periodo_id', $periodoId);
        })->exists();

        if ($hayPagos) {
            throw new \Exception("No se puede recalcular: el período tiene pagos registrados. Anule los recibos primero o use el ajuste temporal.");
        }

        // Eliminar expensas existentes del período y regenerar de forma atómica
        return DB::transaction(function () use ($periodoId) {
            Expensa::where('periodo_id', $periodoId)->delete();

            return $this->generarExpensas($periodoId);
        });
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
