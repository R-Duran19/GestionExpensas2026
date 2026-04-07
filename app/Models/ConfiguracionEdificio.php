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
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre_edificio',
        'direccion',
        'telefono',
        'email',
        'cuit',
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
     * @var array<string, string>
     */
    protected $casts = [
        'activo' => 'boolean',
    ];

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
