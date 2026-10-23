<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_turisticos', function (Blueprint $table) {
            $table->string('cTipoItinerario', 16)->default('CATALOGO');
            $table->string('cItinerarioPdf')->nullable();
        });
        Schema::table('venta_paquete_detalles', fn (Blueprint $table) => $table->string('cItinerarioPdf')->nullable());
    }
    public function down(): void
    {
        Schema::table('venta_paquete_detalles', fn (Blueprint $table) => $table->dropColumn('cItinerarioPdf'));
        Schema::table('servicio_turisticos', fn (Blueprint $table) => $table->dropColumn(['cTipoItinerario', 'cItinerarioPdf']));
    }
};
