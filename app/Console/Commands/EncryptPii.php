<?php

namespace App\Console\Commands;

use App\Helpers\Pii;
use App\Models\ConfiguracionEdificio;
use App\Models\Inquilino;
use App\Models\Propietario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * [SEGURIDAD-09 / Fase 3.B] Cifra PII en columnas _enc y calcula _hash.
 *
 * Idempotente y reanudable: solo procesa filas donde _enc ES null.
 * Se puede correr varias veces si falla a mitad sin perder trabajo.
 *
 * IMPORTANTE (post-switch 3.C): los modelos tienen cast 'encrypted' sobre
 * las columnas _enc. Si escribieramos via \$model->ci_enc = X, Laravel
 * cifraria dos veces. Por eso este comando escribe directo en la BD via
 * DB::table()->update() y lee con getRawOriginal() (sin accessor ni cast).
 *
 * Uso:
 *   php artisan pii:encrypt                          # procesa pendientes
 *   php artisan pii:encrypt --only=propietarios
 *   php artisan pii:encrypt --dry-run                # solo reporta
 *   php artisan pii:encrypt --force                  # re-procesa TODO
 *   php artisan pii:encrypt --rehash                 # solo recalcular ci_hash
 *
 * Orden recomendado post-deploy:
 *   1. php artisan pii:encrypt --dry-run    (ver cuantos hay)
 *   2. php artisan pii:encrypt              (migrar pendientes)
 *
 * Recuperacion de datos mal migrados (doble-cifrado u otro problema):
 *   php artisan pii:encrypt --force          (re-cifra TODO desde columnas viejas)
 */
class EncryptPii extends Command
{
    protected $signature = 'pii:encrypt
                            {--only= : Limitar a una tabla: propietarios|inquilinos|configuracion}
                            {--dry-run : Solo reportar, no escribir en BD}
                            {--force : Re-procesar TODAS las filas (ignora idempotencia)}
                            {--rehash : Solo recalcular ci_hash (tras rotar PII_HASH_KEY)}';

    protected $description = 'Cifra PII (CI, telefono, email, cuit) en columnas _enc y calcula _hash. Idempotente.';

    private int $procesados = 0;
    private int $saltados = 0;
    private int $errores = 0;

