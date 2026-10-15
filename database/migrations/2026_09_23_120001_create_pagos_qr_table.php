<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [PORTAL QR / Fase 4.1 — RF-49] Intención de pago con QR bancario.
     *
     * Clave de CONSISTENCIA: 1 QR activo por usuario (unique parcial via
     * índice único en user_id donde estado='pendiente' — se simula con
     * índice compuesto y control en el service, ya que MySQL no soporta
     * índices parciales nativos; el service garantiza la regla).
     */
    public function up(): void
    {
        Schema::create('pagos_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('pagador_tipo', ['propietario', 'inquilino'])
                  ->comment('Convención pagador_* (ADR 2026-08-04)');
            $table->unsignedBigInteger('pagador_id');
            $table->foreignId('unidad_id')->constrained('unidades');

            $table->string('alias', 50)->unique()
                  ->comment('Alias único del QR en el banco');
            $table->decimal('monto', 10, 2)
                  ->comment('Monto ELEGIDO por el usuario (el banco cobra monto exacto)');
            $table->enum('moneda', ['BOB'])->default('BOB');

            $table->text('seleccion')->nullable()
                  ->comment('JSON informativo de expensas sugeridas al generar');
            $table->string('glosa', 200)->nullable();

            $table->enum('estado', ['pendiente', 'pagado', 'expirado', 'cancelado', 'pagado_monto_divergente'])
                  ->default('pendiente')
                  ->comment('Ciclo de vida del QR');
            $table->timestamp('fecha_vencimiento')
                  ->comment('Vencimiento del QR en el banco');

            $table->mediumText('imagen_qr')->nullable()
                  ->comment('Imagen base64 devuelta por el banco');

            $table->string('numero_orden')->nullable()
                  ->comment('Número de orden del banco al pagar');
            $table->string('nombre_cliente')->nullable();
            $table->string('documento_cliente')->nullable();
            $table->timestamp('fecha_proceso')->nullable();

            $table->foreignId('ingreso_id')->nullable()
                  ->comment('Ingreso generado por la acreditación');

            $table->timestamps();

            $table->index(['user_id', 'estado'], 'idx_pagosqr_user_estado');
            $table->index(['estado', 'fecha_vencimiento'], 'idx_pagosqr_estado_venc');
            $table->index(['pagador_tipo', 'pagador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_qr');
    }
};
