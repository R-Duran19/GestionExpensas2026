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
        $camposTexto = ['nombre', 'descripcion', 'icono'];

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
