<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/*
|--------------------------------------------------------------------------
| Register Commands
|--------------------------------------------------------------------------
|
| After registering a command, you should add its class to the list of
| commands when calling Artisan::call(). You can do this manually or by
| using load() which will automatically discover and register commands.
|
*/

// Registra el comando de limpieza UTF-8
try {
    File::requireOnce(base_path('app/Console/Commands/LimpiarUtf8Command.php'));
    Illuminate\Console\Application::starting(function ($app) {
        $app->resolve(\Illuminate\Console\Scheduling\Schedule::class);
    });
} catch (\Exception $e) {
    // El comando ya está registrado o hay un error
}
