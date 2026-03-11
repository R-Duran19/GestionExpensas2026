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
        Schema::create('lecturas_medidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_expensa');
            $table->foreignId('unidad_id')->constrained('unidades');

            // Datos de lectura
            $table->decimal('lectura_anterior', 8, 2)->nullable();
            $table->decimal('lectura_actual', 8, 2)->nullable();
            $table->decimal('consumo_m3', 8, 2);

            // Para lecturas compartidas (referencia al grupo)
            $table->foreignId('medidor_grupo_id')->nullable()->constrained('medidor_grupos')->nullOnDelete();

            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();

            $table->unique(['periodo_id', 'unidad_id']);
            $table->index('periodo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturas_medidor');
    }
};
