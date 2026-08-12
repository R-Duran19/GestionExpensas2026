<?php

namespace App\Console\Commands;

use App\Helpers\Pii;
use App\Models\ConfiguracionEdificio;
use App\Models\Inquilino;
use App\Models\Propietario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [SEGURIDAD-09 / Fase 3.B] Cifra PII en columnas _enc y calcula _hash.
 *
 * Idempotente y reanudable: solo procesa filas donde _enc ES null.
 * Se puede correr varias veces si falla a mitad sin perder trabajo.
 *
 * Uso:
 *   php artisan pii:encrypt            # procesa todas las tablas
 *   php artisan pii:encrypt --only=propietarios
 *   php artisan pii:encrypt --only=inquilinos
 *   php artisan pii:encrypt --only=configuracion
 *   php artisan pii:encrypt --dry-run  # solo reporta, no escribe
 *   php artisan pii:encrypt --rehash   # recalcular ci_hash (sin tocar _enc)
 *
 * Debe correrse DESPUES de la migration 3.A (columnas _enc existen)
 * y ANTES del switch 3.C (modelos todavia no usan los casts).
 *
 * Si algo sale mal a mitad: reanudar con el mismo comando, retoma donde
 * quedo (procesa solo los _enc null). Las columnas viejas (ci, telefono,
 * email, cuit) NO se tocan, siguen legibles como antes.
 */
class EncryptPii extends Command
{
    protected $signature = 'pii:encrypt
                            {--only= : Limitar a una tabla: propietarios|inquilinos|configuracion}
                            {--dry-run : Solo reportar, no escribir en BD}
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

        $only = $this->option('only');
        $tablas = $this->tablasAProcesar($only);

        if (empty($tablas)) {
            $this->error("Tabla no valida. Opciones: propietarios, inquilinos, configuracion");
            return self::FAILURE;
        }

        // tablasAProcesar() devuelve [nombre => config]; iteramos las keys.
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
    private function procesarTabla(string $nombreTabla): void
    {
        $config = $this->tablasAProcesar($nombreTabla)[$nombreTabla];
        $modelClass = $config['model'];

        $this->newLine();
        $this->info("-- Procesando: {$nombreTabla}");

        // Query base: filas pendientes (con primer _enc null).
        // En --rehash se procesan todas las filas sin filtrar.
        $primerEncField = reset($config['fields']); // ej: ci_enc
        $query = $modelClass::query();

        if (!$this->option('rehash')) {
            // Idempotencia normal: solo filas con _enc null
            $query->whereNull($primerEncField);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->line("   (nada para procesar, todas las filas ya tienen {$primerEncField} poblado)");
            return;
        }

        $this->line("   Filas a procesar: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($query->cursor() as $registro) {
            try {
                $this->procesarRegistro($registro, $config);
                $this->procesados++;
            } catch (\Throwable $e) {
                $this->errores++;
                $this->saltados++;
                Log::error('EncryptPii: error procesando registro', [
                    'tabla' => $nombreTabla,
                    'id' => $registro->id ?? null,
                    'error' => $e->getMessage(),
                ]);
                $bar->clear();
                $this->error("   ERROR en {$nombreTabla} id={$registro->id}: {$e->getMessage()}");
                $bar->display();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Cifra y/o hashea un registro individual.
     */
    private function procesarRegistro(object $registro, array $config): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $rehash = (bool) $this->option('rehash');

        // --- MODO REHASH: solo recalcular _hash ---
        if ($rehash && !empty($config['hash_field'])) {
            $source = $config['hash_source'];
            $valorOriginal = $registro->{$source} ?? $registro->{$config['fields'][$source]} ?? null;
            // Si la columna vieja sigue existiendo (pre-drop), usar esa.
            // Sino, usar la descifrada de _enc.

            if ($valorOriginal === null) {
                // intentar descifrar del _enc
                $encField = $config['fields'][$source];
                if (!empty($registro->{$encField})) {
                    try {
                        $valorOriginal = decrypt($registro->{$encField});
                    } catch (\Throwable $e) {
                        $valorOriginal = null;
                    }
                }
            }

            if (!$dryRun) {
                $registro->{$config['hash_field']} = Pii::hash($valorOriginal ? (string) $valorOriginal : null);
                $registro->save();
            }
            return;
        }

        // --- MODO NORMAL: cifrar _enc + calcular _hash ---
        $dirty = false;
        foreach ($config['fields'] as $campoViejo => $campoEnc) {
            $valor = $registro->{$campoViejo};

            // Skip si el _enc ya esta poblado (idempotencia por fila)
            if (!empty($registro->{$campoEnc})) {
                continue;
            }

            if ($valor === null || $valor === '') {
                // Marcar como procesado con string vacio cifrado para que no se reprocese
                $registro->{$campoEnc} = null;
            } else {
                $registro->{$campoEnc} = Pii::encrypt((string) $valor);
            }
            $dirty = true;
        }

        // Hash de CI (solo propietarios e inquilinos)
        if (!empty($config['hash_field']) && empty($registro->{$config['hash_field']})) {
            $ci = $registro->{$config['hash_source']};
            $registro->{$config['hash_field']} = Pii::hash($ci !== null && $ci !== '' ? (string) $ci : null);
            $dirty = true;
        }

        if ($dirty && !$dryRun) {
            // saveQuietly: no dispara eventos (evita logging, observadores, etc.)
            // que podrian interferir con la migracion masiva.
            $registro->saveQuietly();
        } elseif (!$dirty) {
            $this->saltados++;
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
        } elseif ($this->errores === 0 && $this->procesados > 0) {
            $this->info('OK. Verifica con: php artisan pii:encrypt --dry-run');
        } elseif ($this->errores > 0) {
            $this->error('Hubo errores. Revisa storage/logs/laravel.log y vuelve a correr el comando (es idempotente).');
        }
    }
}
