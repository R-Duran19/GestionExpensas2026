<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionEdificio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'configuracion_edificio';

    /**
     * The attributes that are mass assignable.
     *
     * [SEGURIDAD-09 / Fase 3.C] telefono/email/cuit siguen en fillable para
     * compatibilidad; los mutadores redirigen a _enc.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre_edificio',
        'direccion',
        'telefono',
        'email',
        'cuit',
        'telefono_enc',
        'email_enc',
        'cuit_enc',
        'logo_path',
        'ciudad',
        'provincia',
        'codigo_postal',
        'informacion_adicional',
        'activo',
        'creado_por',
        'actualizado_por',
    ];

    /**
     * The attributes that should be cast.
     *
     * [SEGURIDAD-09 / Fase 3.C] Los _enc se cifran/descifran automaticamente.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activo' => 'boolean',
        'telefono_enc' => 'encrypted',
        'email_enc' => 'encrypted',
        'cuit_enc' => 'encrypted',
    ];

    // [SEGURIDAD-09 / Fase 3.C] Accesores PII: prefieren _enc, fallback a vieja.

    public function getTelefonoAttribute()
    {
        if (!empty($this->attributes['telefono_enc'])) {
            return $this->telefono_enc;
        }
        return $this->attributes['telefono'] ?? null;
    }

    public function getEmailAttribute()
    {
        if (!empty($this->attributes['email_enc'])) {
            return $this->email_enc;
        }
        return $this->attributes['email'] ?? null;
    }

    public function getCuitAttribute()
    {
        if (!empty($this->attributes['cuit_enc'])) {
            return $this->cuit_enc;
        }
        return $this->attributes['cuit'] ?? null;
    }

    // [SEGURIDAD-09 / Fase 3.C] Mutadores PII (escriben _enc + vieja legacy).

    public function setTelefonoAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['telefono_enc'] = null;
            $this->attributes['telefono'] = null;
            return;
        }
        $this->telefono_enc = $value;
        $this->attributes['telefono'] = $value;
    }

    public function setEmailAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['email_enc'] = null;
            $this->attributes['email'] = null;
            return;
        }
        $this->email_enc = $value;
        $this->attributes['email'] = $value;
    }

    public function setCuitAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['cuit_enc'] = null;
            $this->attributes['cuit'] = null;
            return;
        }
        $this->cuit_enc = $value;
        $this->attributes['cuit'] = $value;
    }

    /**
     * Relación con el usuario que creó la configuración
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación con el usuario que actualizó la configuración
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Scope para obtener configuración activa
     */
    public function scopeActiva($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener la configuración activa del edificio
     */
    public static function obtenerConfiguracion(): ?self
    {
        return self::activa()->first();
    }

    /**
     * Obtener la dirección completa formateada
     */
    public function getDireccionCompletaAttribute(): string
    {
        $partes = array_filter([
            $this->direccion,
            $this->ciudad,
            $this->provincia,
            $this->codigo_postal,
        ]);

        return implode(', ', $partes);
    }

    /**
     * Obtener la URL del logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path) {
            return \Storage::url($this->logo_path);
        }
        return null;
    }

    /**
     * Verificar si tiene logo
     */
    public function tieneLogo(): bool
    {
        return !empty($this->logo_path) && \Storage::exists($this->logo_path);
    }
}
