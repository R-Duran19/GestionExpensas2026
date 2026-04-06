<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaIngresoHistorial extends Model
{
    use HasFactory;

    protected $table = 'categorias_ingresos_historial';

    protected $fillable = [
        'categoria_ingreso_id',
        'nombre_anterior',
        'nombre_nuevo',
        'descripcion_anterior',
        'descripcion_nueva',
        'color_anterior',
        'color_nuevo',
        'motivo',
        'cambiado_por',
    ];

    protected $casts = [
        'categoria_ingreso_id' => 'integer',
        'cambiado_por' => 'integer',
    ];

    /**
     * Relación con la categoría de ingreso
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaIngreso::class, 'categoria_ingreso_id');
    }

    /**
     * Relación con el usuario que hizo el cambio
     */
    public function cambiadoPor()
    {
        return $this->belongsTo(User::class, 'cambiado_por');
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_ingreso_id', $categoriaId);
    }

    /**
     * Scope para obtener los cambios más recientes primero
     */
    public function scopeRecientes($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
