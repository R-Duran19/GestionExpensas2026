<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Ingreso extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_recibo',
        'fecha',
        'categoria_ingreso_id',
        'tipo_ingreso',
        'importe',
        'monto_total',
        'metodo_pago',
        'descripcion',
        'comprobante_foto',
        'referencia_bancaria',
        'unidad_id',
        'observaciones',
        'activo',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'importe' => 'decimal:2',
        'monto_total' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * Relación con la categoría de ingreso
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaIngreso::class, 'categoria_ingreso_id');
    }

    /**
     * Relación con la unidad (opcional)
     */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
    }

    /**
     * Relación con los detalles del ingreso (cabecera-detalle)
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(IngresoDetalle::class, 'ingreso_id');
    }

    /**
     * Relación con los saldos a favor generados
     */
    public function saldosFavor(): HasMany
    {
        return $this->hasMany(SaldoAFavor::class, 'ingreso_id');
    }

    /**
     * Relación con el usuario que creó el ingreso
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación con el usuario que actualizó el ingreso
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Scope para ingresos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para pagos de expensas
     */
    public function scopePagosExpensas($query)
    {
        return $query->where('tipo_ingreso', 'expensa');
    }

    /**
     * Scope para ingresos extraordinarios
     */
    public function scopeExtraordinarios($query)
    {
        return $query->where('tipo_ingreso', 'extraordinario');
    }

    /**
     * Scope para filtrar por fecha
     */
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope para filtrar por método de pago
     */
    public function scopePorMetodoPago($query, $metodoPago)
    {
        return $query->where('metodo_pago', $metodoPago);
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_ingreso_id', $categoriaId);
    }

    /**
     * Scope para filtrar por unidad
     */
    public function scopePorUnidad($query, $unidadId)
    {
        return $query->where('unidad_id', $unidadId);
    }

    /**
     * Accessor para obtener la URL completa del comprobante
     */
    public function getComprobanteFotoUrlAttribute()
    {
        if ($this->comprobante_foto) {
            return Storage::url($this->comprobante_foto);
        }
        return null;
    }

    /**
     * Verifica si el ingreso tiene comprobante
     */
    public function tieneComprobante(): bool
    {
        return !empty($this->comprobante_foto) && Storage::exists($this->comprobante_foto);
    }

    /**
     * Obtiene el método de pago formateado para mostrar
     */
    public function getMetodoPagoFormateadoAttribute(): string
    {
        return match($this->metodo_pago) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia/Depósito',
            'qr' => 'QR',
            'otro' => 'Otro',
            default => ucfirst($this->metodo_pago),
        };
    }

    /**
     * Verificar si es pago de expensa
     */
    public function esPagoExpensa(): bool
    {
        return $this->tipo_ingreso === 'expensa';
    }

    /**
     * Verificar si es ingreso extraordinario
     */
    public function esExtraordinario(): bool
    {
        return $this->tipo_ingreso === 'extraordinario';
    }

    /**
     * Obtener el tipo de ingreso formateado
     */
    public function getTipoIngresoFormateadoAttribute(): string
    {
        return match($this->tipo_ingreso) {
            'expensa' => 'Pago de Expensa',
            'extraordinario' => 'Ingreso Extraordinario',
            default => ucfirst($this->tipo_ingreso),
        };
    }

    /**
     * Obtener las unidades involucradas en los detalles (para pagos de expensa)
     */
    public function getUnidadesAttribute()
    {
        return $this->detalles->map(fn($d) => $d->expensa->unidad)->unique('id');
    }

    /**
     * Obtener el total de detalles
     */
    public function getTotalDetallesAttribute(): int
    {
        return $this->detalles->count();
    }

    /**
     * Obtener el monto total aplicado en detalles
     */
    public function getMontoTotalAplicadoAttribute(): float
    {
        return (float) $this->detalles->sum('monto_aplicado');
    }

    /**
     * Verificar si el ingreso tiene detalles
     */
    public function tieneDetalles(): bool
    {
        return $this->detalles->isNotEmpty();
    }

    /**
     * Verificar si generó saldos a favor
     */
    public function tieneSaldosAFavor(): bool
    {
        return $this->saldosFavor->isNotEmpty();
    }

    /**
     * Obtener el total de saldos a favor generados
     */
    public function getTotalSaldosAFavorAttribute(): float
    {
        return (float) $this->saldosFavor->sum('monto_saldo');
    }
}
