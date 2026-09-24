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
        // [SEGURIDAD-09 / Fase 2.3] Proxies confiables restringidos a redes privadas.
        // Antes era at: '*' (confia en cualquiera, inseguro).
        // Cubre nginx-proxy-manager y cualquier otro contenedor Docker sin romper
        // si la IP interna del proxy cambia al recrear el contenedor.
        $middleware->trustProxies(at: [
            '127.0.0.1',        // localhost
            '10.0.0.0/8',       // redes privadas clase A
            '172.16.0.0/12',    // redes privadas clase B (Docker bridge tipico)
            '192.168.0.0/16',   // redes privadas clase C
        ]);

        // [SEGURIDAD-09 / Fase 2.2] Forzar esquema HTTPS en URLs generadas.
        $middleware->append(\App\Http\Middleware\ForceHttps::class);

        // Registrar middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdmin::class,
            'portal' => \App\Http\Middleware\CheckPortal::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
