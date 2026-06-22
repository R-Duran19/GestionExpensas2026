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
     * Nombre de la tabla (Laravel no pluraliza correctamente español)
     */
    protected $table = 'expensas';

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
        'expensa_pendiente',
        'agua_pendiente',
        'periodo_cerrado',
        'monto_total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monto_expensa' => 'decimal:2',
        'monto_agua' => 'decimal:2',
        'expensa_pendiente' => 'decimal:2',
        'agua_pendiente' => 'decimal:2',
        'periodo_cerrado' => 'boolean',
        'monto_total' => 'decimal:2',
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
     * Get the boleta for this expensa.
     */
    public function boleta()
    {
        return $this->hasOne(Boleta::class, 'expensa_id');
    }

    /**
     * Get the detalles de pago (ingreso_detalles) for this expensa.
     */
    public function detallesPago(): HasMany
    {
        return $this->hasMany(IngresoDetalle::class, 'expensa_id');
    }

    /**
     * Get the ingresos (pagos) for this expensa (legacy compatibility).
     */
    public function ingresos()
    {
        return $this->detallesPago()->with('ingreso');
    }

    /**
     * Scope para expensas con deuda
     */
    public function scopeConDeuda($query)
    {
        return $query->where(function($q) {
            $q->where('expensa_pendiente', '>', 0)
              ->orWhere('agua_pendiente', '>', 0);
        });
    }

    /**
     * Scope para expensas pagadas (sin deuda)
     */
    public function scopePagadas($query)
    {
        return $query->where('expensa_pendiente', 0)
                     ->where('agua_pendiente', 0);
    }

    /**
     * Scope para expensas con pago parcial
     */
    public function scopeParciales($query)
    {
        return $query->where(function($q) {
            $q->where('expensa_pendiente', '>', 0)
              ->orWhere('agua_pendiente', '>', 0);
        })->where(function($q) {
            $q->where('expensa_pendiente', '<', \DB::raw('monto_expensa'))
              ->orWhere('agua_pendiente', '<', \DB::raw('monto_agua'));
        });
    }

    /**
     * Scope para periodos cerrados
     */
    public function scopePeriodoCerrado($query)
    {
        return $query->where('periodo_cerrado', true);
    }

    /**
     * Scope para periodos abiertos
     */
    public function scopePeriodoAbierto($query)
    {
        return $query->where('periodo_cerrado', false);
    }

    /**
     * Obtener propietario actual (a través de unidad)
     */
    public function getPropietarioActualAttribute()
    {
        return $this->unidad?->propietarioActual;
    }

    /**
     * Verificar si está totalmente pagada
     */
    public function estaPagada(): bool
    {
        return $this->expensa_pendiente == 0 && $this->agua_pendiente == 0;
    }

    /**
     * Verificar si tiene deuda pendiente
     */
    public function tieneDeuda(): bool
    {
        return $this->expensa_pendiente > 0 || $this->agua_pendiente > 0;
    }

    /**
     * Verificar si tiene deuda de expensa
     */
    public function tieneDeudaExpensa(): bool
    {
        return $this->expensa_pendiente > 0;
    }

    /**
     * Verificar si tiene deuda de agua
     */
    public function tieneDeudaAgua(): bool
    {
        return $this->agua_pendiente > 0;
    }

    /**
     * Obtener total pendiente
     */
    public function getTotalPendienteAttribute(): float
    {
        return (float) $this->expensa_pendiente + (float) $this->agua_pendiente;
    }

    /**
     * Obtener total pagado (monto de emisión - pendiente)
     */
    public function getTotalPagadoAttribute(): float
    {
        return (float) $this->monto_total - (float) $this->total_pendiente;
    }

    /**
     * Get estado label (basado en saldos pendientes)
     */
    public function getEstadoLabelAttribute(): string
    {
        if ($this->estaPagada()) {
            return 'Pagado';
        } elseif ($this->tieneDeuda()) {
            return 'Pendiente';
        } else {
            return 'Parcial';
        }
    }

    /**
     * Get estado color for UI
     */
    public function getEstadoColorAttribute(): string
    {
        if ($this->estaPagada()) {
            return 'green';
        } elseif ($this->tieneDeuda()) {
            return 'red';
        } else {
            return 'yellow';
        }
    }

    /**
     * Obtener el porcentaje pagado de expensa
     */
    public function getPorcentajePagadoExpensaAttribute(): float
    {
        if ($this->monto_expensa == 0) {
            return 0;
        }

        $pagado = $this->monto_expensa - $this->expensa_pendiente;
        return ($pagado / $this->monto_expensa) * 100;
    }

    /**
     * Obtener el porcentaje pagado de agua
     */
    public function getPorcentajePagadoAguaAttribute(): float
    {
        if ($this->monto_agua == 0) {
            return 100; // Si no tiene agua, se considera 100%
        }

        $pagado = $this->monto_agua - $this->agua_pendiente;
        return ($pagado / $this->monto_agua) * 100;
    }

    /**
     * Obtener el porcentaje total pagado
     */
    public function getPorcentajePagadoTotalAttribute(): float
    {
        if ($this->monto_total == 0) {
            return 0;
        }

        return ($this->total_pagado / $this->monto_total) * 100;
    }
}
