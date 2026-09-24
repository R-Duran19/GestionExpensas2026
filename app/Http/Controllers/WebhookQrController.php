<?php

namespace App\Http\Controllers;

use App\Models\PagoQr;
use App\Services\QrBanco\PagoQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * [PORTAL QR / Fase 4] Webhook del banco (endpoint PÚBLICO sin auth).
 *
 * El banco notifica pagos: {alias, monto, numeroOrdenOriginante,
 * nombreCliente, documentoCliente, fechaproceso, ...}
 *
 * Mejoras vs el sistema de tickets (Plan 12):
 * - IDEMPOTENCIA: lockForUpdate + estado final => un solo ingreso aunque el
 *   webhook llegue duplicado o en carrera con el polling del portal.
 * - Validación estricta de payload (solo alias es obligatorio).
 * - Rate limit 30/min (ver routes/web.php) + log mínimo sin PII extra.
 *
 * RESPUESTA exigida por el banco: {codigo: "0000", mensaje: "Registro Exitoso"}
 */
class WebhookQrController extends Controller
{
    public function __invoke(Request $request, PagoQrService $service): JsonResponse
    {
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
