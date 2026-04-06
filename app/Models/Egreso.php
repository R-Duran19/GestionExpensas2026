<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Egreso extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_egreso',
        'fecha',
        'categoria_egreso_id',
        'concepto',
        'numero_comprobante',
        'importe',
        'descripcion',
        'comprobante_foto',
        'proveedor',
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
     * Relación con la categoría de egreso
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaEgreso::class, 'categoria_egreso_id');
    }

    /**
     * Relación con el usuario que creó el egreso
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación con el usuario que actualizó el egreso
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Scope para filtrar egresos activos
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
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_egreso_id', $categoriaId);
    }

    /**
     * Scope para filtrar por proveedor
     */
    public function scopePorProveedor($query, $proveedor)
    {
        return $query->where('proveedor', 'like', "%{$proveedor}%");
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
     * Verifica si el egreso tiene comprobante
     */
    public function tieneComprobante(): bool
    {
        return !empty($this->comprobante_foto) && Storage::exists($this->comprobante_foto);
    }

    /**
     * Obtiene el número de egreso formateado
     */
    public function getNumeroEgresoFormateadoAttribute(): string
    {
        return strtoupper($this->numero_egreso);
    }
}
