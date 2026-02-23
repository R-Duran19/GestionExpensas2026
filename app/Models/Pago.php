<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'expensa_id',
        'unidad_id',
        'monto',
        'modalidad',
        'fecha_pago',
        'comprobante_url',
        'estado',
        'verificado_por',
        'verificado_en',
        'referencia_bancaria',
        'observaciones',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'date',
        'verificado_en' => 'datetime',
    ];

    /**
     * Get the expensa for this pago.
     */
    public function expensa(): BelongsTo
    {
        return $this->belongsTo(Expensa::class, 'expensa_id');
    }

    /**
     * Get the unidad for this pago.
     */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    /**
     * Get the user that verified the pago.
     */
    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    /**
     * Get the QR for this pago (if applicable).
     */
    public function qr()
    {
        return $this->hasOne(Qr::class, 'pago_id');
    }

    /**
     * Scope to get pagos by estado.
     */
    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope to get only pending pagos.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope to get only verified pagos.
     */
    public function scopeVerificados($query)
    {
        return $query->where('estado', 'verificado');
    }

    /**
     * Get estado label.
     */
    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            'pendiente' => 'Pendiente',
            'verificado' => 'Verificado',
            'rechazado' => 'Rechazado',
            default => 'Desconocido',
        };
    }

    /**
     * Get modalidad label.
     */
    public function getModalidadLabelAttribute(): string
    {
        return match($this->modalidad) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'qr' => 'QR',
            default => 'Desconocido',
        };
    }
}
