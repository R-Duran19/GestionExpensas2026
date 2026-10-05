<?php

namespace App\Services;

use App\Models\CajaTotal;
use App\Models\Ingreso;
use App\Models\Egreso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CajaService
{
    /**
     * Recalcular totales de una fecha específica
     */
    public function calcularTotalesDia(string $fecha, ?int $userId = null): CajaTotal
    {
        // [REMEDIACIÓN-2.5] Transacción + lock de la fila del día: los tres
        // recomputes (este, IngresoService y PagoExpensaService) hacían
        // read-sum-save desprotegido y dos escrituras concurrentes para la
        // misma fecha perdían updates (totales ≠ ledger).
        return DB::transaction(function () use ($fecha, $userId) {
            $totales = CajaTotal::obtenerPorFecha($fecha);

            $totales = CajaTotal::lockForUpdate()->findOrFail($totales->id);

            // Calcular totales de ingresos del día
            $ingresosQuery = Ingreso::porFecha($fecha)->activos();

            $totales->total_ingresos_efectivo = (clone $ingresosQuery)->porMetodoPago('efectivo')->sum('importe');
            $totales->total_ingresos_transferencia = (clone $ingresosQuery)->porMetodoPago('transferencia')->sum('importe');
            $totales->total_ingresos_qr = (clone $ingresosQuery)->porMetodoPago('qr')->sum('importe');
            $totales->total_ingresos_otro = (clone $ingresosQuery)->porMetodoPago('otro')->sum('importe');
            $totales->total_ingresos = (clone $ingresosQuery)->sum('importe');
            $totales->cantidad_ingresos = (clone $ingresosQuery)->count();

            // Calcular totales de egresos del día
            $egresosDia = Egreso::porFecha($fecha)->activos()->get();
            $totales->total_egresos = $egresosDia->sum('importe');
            $totales->cantidad_egresos = $egresosDia->count();

            // Calcular balance neto
            $totales->balance_neto = $totales->total_ingresos - $totales->total_egresos;

            $totales->actualizado_por = $userId ?? Auth::id() ?? 1;
            $totales->save();

            return $totales;
        });
    }

    /**
     * Recalcular totales de un rango de fechas
     */
    public function calcularTotalesRango(string $fechaInicio, string $fechaFin): void
    {
        $fechaActual = $fechaInicio;
        $fin = \Carbon\Carbon::parse($fechaFin);
        $userId = Auth::id() ?? 1;

        while (\Carbon\Carbon::parse($fechaActual)->lte($fin)) {
            $this->calcularTotalesDia($fechaActual, $userId);
            $fechaActual = \Carbon\Carbon::parse($fechaActual)->addDay()->format('Y-m-d');
        }
    }

    /**
     * Obtener resumen mensual para dashboard
     */
    public function obtenerResumenMes(int $ano, int $mes): array
    {
        $totales = CajaTotal::totalesMes($ano, $mes);

        return [
            'ano' => $ano,
            'mes' => $mes,
            'mes_nombre' => \Carbon\Carbon::create($ano, $mes, 1)->locale('es')->monthName,
            'ingresos' => [
                'total' => $totales['total_ingresos'],
                'efectivo' => $totales['total_ingresos_efectivo'],
                'transferencia' => $totales['total_ingresos_transferencia'],
                'qr' => $totales['total_ingresos_qr'],
                'otro' => $totales['total_ingresos_otro'],
                'cantidad' => $totales['cantidad_ingresos'],
            ],
            'egresos' => [
                'total' => $totales['total_egresos'],
                'cantidad' => $totales['cantidad_egresos'],
            ],
            'balance_neto' => $totales['balance_neto'],
        ];
    }

    /**
     * Obtener balance actual (hoy)
     */
    public function obtenerBalanceActual(): float
    {
        return CajaTotal::balanceActual();
    }

    /**
     * Obtener acumulado hasta una fecha
     */
    public function obtenerAcumuladoHasta(string $fecha): array
    {
        return CajaTotal::acumuladoHasta($fecha);
    }

    /**
     * Obtener datos para el dashboard
     */
    public function obtenerDatosDashboard(): array
    {
        $hoy = now();
        $inicioMes = $hoy->copy()->startOfMonth()->format('Y-m-d');
        $finMes = $hoy->copy()->endOfMonth()->format('Y-m-d');

        // Totales del mes actual
        $resumenMes = $this->obtenerResumenMes($hoy->year, $hoy->month);

        // Totales de hoy
        $totalesHoy = CajaTotal::obtenerPorFecha($hoy->format('Y-m-d'));

        // Últimos 7 días
        $ultimos7Dias = CajaTotal::ultimosDias(7);

        // Últimos ingresos
        $ultimosIngresos = Ingreso::activos()
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Últimos egresos
        $ultimosEgresos = Egreso::activos()
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'hoy' => [
                'fecha' => $hoy->format('Y-m-d'),
                'total_ingresos' => (float) $totalesHoy->total_ingresos,
                'total_egresos' => (float) $totalesHoy->total_egresos,
                'balance_neto' => (float) $totalesHoy->balance_neto,
                'cantidad_ingresos' => $totalesHoy->cantidad_ingresos,
                'cantidad_egresos' => $totalesHoy->cantidad_egresos,
            ],
            'mes' => $resumenMes,
            'ultimos_7_dias' => $ultimos7Dias->map(function ($total) {
                return [
                    'fecha' => $total->fecha->format('Y-m-d'),
                    'ingresos' => (float) $total->total_ingresos,
                    'egresos' => (float) $total->total_egresos,
                    'balance' => (float) $total->balance_neto,
                ];
            })->toArray(),
            'ultimos_ingresos' => $ultimosIngresos->map(function ($ingreso) {
                return [
                    'id' => $ingreso->id,
                    'numero_recibo' => $ingreso->numero_recibo,
                    'fecha' => $ingreso->fecha->format('Y-m-d'),
                    'importe' => (float) $ingreso->importe,
                    'metodo_pago' => $ingreso->metodo_pago,
                    'categoria' => $ingreso->categoria?->nombre,
                    'categoria_color' => $ingreso->categoria?->color,
                ];
            })->toArray(),
            'ultimos_egresos' => $ultimosEgresos->map(function ($egreso) {
                return [
                    'id' => $egreso->id,
                    'numero_egreso' => $egreso->numero_egreso,
                    'fecha' => $egreso->fecha->format('Y-m-d'),
                    'concepto' => $egreso->concepto,
                    'importe' => (float) $egreso->importe,
                    'proveedor' => $egreso->proveedor,
                    'categoria' => $egreso->categoria?->nombre,
                    'categoria_color' => $egreso->categoria?->color,
                ];
            })->toArray(),
        ];
    }

    /**
     * Obtener resumen por métodos de pago para un período
     */
    public function obtenerResumenMetodosPago(string $fechaInicio, string $fechaFin): array
    {
        $totales = CajaTotal::whereBetween('fecha', [$fechaInicio, $fechaFin])->get();

        return [
            'periodo' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin,
            ],
            'metodos_pago' => [
                'efectivo' => [
                    'total' => (float) $totales->sum('total_ingresos_efectivo'),
                    'porcentaje' => $totales->sum('total_ingresos') > 0
                        ? round(($totales->sum('total_ingresos_efectivo') / $totales->sum('total_ingresos')) * 100, 2)
                        : 0,
                ],
                'transferencia' => [
                    'total' => (float) $totales->sum('total_ingresos_transferencia'),
                    'porcentaje' => $totales->sum('total_ingresos') > 0
                        ? round(($totales->sum('total_ingresos_transferencia') / $totales->sum('total_ingresos')) * 100, 2)
                        : 0,
                ],
                'qr' => [
                    'total' => (float) $totales->sum('total_ingresos_qr'),
                    'porcentaje' => $totales->sum('total_ingresos') > 0
                        ? round(($totales->sum('total_ingresos_qr') / $totales->sum('total_ingresos')) * 100, 2)
                        : 0,
                ],
                'otro' => [
                    'total' => (float) $totales->sum('total_ingresos_otro'),
                    'porcentaje' => $totales->sum('total_ingresos') > 0
                        ? round(($totales->sum('total_ingresos_otro') / $totales->sum('total_ingresos')) * 100, 2)
                        : 0,
                ],
            ],
            'total_general' => (float) $totales->sum('total_ingresos'),
        ];
    }

    /**
     * Recalcular todos los totales (operación masiva)
     */
    public function recalcularTodosLosTotales(): array
    {
        $fechaMinima = Ingreso::min('fecha') ?? Egreso::min('fecha') ?? now()->format('Y-m-d');
        $fechaMaxima = now()->format('Y-m-d');

        $inicio = \Carbon\Carbon::parse($fechaMinima);
        $fin = \Carbon\Carbon::parse($fechaMaxima);

        $fechasProcesadas = 0;
        $fechaActual = $inicio->copy();
        $userId = Auth::id() ?? 1; // Usar 1 como fallback si no hay usuario autenticado

        while ($fechaActual->lte($fin)) {
            $this->calcularTotalesDia($fechaActual->format('Y-m-d'), $userId);
            $fechasProcesadas++;
            $fechaActual->addDay();
        }

        return [
            'mensaje' => 'Totales recalculados exitosamente',
            'fechas_procesadas' => $fechasProcesadas,
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => $fin->format('Y-m-d'),
        ];
    }
}
