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
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_recibo', 20)->unique();
            $table->date('fecha')->comment('Fecha del ingreso');

            // Categoría y tipo
            $table->foreignId('categoria_ingreso_id')->constrained('categorias_ingresos');
            $table->enum('tipo_ingreso', ['expensa', 'extraordinario'])
                  ->default('expensa')
                  ->comment('expensa: pago de expensa, extraordinario: multas/cuotas especiales');

            // Montos
            $table->decimal('importe', 12, 2)->comment('Importe del ingreso (compatibilidad)');
            $table->decimal('monto_total', 12, 2)
                  ->default(0)
                  ->comment('Monto total del recibo (suma de detalles)');

            // Método de pago
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'qr', 'otro'])->default('efectivo');

            // Detalles adicionales
            $table->text('descripcion')->nullable();
            $table->string('comprobante_foto', 500)->nullable()->comment('Ruta al archivo WebP del comprobante');
            $table->string('referencia_bancaria', 100)->nullable()->comment('Referencia de transferencia');

            // Relación opcional con unidad (obtenida de detalles en pagos de expensa)
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->onDelete('set null');

            // Pagador: registra quién hizo el pago (propietario o inquilino)
            // pagador_tipo es el tipo de modelo y pagador_id el ID en su tabla
            $table->enum('pagador_tipo', ['propietario', 'inquilino'])->nullable()
                  ->comment('Tipo de persona que pagó: propietario o inquilino');
            $table->unsignedBigInteger('pagador_id')->nullable()
                  ->comment('ID del pagador en su tabla (propietarios o inquilinos)');

            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);

            // Auditoría
            $table->foreignId('creado_por')->constrained('users');
            $table->foreignId('actualizado_por')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();

            // Índices
            $table->index('numero_recibo');
            $table->index('fecha');
            $table->index('categoria_ingreso_id');
            $table->index('metodo_pago');
            $table->index(['tipo_ingreso', 'fecha'], 'idx_ingreso_tipo_fecha');
            $table->index(['pagador_tipo', 'pagador_id'], 'idx_ingreso_pagador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
