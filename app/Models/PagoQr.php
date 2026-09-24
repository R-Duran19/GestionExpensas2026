<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [PORTAL QR / Fase 4.1] Intención de pago con QR bancario.
 *
 * Reglas de consistencia (Plan 12):
 * - 1 QR activo por usuario (regla garantizada en PagoQrService).
 * - El monto es SIEMPRE el elegido por el usuario; la acreditación usa el
 *   FIFO de PagoExpensaService y el excedente cae a SaldoAFavor.
 * - Logout NO invalida el QR: vive server-side hasta vencer/pagarse.
 */
class PagoQr extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PAGADO = 'pagado';
    public const ESTADO_EXPIRADO = 'expirado';
    public const ESTADO_CANCELADO = 'cancelado';
    public const ESTADO_DIVERGENTE = 'pagado_monto_divergente';

    protected $table = 'pagos_qr';

    protected $fillable = [
        'user_id',
        'pagador_tipo',
        'pagador_id',
        'unidad_id',
        'alias',
        'monto',
        'moneda',
        'seleccion',
        'glosa',
        'estado',
        'fecha_vencimiento',
        'imagen_qr',
        'numero_orden',
        'nombre_cliente',
        'documento_cliente',
        'fecha_proceso',
        'ingreso_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'datetime',
        'fecha_proceso' => 'datetime',
        'seleccion' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
    }

    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(Ingreso::class);
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function estaVencido(): bool
    {
        return $this->fecha_vencimiento->isPast() && $this->estaPendiente();
    }

    /**
     * Scope: QRs activos (pendientes no vencidos).
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE)
                     ->where('fecha_vencimiento', '>', now());
    }
}
