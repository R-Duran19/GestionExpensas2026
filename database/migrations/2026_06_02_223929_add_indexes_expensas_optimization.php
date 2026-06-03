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
        // Intentar crear índices individualmente, ignorando si ya existen
        try {
            Schema::table('propietarios', function (Blueprint $table) {
                $table->index('ci', 'idx_ci');
            });
        } catch (\Exception $e) {
            // El índice ya existe, continuar
        }

        try {
            Schema::table('expensa_total', function (Blueprint $table) {
                $table->index(['periodo_id', 'propietario_id'], 'idx_periodo_propietario');
            });
        } catch (\Exception $e) {
            // El índice ya existe, continuar
        }

        try {
            Schema::table('expensa_total', function (Blueprint $table) {
                $table->index(['periodo_id', 'estado'], 'idx_periodo_estado');
            });
        } catch (\Exception $e) {
            // El índice ya existe, continuar
        }

        try {
            Schema::table('propietario_unidad', function (Blueprint $table) {
                $table->index(['unidad_id', 'es_propietario_actual'], 'idx_unidad_actual');
            });
        } catch (\Exception $e) {
            // El índice ya existe, continuar
        }

        try {
            Schema::table('propietario_unidad', function (Blueprint $table) {
                $table->index(['propietario_id', 'es_propietario_actual'], 'idx_propietario_actual');
            });
        } catch (\Exception $e) {
            // El índice ya existe, continuar
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices de forma segura
        try {
            Schema::table('propietarios', function (Blueprint $table) {
                $table->dropIndex('idx_ci');
            });
        } catch (\Exception $e) {
            // El índice no existe, continuar
        }

        try {
            Schema::table('expensa_total', function (Blueprint $table) {
                $table->dropIndex('idx_periodo_propietario');
            });
        } catch (\Exception $e) {
            // El índice no existe, continuar
        }

        try {
            Schema::table('expensa_total', function (Blueprint $table) {
                $table->dropIndex('idx_periodo_estado');
            });
        } catch (\Exception $e) {
            // El índice no existe, continuar
        }

        try {
            Schema::table('propietario_unidad', function (Blueprint $table) {
                $table->dropIndex('idx_unidad_actual');
            });
        } catch (\Exception $e) {
            // El índice no existe, continuar
        }

        try {
            Schema::table('propietario_unidad', function (Blueprint $table) {
                $table->dropIndex('idx_propietario_actual');
            });
        } catch (\Exception $e) {
            // El índice no existe, continuar
        }
    }
};
