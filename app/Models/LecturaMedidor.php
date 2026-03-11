<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturaMedidor extends Model
{
    use HasFactory;

    protected $table = 'lecturas_medidor';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'periodo_id',
        'unidad_id',
        'medidor_grupo_id',
        'lectura_anterior',
        'lectura_actual',
        'consumo_m3',
        'observaciones',
        'registrado_por',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lectura_anterior' => 'decimal:2',
        'lectura_actual' => 'decimal:2',
        'consumo_m3' => 'decimal:2',
    ];

    /**
     * Get the periodo for this lectura.
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoExpensa::class, 'periodo_id');
    }

    /**
     * Get the unidad for this lectura.
     */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    /**
     * Get the medidor grupo (for shared lecturas).
     */
    public function medidorGrupo(): BelongsTo
    {
        return $this->belongsTo(MedidorGrupo::class, 'medidor_grupo_id');
    }

    /**
     * Get the user that registered the lectura.
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Scope to get lecturas by periodo.
     */
    public function scopePorPeriodo($query, int $periodoId)
    {
        return $query->where('periodo_id', $periodoId);
    }

    /**
     * Get tipo medidor (derived from unidad).
     */
    public function getTipoAttribute(): string
    {
        return $this->unidad?->tipo_medidor ?: 'individual';
    }

    /**
     * Get tipo label.
     */
    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'individual' => 'Individual',
            'compartido' => 'Compartido',
            default => 'Desconocido',
        };
    }
}
