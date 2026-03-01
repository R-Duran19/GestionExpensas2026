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
        Schema::create('periodos_expensa', function (Blueprint $table) {
            $table->id();
            $table->integer('mes'); // 1-12
            $table->integer('año'); // Ej: 2025
            $table->date('fecha_apertura');
            $table->date('fecha_cierre')->nullable();
            $table->enum('estado', ['abierto', 'cerrado'])->default('abierto');
            $table->text('motivo_reapertura')->nullable();
            $table->foreignId('cerrado_por')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['mes', 'año']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos_expensa');
    }
};
