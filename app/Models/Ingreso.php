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
     * Boot del modelo para limpiar caracteres UTF-8
     */
    protected static function boot()
    {
        parent::boot();

        static::retrieved(function ($model) {
            $model->limpiarUtf8();
        });
    }

    /**
     * Limpiar caracteres UTF-8 inválidos del modelo
     */
    protected function limpiarUtf8()
    {
        $camposTexto = ['descripcion', 'referencia_bancaria', 'observaciones'];

        foreach ($camposTexto as $campo) {
            if (!empty($this->{$campo}) && is_string($this->{$campo})) {
                $this->{$campo} = $this->limpiarString($this->{$campo});
            }
        }
    }

    /**
     * Limpiar caracteres UTF-8 inválidos de un string
     */
    protected function limpiarString($string)
    {
        if (empty($string)) {
            return $string;
        }

        // Convertir a UTF-8 válido
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remover caracteres UTF-8 inválidos
        $string = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $string);

        // Remover caracteres de control problemáticos
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        return $string;
    }

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
