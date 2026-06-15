<?php

namespace App\Services;

use App\Models\Expensa;
use App\Models\Unidad;
use App\Models\PeriodoExpensa;
use App\Models\LecturaMedidor;
use App\Models\FacturaAguaPeriodo;
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

        // Obtener todas las unidades activas
        $unidades = Unidad::activos()->get();

        $resultados = [
            'exitosas' => 0,
            'errores' => [],
            'detalles' => []
        ];

        DB::beginTransaction();

        try {
            // Generar expensas individuales por unidad
            $expensasGeneradas = [];
            foreach ($unidades as $unidad) {
                try {
                    $expensa = $this->generarExpensaUnidad($periodoId, $unidad->id);
                    $expensasGeneradas[] = $expensa;
                    $resultados['exitosas']++;
                    $resultados['detalles'][] = [
                        'unidad' => $unidad->nombre_completo,
                        'monto_total' => $expensa->monto_total
                    ];
                } catch (\Exception $e) {
                    $resultados['errores'][] = [
                        'unidad' => $unidad->nombre_completo,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Generar expensas totales por propietario
            $this->generarExpensasTotales($periodoId, $expensasGeneradas);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $resultados;
    }

    /**
     * Generar expensa para una unidad específica
     *
     * @param int $periodoId
     * @param int $unidadId
     * @return Expensa
     */
    public function generarExpensaUnidad(int $periodoId, int $unidadId): Expensa
    {
        $periodo = PeriodoExpensa::findOrFail($periodoId);
        $unidad = Unidad::findOrFail($unidadId);

        // 1. Calcular expensa de departamento/oficina
        $montoExpensa = $this->calcularMontoExpensa($unidad);

        // 2. Calcular monto de agua (solo si tiene medidor)
        $montoAgua = $unidad->tiene_medidor
            ? $this->calcularMontoAgua($periodoId, $unidadId)
            : 0;

        // 3. Obtener deuda anterior de períodos cerrados
        $deudaAnterior = $this->obtenerDeudaAnterior($unidadId, $periodoId);

        // 4. Obtener saldo a favor (si pagó de más en períodos anteriores)
        $saldoAFavor = $this->obtenerSaldoAFavor($unidadId, $periodoId);

        // 5. Calcular total
        $subtotal = $montoExpensa + $montoAgua;
        $montoTotal = $subtotal + $deudaAnterior - $saldoAFavor;

        // 6. Redondear montos
        $montoExpensa = $this->redondear($montoExpensa);
        $montoAgua = $this->redondear($montoAgua);
        $deudaAnterior = $this->redondear($deudaAnterior);
        $saldoAFavor = $this->redondear($saldoAFavor);
        // Calcular total usando los montos redondeados para consistencia
        $montoTotal = $montoExpensa + $montoAgua + $deudaAnterior - $saldoAFavor;

        // 7. Crear o actualizar expensa individual
        $expensa = Expensa::updateOrCreate(
            [
                'periodo_id' => $periodoId,
                'unidad_id' => $unidadId,
            ],
            [
                'monto_expensa' => $montoExpensa,
                'monto_agua' => $montoAgua,
                'deuda_anterior' => $deudaAnterior,
                'monto_total' => $montoTotal,
                // Inicializar campos de pago correctamente
                'monto_pendiente' => $montoTotal,  // Al crear, pendiente = total
                'monto_pagado' => 0,
                'estado' => 'pendiente',
                'fecha_pago' => null,
            ]
        );

        Log::info('Expensa generada', [
            'periodo' => $periodo->nombre_periodo,
            'unidad' => $unidad->nombre_completo,
            'monto_total' => $montoTotal
        ]);

        return $expensa;
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
        $factor = $unidad->factor_prorrateo; // Obtenido desde CategoriaUnidad enum

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

        // Validar que el período tenga factores calculados
        if (is_null($periodo->factor_domiciliario) || is_null($periodo->factor_comercial)) {
            throw new \Exception("El período {$periodo->nombre_periodo} no tiene los factores de agua calculados. Debe calcular los factores antes de generar las expensas.");
        }

        // Buscar lectura de medidor de este período
        $lectura = LecturaMedidor::where('periodo_id', $periodoId)
            ->where('unidad_id', $unidadId)
            ->first();

        if (!$lectura) {
            // Si no hay lectura registrada, asumir 0
            return 0;
        }

        // El consumo ya está calculado en la lectura
        $consumoM3 = $lectura->consumo_m3;

        // Usar el factor correspondiente según la categoría de la unidad
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

        // Guardar el factor en el período (para auditoría)
        $periodo = PeriodoExpensa::find($periodoId);
        $periodo->factor_domiciliario = $factorAgua; // Se usa el mismo para ambos
        $periodo->factores_calculados_at = now();
        $periodo->factores_calculados_por = auth()->id();
        $periodo->save();

        return $factorAgua;
    }

    /**
     * Obtener deuda anterior de períodos cerrados
     *
     * @param int $unidadId
     * @param int $periodoIdActual
     * @return float
     */
    protected function obtenerDeudaAnterior(int $unidadId, int $periodoIdActual): float
    {
        // Buscar expensas de períodos ANTERIORES al actual
        $periodoActual = PeriodoExpensa::find($periodoIdActual);

        $expensasAnteriores = Expensa::where('unidad_id', $unidadId)
            ->whereHas('periodo', function ($query) use ($periodoActual) {
                // Períodos anteriores (por mes/año)
                $query->where(function ($q) use ($periodoActual) {
                    $q->where('año', '<', $periodoActual->año)
                      ->orWhere(function ($q2) use ($periodoActual) {
                          $q2->where('año', '=', $periodoActual->año)
                             ->where('mes', '<', $periodoActual->mes);
                      });
                });
            })
            ->get();

        $deudaTotal = 0;

        foreach ($expensasAnteriores as $expensa) {
            // Sumar lo que deben (pendiente + parcial)
            if ($expensa->estado === 'pendiente') {
                $deudaTotal += $expensa->monto_total;
            } elseif ($expensa->estado === 'parcial') {
                $deudaTotal += $expensa->monto_pendiente;
            }

            // Restar saldos a favor de períodos anteriores
            if ($expensa->saldo_a_favor > 0) {
                $deudaTotal -= $expensa->saldo_a_favor;
            }
        }

        return max(0, $deudaTotal); // No puede ser negativo
    }

    /**
     * Obtener saldo a favor de períodos anteriores
     * (si pagó más de lo que debía)
     *
     * @param int $unidadId
     * @param int $periodoIdActual
     * @return float
     */
    protected function obtenerSaldoAFavor(int $unidadId, int $periodoIdActual): float
    {
        // Buscar expensas de períodos ANTERIORES al actual
        $periodoActual = PeriodoExpensa::find($periodoIdActual);

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

        $saldoAFavor = 0;

        foreach ($expensasAnteriores as $expensa) {
            // Si pagó más de lo que debía
            if ($expensa->monto_pagado > $expensa->monto_total) {
                $saldoAFavor += ($expensa->monto_pagado - $expensa->monto_total);
            }

            // Si ya tenía saldo a favor registrado
            if ($expensa->saldo_a_favor > 0) {
                $saldoAFavor += $expensa->saldo_a_favor;
            }
        }

        return $saldoAFavor;
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
     * Recalcular todas las expensas de un período
     * (útil si hubo cambios en lecturas o factores)
     *
     * @param int $periodoId
     * @return array
     */
    public function recalcularExpensas(int $periodoId): array
    {
        // Eliminar expensas existentes del período
        Expensa::where('periodo_id', $periodoId)->delete();

        // Eliminar expensas totales existentes del período
        \App\Models\ExpensaTotal::where('periodo_id', $periodoId)->delete();

        // Generar nuevamente
        return $this->generarExpensas($periodoId);
    }

    /**
     * Validar que el período esté completo para calcular expensas
     *
     * @param int $periodoId
     * @return array{valido: bool, errores: array}
     */
    public function validarPeriodoCompleto(int $periodoId): array
    {
        $errores = [];

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

        // Advertir si faltan lecturas (no es error, porque parqueos/bauleras no tienen)
        $unidadesConMedidor = Unidad::activos()->conMedidor()->count();
        $lecturas = LecturaMedidor::where('periodo_id', $periodoId)->count();

        if ($lecturas < $unidadesConMedidor) {
            $errores[] = "Faltan lecturas de medidor ({$lecturas} de {$unidadesConMedidor})";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Generar expensas totales por propietario
     *
     * @param int $periodoId
     * @param array $expensasGeneradas
     * @return void
     */
    protected function generarExpensasTotales(int $periodoId, array $expensasGeneradas): void
    {
        $periodo = PeriodoExpensa::findOrFail($periodoId);

        // Agrupar expensas por propietario
        $expensasPorPropietario = [];
        foreach ($expensasGeneradas as $expensa) {
            $propietario = $expensa->unidad->propietarioActual;
            if (!$propietario) {
                continue; // Saltar unidades sin propietario
            }

            $propietarioId = $propietario->id;
            if (!isset($expensasPorPropietario[$propietarioId])) {
                $expensasPorPropietario[$propietarioId] = [
                    'propietario' => $propietario,
                    'expensas' => [],
                    'monto_total' => 0,
                    'monto_pagado' => 0,
                    'monto_pendiente' => 0,
                ];
            }

            $expensasPorPropietario[$propietarioId]['expensas'][] = $expensa;
            $expensasPorPropietario[$propietarioId]['monto_total'] += $expensa->monto_total;

            // Sumar montos de pagos de las expensas individuales
            $expensasPorPropietario[$propietarioId]['monto_pagado'] += $expensa->monto_pagado;
            $expensasPorPropietario[$propietarioId]['monto_pendiente'] += $expensa->monto_pendiente;
        }

        // Generar expensas totales para cada propietario
        foreach ($expensasPorPropietario as $propietarioId => $datos) {
            $propietario = $datos['propietario'];
            $montoTotalBruto = $datos['monto_total'];
            $montoPagadoTotal = $datos['monto_pagado'];
            $montoPendienteTotal = $datos['monto_pendiente'];

            // Buscar saldo a favor del período anterior
            $saldoAFavorAnterior = $this->obtenerSaldoAFavorAnterior($propietarioId, $periodoId);

            // Calcular monto total neto (aplicando saldo a favor si existe)
            $montoTotalNeto = $montoTotalBruto - $saldoAFavorAnterior;

            // Determinar estado según los montos de las expensas individuales
            if ($montoPendienteTotal > 0 && $montoPagadoTotal > 0) {
                // Pagó parcialmente
                $estado = 'parcial';
            } elseif ($montoPendienteTotal > 0 && $montoPagadoTotal === 0) {
                // No pagó nada
                $estado = 'pendiente';
            } elseif ($montoPendienteTotal === 0 && $montoPagadoTotal > 0) {
                // Pagó todo (o más, si hay saldo a favor)
                $estado = 'pagado';
            } else {
                // Caso edge: pagó exacto o hay variación por redondeo
                $estado = 'pagado';
            }

            // Recalcular montos finales considerando saldo a favor
            $saldoAFavor = 0;
            if ($montoTotalNeto < 0) {
                // Saldo a favor del período actual
                $saldoAFavor = abs($montoTotalNeto);
                $montoTotalNeto = 0;
                $montoPendienteTotal = 0;
                $montoPagadoTotal += $saldoAFavor; // Considerar como pago
                $estado = 'pagado';
            }

            // Redondear montos
            $montoTotal = $this->redondear($montoTotalNeto);
            $montoPagado = $this->redondear($montoPagadoTotal);
            $montoPendiente = $this->redondear($montoPendienteTotal);
            $saldoAFavor = $this->redondear($saldoAFavor);

            // Crear o actualizar expensa total
            $expensaTotal = \App\Models\ExpensaTotal::updateOrCreate(
                [
                    'periodo_id' => $periodoId,
                    'propietario_id' => $propietarioId,
                ],
                [
                    'monto_total' => $montoTotal,
                    'monto_pagado' => $montoPagado,
                    'monto_pendiente' => $montoPendiente,
                    'saldo_a_favor' => $saldoAFavor,
                    'estado' => $estado,
                ]
            );

            Log::info('Expensa total generada', [
                'periodo' => $periodo->nombre_periodo,
                'propietario' => $propietario->nombre . ' ' . $propietario->apellido,
                'monto_total_bruto' => $montoTotalBruto,
                'monto_pagado_unidades' => $montoPagadoTotal,
                'monto_pendiente_unidades' => $montoPendienteTotal,
                'saldo_a_favor_anterior' => $saldoAFavorAnterior,
                'monto_total_neto' => $montoTotal,
                'monto_pagado_final' => $montoPagado,
                'monto_pendiente_final' => $montoPendiente,
                'saldo_a_favor_final' => $saldoAFavor,
                'estado' => $estado
            ]);
        }
    }

    /**
     * Obtener saldo a favor del período anterior
     *
     * @param int $propietarioId
     * @param int $periodoIdActual
     * @return float
     */
    protected function obtenerSaldoAFavorAnterior(int $propietarioId, int $periodoIdActual): float
    {
        $periodoActual = PeriodoExpensa::find($periodoIdActual);

        // Buscar expensa total de períodos ANTERIORES del mismo propietario
        $expensaTotalAnterior = \App\Models\ExpensaTotal::where('propietario_id', $propietarioId)
            ->whereHas('periodo', function ($query) use ($periodoActual) {
                $query->where(function ($q) use ($periodoActual) {
                    $q->where('año', '<', $periodoActual->año)
                      ->orWhere(function ($q2) use ($periodoActual) {
                          $q2->where('año', '=', $periodoActual->año)
                             ->where('mes', '<', $periodoActual->mes);
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->first();

        // Retornar saldo a favor del período anterior si existe y está pagado
        if ($expensaTotalAnterior && $expensaTotalAnterior->saldo_a_favor > 0) {
            return $expensaTotalAnterior->saldo_a_favor;
        }

        return 0;
    }
}
