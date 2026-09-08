<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Flag persistente para la HERRAMIENTA TEMPORAL de sobreescritura de montos.
 *
 * El estado se guarda como un archivo en storage/app/ajuste-temporal.activo
 * (sobrevive a rebuilds de Docker porque storage va montado, y a cache:clear).
 *
 * Uso por consola:
 *   php artisan ajuste-temporal on      # activar
 *   php artisan ajuste-temporal off     # desactivar
 *   php artisan ajuste-temporal status  # ver estado (default)
 */
class AjusteTemporalFlag
{
    public const ARCHIVO = 'ajuste-temporal.activo';

    public static function enabled(): bool
    {
        return Storage::disk('local')->exists(self::ARCHIVO);
    }

    public static function activar(): void
    {
        Storage::disk('local')->put(self::ARCHIVO, now()->toDateTimeString() . ' por user ' . (auth()->id() ?? 'consola'));
    }

    public static function desactivar(): void
    {
        Storage::disk('local')->delete(self::ARCHIVO);
    }
}
