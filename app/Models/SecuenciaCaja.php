<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SecuenciaCaja extends Model
{
    use HasFactory;

    protected $table = 'secuencias_caja';

    protected $fillable = [
        'tipo',
        'ano',
        'mes',
        'ultimo_numero',
        'actualizado_por',
    ];

    protected $casts = [
        'ano' => 'integer',
        'mes' => 'integer',
        'ultimo_numero' => 'integer',
    ];

    /**
     * Relación con el usuario que actualizó la secuencia
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Scope para secuencias de ingresos
     */
    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'ingreso');
    }

    /**
     * Scope para secuencias de egresos
     */
    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'egreso');
    }

    /**
     * Obtiene o crea la secuencia para ingresos de un mes/año específico
     */
    public static function obtenerIngreso(int $ano, int $mes): self
    {
        return self::firstOrCreate(
            [
                'tipo' => 'ingreso',
                'ano' => $ano,
                'mes' => $mes,
            ],
            [
                'ultimo_numero' => 0,
                'actualizado_por' => auth()->id(),
            ]
        );
    }

    /**
     * Obtiene o crea la secuencia para egresos
     */
    public static function obtenerEgreso(): self
    {
        return self::firstOrCreate(
            [
                'tipo' => 'egreso',
                'ano' => null,
                'mes' => null,
            ],
            [
                'ultimo_numero' => 0,
                'actualizado_por' => auth()->id(),
            ]
        );
    }

    /**
     * Incrementa el número de secuencia usando lock para evitar race conditions
     */
    public function incrementar(int $userId): int
    {
        return DB::transaction(function () use ($userId) {
            // [REMEDIACIÓN-2.7] Lock por CLAVE (tipo,año,mes): el `$this
            // ->lockForUpdate()->first()` original (Model::__call → builder
            // sin restricciones) bloqueaba e incrementaba la PRIMERA fila de
            // la tabla, compartiendo un solo contador entre todas las
            // secuencias y todos los meses.
            $secuencia = static::query()
                ->where('tipo', $this->tipo)
                ->where('ano', $this->ano)
                ->where('mes', $this->mes)
                ->lockForUpdate()
                ->first();

            if (!$secuencia) {
                $secuencia = $this;
            }

            $secuencia->ultimo_numero += 1;
            $secuencia->actualizado_por = $userId;
            $secuencia->save();

            return $secuencia->ultimo_numero;
        });
    }

    /**
     * Genera el número de recibo formateado para ingresos
     * Formato: REC-YY-MM-XXXXXXX
     */
    public static function generarNumeroRecibo(int $ano, int $mes): string
    {
        $secuencia = self::obtenerIngreso($ano, $mes);
        $numero = $secuencia->incrementar(auth()->id());

        $anoShort = substr((string) $ano, -2);
        $mesFormatted = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $numeroFormatted = str_pad((string) $numero, 7, '0', STR_PAD_LEFT);

        return "REC-{$anoShort}-{$mesFormatted}-{$numeroFormatted}";
    }

    /**
     * Genera el número de egreso formateado
     * Formato: EGR-XXXXXX
     */
    public static function generarNumeroEgreso(): string
    {
        $secuencia = self::obtenerEgreso();
        $numero = $secuencia->incrementar(auth()->id());

        $numeroFormatted = str_pad((string) $numero, 6, '0', STR_PAD_LEFT);

        return "EGR-{$numeroFormatted}";
    }
}
