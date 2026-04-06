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
        Schema::create('secuencias_caja', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20); // 'ingreso' o 'egreso'
            $table->unsignedInteger('ano')->nullable(); // Año para ingresos
            $table->unsignedTinyInteger('mes')->nullable(); // Mes (1-12) para ingresos, NULL para egresos
            $table->unsignedBigInteger('ultimo_numero')->default(0);
            $table->foreignId('actualizado_por')->constrained('users');
            $table->timestamps();

            $table->unique(['tipo', 'ano', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secuencias_caja');
    }
};
