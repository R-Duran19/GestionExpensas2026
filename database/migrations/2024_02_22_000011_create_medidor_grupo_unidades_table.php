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
        Schema::create('medidor_grupo_unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medidor_grupo_id')->constrained('medidor_grupos')->onDelete('cascade');
            $table->foreignId('unidad_id')->constrained('unidades')->onDelete('cascade');
            $table->date('fecha_asignacion');
            $table->date('fecha_retiro')->nullable();
            $table->timestamps();

            // Nombre personalizado para evitar el límite de 64 caracteres de MySQL
            $table->unique(['medidor_grupo_id', 'unidad_id', 'fecha_asignacion'], 'medidor_grupo_unidad_fecha_unique');
            $table->index('medidor_grupo_id');
            $table->index('unidad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medidor_grupo_unidades');
    }
};
