<?php

namespace App\Services\QrBanco;

use App\Models\Expensa;
use App\Models\PagoQr;
use App\Models\User;
use App\Services\PagoExpensaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * [PORTAL QR / Fase 4] Servicio de intención de pago con QR.
 *
 * REGLAS DE CONSISTENCIA (Plan 12, decisión 7 y 9):
 * 1. UN QR activo por usuario: si existe un pendiente no vencido, se devuelve
 *    el MISMO (recargar o re-entrar nunca genera otro QR).
 * 2. Cambiar selección/monto => confirmación previa => cancela y genera nuevo.
 * 3. Logout NO invalida el QR (vive server-side hasta vencer o pagarse).
 * 4. Acreditación: el monto pagado SIEMPRE coincide con el QR (el banco cobra
 *    monto exacto). Se aplica FIFO con PagoExpensaService; el excedente va a
 *    SaldoAFavor. Monto divergente (QR estático externo) => revisión admin.
 * 5. Idempotencia: webhook duplicado o carrera polling/webhook => un solo
 *    ingreso (lock pesimista + control de estado en la transacción).
 */
class PagoQrService
{
    public function __construct(
        protected QrBancoDriver $driver,
        protected PagoExpensaService $pagoExpensaService,
    ) {}

    /**
     * Resolver el driver según configuración (mc4 | fake).
     */
    public static function driver(): QrBancoDriver
    {
        $driver = config('services.qr_banco.driver', 'mc4');

        return match ($driver) {
            'fake' => new FakeQrDriver(),
            default => new Mc4QrDriver(),
        };
    }

