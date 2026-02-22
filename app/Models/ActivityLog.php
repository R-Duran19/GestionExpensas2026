<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ActivityLog
 *
 * Representa un registro de actividad en el sistema para auditoría.
 *
 * @property int $id
 * @property int|null $user_id ID del usuario que realizó la acción
 * @property string $action Acción realizada (ej: 'created', 'updated', 'deleted')
 * @property string|null $model_type Tipo del modelo afectado
 * @property int|null $model_id ID del modelo afectado
 * @property string|null $description Descripción legible de la acción
 * @property array|null $properties Propiedades adicionales en JSON
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read User|null $user Usuario que realizó la acción
 *
 * @package App\Models
 */
class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'properties',
    ];

    /**
     * Los atributos que deben ser casteados.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Un log pertenece a un usuario.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filtrar logs por usuario.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filtrar logs por acción.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filtrar logs por modelo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $modelType
     * @param int|null $modelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        return $query->where('model_type', $modelType)
                    ->when($modelId, function ($q) use ($modelId) {
                        return $q->where('model_id', $modelId);
                    });
    }

    /**
     * Scope: Logs recientes (últimos N días).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Accesor: Obtiene el modelo relacionado si existe.
     *
     * @return Model|null
     */
    public function getModelAttribute(): ?Model
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }

        return null;
    }
}
