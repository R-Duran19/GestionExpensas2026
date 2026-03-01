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
        Schema::create('lectura_grupos_periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_expensa')->onDelete('cascade');
            $table->foreignId('medidor_grupo_id')->constrained('medidor_grupos')->onDelete('cascade');
            $table->decimal('lectura_anterior', 10, 2)->default(0);
            $table->decimal('lectura_actual', 10, 2)->default(0);
            $table->decimal('consumo_total', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Índice único para evitar duplicados: mismo grupo en mismo período
            $table->unique(['periodo_id', 'medidor_grupo_id'], 'unique_periodo_grupo');

            // Índices para búsquedas frecuentes
            $table->index('periodo_id');
            $table->index('medidor_grupo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lectura_grupos_periodos');
    }
};
