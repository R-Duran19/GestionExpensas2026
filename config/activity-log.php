<?php

/**
 * Configuración del Sistema de Activity Logs
 *
 * @package Config
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Habilitar/Deshabilitar logging en consola
    |--------------------------------------------------------------------------
    |
    | Determina si se deben registrar logs cuando se ejecuta comandos artisan.
    | Útil para evitar logs masivos durante seeders o migraciones.
    |
    */
    'activity_log_console' => env('ACTIVITY_LOG_CONSOLE', false),

    /*
    |--------------------------------------------------------------------------
    | Días de retención de logs
    |--------------------------------------------------------------------------
    |
    | Período de tiempo en días que los registros se mantienen en la base
    | de datos. Después de este período, se recomienda limpiar los logs viejos
    | mediante un comando de limpieza programado.
    |
    */
    'retention_days' => env('ACTIVITY_LOG_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Acciones a registrar automáticamente
    |--------------------------------------------------------------------------
    |
    | Define qué eventos del modelo Eloquent se registran automáticamente
    | cuando se usa el trait Loggable.
    |
    */
    'auto_log_events' => [
        'created' => true,
        'updated' => true,
        'deleted' => true,
        'restored' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Modelos excluidos del logging automático
    |--------------------------------------------------------------------------
    |
    | Modelos que no deben generar logs automáticamente aunque usen el trait.
    | Útil para modelos de alta frecuencia (ej: ActivityLog, Sessions, etc).
    |
    */
    'excluded_models' => [
        \App\Models\ActivityLog::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nivel de detalle de propiedades
    |--------------------------------------------------------------------------
    |
    | 'full': Guarda todos los atributos del modelo
    | 'changes': Solo guarda los cambios (para updated)
    | 'minimal': Solo guarda ID y tipo de modelo
    |
    */
    'properties_detail_level' => env('ACTIVITY_LOG_DETAIL_LEVEL', 'changes'),

];
