<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cotizaciones_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->string('cliente_nombre', 255);
            $table->string('nombre', 150)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('moneda', 3);
            $table->decimal('total', 12, 2);
            $table->json('partidas');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cotizaciones_servicios'); }
};
