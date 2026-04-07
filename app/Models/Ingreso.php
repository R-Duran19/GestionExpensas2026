<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Ingreso extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_recibo',
        'fecha',
        'categoria_ingreso_id',
        'importe',
        'metodo_pago',
        'descripcion',
        'comprobante_foto',
        'referencia_bancaria',
        'unidad_id',
        'expensa_total_id',
        'propietario_id',
        'observaciones',
        'activo',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'importe' => 'decimal:2',
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
     * Relación con expensa_total (para pagos de expensas)
     */
    public function expensaTotal(): BelongsTo
    {
        return $this->belongsTo(ExpensaTotal::class, 'expensa_total_id');
    }

    /**
     * Relación con propietario (para pagos de expensas)
     */
    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'propietario_id');
    }

    /**
     * Scope para filtrar pagos de expensas
     */
    public function scopePagosExpensas($query)
    {
        return $query->whereNotNull('expensa_total_id');
    }

    /**
     * Scope para filtrar por propietario
     */
    public function scopePorPropietario($query, $propietarioId)
    {
        return $query->where('propietario_id', $propietarioId);
    }

    /**
     * Relación con el usuario que creó el ingreso
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación con el usuario que actualizó el ingreso
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Scope para filtrar ingresos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar por fecha específica
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
}
