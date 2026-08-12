<?php

namespace App\Models;

use App\Helpers\Pii;
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
     *
     * [SEGURIDAD-09 / Fase 3.C] CI/telefono/email siguen en fillable para
     * compatibilidad; los mutadores redirigen la escritura a _enc + _hash.
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'ci',
        'telefono',
        'email',
        'ci_enc',
        'telefono_enc',
        'email_enc',
        'ci_hash',
        'activo',
        'observaciones',
    ];

    /**
     * Los atributos que deben ser casteados.
     *
     * [SEGURIDAD-09 / Fase 3.C] NO usamos el cast 'encrypted' de Laravel
     * porque este aplica serialize/unserialize que rompe con accesores
     * custom. En su lugar, ciframos/desciframos explicitamente en los
     * accesores/mutadores con Pii::encrypt() / Pii::decrypt() (sin serialize).
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

    // [SEGURIDAD-09 / Fase 3.C] Accesores PII:
    // Descifran manualmente desde _enc (sin unserialize, sin cast).
    // Fallback a columna vieja (legacy) si _enc esta vacia.

    public function getCiAttribute()
    {
        if (!empty($this->attributes['ci_enc'])) {
            return Pii::decrypt($this->attributes['ci_enc']);
        }
        return $this->attributes['ci'] ?? null;
    }

    public function getTelefonoAttribute()
    {
        if (!empty($this->attributes['telefono_enc'])) {
            return Pii::decrypt($this->attributes['telefono_enc']);
        }
        return $this->attributes['telefono'] ?? null;
    }

    public function getEmailAttribute()
    {
        if (!empty($this->attributes['email_enc'])) {
            return Pii::decrypt($this->attributes['email_enc']);
        }
        return $this->attributes['email'] ?? null;
    }

    // [SEGURIDAD-09 / Fase 3.C] Mutadores PII:
    // Cifran manualmente a _enc (sin serialize) + escriben ci_hash + columna vieja legacy.

    public function setCiAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['ci_enc'] = null;
            $this->attributes['ci_hash'] = null;
            $this->attributes['ci'] = null;
            return;
        }
        $this->attributes['ci_enc'] = Pii::encrypt((string) $value);
        $this->attributes['ci_hash'] = Pii::hash((string) $value);
        $this->attributes['ci'] = $value;   // legacy, hasta migration 3.D
    }

    public function setTelefonoAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['telefono_enc'] = null;
            $this->attributes['telefono'] = null;
            return;
        }
        $this->attributes['telefono_enc'] = Pii::encrypt((string) $value);
        $this->attributes['telefono'] = $value;
    }

    public function setEmailAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['email_enc'] = null;
            $this->attributes['email'] = null;
            return;
        }
        $this->attributes['email_enc'] = Pii::encrypt((string) $value);
        $this->attributes['email'] = $value;
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
     * [SEGURIDAD-09 / Fase 3.C] Buscar por CI EXACTO via hash.
     * Reemplaza el LIKE viejo que rompia con columnas cifradas.
     * Solo permite igualdad (no LIKE), pero preserva unicidad de CI.
     */
    public function scopeBuscarPorCI($query, $ci)
    {
        if ($ci === null || $ci === '') {
            return $query;
        }
        return $query->where('ci_hash', Pii::hash($ci));
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
     * Scope para búsqueda general.
     *
     * [SEGURIDAD-09 / Fase 3.C] Antes buscaba por CI/email con LIKE, lo cual
     * rompe con columnas cifradas. Ahora busca SOLO por nombre y apellido.
     * Para busqueda exacta por CI, usar scopeBuscarPorCI (via hash).
     * Maneja múltiples palabras (ej: "Maria Perez").
     */
    public function scopeBuscar($query, $termino)
    {
        // Limpiar y dividir en palabras (maneja espacios múltiples)
        $termino = trim($termino);
        if (empty($termino)) {
            return $query;
        }

        // Dividir por espacios y filtrar palabras vacías
        $palabras = preg_split('/\s+/', $termino, -1, PREG_SPLIT_NO_EMPTY);
        $palabras = array_filter($palabras, fn($p) => !empty(trim($p)));

        // Si es una sola palabra, búsqueda simple en nombre/apellido
        if (count($palabras) === 1) {
            $termino = $palabras[0];
            return $query->where(function ($q) use ($termino) {
                $q->where('nombre', 'like', "%{$termino}%")
                  ->orWhere('apellido', 'like', "%{$termino}%");
            });
        }

        // Múltiples palabras: buscar combinaciones en nombre/apellido
        return $query->where(function ($q) use ($palabras) {
            foreach ($palabras as $index => $palabra) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $q->$method(function ($subQ) use ($palabra) {
                    $subQ->where('nombre', 'like', "%{$palabra}%")
                         ->orWhere('apellido', 'like', "%{$palabra}%");
                });
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
