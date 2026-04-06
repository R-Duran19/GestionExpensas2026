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
            $table->foreignId('categoria_ingreso_id')->constrained('categorias_ingresos');
            $table->decimal('importe', 12, 2)->comment('Importe del ingreso');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'qr', 'otro'])->default('efectivo');
            $table->text('descripcion')->nullable();
            $table->string('comprobante_foto', 500)->nullable()->comment('Ruta al archivo WebP del comprobante');
            $table->string('referencia_bancaria', 100)->nullable()->comment('Referencia de transferencia');
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->onDelete('set null');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->constrained('users');
            $table->foreignId('actualizado_por')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index('numero_recibo');
            $table->index('fecha');
            $table->index('categoria_ingreso_id');
            $table->index('metodo_pago');
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
