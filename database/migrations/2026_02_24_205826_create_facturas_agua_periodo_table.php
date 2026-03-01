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
        Schema::create('facturas_agua_periodo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_expensa');
            $table->foreignId('factura_agua_config_id')->constrained('factura_agua_config');
            $table->string('numero_factura', 50);
            $table->enum('categoria', ['domiciliaria', 'comercial']);
            $table->decimal('importe', 10, 2); // Importe total en Bs.
            $table->decimal('consumo_m3', 10, 2); // Consumo total en M³
            $table->date('fecha_factura');
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();

            $table->unique(['periodo_id', 'factura_agua_config_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas_agua_periodo');
    }
};
