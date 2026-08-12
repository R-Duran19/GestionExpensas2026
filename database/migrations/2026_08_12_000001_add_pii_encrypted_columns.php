<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [SEGURIDAD-09 / Fase 3.A] Preparar columnas para cifrado PII.
 *
 * Agrega columnas NUEVAS (_enc para datos cifrados, _hash para busqueda exacta)
 * SIN tocar las columnas viejas. Estrategia en 3 pasos (sin downtime):
 *
 *   Paso A (esta migration): agregar columnas nullable
 *   Paso B (comando EncryptPii): copiar datos viejos -> nuevos (cifrados)
 *   Paso C (switch codigo): modelos usan _enc con accessor transparente
 *   Paso D (futuro, tras 2 semanas estable): drop columnas viejas
 *
 * Idempotente: cada Schema::table chequea si la columna ya existe antes de
 * agregarla. Se puede correr varias veces sin error (util para rollback+retry).
 *
 * Tablas afectadas:
 *   - propietarios:           ci_enc, telefono_enc, email_enc, ci_hash
 *   - inquilinos:             ci_enc, telefono_enc, email_enc, ci_hash
 *   - configuracion_edificio: telefono_enc, email_enc, cuit_enc
 *
 * Riesgo: nulo (solo agrega columnas nullable, no toca datos existentes).
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- PROPIETARIOS ---
        if (Schema::hasTable('propietarios')) {
            Schema::table('propietarios', function (Blueprint $table) {
                // text en vez de string: los valores cifrados con OpenSSL + APP_KEY
                // pueden llegar a ~300 chars para strings cortos. text evita overflow.
                if (!Schema::hasColumn('propietarios', 'ci_enc')) {
                    $table->text('ci_enc')->nullable()->after('ci');
                }
                if (!Schema::hasColumn('propietarios', 'telefono_enc')) {
                    $table->text('telefono_enc')->nullable()->after('telefono');
                }
                if (!Schema::hasColumn('propietarios', 'email_enc')) {
                    $table->text('email_enc')->nullable()->after('email');
                }
                // ci_hash: HMAC-SHA256 hex (64 chars) para unicidad y busqueda exacta.
                // NO permite LIKE ni rangos, solo igualdad.
                if (!Schema::hasColumn('propietarios', 'ci_hash')) {
                    $table->string('ci_hash', 64)->nullable()->index()->after('ci_enc');
                }
            });
        }

        // --- INQUILINOS ---
        if (Schema::hasTable('inquilinos')) {
            Schema::table('inquilinos', function (Blueprint $table) {
                if (!Schema::hasColumn('inquilinos', 'ci_enc')) {
                    $table->text('ci_enc')->nullable()->after('ci');
                }
                if (!Schema::hasColumn('inquilinos', 'telefono_enc')) {
                    $table->text('telefono_enc')->nullable()->after('telefono');
                }
                if (!Schema::hasColumn('inquilinos', 'email_enc')) {
                    $table->text('email_enc')->nullable()->after('email');
                }
                if (!Schema::hasColumn('inquilinos', 'ci_hash')) {
                    $table->string('ci_hash', 64)->nullable()->index()->after('ci_enc');
                }
            });
        }

        // --- CONFIGURACION EDIFICIO (singleton) ---
        if (Schema::hasTable('configuracion_edificio')) {
            Schema::table('configuracion_edificio', function (Blueprint $table) {
                if (!Schema::hasColumn('configuracion_edificio', 'telefono_enc')) {
                    $table->text('telefono_enc')->nullable()->after('telefono');
                }
                if (!Schema::hasColumn('configuracion_edificio', 'email_enc')) {
                    $table->text('email_enc')->nullable()->after('email');
                }
                if (!Schema::hasColumn('configuracion_edificio', 'cuit_enc')) {
                    $table->text('cuit_enc')->nullable()->after('cuit');
                }
            });
        }
    }

    public function down(): void
    {
        // Eliminar las columnas agregadas. Seguro porque son nullable y no se usan
        // en codigo fuera del switch (commit 3.C).
        foreach (['propietarios', 'inquilinos'] as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    foreach (['ci_enc', 'telefono_enc', 'email_enc', 'ci_hash'] as $col) {
                        if (Schema::hasColumn($tabla, $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }

        if (Schema::hasTable('configuracion_edificio')) {
            Schema::table('configuracion_edificio', function (Blueprint $table) {
                foreach (['telefono_enc', 'email_enc', 'cuit_enc'] as $col) {
                    if (Schema::hasColumn('configuracion_edificio', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
