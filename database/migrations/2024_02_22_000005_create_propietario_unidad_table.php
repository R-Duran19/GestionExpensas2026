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
        Schema::create('propietario_unidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propietario_id')
                  ->constrained('propietarios')
                  ->onDelete('cascade')
                  ->comment('ID del propietario');
            $table->foreignId('unidad_id')
                  ->constrained('unidades')
                  ->onDelete('cascade')
                  ->comment('ID de la unidad');
            $table->date('fecha_inicio')->comment('Fecha desde que es propietario');
            $table->date('fecha_fin')->nullable()->comment('Fecha fin de propiedad (si ya no es dueño)');
            $table->boolean('es_propietario_actual')->default(true)->comment('¿Es el propietario actual?');
            $table->timestamps();

            // Restricciones únicas
            $table->unique(['propietario_id', 'unidad_id', 'fecha_inicio'], 'unique_propiedad_periodo');
            $table->index(['unidad_id', 'es_propietario_actual'], 'idx_unidad_propietario_actual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propietario_unidad');
    }
};
