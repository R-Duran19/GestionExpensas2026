<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriaEgreso extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categorias_egresos';

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
     * Relación con los egresos que usan esta categoría
     */
    public function egresos()
    {
        return $this->hasMany(Egreso::class, 'categoria_egreso_id');
    }

    /**
     * Relación con el historial de cambios
     */
    public function historial()
    {
        return $this->hasMany(CategoriaEgresoHistorial::class, 'categoria_egreso_id');
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
     * Verifica si la categoría tiene egresos asociados
     */
    public function tieneEgresos(): bool
    {
        return $this->egresos()->withTrashed()->count() > 0;
    }

    /**
     * Obtiene el conteo de egresos activos
     */
    public function conteoEgresosActivos(): int
    {
        return $this->egresos()->activos()->count();
    }

    /**
     * Obtiene el total de importes de egresos activos
     */
    public function totalEgresosActivos(): float
    {
        return (float) $this->egresos()->activos()->sum('importe');
    }
}
