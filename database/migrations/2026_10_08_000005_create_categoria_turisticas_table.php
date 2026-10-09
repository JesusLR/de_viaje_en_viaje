<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_turisticas', function (Blueprint $table) {
            $table->id('iID');
            $table->string('cNombre', 150)->unique();
            $table->text('cDescripcion')->nullable();
            $table->string('cImagen', 255)->nullable();
            $table->boolean('lActivo')->default(true);
            $table->foreignId('iIDUsuario')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_turisticas');
    }
};
