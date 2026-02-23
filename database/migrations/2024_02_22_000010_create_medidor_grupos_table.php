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
        Schema::create('medidor_grupos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100); // Ej: "Locales Piso 1"
            $table->enum('tipo_distribucion', ['equitativa'])->default('equitativa');
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medidor_grupos');
    }
};
