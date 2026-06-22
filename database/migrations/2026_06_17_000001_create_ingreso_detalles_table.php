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
        Schema::create('ingreso_detalles', function (Blueprint $table) {
            $table->id();

            // Relación con la cabecera (ingreso)
            $table->foreignId('ingreso_id')
                  ->constrained('ingresos')
                  ->onDelete('cascade')
                  ->comment('ID del ingreso cabecera');

            // Relación con la expensa específica
            $table->foreignId('expensa_id')
                  ->constrained('expensas')
                  ->onDelete('cascade')
                  ->comment('ID de la expensa a la que se aplica el pago');

            // Concepto del pago (granularidad)
            $table->enum('concepto', ['expensa', 'agua', 'ambos'])
                  ->default('ambos')
                  ->comment('expensa: solo mantenimiento, agua: solo agua, ambos: ambos conceptos');

            // Montos aplicados
            $table->decimal('monto_aplicado', 10, 2)->comment('Monto total aplicado a esta expensa');
            $table->decimal('monto_expensa_aplicado', 10, 2)
                  ->default(0)
                  ->comment('Monto aplicado al concepto expensa');
            $table->decimal('monto_agua_aplicado', 10, 2)
                  ->default(0)
                  ->comment('Monto aplicado al concepto agua');

            $table->timestamps();

            // Índices críticos para performance
            $table->index(['ingreso_id', 'expensa_id'], 'idx_detalle_ingreso_expensa');
            $table->index(['expensa_id', 'concepto'], 'idx_detalle_expensa_concepto');
            $table->index('concepto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingreso_detalles');
    }
};
