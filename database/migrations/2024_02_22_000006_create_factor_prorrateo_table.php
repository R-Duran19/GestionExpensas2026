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
        Schema::create('factor_prorrateo', function (Blueprint $table) {
            $table->id();
            $table->enum('categoria', ['domiciliaria', 'comercial']);
            $table->decimal('factor', 4, 2); // 2.10 o 3.50
            $table->date('vigencia_desde')->default(now());
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();

            $table->unique(['categoria', 'vigencia_desde']);
            $table->index('categoria');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factor_prorrateo');
    }
};
