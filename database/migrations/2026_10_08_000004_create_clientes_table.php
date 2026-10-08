<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('iID');
            $table->string('cNombre', 100);
            $table->string('cPrimerApellido', 100);
            $table->string('cSegundoApellido', 100)->nullable();
            $table->string('cTelefono', 20)->nullable();
            $table->string('cEmail', 150)->nullable();
            $table->string('cRFC', 20)->nullable();
            $table->text('cDireccion')->nullable();
            $table->boolean('lActivo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
