<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expensa extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'periodo_id',
        'unidad_id',
        'monto_expensa',
        'monto_agua',
        'deuda_anterior',
        'monto_total',
        'monto_pagado',
        'monto_pendiente',
        'saldo_a_favor',
        'estado',
        'observaciones',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monto_expensa' => 'decimal:2',
        'monto_agua' => 'decimal:2',
        'deuda_anterior' => 'decimal:2',
        'monto_total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
        'saldo_a_favor' => 'decimal:2',
    ];

    /**
     * Get the periodo for this expensa.
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoExpensa::class, 'periodo_id');
    }

    /**
     * Get the unidad for this expensa.
     */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    /**
     * Get the pagos for this expensa.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'expensa_id');
    }

    /**
     * Get the boleta for this expensa.
     */
    public function boleta()
    {
        return $this->hasOne(Boleta::class, 'expensa_id');
    }

    /**
     * Scope to get expensas by estado.
     */
    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope to get only pending expensas.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope to get only partially paid expensas.
     */
    public function scopeParciales($query)
    {
        return $query->where('estado', 'parcial');
    }

    /**
     * Scope to get only paid expensas.
     */
    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagado');
    }

    /**
     * Get estado label.
     */
    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            'pendiente' => 'Pendiente',
            'parcial' => 'Parcial',
            'pagado' => 'Pagado',
            default => 'Desconocido',
        };
    }

    /**
     * Get estado color for UI.
     */
    public function getEstadoColorAttribute(): string
    {
        return match($this->estado) {
            'pendiente' => 'red',
            'parcial' => 'yellow',
            'pagado' => 'green',
            default => 'gray',
        };
    }
}
