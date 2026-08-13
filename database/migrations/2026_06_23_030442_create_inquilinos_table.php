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
        Schema::create('inquilinos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->comment('Nombre del inquilino');
            $table->string('apellido')->comment('Apellido del inquilino');
            $table->string('ci', 20)->unique()->comment('Cédula de Identidad única');
            $table->string('telefono', 20)->nullable()->comment('Teléfono de contacto');
            $table->string('email')->nullable()->comment('Email opcional');
            $table->boolean('activo')->default(true)->comment('¿Está activo?');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('activo');
            $table->index('ci');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquilinos');
    }
};
