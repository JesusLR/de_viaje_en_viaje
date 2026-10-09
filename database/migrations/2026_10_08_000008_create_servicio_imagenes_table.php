<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_imagenes', function (Blueprint $table) {
            $table->id('iID');
            $table->foreignId('iIDServicio')->constrained('servicio_turisticos', 'iID')->cascadeOnDelete();
            $table->string('cRutaImagen', 255);
            $table->integer('iOrden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_imagenes');
    }
};
