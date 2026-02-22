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
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique()->comment('Código único de unidad (ej: DEP-11-A, OF-3-A)');
            $table->string('tipo_unidad', 20)->comment('departamento, oficina, local, parqueo, baulera');
            $table->string('piso', 50)->comment('Ubicación: Planta X, Sótano X');
            $table->decimal('metros_cuadrados', 8, 2)->comment('Superficie en m²');
            $table->string('categoria_unidad', 20)->comment('domiciliaria, comercial');
            $table->boolean('tiene_medidor')->default(true)->comment('¿Tiene medidor de agua individual?');
            $table->boolean('activo')->default(true)->comment('¿Tiene propietario asignado?');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas frecuentes
            $table->index('tipo_unidad');
            $table->index('categoria_unidad');
            $table->index('activo');
            $table->index('piso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
