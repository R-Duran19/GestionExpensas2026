<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactorProrrateo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'factor_prorrateo';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'categoria',
        'factor',
        'vigencia_desde',
        'activo',
        'creado_por',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'factor' => 'decimal:2',
        'vigencia_desde' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Get the user that created the factor.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Get the historial records for this factor.
     */
    public function historial(): HasMany
    {
        return $this->hasMany(FactorHistorial::class, 'factor_prorrateo_id');
    }

    /**
     * Scope to get only active factors.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope to get factors by category.
     */
    public function scopePorCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope to get the current valid factor for a category.
     */
    public function scopeVigente($query, string $categoria)
    {
        return $query->where('categoria', $categoria)
                    ->where('activo', true)
                    ->where('vigencia_desde', '<=', now())
                    ->orderBy('vigencia_desde', 'desc');
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
