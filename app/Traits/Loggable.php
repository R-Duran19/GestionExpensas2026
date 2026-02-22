<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait Loggable
 *
 * Añade funcionalidad de logging automático a los modelos Eloquent.
 * Registra automáticamente las acciones de crear, actualizar y eliminar.
 *
 * @package App\Traits
 *
 * @mixin Model
 */
trait Loggable
{
    /**
     * Boot del trait (se ejecuta automáticamente al usar el trait).
     *
     * Registra los event listeners para las operaciones del modelo.
     *
     * @return void
     */
    protected static function bootLoggable(): void
    {
        // Evento: Modelo creado
        static::created(function ($model) {
            if (self::shouldLog('created')) {
                self::logAction($model, 'created');
            }
        });

        // Evento: Modelo actualizado
        static::updated(function ($model) {
            if (self::shouldLog('updated')) {
                self::logAction($model, 'updated');
            }
        });

        // Evento: Modelo eliminado
        static::deleted(function ($model) {
            if (self::shouldLog('deleted')) {
                self::logAction($model, 'deleted');
            }
        });

        // Evento: Modelo restaurado (soft delete)
        static::restored(function ($model) {
            if (self::shouldLog('restored')) {
                self::logAction($model, 'restored');
            }
        });
    }

    /**
     * Determina si una acción debe ser registrada.
     *
     * @param string $action
     * @return bool
     */
    protected static function shouldLog(string $action): bool
    {
        // No registrar si estamos en modo consola (artisan, tests, etc.)
        if (app()->runningInConsole() && !config('logging.activity_log_console', false)) {
            return false;
        }

        // No registrar si no hay usuario autenticado
        if (!Auth::check()) {
            return false;
        }

        return true;
    }

    /**
     * Registra una acción en el log de actividad.
     *
     * @param Model $model Modelo afectado
     * @param string $action Acción realizada
     * @param string|null $description Descripción personalizada (opcional)
     * @param array|null $properties Propiedades adicionales (opcional)
     * @return ActivityLog|null
     */
    protected static function logAction(
        Model $model,
        string $action,
        ?string $description = null,
        ?array $properties = null
    ): ?ActivityLog {
        try {
            // Preparar propiedades si no se proporcionan
            if ($properties === null) {
                $properties = self::prepareProperties($model, $action);
            }

            // Generar descripción automática si no se proporciona
            if ($description === null) {
                $description = self::generateDescription($model, $action);
            }

            return ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'description' => $description,
                'properties' => $properties,
            ]);
        } catch (\Exception $e) {
            // Si falla el logging, no interrumpir la operación principal
            logger()->error('Error al registrar activity log', [
                'error' => $e->getMessage(),
                'model' => get_class($model),
                'action' => $action,
            ]);

            return null;
        }
    }

    /**
     * Prepara las propiedades para el log según la acción.
     *
     * @param Model $model
     * @param string $action
     * @return array
     */
    protected static function prepareProperties(Model $model, string $action): array
    {
        return match ($action) {
            'created' => [
                'attributes' => $model->getAttributes(),
            ],
            'updated' => [
                'old' => $model->getOriginal(),
                'new' => $model->getChanges(),
            ],
            'deleted' => [
                'attributes' => $model->getOriginal(),
            ],
            'restored' => [
                'attributes' => $model->getAttributes(),
            ],
            default => [],
        };
    }

    /**
     * Genera una descripción legible para la acción.
     *
     * @param Model $model
     * @param string $action
     * @return string
     */
    protected static function generateDescription(Model $model, string $action): string
    {
        $modelName = class_basename($model);

        return match ($action) {
            'created' => "{$modelName} creado: {$model->getKey()}",
            'updated' => "{$modelName} actualizado: {$model->getKey()}",
            'deleted' => "{$modelName} eliminado: {$model->getKey()}",
            'restored' => "{$modelName} restaurado: {$model->getKey()}",
            default => "{$modelName}: {$action}",
        };
    }

    /**
     * Registra un log manual para una acción personalizada.
     *
     * Útil para acciones que no son eventos del modelo.
     *
     * @param string $action
     * @param string|null $description
     * @param array|null $properties
     * @return ActivityLog|null
     */
    public function logCustomAction(
        string $action,
        ?string $description = null,
        ?array $properties = null
    ): ?ActivityLog {
        return self::logAction($this, $action, $description, $properties);
    }
}
