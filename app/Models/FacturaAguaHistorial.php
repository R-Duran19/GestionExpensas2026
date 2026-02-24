<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaAguaHistorial extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'factura_agua_historial';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'factura_agua_config_id',
        'numero_factura_anterior',
        'numero_factura_nuevo',
        'cambiado_por',
        'motivo',
    ];

    /**
     * Get the factura config that owns the historial.
     */
    public function facturaAguaConfig(): BelongsTo
    {
        return $this->belongsTo(FacturaAguaConfig::class, 'factura_agua_config_id');
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
        return "Número de factura cambiado de {$this->numero_factura_anterior} a {$this->numero_factura_nuevo}";
    }
}
