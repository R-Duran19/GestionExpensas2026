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
        Schema::create('inquilino_unidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->onDelete('cascade');
            $table->foreignId('unidad_id')->constrained('unidades')->onDelete('cascade');
            $table->date('fecha_inicio')->comment('Fecha desde que es inquilino');
            $table->date('fecha_fin')->nullable()->comment('Fecha fin del alquiler');
            $table->boolean('es_inquilino_actual')->default(true);
            $table->decimal('monto_alquiler', 10, 2)->nullable()->comment('Monto referencial del alquiler');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['inquilino_id', 'unidad_id', 'fecha_inicio']);
            $table->index(['unidad_id', 'es_inquilino_actual']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquilino_unidad');
    }
};
