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
        Schema::create('categorias_ingresos_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_ingreso_id')->constrained('categorias_ingresos')->onDelete('cascade');
            $table->string('nombre_anterior');
            $table->string('nombre_nuevo');
            $table->text('descripcion_anterior')->nullable();
            $table->text('descripcion_nueva')->nullable();
            $table->string('color_anterior', 7)->nullable();
            $table->string('color_nuevo', 7)->nullable();
            $table->string('motivo')->nullable();
            $table->foreignId('cambiado_por')->constrained('users');
            $table->timestamps();

            $table->index('categoria_ingreso_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias_ingresos_historial');
    }
};
