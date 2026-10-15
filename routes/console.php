<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| [PORTAL QR / Fase 4] Conciliación de QRs: expira vencidos y consulta al
| banco los pendientes próximos a vencer (por si el webhook se perdió).
| Requiere cron del sistema: * * * * * php artisan schedule:run
*/
Schedule::command('qr:expirar --conciliar')->everyFifteenMinutes();
