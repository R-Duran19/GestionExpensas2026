<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactorHistorial extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'factor_prorrateo_id',
        'factor_anterior',
        'factor_nuevo',
        'cambiado_por',
        'motivo',
        'vigencia_desde',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'factor_anterior' => 'decimal:2',
        'factor_nuevo' => 'decimal:2',
        'vigencia_desde' => 'date',
    ];

    /**
     * Get the factor that owns the historial.
     */
    public function factorProrrateo(): BelongsTo
    {
        return $this->belongsTo(FactorProrrateo::class, 'factor_prorrateo_id');
    }

    /**
     * Get the user that made the change.
     */
    public function cambiadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cambiado_por');
    }

    /**
     * Get the change description.
     */
    public function getCambioDescripcionAttribute(): string
    {
        return "Factor cambiado de {$this->factor_anterior} a {$this->factor_nuevo}";
    }
}
