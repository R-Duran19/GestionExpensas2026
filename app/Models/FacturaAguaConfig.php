<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacturaAguaConfig extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'factura_agua_config';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo',
        'numero_factura',
        'categoria',
        'activo',
        'creado_por',
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
     * Get the user that created the factura config.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Get the historial records for this factura config.
     */
    public function historial(): HasMany
    {
        return $this->hasMany(FacturaAguaHistorial::class, 'factura_agua_config_id');
    }

    /**
     * Scope to get only active factura configs.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope to get configs by type.
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope to get configs by category.
     */
    public function scopePorCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Get type label.
     */
    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'domiciliaria_1' => 'Domiciliaria 1',
            'domiciliaria_2' => 'Domiciliaria 2',
            'comercial' => 'Comercial',
            default => 'Desconocido',
        };
    }

    /**
     * Get category label.
     */
    public function getCategoriaLabelAttribute(): string
    {
        return match($this->categoria) {
            'domiciliaria' => 'Domiciliaria',
            'comercial' => 'Comercial',
            default => 'Desconocida',
        };
    }
}
