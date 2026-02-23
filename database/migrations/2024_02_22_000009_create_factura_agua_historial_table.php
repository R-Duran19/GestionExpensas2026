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
        Schema::create('factura_agua_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_agua_config_id')->constrained('factura_agua_config');
            $table->string('numero_factura_anterior', 50);
            $table->string('numero_factura_nuevo', 50);
            $table->foreignId('cambiado_por')->constrained('users');
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->index('factura_agua_config_id');
            $table->index('cambiado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_agua_historial');
    }
};
