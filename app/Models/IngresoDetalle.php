<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngresoDetalle extends Model
{
    protected $table = 'ingreso_detalles';

    protected $fillable = [
        'ingreso_id',
        'expensa_id',
        'concepto',
        'monto_aplicado',
        'monto_expensa_aplicado',
        'monto_agua_aplicado',
    ];

    protected $casts = [
        'monto_aplicado' => 'decimal:2',
        'monto_expensa_aplicado' => 'decimal:2',
        'monto_agua_aplicado' => 'decimal:2',
    ];

    /**
     * Relación con el ingreso cabecera
     * Incluye soft-deleted para mostrar historial completo
     */
    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id')->withTrashed();
    }

    /**
     * Relación con la expensa
     */
    public function expensa(): BelongsTo
    {
        return $this->belongsTo(Expensa::class, 'expensa_id');
    }

    /**
     * Scope para filtrar por concepto
     */
    public function scopePorConcepto($query, string $concepto)
    {
        return $query->where('concepto', $concepto);
    }

    /**
     * Scope para detalles de expensa
     */
    public function scopeDeExpensa($query, int $expensaId)
    {
        return $query->where('expensa_id', $expensaId);
    }

    /**
     * Scope para detalles de un ingreso
     */
    public function scopeDeIngreso($query, int $ingresoId)
    {
        return $query->where('ingreso_id', $ingresoId);
    }

    /**
     * Verificar si el pago es solo de expensa
     */
    public function esSoloExpensa(): bool
    {
        return $this->concepto === 'expensa';
    }

    /**
     * Verificar si el pago es solo de agua
     */
    public function esSoloAgua(): bool
    {
        return $this->concepto === 'agua';
    }

    /**
     * Verificar si el pago es de ambos conceptos
     */
    public function esAmbos(): bool
    {
        return $this->concepto === 'ambos';
    }

    /**
     * Obtener el total aplicado (suma de conceptos)
     */
    public function getTotalAplicadoAttribute(): float
    {
        return (float) $this->monto_expensa_aplicado + (float) $this->monto_agua_aplicado;
    }
}
