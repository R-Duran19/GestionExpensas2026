<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [PORTAL / Fase 1.1] Vínculo usuario ↔ persona + login por CI.
     *
     * - tipo/persona_id: misma convención que pagador_tipo/pagador_id de
     *   ingresos (ADR 2026-08-04). Admin: ambas NULL.
     * - ci_hash: HMAC-SHA256 (Pii::hash) para login por CI sin guardar el CI
     *   en claro. Unique: una cuenta por CI (NULLs múltiples permitidos).
     * - activo: baja lógica de accesos sin borrar el usuario.
     * - must_change_password: fuerza cambio de contraseña en primer login
     *   cuando el admin entrega una credencial temporal.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('tipo', ['admin', 'propietario', 'inquilino'])
                  ->default('admin')
                  ->after('id')
                  ->comment('Rol de acceso: admin, propietario o inquilino del portal');
            $table->unsignedBigInteger('persona_id')
                  ->nullable()
                  ->after('tipo')
                  ->comment('ID en propietarios o inquilinos según tipo');
            $table->string('ci_hash', 64)
                  ->nullable()
                  ->unique()
                  ->after('persona_id')
                  ->comment('HMAC-SHA256 del CI para login (nunca el CI en claro)');
            $table->boolean('activo')
                  ->default(true)
                  ->after('ci_hash')
                  ->comment('Baja lógica de acceso al portal');
            $table->boolean('must_change_password')
                  ->default(false)
                  ->after('activo')
                  ->comment('Forzar cambio de contraseña en próximo login');
            $table->timestamp('last_login_at')
                  ->nullable()
                  ->after('must_change_password')
                  ->comment('Último login exitoso');

            $table->index(['tipo', 'persona_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tipo', 'persona_id']);
            $table->dropColumn([
                'tipo',
                'persona_id',
                'ci_hash',
                'activo',
                'must_change_password',
                'last_login_at',
            ]);
        });
    }
};
