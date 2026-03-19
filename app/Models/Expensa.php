<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expensa extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla (Laravel no pluraliza correctamente español)
     */
    protected $table = 'expensas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'periodo_id',
        'unidad_id',
        'monto_expensa',
        'monto_agua',
        'deuda_anterior',
        'monto_total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monto_expensa' => 'decimal:2',
        'monto_agua' => 'decimal:2',
        'deuda_anterior' => 'decimal:2',
        'monto_total' => 'decimal:2',
    ];

    /**
     * Get the periodo for this expensa.
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoExpensa::class, 'periodo_id');
    }

    /**
     * Get the unidad for this expensa.
     */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    /**
     * Get the boleta for this expensa.
     */
    public function boleta()
    {
        return $this->hasOne(Boleta::class, 'expensa_id');
    }
}
