<?php

namespace App\Services\QrBanco;

use App\Models\PagoQr;

/**
 * [PORTAL QR] Driver simulado para tests y modo sandbox.
 *
 * Comportamiento controlado por Cache:
 *  - Cache::put('fake_qr_pago_<alias>', true) => verificarEstado devuelve PAGADO
 *  - Default => PENDIENTE
 *  - Cache::put('fake_qr_error', true) => generarQr lanza excepción
 */
class FakeQrDriver implements QrBancoDriver
{
    public function generarQr(string $alias, float $monto, string $glosa, \DateTimeInterface $vencimiento): array
    {
        if (\Illuminate\Support\Facades\Cache::pull('fake_qr_error')) {
            throw new \Exception('Simulador: banco no disponible');
        }

        return [
            'imagen_qr' => 'data:image/png;base64,FAKE_' . $alias,
            'respuesta' => ['codigo' => '0000', 'mensaje' => 'OK (fake)'],
        ];
    }

    public function verificarEstado(string $alias): string
    {
        $pagado = \Illuminate\Support\Facades\Cache::get("fake_qr_pago_{$alias}", false);

        return $pagado ? 'PAGADO' : 'PENDIENTE';
    }

    public function inhabilitarQr(string $alias): bool
    {
        return true;
    }
}
