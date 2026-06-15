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
        Schema::table('expensas', function (Blueprint $table) {
            // Agregar campos de estado de pago
            $table->enum('estado', ['pendiente', 'parcial', 'pagado'])
                  ->default('pendiente')
                  ->after('monto_total')
                  ->comment('Estado de pago de la expensa');

            $table->decimal('monto_pagado', 10, 2)
                  ->default(0)
                  ->after('estado')
                  ->comment('Monto total pagado de esta expensa');

            $table->decimal('monto_pendiente', 10, 2)
                  ->default(0)
                  ->after('monto_pagado')
                  ->comment('Monto pendiente de pago (monto_total - monto_pagado)');

            $table->date('fecha_pago')
                  ->nullable()
                  ->after('monto_pendiente')
                  ->comment('Fecha del último pago registrado');

            // Índices para búsquedas frecuentes
            $table->index('estado');
            $table->index('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expensas', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['estado']);
            $table->dropIndex(['fecha_pago']);

            // Eliminar campos
            $table->dropColumn(['estado', 'monto_pagado', 'monto_pendiente', 'fecha_pago']);
        });
    }
};
