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
        // Limpiar y dividir en palabras
        $termino = trim($termino);
        $palabras = explode(' ', $termino);

        // Si es una sola palabra, búsqueda normal
        if (count($palabras) === 1) {
            return $query->where('ci', 'like', "%{$termino}%")
                        ->orWhere('nombre', 'like', "%{$termino}%")
                        ->orWhere('apellido', 'like', "%{$termino}%")
                        ->orWhere('email', 'like', "%{$termino}%");
        }

        // Si son múltiples palabras, buscar cada una en cualquier campo
        return $query->where(function ($q) use ($palabras) {
            foreach ($palabras as $palabra) {
                $q->orWhere('ci', 'like', "%{$palabra}%")
                  ->orWhere('nombre', 'like', "%{$palabra}%")
                  ->orWhere('apellido', 'like', "%{$palabra}%")
                  ->orWhere('email', 'like', "%{$palabra}%");
            }
        });
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

    /**
     * Expensas totales de este propietario (agrupadas por período)
     */
    public function expensaTotal()
    {
        return $this->hasMany(ExpensaTotal::class, 'propietario_id');
    }
}
