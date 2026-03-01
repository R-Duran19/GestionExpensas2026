<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoExpensa extends Model
{
    use HasFactory;

    protected $table = 'periodos_expensa';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mes',
        'año',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'motivo_reapertura',
        'cerrado_por',
        'factor_domiciliario',
        'factor_comercial',
        'factores_calculados_at',
        'factores_calculados_por',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'datetime',
        'factor_domiciliario' => 'decimal:8',
        'factor_comercial' => 'decimal:8',
        'factores_calculados_at' => 'datetime',
    ];

    /**
     * Get the user that closed the periodo.
     */
    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    /**
     * Get the user that calculated the factors.
     */
    public function factoresCalculadosPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'factores_calculados_por');
    }

    /**
     * Check if factors are calculated.
     */
    public function getTieneFactoresCalculadosAttribute(): bool
    {
        return !is_null($this->factor_domiciliario) && !is_null($this->factor_comercial);
    }

    /**
     * Get the expensas for this periodo.
     */
    public function expensas(): HasMany
    {
        return $this->hasMany(Expensa::class, 'periodo_id');
    }

    /**
     * Get the facturas agua for this periodo.
     */
    public function facturasAgua(): HasMany
    {
        return $this->hasMany(FacturaAguaPeriodo::class, 'periodo_id');
    }

    /**
     * Get the lecturas medidor for this periodo.
     */
    public function lecturasMedidor(): HasMany
    {
        return $this->hasMany(LecturaMedidor::class, 'periodo_id');
    }

    /**
     * Get the lecturas de grupos for this periodo.
     */
    public function lecturasGruposPeriodos(): HasMany
    {
        return $this->hasMany(LecturaGrupoPeriodo::class, 'periodo_id');
    }

    /**
     * Scope to get only open periodos.
     */
    public function scopeAbiertos($query)
    {
        return $query->where('estado', 'abierto');
    }

    /**
     * Scope to get only closed periodos.
     */
    public function scopeCerrados($query)
    {
        return $query->where('estado', 'cerrado');
    }

    /**
     * Get estado label.
     */
    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            'abierto' => 'Abierto',
            'cerrado' => 'Cerrado',
            default => 'Desconocido',
        };
    }

    /**
     * Get the period name (Mes Año).
     */
    public function getNombrePeriodoAttribute(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return "{$meses[$this->mes]} {$this->año}";
    }
}
