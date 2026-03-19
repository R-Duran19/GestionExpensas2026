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
        Schema::create('expensa_total', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_expensa')->onDelete('cascade');
            $table->foreignId('propietario_id')->constrained('propietarios')->onDelete('cascade');
            $table->decimal('monto_total', 10, 2)->default(0)->comment('Total a pagar después de aplicar saldos a favor');
            $table->decimal('monto_pagado', 10, 2)->default(0)->comment('Total pagado por el propietario');
            $table->decimal('monto_pendiente', 10, 2)->default(0)->comment('Total pendiente de pago');
            $table->decimal('saldo_a_favor', 10, 2)->default(0)->comment('Saldo a favor del propietario');
            $table->string('estado')->default('pendiente')->comment('pendiente, parcial, pagado');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index(['periodo_id', 'propietario_id']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expensa_total');
    }
};
