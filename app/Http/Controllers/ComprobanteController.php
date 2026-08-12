<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use App\Models\Egreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [SEGURIDAD-09 / Fase 1.2] Sirve comprobantes desde disco privado.
 *
 * Antes: los comprobantes estaban en storage/app/public/caja/* y se servian
 * publicamente via /storage/* sin autenticacion.
 *
 * Ahora: las rutas requieren auth+admin+throttle. Los NUEVOS uploads van a
 * storage/app/private/caja/*. Los VIEJOS (en public/) se siguen sirviendo
 * via fallback para no romper los existentes mientras se migran/descartan.
 */
class ComprobanteController extends Controller
{
    /**
     * Disco destino de los nuevos uploads (definido en config/filesystems.php).
     * 'local' apunta a storage/app/private con 'serve' => true.
     */
    const DISC_PRIVADO = 'local';

    /**
     * Disco de los uploads viejos (pre-migracion).
     */
    const DISC_PUBLICO = 'public';

    /**
     * Comprobante de un Ingreso (caja/ingresos/*).
     *
     * @return StreamedResponse
     */
    public function ingreso(Request $request, Ingreso $ingreso)
    {
        return $this->servir($ingreso->comprobante_foto);
    }

    /**
     * Comprobante de un Egreso (caja/egresos/*).
     *
     * @return StreamedResponse
     */
    public function egreso(Request $request, Egreso $egreso)
    {
        return $this->servir($egreso->comprobante_foto);
    }

    /**
     * Comprobante de un Pago de expensas (ingreso con tipo_ingreso='expensa').
     *
     * @return StreamedResponse
     */
    public function pago(Request $request, Ingreso $ingreso)
    {
        // Validar que efectivamente sea un pago de expensas
        if ($ingreso->tipo_ingreso !== 'expensa') {
            abort(404);
        }

        return $this->servir($ingreso->comprobante_foto);
    }

    /**
     * Sirve un archivo de comprobante buscando primero en el disco privado
     * (uploads nuevos) y luego en el publico (uploads viejos, fallback).
     *
     * @param string|null $ruta Ruta relativa del comprobante (ej: 'caja/ingresos/2026/08/abc.webp')
     * @return StreamedResponse
     */
    protected function servir(?string $ruta)
    {
        if (empty($ruta)) {
            abort(404, 'El registro no tiene comprobante adjunto.');
        }

        // 1. Buscar en disco privado (uploads nuevos post-Fase 1.2)
        if (Storage::disk(self::DISC_PRIVADO)->exists($ruta)) {
            return $this->respuestaArchivo(self::DISC_PRIVADO, $ruta);
        }

        // 2. Fallback: buscar en disco publico (uploads viejos pre-Fase 1.2)
        // Esto mantiene funcionando los comprobantes existentes sin migracion.
        if (Storage::disk(self::DISC_PUBLICO)->exists($ruta)) {
            return $this->respuestaArchivo(self::DISC_PUBLICO, $ruta);
        }

        abort(404, 'El archivo del comprobante no existe.');
    }

    /**
     * Construye la respuesta HTTP con headers apropiados para una imagen WebP.
     * inline: permite mostrar la imagen en <img> y en pestaña nueva.
     */
    protected function respuestaArchivo(string $disco, string $ruta): StreamedResponse
    {
        return Storage::disk($disco)->response($ruta, null, [
            'Cache-Control' => 'private, max-age=300, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'",
        ]);
    }
}
