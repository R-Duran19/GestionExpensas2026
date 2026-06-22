<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaldoAFavor extends Model
{
    use SoftDeletes;

    protected $table = 'saldos_a_favor';

    protected $fillable = [
        'unidad_id',
        'ingreso_id',
        'monto_original',
        'monto_aplicado',
        'monto_saldo',
        'estado',
        'consumido_totalmente_en',
        'creado_por',
    ];

    protected $casts = [
        'monto_original' => 'decimal:2',
        'monto_aplicado' => 'decimal:2',
        'monto_saldo' => 'decimal:2',
        'consumido_totalmente_en' => 'datetime',
    ];

    /**
     * Relación con la unidad
     */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    /**
     * Relación con el ingreso original
     * Incluye soft-deleted para mantener el historial
     */
    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id')->withTrashed();
    }

    /**
     * Relación con el usuario que creó el registro
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Scope para saldos disponibles
     */
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'disponible')
                     ->where('monto_saldo', '>', 0);
    }

    /**
     * Scope para saldos de una unidad
     */
    public function scopeDeUnidad($query, int $unidadId)
    {
        return $query->where('unidad_id', $unidadId);
    }

    /**
     * Scope para saldos consumidos
     */
    public function scopeConsumidos($query)
    {
        return $query->where('estado', 'consumido');
    }

    /**
     * Scope para saldos parcialmente consumidos
     */
    public function scopeParcialmenteConsumidos($query)
    {
        return $query->where('estado', 'parcialmente_consumido');
    }

    /**
     * Marcar como consumido
     */
    public function marcarComoConsumido(): void
    {
        $this->update([
            'estado' => 'consumido',
            'monto_saldo' => 0,
            'monto_aplicado' => $this->monto_original,
            'consumido_totalmente_en' => now(),
        ]);
    }

    /**
     * Aplicar parcialmente el saldo
     */
    public function aplicarParcialmente(float $monto): void
    {
        $nuevoSaldo = $this->monto_saldo - $monto;
        $nuevoMontoAplicado = $this->monto_aplicado + $monto;

        $this->update([
            'monto_saldo' => max(0, $nuevoSaldo),
            'monto_aplicado' => $nuevoMontoAplicado,
            'estado' => $nuevoSaldo <= 0 ? 'consumido' : 'parcialmente_consumido',
            'consumido_totalmente_en' => $nuevoSaldo <= 0 ? now() : null,
        ]);
    }

    /**
     * Verificar si está disponible
     */
    public function estaDisponible(): bool
    {
        return $this->estado === 'disponible' && $this->monto_saldo > 0;
    }

    /**
     * Verificar si está consumido
     */
    public function estaConsumido(): bool
    {
        return $this->estado === 'consumido';
    }

    /**
     * Verificar si está parcialmente consumido
     */
    public function estaParcialmenteConsumido(): bool
    {
        return $this->estado === 'parcialmente_consumido';
    }

    /**
     * Obtener el porcentaje consumido
     */
    public function getPorcentajeConsumidoAttribute(): float
    {
        if ($this->monto_original == 0) {
            return 0;
        }

        return ($this->monto_aplicado / $this->monto_original) * 100;
    }

    /**
     * Obtener el porcentaje disponible
     */
    public function getPorcentajeDisponibleAttribute(): float
    {
        return 100 - $this->porcentaje_consumido;
    }
}
