<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destino_turisticos', function (Blueprint $table) {
            $table->id('iID');
            $table->string('cNombre', 150);
            $table->string('cPais', 100)->nullable();
            $table->string('cEstado', 100)->nullable();
            $table->string('cCiudad', 100)->nullable();
            $table->text('cDescripcion')->nullable();
            $table->string('cImagen', 255)->nullable();
            $table->boolean('lActivo')->default(true);
            $table->foreignId('iIDUsuario')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cNombre', 'cPais', 'cEstado', 'cCiudad'], 'destinos_ubicacion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destino_turisticos');
    }
};
