<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriaIngreso extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categorias_ingresos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'color',
        'icono',
        'activo',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con los ingresos que usan esta categoría
     */
    public function ingresos()
    {
        return $this->hasMany(Ingreso::class, 'categoria_ingreso_id');
    }

    /**
     * Relación con el historial de cambios
     */
    public function historial()
    {
        return $this->hasMany(CategoriaIngresoHistorial::class, 'categoria_ingreso_id');
    }

    /**
     * Relación con el usuario que creó la categoría
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación con el usuario que actualizó la categoría
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Scope para filtrar categorías activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Verifica si la categoría tiene ingresos asociados
     */
    public function tieneIngresos(): bool
    {
        return $this->ingresos()->withTrashed()->count() > 0;
    }

    /**
     * Obtiene el conteo de ingresos activos
     */
    public function conteoIngresosActivos(): int
    {
        return $this->ingresos()->activos()->count();
    }

    /**
     * Obtiene el total de importes de ingresos activos
     */
    public function totalIngresosActivos(): float
    {
        return (float) $this->ingresos()->activos()->sum('importe');
    }
}
