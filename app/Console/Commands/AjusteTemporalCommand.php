<?php

namespace App\Console\Commands;

use App\Support\AjusteTemporalFlag;
use Illuminate\Console\Command;

/**
 * Activa/desactiva la HERRAMIENTA TEMPORAL de sobreescritura de montos
 * (cuadre contra Excel). El estado persiste en storage/app y sobrevive
 * a rebuilds del contenedor.
 *
 * Uso:
 *   php artisan ajuste-temporal on      # activar la herramienta
 *   php artisan ajuste-temporal off     # desactivar (oculta link y da 404)
 *   php artisan ajuste-temporal status  # ver estado actual (default)
 */
class AjusteTemporalCommand extends Command
{
    protected $signature = 'ajuste-temporal {accion? : on | off | status}';

    protected $description = 'Activa/desactiva la herramienta TEMPORAL de ajuste de montos por unidad';

    public function handle(): int
    {
        $accion = $this->argument('accion') ?? 'status';

        return match ($accion) {
            'on' => $this->activar(),
            'off' => $this->desactivar(),
            'status' => $this->estado(),
            default => $this->errorAccion($accion),
        };
    }

    private function activar(): int
    {
        AjusteTemporalFlag::activar();
        $this->info('Herramienta de ajuste temporal ACTIVADA.');
        $this->line('Acceso: /periodos/{id}/ajuste-temporal (link rojo en el detalle del período).');
        $this->warn('Recordá: cada sobrescritura queda registrada en auditoría (activity_logs).');

        return self::SUCCESS;
    }

    private function desactivar(): int
    {
        AjusteTemporalFlag::desactivar();
        $this->info('Herramienta de ajuste temporal DESACTIVADA.');
        $this->line('El link desaparece del detalle del período y la URL directa da 404.');
        $this->line('Los montos ya sobrescritos NO se revierten (ver auditoría para revertir a mano).');

        return self::SUCCESS;
    }

    private function estado(): int
    {
        $estado = AjusteTemporalFlag::enabled() ? 'ACTIVADA' : 'DESACTIVADA';
        $this->info("Herramienta de ajuste temporal: {$estado}.");
        if (AjusteTemporalFlag::enabled()) {
            $this->line('Creada: ' . \Illuminate\Support\Facades\Storage::disk('local')->get(AjusteTemporalFlag::ARCHIVO));
        }

        return self::SUCCESS;
    }

    private function errorAccion(string $accion): int
    {
        $this->error("Acción inválida '{$accion}'. Usá: on | off | status");

        return self::FAILURE;
    }
}
