<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        \App\Providers\LivewireServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // [SEGURIDAD-09 / Fase 2.3][REMEDIACIÓN-1.4] Proxies confiables: NO se
        // fijan aquí (config() no existe todavía en el boot). El middleware
        // TrustProxies hace fallback a config('trustedproxy.proxies') en cada
        // request => ver config/trustedproxy.php (default: loopback + RFC1918;
        // en prod, estrechar TRUSTED_PROXIES a la IP de nginx-proxy-manager).

        // [SEGURIDAD-09 / Fase 2.2] Forzar esquema HTTPS en URLs generadas.
        $middleware->append(\App\Http\Middleware\ForceHttps::class);

        // Registrar middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdmin::class,
            'portal' => \App\Http\Middleware\CheckPortal::class,
        ]);

        // [PORTAL QR / Fase 4] El webhook del banco NO lleva CSRF (es una API
        // entrante sin sesión). Rate limit aplicado en la ruta.
        $middleware->validateCsrfTokens(except: [
            'webhook/qr',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
