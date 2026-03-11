<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturaGrupoPeriodo extends Model
{
    use HasFactory;

    protected $table = 'lectura_grupos_periodos';

    protected $fillable = [
        'periodo_id',
        'medidor_grupo_id',
        'lectura_anterior',
        'lectura_actual',
        'consumo_total',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'lectura_anterior' => 'decimal:2',
        'lectura_actual' => 'decimal:2',
        'consumo_total' => 'decimal:2',
    ];

    /**
     * Relación con el período
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoExpensa::class, 'periodo_id');
    }

    /**
     * Relación con el grupo de medidores
     */
    public function medidorGrupo(): BelongsTo
    {
        return $this->belongsTo(MedidorGrupo::class, 'medidor_grupo_id');
    }

    /**
     * Relación con el usuario que registró
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Obtener las lecturas distribuidas de este grupo
     */
    public function lecturasDistribuidas()
    {
        return $this->hasMany(LecturaMedidor::class, 'medidor_grupo_id', 'medidor_grupo_id')
            ->where('periodo_id', $this->periodo_id)
            ->whereNotNull('medidor_grupo_id');
    }
}
