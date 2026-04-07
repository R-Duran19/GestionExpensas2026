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
            // Agregar campo para relacionar con expensa_total (opcional, para pagos de expensas)
            $table->foreignId('expensa_total_id')
                  ->nullable()
                  ->after('unidad_id')
                  ->constrained('expensa_total')
                  ->nullOnDelete()
                  ->comment('ID de expensa total si es un pago de expensa');

            // Agregar campo para relacionar directamente con propietario (opcional)
            $table->foreignId('propietario_id')
                  ->nullable()
                  ->after('expensa_total_id')
                  ->constrained('propietarios')
                  ->nullOnDelete()
                  ->comment('ID del propietario si es un pago de expensa');

            // Índices para búsquedas frecuentes
            $table->index(['expensa_total_id', 'propietario_id']);
            $table->index('propietario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingresos', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['expensa_total_id', 'propietario_id']);
            $table->dropIndex('propietario_id');

            // Eliminar campos
            $table->dropForeign(['expensa_total_id']);
            $table->dropForeign(['propietario_id']);
            $table->dropColumn(['expensa_total_id', 'propietario_id']);
        });
    }
};
