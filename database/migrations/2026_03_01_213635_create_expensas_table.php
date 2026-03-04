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
        Schema::create('expensas', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('periodo_id')->constrained('periodos_expensa')->onDelete('cascade');
            $table->foreignId('unidad_id')->constrained('unidades')->onDelete('cascade');

            // Desglose de montos
            $table->decimal('monto_expensa', 10, 2)->default(0)->comment('Expensa de depto/oficina (m² × factor)');
            $table->decimal('monto_agua', 10, 2)->default(0)->comment('Consumo de agua');
            $table->decimal('deuda_anterior', 10, 2)->default(0)->comment('Deuda de meses anteriores');

            // Totales
            $table->decimal('monto_total', 10, 2)->comment('Suma: expensa + agua + deuda anterior');
            $table->decimal('monto_pagado', 10, 2)->default(0)->comment('Monto pagado hasta ahora');
            $table->decimal('monto_pendiente', 10, 2)->default(0)->comment('Saldo pendiente de pago');
            $table->decimal('saldo_a_favor', 10, 2)->default(0)->comment('Si pagó de más, queda como crédito');

            // Estado
            $table->enum('estado', ['pendiente', 'parcial', 'pagado'])->default('pendiente');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique(['periodo_id', 'unidad_id']);
            $table->index('estado');
            $table->index(['periodo_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expensas');
    }
};
