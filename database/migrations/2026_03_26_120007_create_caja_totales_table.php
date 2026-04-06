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
        Schema::create('caja_totales', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique()->comment('Fecha de los totales');

            // Ingresos por método de pago
            $table->decimal('total_ingresos_efectivo', 12, 2)->default(0);
            $table->decimal('total_ingresos_transferencia', 12, 2)->default(0);
            $table->decimal('total_ingresos_qr', 12, 2)->default(0);
            $table->decimal('total_ingresos_otro', 12, 2)->default(0);
            $table->decimal('total_ingresos', 12, 2)->default(0);

            // Egresos
            $table->decimal('total_egresos', 12, 2)->default(0);

            // Balance
            $table->decimal('balance_neto', 12, 2)->default(0);

            // Contadores
            $table->unsignedInteger('cantidad_ingresos')->default(0);
            $table->unsignedInteger('cantidad_egresos')->default(0);

            $table->foreignId('actualizado_por')->constrained('users');
            $table->timestamps();

            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_totales');
    }
};
