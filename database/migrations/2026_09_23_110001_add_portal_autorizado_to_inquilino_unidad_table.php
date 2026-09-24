<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [PORTAL / Fase 3.1 — RF-48] Autorización del propietario para que el
     * inquilino acceda a la información financiera de la unidad.
     *
     * La autorización vive en el PIVOTE de ocupación: si cambia el inquilino
     * (nueva fila de pivot), la nueva ocupación arranca SIN autorización.
     */
    public function up(): void
    {
        Schema::table('inquilino_unidad', function (Blueprint $table) {
            $table->boolean('portal_autorizado')
                  ->default(false)
                  ->after('es_inquilino_actual')
                  ->comment('RF-48: el propietario autorizó al inquilino a usar el portal');
            $table->timestamp('autorizado_en')
                  ->nullable()
                  ->after('portal_autorizado')
                  ->comment('Momento de la última autorización');
        });
    }

    public function down(): void
    {
        Schema::table('inquilino_unidad', function (Blueprint $table) {
            $table->dropColumn(['portal_autorizado', 'autorizado_en']);
        });
    }
};
