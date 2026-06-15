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
        'deuda_anterior',
        'monto_total',
        'estado',
        'monto_pagado',
        'monto_pendiente',
        'fecha_pago',
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
        'fecha_pago' => 'date',
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
     * Get the ingresos (pagos) for this expensa.
     */
    public function ingresos()
    {
        return $this->hasMany(Ingreso::class, 'expensa_id');
    }

    /**
     * Scope para filtrar expensas pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para filtrar expensas parciales
     */
    public function scopeParciales($query)
    {
        return $query->where('estado', 'parcial');
    }

    /**
     * Scope para filtrar expensas pagadas
     */
    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagado');
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Verificar si la expensa está pagada
     */
    public function estaPagada(): bool
    {
        return $this->estado === 'pagado';
    }

    /**
     * Verificar si la expensa tiene pago parcial
     */
    public function tienePagoParcial(): bool
    {
        return $this->estado === 'parcial';
    }

    /**
     * Verificar si la expensa está pendiente
     */
    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Get estado label
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
     * Get estado color for UI
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

    /**
     * Registrar un pago a esta expensa
     */
    public function registrarPago(float $monto, string $numeroRecibo, string $metodoPago = 'efectivo', string $referenciaBancaria = null)
    {
        // Crear el registro de ingreso
        $ingreso = Ingreso::create([
            'numero_recibo' => $numeroRecibo,
            'fecha' => now(),
            'categoria_ingreso_id' => 1, // "Expensas"
            'importe' => $monto,
            'metodo_pago' => $metodoPago,
            'unidad_id' => $this->unidad_id,
            'expensa_id' => $this->id,
            'creado_por' => 1,
        ]);

        // Actualizar el estado de la expensa
        $nuevoMontoPagado = $this->monto_pagado + $monto;
        $nuevoMontoPendiente = $this->monto_total - $nuevoMontoPagado;

        if ($nuevoMontoPendiente <= 0) {
            // Pago completo
            $this->update([
                'estado' => 'pagado',
                'monto_pagado' => $this->monto_total,
                'monto_pendiente' => 0,
                'fecha_pago' => now(),
            ]);
        } elseif ($nuevoMontoPagado > 0) {
            // Pago parcial
            $this->update([
                'estado' => 'parcial',
                'monto_pagado' => $nuevoMontoPagado,
                'monto_pendiente' => $nuevoMontoPendiente,
                'fecha_pago' => now(),
            ]);
        }

        return $ingreso;
    }
}
