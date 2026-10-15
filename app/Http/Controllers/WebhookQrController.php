<?php

namespace App\Http\Controllers;

use App\Models\PagoQr;
use App\Services\QrBanco\PagoQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * [PORTAL QR / Fase 4] Webhook del banco (endpoint PÚBLICO).
 *
 * El banco notifica pagos: {alias, monto, numeroOrdenOriginante,
 * nombreCliente, documentoCliente, fechaproceso, ...}
 *
 * Mejoras vs el sistema de tickets (Plan 12):
 * - IDEMPOTENCIA: lockForUpdate + estado final => un solo ingreso aunque el
 *   webhook llegue duplicado o en carrera con el polling del portal.
 * - Validación estricta de payload (solo alias es obligatorio).
 * - Rate limit 30/min (ver routes/web.php) + log mínimo sin PII extra.
 * - [REMEDIACIÓN-0.1] AUTENTICACIÓN DEL REMITENTE: HMAC-SHA256 del body crudo,
 *   compartido en el header X-Signature. Sin secreto configurado el endpoint
 *   NO procesa nada (fail-closed). Coordinar el esquema con el banco ANTES de
 *   habilitar el webhook en producción.
 *
 * RESPUESTA exigida por el banco: {codigo: "0000", mensaje: "Registro Exitoso"}
 */
class WebhookQrController extends Controller
{
    public function __invoke(Request $request, PagoQrService $service): JsonResponse
    {
        // [REMEDIACIÓN-0.1] Verificación HMAC sobre el body CRUDO (no re-
        // serializado) para que la firma no dependa del orden/formato del JSON.
        $secret = (string) config('services.qr_banco.webhook_secret');
        $firma = (string) $request->header('X-Signature', '');

        if ($secret === '') {
            Log::critical('Webhook QR sin BANCO_QR_WEBHOOK_SECRET configurado: petición rechazada (fail-closed).');
            return response()->json(['codigo' => '1501', 'mensaje' => 'Webhook no configurado'], 500);
        }

        if ($firma === '' || !hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $firma)) {
            Log::warning('Webhook QR: firma inválida o ausente', [
                'ip' => $request->ip(),
                'con_header' => $firma !== '',
            ]);
            return response()->json(['codigo' => '1401', 'mensaje' => 'Firma invalida'], 401);
        }

        $payload = $request->json()->all() ?? [];
        $alias = $payload['alias'] ?? null;

        // Log mínimo SIN PII del pagador
        Log::info('Webhook QR recibido', ['alias' => $alias]);

        if (!$alias || !is_string($alias)) {
            return response()->json(['codigo' => '1201', 'mensaje' => 'Alias requerido'], 422);
        }

        $pagoQr = PagoQr::where('alias', $alias)->first();

        if (!$pagoQr) {
            // Alias desconocido (QR de otro sistema o inválido): responder
            // éxito genérico para que el banco no reintente infinitamente.
            Log::warning('Webhook QR: alias desconocido', ['alias' => $alias]);
            return response()->json(['codigo' => '0000', 'mensaje' => 'Registro Exitoso']);
        }

        try {
            $resultado = $service->acreditar($pagoQr, $payload);

            Log::info('Webhook QR procesado', [
                'alias' => $alias,
                'estado' => $resultado['pago_qr']->estado,
                'procesado_ahora' => $resultado['procesado_ahora'],
            ]);

            // El banco espera SIEMPRE este formato cuando acepta la notificación
            return response()->json(['codigo' => '0000', 'mensaje' => 'Registro Exitoso']);
        } catch (\Throwable $e) {
            Log::error('Webhook QR: error acreditando', [
                'alias' => $alias,
                'error' => $e->getMessage(),
            ]);

            // Código distinto de 0000 => el banco reintenta más tarde
            return response()->json(['codigo' => '1500', 'mensaje' => 'Error procesando'], 500);
        }
    }
}
