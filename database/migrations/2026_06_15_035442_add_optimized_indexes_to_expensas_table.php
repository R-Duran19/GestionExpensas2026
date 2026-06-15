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
            // Covering index para reportes principales
            $table->index(['periodo_id', 'unidad_id', 'estado'], 'idx_reportes_principales');

            // Índices individuales para filtros
            $table->index('unidad_id', 'idx_unidad_id');
            $table->index('periodo_id', 'idx_periodo_id');
            $table->index('estado', 'idx_estado');

            // Índices para cálculos
            $table->index('monto_pagado', 'idx_monto_pagado');
            $table->index('monto_pendiente', 'idx_monto_pendiente');
        });

        Schema::table('propietario_unidad', function (Blueprint $table) {
            // Optimizar left join con propietario actual
            $table->index(['unidad_id', 'es_propietario_actual', 'fecha_fin'], 'idx_propietario_actual_optimizado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expensas', function (Blueprint $table) {
            $table->dropIndex('idx_reportes_principales');
            $table->dropIndex('idx_unidad_id');
            $table->dropIndex('idx_periodo_id');
            $table->dropIndex('idx_estado');
            $table->dropIndex('idx_monto_pagado');
            $table->dropIndex('idx_monto_pendiente');
        });

        Schema::table('propietario_unidad', function (Blueprint $table) {
            $table->dropIndex('idx_propietario_actual_optimizado');
        });
    }
};
