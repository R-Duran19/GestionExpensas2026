<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla de logs de actividad
 *
 * Registra todas las acciones críticas del sistema para auditoría:
 * - Creación, modificación, eliminación de registros
 * - Apertura/cierre de períodos
 * - Cambios de configuración
 * - Verificación de pagos
 *
 * @package Database\Migrations
 */
return new class extends Migration
{
    /**
     * Ejecuta las migraciones (crea la tabla).
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Usuario que realizó la acción (puede ser null si es sistema)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Usuario que realizó la acción');

            // Acción realizada (ej: 'created', 'updated', 'deleted', 'periodo.cerrado')
            $table->string('action', 100)
                  ->index()
                  ->comment('Acción realizada');

            // Modelo afectado (polimórfico opcional)
            $table->string('model_type', 100)
                  ->nullable()
                  ->comment('Tipo del modelo afectado');

            $table->unsignedBigInteger('model_id')
                  ->nullable()
                  ->comment('ID del modelo afectado');

            // Descripción legible
            $table->text('description')
                  ->nullable()
                  ->comment('Descripción legible de la acción');

            // Propiedades adicionales en JSON (ej: cambios específicos)
            $table->json('properties')
                  ->nullable()
                  ->comment('Propiedades adicionales en JSON');

            // Timestamps
            $table->timestamps();

            // Índices para búsquedas eficientes
            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            // Índice compuesto para búsquedas por fecha y usuario
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Revierte las migraciones (elimina la tabla).
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
