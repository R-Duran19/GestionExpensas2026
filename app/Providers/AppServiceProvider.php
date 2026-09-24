<?php

namespace App\Providers;

use App\Services\QrBanco\FakeQrDriver;
use App\Services\QrBanco\Mc4QrDriver;
use App\Services\QrBanco\QrBancoDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * [PORTAL QR / Fase 4.2] Binding del driver de pasarela QR bancaria.
         * driver=mc4 (producción, mismas credenciales del sistema de tickets)
         * driver=fake (tests y modo simulador). Cambiar de banco = nuevo
         * driver + variable BANCO_QR_DRIVER (Plan 12, decisión 6).
         */
        $this->app->bind(QrBancoDriver::class, function () {
            return match (config('services.qr_banco.driver', 'mc4')) {
                'fake'  => new FakeQrDriver(),
                default => new Mc4QrDriver(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
