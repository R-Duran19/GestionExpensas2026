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
        Schema::create('factura_agua_config', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['domiciliaria_1', 'domiciliaria_2', 'comercial']);
            $table->string('numero_factura', 50); // Número de factura EPSAS
            $table->enum('categoria', ['domiciliaria', 'comercial']);
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();

            $table->unique('tipo');
            $table->index('categoria');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_agua_config');
    }
};
