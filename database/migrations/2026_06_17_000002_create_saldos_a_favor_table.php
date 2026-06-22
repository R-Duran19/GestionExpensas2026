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
        Schema::create('saldos_a_favor', function (Blueprint $table) {
            $table->id();

            // Relación con la unidad (NO con propietario - descentralizado)
            $table->foreignId('unidad_id')
                  ->constrained('unidades')
                  ->onDelete('cascade')
                  ->comment('ID de la unidad con saldo a favor');

            // Relación con el ingreso original que generó el saldo
            $table->foreignId('ingreso_id')
                  ->constrained('ingresos')
                  ->onDelete('cascade')
                  ->comment('ID del ingreso que generó este saldo');

            // Montos
            $table->decimal('monto_original', 10, 2)->comment('Monto original del pago');
            $table->decimal('monto_aplicado', 10, 2)->comment('Monto que fue aplicado a expensas');
            $table->decimal('monto_saldo', 10, 2)->comment('Saldo a favor disponible');

            // Estado del saldo
            $table->enum('estado', ['disponible', 'consumido', 'parcialmente_consumido'])
                  ->default('disponible')
                  ->comment('Estado actual del saldo');

            // Consumo
            $table->timestamp('consumido_totalmente_en')->nullable()
                  ->comment('Fecha en que se consumió completamente');

            // Auditoría
            $table->foreignId('creado_por')->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Índices críticos
            $table->index(['unidad_id', 'estado'], 'idx_saldo_unidad_estado');
            $table->index('estado');
            $table->index('monto_saldo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldos_a_favor');
    }
};
