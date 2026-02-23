<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaAguaPeriodo extends Model
{
    use HasFactory;

    protected $table = 'facturas_agua_periodo';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'periodo_id',
        'factura_agua_config_id',
        'numero_factura',
        'categoria',
        'importe',
        'consumo_m3',
        'fecha_factura',
        'observaciones',
        'registrado_por',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'importe' => 'decimal:2',
        'consumo_m3' => 'decimal:2',
        'fecha_factura' => 'date',
    ];

    /**
     * Get the periodo for this factura agua.
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoExpensa::class, 'periodo_id');
    }

    /**
     * Get the factura config for this factura agua.
     */
    public function facturaAguaConfig(): BelongsTo
    {
        return $this->belongsTo(FacturaAguaConfig::class, 'factura_agua_config_id');
    }

    /**
     * Get the user that registered the factura agua.
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
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
