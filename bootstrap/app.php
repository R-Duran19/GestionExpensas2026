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
        // Configurar proxies confiables para nginx-proxy-manager
        $middleware->trustProxies(at: '*');

        // [SEGURIDAD-09 / Fase 2.2] Forzar esquema HTTPS en URLs generadas.
        $middleware->append(\App\Http\Middleware\ForceHttps::class);

        // Registrar middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
