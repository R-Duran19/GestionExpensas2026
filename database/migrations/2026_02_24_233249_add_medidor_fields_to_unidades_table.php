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
        Schema::table('unidades', function (Blueprint $table) {
            // Tipo de medidor: individual o compartido (por defecto individual)
            $table->enum('tipo_medidor', ['individual', 'compartido'])->default('individual')->nullable()->after('tiene_medidor');

            // Número físico del medidor (para individual)
            $table->string('numero_medidor', 50)->nullable()->after('tipo_medidor');

            // Grupo de medidor compartido (FK)
            $table->foreignId('medidor_grupo_id')->nullable()->constrained('medidor_grupos')->nullOnDelete()->after('numero_medidor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropForeign(['medidor_grupo_id']);
            $table->dropColumn(['tipo_medidor', 'numero_medidor', 'medidor_grupo_id']);
        });
    }
};
