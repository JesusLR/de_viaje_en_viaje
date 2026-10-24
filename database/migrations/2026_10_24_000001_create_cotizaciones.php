<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('whatsapp', 30);
            $table->string('fechas', 255);
            $table->unsignedSmallInteger('dias');
            $table->unsignedSmallInteger('noches');
            $table->unsignedSmallInteger('personas');
            $table->unsignedSmallInteger('adultos');
            $table->unsignedSmallInteger('menores');
            $table->json('edades_menores');
            $table->string('hotel', 255)->nullable();
            $table->string('tipo_hotel', 255);
            $table->string('ambiente', 30);
            $table->decimal('presupuesto', 12, 2);
            $table->string('moneda', 3);
            $table->string('estado', 20)->default('NUEVA')->index();
            $table->foreignId('atendida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('atendida_at')->nullable();
            $table->text('observacion_final')->nullable();
            $table->timestamps();
        });
        Schema::create('cotizacion_observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado', 20);
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_observaciones');
        Schema::dropIfExists('cotizaciones');
    }
};
