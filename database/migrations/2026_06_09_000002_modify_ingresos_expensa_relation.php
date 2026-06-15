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
        Schema::table('ingresos', function (Blueprint $table) {
            // Eliminar la foreign key y columna antigua
            $table->dropForeign(['expensa_total_id']);
            $table->dropColumn('expensa_total_id');

            // Agregar la nueva foreign key directa a expensas
            $table->foreignId('expensa_id')
                  ->nullable()
                  ->after('unidad_id')
                  ->constrained('expensas')
                  ->nullOnDelete()
                  ->comment('ID de expensa directa si es un pago de expensa');

            // Agregar índice para búsquedas frecuentes
            $table->index(['expensa_id', 'unidad_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingresos', function (Blueprint $table) {
            // Eliminar índice y foreign key nueva
            $table->dropIndex(['expensa_id', 'unidad_id']);
            $table->dropForeign(['expensa_id']);
            $table->dropColumn('expensa_id');

            // Restaurar la foreign key antigua (si existía)
            $table->foreignId('expensa_total_id')
                  ->nullable()
                  ->after('unidad_id')
                  ->constrained('expensa_total')
                  ->nullOnDelete()
                  ->comment('ID de expensa total si es un pago de expensa');
        });
    }
};
