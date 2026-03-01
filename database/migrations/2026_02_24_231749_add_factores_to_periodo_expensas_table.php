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
        Schema::table('periodos_expensa', function (Blueprint $table) {
            $table->decimal('factor_domiciliario', 10, 8)->nullable()->after('estado');
            $table->decimal('factor_comercial', 10, 8)->nullable()->after('factor_domiciliario');
            $table->timestamp('factores_calculados_at')->nullable()->after('factor_comercial');
            $table->foreignId('factores_calculados_por')->nullable()->constrained('users')->nullOnDelete()->after('factores_calculados_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periodos_expensa', function (Blueprint $table) {
            $table->dropForeign(['factores_calculados_por']);
            $table->dropColumn([
                'factor_domiciliario',
                'factor_comercial',
                'factores_calculados_at',
                'factores_calculados_por'
            ]);
        });
    }
};
