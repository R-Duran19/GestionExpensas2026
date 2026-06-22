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

            // Montos de emisión (inmutables si periodo_cerrado = true)
            $table->decimal('monto_expensa', 10, 2)->default(0)->comment('Expensa de depto/oficina (m² × factor)');
            $table->decimal('monto_agua', 10, 2)->default(0)->comment('Consumo de agua');

            // Saldos pendientes por concepto (granularidad)
            $table->decimal('expensa_pendiente', 10, 2)->default(0)->comment('Saldo pendiente de expensa');
            $table->decimal('agua_pendiente', 10, 2)->default(0)->comment('Saldo pendiente de agua');

            // Control de periodo cerrado (inmutabilidad)
            $table->boolean('periodo_cerrado')->default(false)->comment('Si true, montos de emisión son inmutables');

            // Total de emisión (expensa + agua, SIN deuda histórica)
            $table->decimal('monto_total', 10, 2)->comment('Suma: expensa + agua (emisión del periodo)');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique(['periodo_id', 'unidad_id']);
            $table->index(['unidad_id', 'periodo_id'], 'idx_expensa_unidad_periodo');
            $table->index('periodo_cerrado');
            $table->index('expensa_pendiente');
            $table->index('agua_pendiente');
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
