<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Qr extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'alias',
        'estado',
        'monto',
        'propietario_id',
        'expensa_id',
        'pago_id',
        'fecha_vencimiento',
        'detalle_glosa',
        'imagen_qr',
        'creado_por',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'datetime',
        'imagen_qr' => 'binary',
    ];

    /**
     * Get the propietario for this QR.
     */
    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'propietario_id');
    }

    /**
     * Get the expensa for this QR.
     */
    public function expensa(): BelongsTo
    {
        return $this->belongsTo(Expensa::class, 'expensa_id');
    }

    /**
     * Get the pago for this QR.
     */
    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    /**
     * Get the user that created the QR.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Scope to get QRs by estado.
     */
    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope to get only pending QRs.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'PENDIENTE');
    }

    /**
     * Scope to get only paid QRs.
     */
    public function scopePagados($query)
    {
        return $query->where('estado', 'PAGADO');
    }

    /**
     * Get estado label.
     */
    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            'PENDIENTE' => 'Pendiente',
            'PAGADO' => 'Pagado',
            'CANCELADO' => 'Cancelado',
            default => 'Desconocido',
        };
    }

    /**
     * Check if QR is expired.
     */
    public function isExpired(): bool
    {
        return $this->fecha_vencimiento->isPast() && $this->estado === 'PENDIENTE';
    }
}