    public function handle(): int
    {
        $this->info('=== Cifrado de PII ===');
        $this->info('Entorno: ' . app()->environment());

        if ($this->option('dry-run')) {
            $this->warn('MODO DRY-RUN: no se escribira en la BD.');
        }
        if ($this->option('force')) {
            $this->warn('MODO FORCE: se re-procesan TODAS las filas desde las columnas viejas.');
        }

        $only = $this->option('only');
        $tablas = $this->tablasAProcesar($only);

        if (empty($tablas)) {
            $this->error("Tabla no valida. Opciones: propietarios, inquilinos, configuracion");
            return self::FAILURE;
        }

        foreach (array_keys($tablas) as $tabla) {
            $this->procesarTabla($tabla);
        }

        $this->reporteFinal();
        return $this->errores > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Mapeo logico -> configuracion de cada tabla.
     */
    private function tablasAProcesar(?string $only): array
    {
        $todas = [
            'propietarios' => [
                'model' => Propietario::class,
                'fields' => ['ci' => 'ci_enc', 'telefono' => 'telefono_enc', 'email' => 'email_enc'],
                'hash_field' => 'ci_hash',
                'hash_source' => 'ci',
            ],
            'inquilinos' => [
                'model' => Inquilino::class,
                'fields' => ['ci' => 'ci_enc', 'telefono' => 'telefono_enc', 'email' => 'email_enc'],
                'hash_field' => 'ci_hash',
                'hash_source' => 'ci',
            ],
            'configuracion' => [
                'model' => ConfiguracionEdificio::class,
                'fields' => ['telefono' => 'telefono_enc', 'email' => 'email_enc', 'cuit' => 'cuit_enc'],
                'hash_field' => null,
                'hash_source' => null,
            ],
        ];

        if ($only) {
            return array_key_exists($only, $todas) ? [$only => $todas[$only]] : [];
        }

        return $todas;
    }

    /**
     * Procesa una tabla completa con cursor (memoria constante).
     */
    private function procesarTabla(string $nombreLogico): void
    {
        $config = $this->tablasAProcesar($nombreLogico)[$nombreLogico];
        $modelClass = $config['model'];
        $tabla = (new $modelClass)->getTable(); // nombre real en BD

        $this->newLine();
        $this->info("-- Procesando: {$nombreLogico} (tabla: {$tabla})");

        // Construir query base
        $primerEncField = reset($config['fields']); // ej: ci_enc
        $query = $modelClass::query()->select('*'); // select * explícito para tener todas las columnas crudas

        // Filtrar por pendientes a menos que sea --force o --rehash
        if (!$this->option('force') && !$this->option('rehash')) {
            $query->whereNull($primerEncField);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->line("   (nada para procesar)");
            return;
        }

        $this->line("   Filas a procesar: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($query->cursor() as $registro) {
            try {
                $this->procesarRegistro($registro, $config, $tabla);
                $this->procesados++;
            } catch (\Throwable $e) {
                $this->errores++;
                $this->saltados++;
                Log::error('EncryptPii: error procesando registro', [
                    'tabla' => $tabla,
                    'id' => $registro->id ?? null,
                    'error' => $e->getMessage(),
                ]);
                $bar->clear();
                $this->error("   ERROR en {$tabla} id={$registro->id}: {$e->getMessage()}");
                $bar->display();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Cifra y/o hashea un registro individual.
     *
     * IMPORTANTE: lee via getRawOriginal() (sin accessor ni cast) y escribe
     * via DB::table()->update() (sin pasar por el modelo). Esto evita que
     * el cast 'encrypted' sobre las columnas _enc produzca doble-cifrado.
     */
    private function procesarRegistro(object $registro, array $config, string $tabla): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $rehash = (bool) $this->option('rehash');
        $force = (bool) $this->option('force');

        // --- MODO REHASH: solo recalcular _hash ---
        if ($rehash && !empty($config['hash_field'])) {
            // Priorizar columna vieja original (sin cifrar). Si no existe (post-drop 3.D),
            // descifrar de _enc.
            $source = $config['hash_source'];
            $valorOriginal = $registro->getRawOriginal($source);

            if (($valorOriginal === null || $valorOriginal === '') && !empty($config['fields'][$source])) {
                $encField = $config['fields'][$source];
                $encValue = $registro->getRawOriginal($encField);
                if ($encValue !== null && $encValue !== '') {
                    try {
                        $valorOriginal = decrypt($encValue);
                    } catch (\Throwable $e) {
                        $valorOriginal = null;
                    }
                }
            }

            if ($dryRun) {
                return;
            }

            DB::table($tabla)
                ->where('id', $registro->id)
                ->update([
                    $config['hash_field'] => Pii::hash($valorOriginal !== null && $valorOriginal !== '' ? (string) $valorOriginal : null),
                ]);
            return;
        }

        // --- MODO NORMAL: cifrar _enc + calcular _hash ---
        $update = [];

        foreach ($config['fields'] as $campoViejo => $campoEnc) {
            // Idempotencia por fila (salvo --force)
            if (!$force && !empty($registro->getRawOriginal($campoEnc))) {
                continue;
            }

            // Leer columna vieja ORIGINAL (sin accessor, sin cast)
            $valor = $registro->getRawOriginal($campoViejo);

            if ($valor === null || $valor === '') {
                $update[$campoEnc] = null;
            } else {
                // Cifrar UNA sola vez. NO pasar por el modelo (evita doble-cifrado).
                $update[$campoEnc] = Pii::encrypt((string) $valor);
            }
        }

        // Hash de CI (solo propietarios e inquilinos)
        if (!empty($config['hash_field'])) {
            if ($force || empty($registro->getRawOriginal($config['hash_field']))) {
                $ci = $registro->getRawOriginal($config['hash_source']);
                $update[$config['hash_field']] = Pii::hash($ci !== null && $ci !== '' ? (string) $ci : null);
            }
        }

        if (empty($update)) {
            $this->saltados++;
            return;
        }

        if (!$dryRun) {
            // Escribir directo en BD, sin pasar por el modelo.
            DB::table($tabla)->where('id', $registro->id)->update($update);
        }
    }

    private function reporteFinal(): void
    {
        $this->newLine();
        $this->newLine();
        $this->info('=== Reporte final ===');
        $this->line("Procesados: {$this->procesados}");
        $this->line("Saltados:    {$this->saltados}");
        $this->line("Errores:     {$this->errores}");

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: no se escribio en la BD.');
        } elseif ($this->option('force')) {
            $this->info('FORCE: todas las filas re-procesadas desde columnas viejas.');
        } elseif ($this->errores === 0 && $this->procesados > 0) {
            $this->info('OK. Verifica con: php artisan pii:encrypt --dry-run');
        } elseif ($this->errores > 0) {
            $this->error('Hubo errores. Revisa storage/logs/laravel.log y vuelve a correr el comando (es idempotente).');
        }
    }
}
