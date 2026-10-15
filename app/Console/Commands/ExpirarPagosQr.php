<?php

namespace App\Console\Commands;

use App\Models\PagoQr;
use App\Services\QrBanco\PagoQrService;
use Illuminate\Console\Command;

/**
 * [PORTAL QR / Fase 4] Conciliación activa de QRs.
 *
 * - Marca expirados localmente los QRs vencidos.
 * - Consulta al banco los QRs pendientes próximos a vencer (por si el
 *   webhook se perdió): si el banco dice PAGADO, acredita.
 *
 * Programar cada 15 min en routes/console.php.
 */
class ExpirarPagosQr extends Command
{
    protected $signature = 'qr:expirar {--conciliar : Consultar también al banco los pendientes}';

    protected $description = 'Expira QRs vencidos y concilia pendientes con el banco (Plan 12 Fase 4)';

    public function handle(PagoQrService $service): int
    {
        // 1. Expirar vencidos: inhabilita en el banco + marca estado local
        $vencidos = PagoQr::where('estado', PagoQr::ESTADO_PENDIENTE)
            ->where('fecha_vencimiento', '<=', now())
            ->get();

        foreach ($vencidos as $qr) {
            $service->expirar($qr);
        }

        if ($vencidos->count() > 0) {
            $this->info($vencidos->count() . " QR(s) inhabilitados y expirados");
        }

        // 2. Conciliación activa (opcional): pendientes por vencer en <12h
        if ($this->option('conciliar')) {
            $pendientes = PagoQr::pendientes()
                ->where('fecha_vencimiento', '<=', now()->addHours(12))
                ->get();

            $this->info("Conciliando {$pendientes->count()} QR(s) próximos a vencer...");

            $acreditados = 0;
            foreach ($pendientes as $qr) {
                try {
                    $resultado = $service->verificarYAcreditar($qr);

                    if ($resultado['pago_procesado']) {
                        $acreditados++;
                        $this->info("  ✅ {$qr->alias} PAGADO y acreditado (tardío)");
                    }
                } catch (\Throwable $e) {
                    $this->warn("  ⚠️  {$qr->alias}: " . $e->getMessage());
                }
            }

            if ($acreditados > 0) {
                $this->info("{$acreditados} pago(s) tardío(s) acreditado(s)");
            }
        }

        return self::SUCCESS;
    }
}
