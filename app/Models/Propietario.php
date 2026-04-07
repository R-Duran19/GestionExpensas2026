<?php

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Propietario extends Model
{
    use HasFactory, SoftDeletes, Loggable;

    /**
     * Nombre de la tabla (Laravel no pluraliza correctamente español)
     */
    protected $table = 'propietarios';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'ci',
        'telefono',
        'email',
        'activo',
        'observaciones',
    ];

    /**
     * Los atributos que deben ser casteados.
     */
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
        $camposTexto = ['nombre', 'apellido', 'email', 'observaciones'];

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
     * Accesores y Mutators
     */

    /**
     * Obtener nombre completo del propietario
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /**
     * Scopes
     */

    /**
     * Scope para propietarios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para propietarios inactivos
     */
    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Scope para buscar por CI
     */
    public function scopeBuscarPorCI($query, $ci)
    {
        return $query->where('ci', 'like', "%{$ci}%");
    }

    /**
     * Scope para buscar por nombre o apellido
     */
    public function scopeBuscarPorNombre($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('apellido', 'like', "%{$termino}%");
    }

    /**
     * Scope para búsqueda general
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('ci', 'like', "%{$termino}%")
                    ->orWhere('nombre', 'like', "%{$termino}%")
                    ->orWhere('apellido', 'like', "%{$termino}%")
                    ->orWhere('email', 'like', "%{$termino}%");
    }

    /**
     * Relaciones
     */

    /**
     * Todas las unidades de este propietario (histórico)
     */
    public function unidades()
    {
        return $this->belongsToMany(Unidad::class, 'propietario_unidad')
                    ->withPivot('fecha_inicio', 'fecha_fin', 'es_propietario_actual')
                    ->withTimestamps()
                    ->orderBy('pivot_fecha_inicio', 'desc');
    }

    /**
     * Obtener solo las unidades actuales (propietario actual)
     */
    public function unidadesActuales()
    {
        return $this->belongsToMany(Unidad::class, 'propietario_unidad')
                    ->wherePivot('es_propietario_actual', true)
                    ->withPivot('fecha_inicio', 'fecha_fin');
    }

    /**
     * Obtener el historial de unidades (incluye las que ya no posee)
     */
    public function historialUnidades()
    {
        return $this->unidades()->withTimestamps();
    }
}
