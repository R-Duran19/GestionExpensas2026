<?php

namespace App\Models;

use App\Enums\CategoriaUnidad;
use App\Enums\TipoUnidad;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unidad extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla (Laravel no pluraliza correctamente español)
     */
    protected $table = 'unidades';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'codigo',
        'tipo_unidad',
        'piso',
        'metros_cuadrados',
        'categoria_unidad',
        'tiene_medidor',
        'tipo_medidor',
        'numero_medidor',
        'medidor_grupo_id',
        'activo',
        'observaciones',
    ];

    /**
     * Los atributos que deben ser casteados.
     * Laravel 12.x soporta nativamente enums de PHP
     */
    protected $casts = [
        'tipo_unidad' => TipoUnidad::class,
        'categoria_unidad' => CategoriaUnidad::class,
        'metros_cuadrados' => 'decimal:2',
        'tiene_medidor' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * Accesores y Mutators
     */

    /**
     * Obtener el nombre completo de la unidad
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->tipo_unidad->getLabel()} {$this->codigo}";
    }

    /**
     * Obtener el factor de prorrateo de esta unidad
     */
    public function getFactorProrrateoAttribute(): float
    {
        return $this->categoria_unidad->getFactorProrrateo();
    }

    /**
     * Obtener el propietario actual como un modelo individual
     */
    public function getPropietarioActualAttribute()
    {
        return $this->propietarios()->where('es_propietario_actual', true)->first();
    }

    /**
     * Obtener el color para UI según tipo
     */
    public function getColorAttribute(): string
    {
        return $this->tipo_unidad->getColor();
    }

    /**
     * Scopes
     */

    /**
     * Scope para unidades activas
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para unidades inactivas
     */
    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Scope para filtrar por tipo de unidad
     */
    public function scopeDeTipo($query, TipoUnidad $tipo)
    {
        return $query->where('tipo_unidad', $tipo->value);
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopeDeCategoria($query, CategoriaUnidad $categoria)
    {
        return $query->where('categoria_unidad', $categoria->value);
    }

    /**
     * Scope para unidades con medidor
     */
    public function scopeConMedidor($query)
    {
        return $query->where('tiene_medidor', true);
    }

    /**
     * Scope para buscar por código
     */
    public function scopeBuscar($query, $termino)
    {
        // Limpiar espacios
        $termino = trim($termino);

        return $query->where('codigo', 'like', "%{$termino}%")
                    ->orWhere('piso', 'like', "%{$termino}%");
    }

    /**
     * Relaciones
     */

    /**
     * Todos los propietarios de esta unidad (histórico)
     */
    public function propietarios()
    {
        return $this->belongsToMany(Propietario::class, 'propietario_unidad')
                    ->withPivot('fecha_inicio', 'fecha_fin', 'es_propietario_actual')
                    ->withTimestamps()
                    ->orderBy('pivot_fecha_inicio', 'desc');
    }

    /**
     * Obtener el propietario actual de esta unidad
     */
    public function propietarioActual()
    {
        return $this->belongsToMany(Propietario::class, 'propietario_unidad')
                    ->wherePivot('es_propietario_actual', true)
                    ->withPivot('fecha_inicio', 'fecha_fin');
    }

    /**
     * Verificar si tiene propietario actual
     */
    public function tienePropietarioActual(): bool
    {
        return $this->propietarioActual()->exists();
    }

    /**
     * Obtener el grupo de medidor al que pertenece esta unidad (si es compartido)
     */
    public function medidorGrupo()
    {
        return $this->belongsTo(MedidorGrupo::class, 'medidor_grupo_id');
    }

    /**
     * Verificar si la unidad tiene medidor individual
     */
    public function tieneMedidorIndividual(): bool
    {
        return $this->tiene_medidor && $this->tipo_medidor === 'individual';
    }

    /**
     * Las expensas de esta unidad
     */
    public function expensas()
    {
        return $this->hasMany(Expensa::class, 'unidad_id');
    }

    /**
     * Las lecturas de medidor de esta unidad
     */
    public function lecturasMedidor()
    {
        return $this->hasMany(LecturaMedidor::class, 'unidad_id');
    }

    /**
     * Verificar si esta unidad pertenece a un grupo de medidor compartido
     */
    public function tieneMedidorCompartido(): bool
    {
        return $this->medidorGrupo()->exists();
    }

    /**
     * Boot method para validaciones automáticas
     */
    protected static function boot()
    {
        parent::boot();

        // Validar compatibilidad tipo-categoría al crear
        static::creating(function ($unidad) {
            $categoriaPermitida = $unidad->tipo_unidad->getCategoriaPermitida();

            if ($unidad->categoria_unidad !== $categoriaPermitida) {
                throw new \InvalidArgumentException(
                    "El tipo {$unidad->tipo_unidad->value} no puede ser categoría {$unidad->categoria_unidad->value}. " .
                    "Debe ser {$categoriaPermitida->value}."
                );
            }
        });

        // Validar compatibilidad al actualizar
        static::updating(function ($unidad) {
            if ($unidad->isDirty('tipo_unidad') || $unidad->isDirty('categoria_unidad')) {
                $categoriaPermitida = $unidad->tipo_unidad->getCategoriaPermitida();

                if ($unidad->categoria_unidad !== $categoriaPermitida) {
                    throw new \InvalidArgumentException(
                        "El tipo {$unidad->tipo_unidad->value} no puede ser categoría {$unidad->categoria_unidad->value}. " .
                        "Debe ser {$categoriaPermitida->value}."
                    );
                }
            }
        });
    }
}
