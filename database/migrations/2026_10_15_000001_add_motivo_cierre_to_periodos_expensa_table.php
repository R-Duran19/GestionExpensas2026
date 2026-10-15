<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [PERÍODOS 2026-10-15] Motivo opcional al CERRAR un período.
 *
 * Simétrico a motivo_reapertura: queda registrado quién cerró (cerrado_por,
 * ya existía) y por qué se cerró (puede estar vacío — es opcional en el
 * diálogo de confirmación).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodos_expensa', function (Blueprint $table) {
            $table->text('motivo_cierre')->nullable()->after('motivo_reapertura');
        });
    }

    public function down(): void
    {
        Schema::table('periodos_expensa', function (Blueprint $table) {
            $table->dropColumn('motivo_cierre');
        });
    }
};
