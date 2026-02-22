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
        Schema::create('propietarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->comment('Nombre del propietario');
            $table->string('apellido')->comment('Apellido del propietario');
            $table->string('ci', 20)->unique()->comment('Cédula de Identidad única');
            $table->string('telefono', 20)->comment('Teléfono de contacto');
            $table->string('email')->nullable()->comment('Email opcional');
            $table->boolean('activo')->default(true)->comment('¿Está activo?');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('activo');
            $table->index('ci');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propietarios');
    }
};
