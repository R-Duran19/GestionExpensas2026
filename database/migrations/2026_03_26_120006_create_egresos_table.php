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
        Schema::create('egresos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_egreso', 20)->unique();
            $table->date('fecha')->comment('Fecha del egreso');
            $table->foreignId('categoria_egreso_id')->constrained('categorias_egresos');
            $table->string('concepto', 255)->comment('Concepto del egreso');
            $table->string('numero_comprobante', 100)->nullable()->comment('Número de factura/recibo');
            $table->decimal('importe', 12, 2)->comment('Importe del egreso');
            $table->text('descripcion')->nullable();
            $table->string('comprobante_foto', 500)->nullable()->comment('Ruta al archivo WebP del comprobante');
            $table->string('proveedor', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->constrained('users');
            $table->foreignId('actualizado_por')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index('numero_egreso');
            $table->index('fecha');
            $table->index('categoria_egreso_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egresos');
    }
};