    /**
     * QR activo del usuario (pendiente no vencido), si existe.
     */
    public function obtenerActivo(User $user): ?PagoQr
    {
        return PagoQr::pendientes()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Crear una nueva intención de pago (cancelando el QR activo previo).
     *
     * @param  User  $user  Usuario del portal (propietario/inquilino)
     * @param  int   $unidadId  Unidad a pagar (debe ser del pagador)
     * @param  float $monto  Monto ELEGIDO (sugerido o libre)
     * @param  array $seleccion  Expensas sugeridas (informativo)
     */
    public function crearIntencion(User $user, int $unidadId, float $monto, array $seleccion = []): PagoQr
    {
        // Regla de oro: 1 QR activo por usuario
        $activo = $this->obtenerActivo($user);

        if ($activo) {
            $this->cancelar($activo);
        }

        $vencimiento = now()->addHours((int) config('services.qr_banco.vencimiento_horas', 24));
        $alias = $this->generarAlias();

        $glosa = $seleccion['glosa']
            ?? sprintf('Expensas unidad %s', $seleccion['unidad_codigo'] ?? "#{$unidadId}");

        $respuesta = $this->driver->generarQr($alias, $monto, $glosa, $vencimiento);

        return PagoQr::create([
            'user_id' => $user->id,
            'pagador_tipo' => $user->tipo,
            'pagador_id' => $user->persona_id,
            'unidad_id' => $unidadId,
            'alias' => $alias,
            'monto' => $monto,
            'moneda' => 'BOB',
            'seleccion' => $seleccion,
            'glosa' => $glosa,
            'estado' => PagoQr::ESTADO_PENDIENTE,
            'fecha_vencimiento' => $vencimiento,
            'imagen_qr' => $respuesta['imagen_qr'],
        ]);
    }

    /**
     * Cancelar un QR pendiente (mejor esfuerzo en el banco).
     */
    public function cancelar(PagoQr $pagoQr): void
    {
        if (!$pagoQr->estaPendiente()) {
            return;
        }

        $this->driver->inhabilitarQr($pagoQr->alias);

        $pagoQr->update(['estado' => PagoQr::ESTADO_CANCELADO]);
    }

    /**
     * Verificar el estado en el banco y acreditar si está PAGADO.
     *
     * Usado por: polling del portal (wire:poll), webhook y conciliación.
     *
     * @param  bool  $forzarBanco  true = ignora el cache de 5s (botón manual
     *                             "Verificar ahora"); el polling usa cache.
     * @return array{estado: string, pago_procesado: bool}
     */
    public function verificarYAcreditar(PagoQr $pagoQr, bool $forzarBanco = false): array
    {
        // Estado local final: nada que hacer
        if (in_array($pagoQr->estado, [PagoQr::ESTADO_PAGADO, PagoQr::ESTADO_DIVERGENTE], true)) {
            return ['estado' => $pagoQr->estado, 'pago_procesado' => false];
        }

        if ($pagoQr->estado === PagoQr::ESTADO_CANCELADO) {
            return ['estado' => $pagoQr->estado, 'pago_procesado' => false];
        }

        // Consultar al banco. Con cache corto para el polling automático
        // (evita martillar el banco cada 15s × N usuarios); sin cache cuando
        // el usuario presiona "Verificar ahora".
        $consultarBanco = function () use ($pagoQr) {
            return $this->driver->verificarEstado($pagoQr->alias);
        };

        $estadoBanco = $forzarBanco
            ? $consultarBanco()
            : Cache::remember("qr_estado_{$pagoQr->alias}", now()->addSeconds(5), $consultarBanco);

        if (strtoupper($estadoBanco) !== 'PAGADO') {
            // Conciliación pasiva: si ya venció localmente, marcar expirado
            if ($pagoQr->estaVencido()) {
                $pagoQr->update(['estado' => PagoQr::ESTADO_EXPIRADO]);
                return ['estado' => PagoQr::ESTADO_EXPIRADO, 'pago_procesado' => false];
            }

            return ['estado' => $pagoQr->estado, 'pago_procesado' => false];
        }

        // ¡PAGADO! Acreditar (idempotente)
        $resultado = $this->acreditar($pagoQr);

        return [
            'estado' => $resultado['pago_qr']->estado,
            'pago_procesado' => $resultado['procesado_ahora'],
        ];
    }

    /**
     * Acreditar un pago confirmado por el banco.
     *
     * IDEMPOTENTE: transacción + lockForUpdate sobre pagos_qr. Si el estado
     * ya es final (pagado/divergente), otro proceso lo acreditó antes.
     *
     * @param  array  $datosBanco  Datos del webhook (opcional en polling)
     */
    public function acreditar(PagoQr $pagoQr, array $datosBanco = []): array
    {
        return DB::transaction(function () use ($pagoQr, $datosBanco) {
            // Lock pesimista + relectura del estado fresco
            $qr = PagoQr::lockForUpdate()->findOrFail($pagoQr->id);

            if (in_array($qr->estado, [PagoQr::ESTADO_PAGADO, PagoQr::ESTADO_DIVERGENTE], true)) {
                // Idempotencia: ya procesado por webhook/polling concurrente
                return ['pago_qr' => $qr, 'procesado_ahora' => false];
            }

            // [REMEDIACIÓN-1.1] Política compartida por TODOS los canales:
            // un QR cancelado por el usuario es un aborto explícito y ningún
            // canal puede acreditarlo (antes solo el polling lo rechazaba y
            // el webhook sí lo acreditaba vía esta misma función).
            // EXPIRADO sigue acreditable a PROPÓSITO (pago tardío: el dinero
            // entró; test test_pago_tardio_tras_expiracion_se_acredita).
            if ($qr->estado === PagoQr::ESTADO_CANCELADO) {
                Log::info('QR cancelado: notificación de pago ignorada', ['alias' => $qr->alias]);
                return ['pago_qr' => $qr, 'procesado_ahora' => false];
            }

            // Registrar datos del pagador bancario
            $qr->fill([
                'numero_orden' => $datosBanco['numeroOrdenOriginante'] ?? $qr->numero_orden,
                'nombre_cliente' => $datosBanco['nombreCliente'] ?? $qr->nombre_cliente,
                'documento_cliente' => $datosBanco['documentoCliente'] ?? $qr->documento_cliente,
                'fecha_proceso' => $qr->fecha_proceso ?? now(),
            ]);

            // Monto pagado vs. monto del QR: el banco cobra monto exacto, por
            // lo que SIEMPRE coincide para QRs generados por el sistema. Un
            // divergente implica un pago externo con nuestro alias => revisión.
            $montoPagado = (float) ($datosBanco['monto'] ?? $qr->monto);
            $montoQr = (float) $qr->monto;

            if (abs($montoPagado - $montoQr) > 0.01) {
                $qr->estado = PagoQr::ESTADO_DIVERGENTE;
                $qr->save();

                Log::warning('QR con monto divergente - requiere revisión admin', [
                    'alias' => $qr->alias,
                    'monto_qr' => $montoQr,
                    'monto_pagado' => $montoPagado,
                ]);

                return ['pago_qr' => $qr, 'procesado_ahora' => true];
            }

            // Distribución EXACTA elegida por el usuario en el portal
            // (caja-style: expensa_id + concepto + montos por concepto). Si no
            // existe (QRs legacy), fallback a FIFO sobre la unidad.
            $distribucion = $qr->seleccion['distribucion'] ?? null;

            if (is_array($distribucion) && !empty($distribucion)) {
                $expensasPago = $this->validarDistribucion($distribucion, $montoQr);
            } else {
                $expensasPago = $this->construirDistribucionFifo($qr->unidad_id, $montoQr);
            }

            if (empty($expensasPago)) {
                // No había deuda: TODO va a saldo a favor vía una expensa dummy
                // imposible — por seguridad, marcar divergente para revisión.
                $qr->estado = PagoQr::ESTADO_DIVERGENTE;
                $qr->save();

                Log::warning('QR pagado sin deuda pendiente en la unidad', ['alias' => $qr->alias]);
                // [REMEDIACIÓN-2.9] procesado_ahora=false: NADA se acreditó
                // (antes devolvía true y el admin registraba un crédito falso).
                return ['pago_qr' => $qr, 'procesado_ahora' => false];
            }

            // Autenticar al pagador durante la acreditación (creado_por,
            // ActivityLog y totales de caja quedan en su nombre).
            // onceUsingId: login SIN sesión (seguro en webhook y en polling).
            $usuarioOriginal = Auth::user();
            try {
                Auth::onceUsingId($qr->user_id);

                $procesado = $this->pagoExpensaService->procesarRecibo([
                    'expensas' => $expensasPago,
                    'metodo_pago' => 'qr',
                    'referencia_bancaria' => $qr->numero_orden ?? $qr->alias,
                    'pagador_tipo' => $qr->pagador_tipo,
                    'pagador_id' => $qr->pagador_id,
                    'descripcion' => "Pago QR {$qr->alias}",
                    'observaciones' => 'Acreditación automática portal QR',
                ]);
            } finally {
                // Restaurar el usuario del caller (null en webhook: no se toca)
                if ($usuarioOriginal) {
                    Auth::setUser($usuarioOriginal);
                }
            }

            $qr->estado = PagoQr::ESTADO_PAGADO;
            $qr->ingreso_id = $procesado['ingreso']->id;
            $qr->save();

            Log::info('QR pagado y acreditado', [
                'alias' => $qr->alias,
                'ingreso_id' => $qr->ingreso_id,
                'monto' => $qr->monto,
                'pagador' => "{$qr->pagador_tipo}#{$qr->pagador_id}",
            ]);

            return ['pago_qr' => $qr->fresh(), 'procesado_ahora' => true];
        });
    }

    /**
     * Validar la distribución exacta elegida en el portal (caja-style).
     *
     * Por si la deuda cambió desde que se generó el QR (admin registró un
     * pago mientras tanto), cada monto se CAPA al pendiente actual: la
     * diferencia cae a SaldoAFavor vía PagoExpensaService (mismo
     * comportamiento que un excedente en caja).
     *
     * @param  array  $distribucion  [{expensa_id, concepto, monto_aplicar, monto_expensa, monto_agua}]
     * @return array lista lista para PagoExpensaService::procesarRecibo
     */
    protected function validarDistribucion(array $distribucion, float $montoQr): array
    {
        $expensasPago = [];
        $totalDistribucion = 0.0;

        foreach ($distribucion as $fila) {
            $expensa = Expensa::lockForUpdate()->find($fila['expensa_id'] ?? null);

            if (!$expensa) {
                continue; // la expensa ya no existe (raro): se omite
            }

            $concepto = in_array($fila['concepto'] ?? '', ['ambos', 'expensa', 'agua'], true)
                ? $fila['concepto'] : 'ambos';

            $montoAplicar = min(
                (float) ($fila['monto_aplicar'] ?? 0),
                (float) $expensa->expensa_pendiente + (float) $expensa->agua_pendiente
            );

            if ($montoAplicar <= 0) {
                continue; // ya está pagada
            }

            $expensasPago[] = [
                'expensa_id'    => $expensa->id,
                'monto_aplicar' => round($montoAplicar, 2),
                'concepto'      => $concepto,
                'monto_expensa' => round((float) ($fila['monto_expensa'] ?? 0), 2),
                'monto_agua'    => round((float) ($fila['monto_agua'] ?? 0), 2),
            ];

            $totalDistribucion += $montoAplicar;
        }

        // Si la deuda bajó y la distribución quedó menor al monto del QR, el
        // excedente se agrega a la primera fila => SaldoAFavor automático.
        $diferencia = round($montoQr - $totalDistribucion, 2);
        if ($diferencia > 0.01 && !empty($expensasPago)) {
            $expensasPago[0]['monto_aplicar'] = round($expensasPago[0]['monto_aplicar'] + $diferencia, 2);
        }

        return $expensasPago;
    }

    /**
     * Distribuir un monto FIFO sobre las expensas pendientes de la unidad
     * (más antigua primero). El excedente final cae como saldo a favor en la
     * última expensa (comportamiento de PagoExpensaService).
     *
     * @return array<int, array{expensa_id: int, monto_aplicar: float, concepto: string}>
     */
    protected function construirDistribucionFifo(int $unidadId, float $monto): array
    {
        $expensas = Expensa::query()
            ->where('unidad_id', $unidadId)
            ->where(function ($q) {
                $q->where('expensa_pendiente', '>', 0)
                  ->orWhere('agua_pendiente', '>', 0);
            })
            ->join('periodos_expensa as pe', 'pe.id', '=', 'expensas.periodo_id')
            ->orderBy('pe.año')
            ->orderBy('pe.mes')
            ->select('expensas.*')
            ->lockForUpdate()
            ->get();

        $distribucion = [];
        $restante = round($monto, 2);

        foreach ($expensas as $expensa) {
            if ($restante <= 0) {
                break;
            }

            $deudaTotal = (float) $expensa->expensa_pendiente + (float) $expensa->agua_pendiente;
            $aplicar = min($restante, $deudaTotal);

            $distribucion[] = [
                'expensa_id' => $expensa->id,
                'monto_aplicar' => round($aplicar, 2),
                'concepto' => 'ambos',
            ];

            $restante = round($restante - $aplicar, 2);
        }

        // Sobrante sin deuda: aplicarlo como excedente a la primera expensa
        // (PagoExpensaService genera el SaldoAFavor automáticamente).
        if ($restante > 0 && !empty($distribucion)) {
            $distribucion[0]['monto_aplicar'] = round($distribucion[0]['monto_aplicar'] + $restante, 2);
        }

        return $distribucion;
    }

    /**
     * Alias único legible: EXP{año}{mes}U{unidadId}-{random}
     */
    protected function generarAlias(): string
    {
        return 'EXP' . now()->format('ym') . 'U' . Str::upper(Str::random(10));
    }
}
