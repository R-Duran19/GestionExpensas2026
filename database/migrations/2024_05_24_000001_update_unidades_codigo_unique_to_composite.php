<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar el índice unique actual (solo codigo)
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropUnique('unidades_codigo_unique');
        });

        // Crear nuevo índice unique compuesto (codigo + tipo_unidad)
        Schema::table('unidades', function (Blueprint $table) {
            $table->unique(['codigo', 'tipo_unidad'], 'unidades_codigo_tipo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir: eliminar índice compuesto
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropUnique('unidades_codigo_tipo_unique');
        });

        // Restaurar índice original
        Schema::table('unidades', function (Blueprint $table) {
            $table->string('codigo', 20)->unique()->change();
        });
    }
};
