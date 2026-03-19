<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpensaTotal extends Model
{
    use HasFactory;

    protected $table = 'expensa_total';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'periodo_id',
        'propietario_id',
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
        'monto_total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
        'saldo_a_favor' => 'decimal:2',
    ];

    /**
     * Get the periodo for this expensa total.
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoExpensa::class, 'periodo_id');
    }

    /**
     * Get the propietario for this expensa total.
     */
    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'propietario_id');
    }

    /**
     * Get the pagos for this expensa total.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'expensa_total_id');
    }

    /**
     * Scope to get expensas totales by estado.
     */
    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope to get only pending expensas totales.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope to get only partially paid expensas totales.
     */
    public function scopeParciales($query)
    {
        return $query->where('estado', 'parcial');
    }

    /**
     * Scope to get only paid expensas totales.
     */
    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagado');
    }

    /**
     * Obtener las expensas individuales del propietario para este período
     */
    public function getExpensasAttribute()
    {
        $unidadIds = $this->propietario->unidades->pluck('id');
        return Expensa::where('periodo_id', $this->periodo_id)
            ->whereIn('unidad_id', $unidadIds)
            ->get();
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
