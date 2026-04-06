<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CajaTotal extends Model
{
    use HasFactory;

    protected $table = 'caja_totales';

    protected $fillable = [
        'fecha',
        'total_ingresos_efectivo',
        'total_ingresos_transferencia',
        'total_ingresos_qr',
        'total_ingresos_otro',
        'total_ingresos',
        'total_egresos',
        'balance_neto',
        'cantidad_ingresos',
        'cantidad_egresos',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total_ingresos_efectivo' => 'decimal:2',
        'total_ingresos_transferencia' => 'decimal:2',
        'total_ingresos_qr' => 'decimal:2',
        'total_ingresos_otro' => 'decimal:2',
        'total_ingresos' => 'decimal:2',
        'total_egresos' => 'decimal:2',
        'balance_neto' => 'decimal:2',
        'cantidad_ingresos' => 'integer',
        'cantidad_egresos' => 'integer',
    ];

    /**
     * Relación con el usuario que actualizó los totales
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Obtiene o crea los totales de una fecha específica
     */
    public static function obtenerPorFecha($fecha): self
    {
        return self::firstOrCreate(
            ['fecha' => $fecha],
            [
                'total_ingresos_efectivo' => 0,
                'total_ingresos_transferencia' => 0,
                'total_ingresos_qr' => 0,
                'total_ingresos_otro' => 0,
                'total_ingresos' => 0,
                'total_egresos' => 0,
                'balance_neto' => 0,
                'cantidad_ingresos' => 0,
                'cantidad_egresos' => 0,
                'actualizado_por' => auth()->id(),
            ]
        );
    }

    /**
     * Obtiene el balance actual (hoy)
     */
    public static function balanceActual(): float
    {
        $hoy = now()->format('Y-m-d');
        $totales = self::obtenerPorFecha($hoy);
        return (float) $totales->balance_neto;
    }

    /**
     * Obtiene los totales acumulados hasta una fecha
     */
    public static function acumuladoHasta($fecha): array
    {
        $totales = self::where('fecha', '<=', $fecha)->get();

        return [
            'total_ingresos' => $totales->sum('total_ingresos'),
            'total_egresos' => $totales->sum('total_egresos'),
            'balance_neto' => $totales->sum('balance_neto'),
            'cantidad_ingresos' => $totales->sum('cantidad_ingresos'),
            'cantidad_egresos' => $totales->sum('cantidad_egresos'),
        ];
    }

    /**
     * Obtiene los totales de un mes específico
     */
    public static function totalesMes(int $ano, int $mes): array
    {
        $totales = self::whereYear('fecha', $ano)
            ->whereMonth('fecha', $mes)
            ->get();

        return [
            'total_ingresos' => $totales->sum('total_ingresos'),
            'total_egresos' => $totales->sum('total_egresos'),
            'balance_neto' => $totales->sum('balance_neto'),
            'cantidad_ingresos' => $totales->sum('cantidad_ingresos'),
            'cantidad_egresos' => $totales->sum('cantidad_egresos'),
            'total_ingresos_efectivo' => $totales->sum('total_ingresos_efectivo'),
            'total_ingresos_transferencia' => $totales->sum('total_ingresos_transferencia'),
            'total_ingresos_qr' => $totales->sum('total_ingresos_qr'),
            'total_ingresos_otro' => $totales->sum('total_ingresos_otro'),
        ];
    }

    /**
     * Obtiene los totales de los últimos N días
     */
    public static function ultimosDias(int $dias): \Illuminate\Database\Eloquent\Collection
    {
        $fechaInicio = now()->subDays($dias - 1)->format('Y-m-d');
        $fechaFin = now()->format('Y-m-d');

        return self::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha')
            ->get();
    }
}
