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
        Schema::create('factor_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factor_prorrateo_id')->constrained('factor_prorrateo');
            $table->decimal('factor_anterior', 4, 2);
            $table->decimal('factor_nuevo', 4, 2);
            $table->foreignId('cambiado_por')->constrained('users');
            $table->text('motivo');
            $table->date('vigencia_desde');
            $table->timestamps();

            $table->index('factor_prorrateo_id');
            $table->index('cambiado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factor_historial');
    }
};
