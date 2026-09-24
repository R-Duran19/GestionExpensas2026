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
     * Obtener el inquilino actual como un modelo individual
     */
    public function getInquilinoActualAttribute()
    {
        return $this->inquilinos()->wherePivot('es_inquilino_actual', true)->first();
    }

    /**
     * Obtener el color para UI según tipo
     */
    public function getColorAttribute(): string
    {
        return $this->tipo_unidad->getColor();
    }

    /**
     * Helpers de ordenamiento (orden natural unificado para códigos de unidad)
     *
     * Soluciona el problema clásico de orden lexicográfico:
     *   dep-1, dep-11, dep-2  ->  dep-1, dep-2, ..., dep-11
     *
     * Requiere MySQL/MariaDB con soporte REGEXP_SUBSTR (MySQL 8.0+ / MariaDB 10.5+).
     */

    /**
     * Expresión SQL para ordenar unidades por código de forma natural.
     *
     * Orden de claves (todas respetan la dirección indicada):
     *   1. Prefijo alfabético antes del primer guion (DEP, OF, LOC, P, B, PB)
     *   2. Primer número encontrado en el código, como entero (11, 12, 28, 30, ...)
     *   3. Longitud del código (agrupa 1 dígito vs 2 dígitos vs 3 dígitos)
     *   4. El código completo (desempate alfabético final: A, B, C, ...)
     *
     * Ejemplo para departamentos:
     *   DEP-11-A, DEP-11-B, ..., DEP-28-29-A, DEP-28-29-B, DEP-30
     *
     * @param  string $column   Nombre de la columna (admite alias: 'u.codigo', 'unidades.codigo')
     * @param  string $direction 'ASC' o 'DESC'
     * @return string           Expresión lista para ->orderByRaw(...)
     */
    public static function ordenPorCodigoRaw(string $column = 'codigo', string $direction = 'ASC'): string
    {
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        return implode(', ', [
            "SUBSTRING_INDEX({$column}, '-', 1) {$dir}",
            "CAST(REGEXP_SUBSTR({$column}, '[0-9]+') AS UNSIGNED) {$dir}",
            "LENGTH({$column}) {$dir}",
            "{$column} {$dir}",
        ]);
    }

    /**
     * Expresión SQL para ordenar por tipo de unidad de forma consistente en todo el sistema.
     *
     * Orden lógico: departamentos -> oficinas -> locales -> parqueos -> bauleras
     * (residenciales/comerciales primero, accesorios al final).
     */
    public static function ordenPorTipoUnidadRaw(string $column = 'tipo_unidad'): string
    {
        return "FIELD({$column}, 'departamento', 'oficina', 'local', 'parqueo', 'baulera')";
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
     * Scope para buscar por código, piso o propietario
     * Busca en código, piso, y en el nombre/apellido del propietario actual
     */
    public function scopeBuscar($query, $termino)
    {
        // Limpiar espacios
        $termino = trim($termino);
        if (empty($termino)) {
            return $query;
        }

        // Dividir por espacios y filtrar palabras vacías
        $palabras = preg_split('/\s+/', $termino, -1, PREG_SPLIT_NO_EMPTY);
        $palabras = array_filter($palabras, fn($p) => !empty(trim($p)));

        // Si es una sola palabra, búsqueda simple
        if (count($palabras) === 1) {
            $termino = $palabras[0];
            return $query->where(function ($q) use ($termino) {
                $q->where('codigo', 'like', "%{$termino}%")
                  ->orWhere('piso', 'like', "%{$termino}%")
                  ->orWhereHas('propietarioActual', function ($propQ) use ($termino) {
                      $propQ->where('nombre', 'like', "%{$termino}%")
                            ->orWhere('apellido', 'like', "%{$termino}%");
                  })
                  ->orWhereHas('inquilinoActual', function ($inqQ) use ($termino) {
                      $inqQ->where('nombre', 'like', "%{$termino}%")
                            ->orWhere('apellido', 'like', "%{$termino}%");
                  });
            });
        }

        // Si son múltiples palabras, buscar combinaciones
        return $query->where(function ($q) use ($palabras) {
            foreach ($palabras as $index => $palabra) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $q->$method(function ($subQ) use ($palabra) {
                    $subQ->where('codigo', 'like', "%{$palabra}%")
                          ->orWhere('piso', 'like', "%{$palabra}%")
                          ->orWhereHas('propietarioActual', function ($propQ) use ($palabra) {
                              $propQ->where('nombre', 'like', "%{$palabra}%")
                                    ->orWhere('apellido', 'like', "%{$palabra}%");
                          })
                          ->orWhereHas('inquilinoActual', function ($inqQ) use ($palabra) {
                              $inqQ->where('nombre', 'like', "%{$palabra}%")
                                    ->orWhere('apellido', 'like', "%{$palabra}%");
                          });
                });
            }
        });
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
     * Todos los inquilinos de esta unidad (histórico)
     */
    public function inquilinos()
    {
        return $this->belongsToMany(Inquilino::class, 'inquilino_unidad')
                    ->withPivot('fecha_inicio', 'fecha_fin', 'es_inquilino_actual', 'monto_alquiler', 'observaciones')
                    ->withTimestamps()
                    ->orderBy('pivot_fecha_inicio', 'desc');
    }

    /**
     * Obtener el inquilino actual de esta unidad
     */
    public function inquilinoActual()
    {
        return $this->belongsToMany(Inquilino::class, 'inquilino_unidad')
                    ->wherePivot('es_inquilino_actual', true)
                    ->withPivot('fecha_inicio', 'fecha_fin', 'monto_alquiler', 'observaciones', 'portal_autorizado', 'autorizado_en');
    }

    /**
     * Verificar si tiene inquilino actual
     */
    public function tieneInquilinoActual(): bool
    {
        // Si la relación ya fue eager-loaded, NO pegar a la BD por cada unidad
        if ($this->relationLoaded('inquilinoActual')) {
            return $this->inquilinoActual->isNotEmpty();
        }

        return $this->inquilinoActual()->exists();
    }

    /**
     * Obtener responsable de pago (inquilino si existe, sino propietario)
     * Retorna array con tipo ('inquilino' o 'propietario') y entidad
     */
    public function getResponsablePagoAttribute()
    {
        if ($this->tieneInquilinoActual()) {
            return [
                'tipo' => 'inquilino',
                'entidad' => $this->inquilinoActual,
            ];
        }

        return [
            'tipo' => 'propietario',
            'entidad' => $this->propietarioActual,
        ];
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
