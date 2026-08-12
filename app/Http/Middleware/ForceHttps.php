<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * [SEGURIDAD-09 / Fase 2.2] Fuerza HTTPS en URLs generadas por Laravel.
 *
 * En produccion (o si FORCE_HTTPS=true en .env), todos los URL::to(),
 * route(), asset(), etc. generan con esquema https://.
 *
 * Importante cuando la app esta detras de un proxy que termina TLS:
 * sin esto, Laravel genera URLs http:// y las redirecciones mezclan esquemas,
 * lo que puede romper CSRF, cookies seguras y HSTS.
 *
 * No redirige HTTP -> HTTPS (eso lo hace nginx-proxy-manager o el HSTS).
 * Solo asegura que las URLs que Laravel GENERA internamente sean https://.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->debeForzarHttps()) {
            URL::forceScheme('https');
        }

        return $next($request);
    }

    private function debeForzarHttps(): bool
    {
        // En produccion siempre. En otros entornos solo si FORCE_HTTPS=true.
        return app()->environment('production')
            || config('app.force_https', false) === true
            || env('FORCE_HTTPS', false) === true;
    }
}
