<?php

namespace App\Services\QrBanco;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [PORTAL QR / Fase 4.2] Driver para la API QR del Banco MC4.
 *
 * Calco directo del cliente probado en producción por el sistema de tickets
 * (TicketsBackend_365Soft/src/shared/utils/banco-qr.util.ts):
 *   POST /autenticacion/v1/generarToken  (header: apikey)
 *   POST /api/v1/generaQr                (header: apikeyServicio + Bearer)
 *   POST /api/v1/estadoTransaccion       (header: apikeyServicio + Bearer)
 *   POST /api/v1/inhabilitarPago         (header: apikeyServicio + Bearer)
 *
 * Respuestas del banco: {codigo: '0000'|'OK', mensaje, objeto}
 */
class Mc4QrDriver implements QrBancoDriver
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $serviceKey;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected string $callbackUrl;

    public function __construct()
    {
        $cfg = config('services.qr_banco');
        $this->apiUrl = rtrim($cfg['api_url'], '/');
        $this->apiKey = $cfg['api_key'];
        $this->serviceKey = $cfg['service_key'];
        $this->username = $cfg['username'];
        $this->password = $cfg['password'];
        $this->timeout = $cfg['timeout'];
        $this->callbackUrl = $cfg['callback_url'];
    }

    /**
     * Token con cache de 55 min (el del banco expira en 60).
     */
    protected function token(): string
    {
        return Cache::remember('qr_banco_mc4_token', now()->addMinutes(55), function () {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['apikey' => $this->apiKey])
                ->post("{$this->apiUrl}/autenticacion/v1/generarToken", [
                    'password' => $this->password,
                    'username' => $this->username,
                ]);

            $data = $response->json() ?? [];

            if (!in_array($data['codigo'] ?? '', ['0000', 'OK'], true)) {
                Log::error('MC4 QR: fallo generando token', ['mensaje' => $data['mensaje'] ?? 'sin respuesta']);
                throw new \Exception('Error autenticando con el banco');
            }

            return $data['objeto']['token'];
        });
    }

    public function generarQr(string $alias, float $monto, string $glosa, \DateTimeInterface $vencimiento): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'apikeyServicio' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->token(),
            ])
            ->post("{$this->apiUrl}/api/v1/generaQr", [
                'alias' => $alias,
                'callback' => $this->callbackUrl,
                'detalleGlosa' => $glosa,
                'monto' => (float) $monto,
                'moneda' => 'BOB',
                'fechaVencimiento' => $vencimiento->format('d/m/Y'),
                'tipoSolicitud' => 'API',
                'unicoUso' => true,
            ]);

        $data = $response->json() ?? [];

        if (!in_array($data['codigo'] ?? '', ['0000', 'OK'], true)) {
            throw new \Exception('El banco rechazó la generación del QR: ' . ($data['mensaje'] ?? 'sin mensaje'));
        }

        return [
            'imagen_qr' => self::normalizarImagen($data['objeto']['imagenQr'] ?? null),
            'respuesta' => $data,
        ];
    }

    /**
     * [FIX probado contra MC4 real 2026-09-24] El banco devuelve la imagen
     * como base64 CRUDO (empieza con iVBORw0KGgo...) SIN el prefijo
     * data:image/png;base64, — sin normalizar, el <img> del navegador no
     * renderiza nada (imagen rota). Acepta ambos formatos.
     */
    public static function normalizarImagen(?string $imagen): ?string
    {
        if (empty($imagen)) {
            return null;
        }

        if (str_starts_with($imagen, 'data:')) {
            return $imagen; // ya viene con prefijo (formato documentado)
        }

        return 'data:image/png;base64,' . $imagen;
    }

    public function verificarEstado(string $alias): string
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'apikeyServicio' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->token(),
            ])
            ->post("{$this->apiUrl}/api/v1/estadoTransaccion", [
                'alias' => $alias,
            ]);

        $data = $response->json() ?? [];

        if (!in_array($data['codigo'] ?? '', ['0000', 'OK'], true)) {
            throw new \Exception('Error verificando estado del QR: ' . ($data['mensaje'] ?? 'sin mensaje'));
        }

        return (string) ($data['objeto']['estadoActual'] ?? 'PENDIENTE');
    }

    public function inhabilitarQr(string $alias): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'apikeyServicio' => $this->serviceKey,
                    'Authorization' => 'Bearer ' . $this->token(),
                ])
                ->post("{$this->apiUrl}/api/v1/inhabilitarPago", [
                    'alias' => $alias,
                ]);

            return in_array($response->json('codigo'), ['0000', 'OK'], true);
        } catch (\Throwable $e) {
            // Mejor esfuerzo: si el banco falla, el QR igual expira solo
            Log::warning('MC4 QR: no se pudo inhabilitar el QR', ['alias' => $alias, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
