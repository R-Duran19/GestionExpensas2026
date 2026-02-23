<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedidorGrupo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'medidor_grupos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'tipo_distribucion',
        'activo',
        'observaciones',
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
     * Get the user that created the medidor grupo.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Get the unidades associated with this medidor grupo.
     */
    public function unidades(): BelongsToMany
    {
        return $this->belongsToMany(Unidad::class, 'medidor_grupo_unidades')
                    ->withPivot('fecha_asignacion', 'fecha_retiro')
                    ->withTimestamps()
                    ->whereNull('medidor_grupo_unidades.fecha_retiro');
    }

    /**
     * Get all unidades (including historical) for this medidor grupo.
     */
    public function todasUnidades(): BelongsToMany
    {
        return $this->belongsToMany(Unidad::class, 'medidor_grupo_unidades')
                    ->withPivot('fecha_asignacion', 'fecha_retiro')
                    ->withTimestamps();
    }

    /**
     * Get the lecturas for this medidor grupo.
     */
    public function lecturasMedidor(): HasMany
    {
        return $this->hasMany(LecturaMedidor::class, 'medidor_grupo_id');
    }

    /**
     * Scope to get only active medidor grupos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Get the count of active unidades in this group.
     */
    public function getUnidadesActivasCountAttribute(): int
    {
        return $this->unidades()->count();
    }

    /**
     * Get distribution type label.
     */
    public function getTipoDistribucionLabelAttribute(): string
    {
        return match($this->tipo_distribucion) {
            'equitativa' => 'Equitativa (partes iguales)',
            default => 'Desconocido',
        };
    }
}
