<?php

namespace App\Services\QrBanco;

/**
 * [PORTAL QR / Fase 4.2] Contrato de driver para pasarelas QR bancarias.
 *
 * Permite cambiar de banco (MC4 hoy, Bisa mañana) sin tocar el flujo del
 * sistema: solo se implementa otro driver y se cambia BANCO_QR_DRIVER.
 */
interface QrBancoDriver
{
    /**
     * Generar un QR dinámico.
     *
     * @param  string  $alias  Alias único del pago
     * @param  float   $monto  Monto exacto
     * @param  string  $glosa  Descripción del pago
     * @param  \DateTimeInterface  $vencimiento  Fecha de vencimiento
     * @return array{imagen_qr: ?string, respuesta: array}
     *
     * @throws \Exception si el banco rechaza la operación
     */
    public function generarQr(string $alias, float $monto, string $glosa, \DateTimeInterface $vencimiento): array;

    /**
     * Consultar el estado actual del QR en el banco.
     *
     * @return string Estado reportado: PENDIENTE | PAGADO | ...
     *
     * @throws \Exception si falla la consulta
     */
    public function verificarEstado(string $alias): string;

    /**
     * Inhabilitar un QR pendiente en el banco (mejor esfuerzo).
     */
    public function inhabilitarQr(string $alias): bool;
}
