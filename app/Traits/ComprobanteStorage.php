<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * [SEGURIDAD-09 / Fase 1.2] Helpers para gestionar comprobantes en disco privado.
 *
 * Los NUEVOS uploads se guardan en storage/app/private/caja/* (disco 'local').
 * Los VIEJOS (pre-Fase 1.2) siguen en storage/app/public/caja/* (disco 'public').
 *
 * Este trait borra de AMBOS discos silenciosamente para cubrir los dos casos
 * sin importar donde este el archivo.
 */
trait ComprobanteStorage
{
    /**
     * Borra un comprobante buscando en ambos discos (private + public).
     * Silencioso: no falla si el archivo no existe.
     */
    protected function borrarComprobante(?string $ruta): void
    {
        if (empty($ruta)) {
            return;
        }

        // Disco privado (uploads nuevos post-Fase 1.2)
        if (Storage::disk('local')->exists($ruta)) {
            Storage::disk('local')->delete($ruta);
        }

        // Disco publico (uploads viejos pre-Fase 1.2 - fallback)
        if (Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }

    /**
     * Verifica si un comprobante existe en cualquiera de los dos discos.
     */
    protected function existeComprobante(?string $ruta): bool
    {
        if (empty($ruta)) {
            return false;
        }

        return Storage::disk('local')->exists($ruta)
            || Storage::disk('public')->exists($ruta);
    }
}
