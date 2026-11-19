<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_turisticos', function (Blueprint $table) {
            $table->boolean('lOpcionesPrecio')->default(false);
            $table->json('opciones_precio')->nullable();
        });
        Schema::table('venta_paquete_detalles', fn (Blueprint $table) => $table->json('opcion_precio')->nullable());
    }

    public function down(): void
    {
        Schema::table('venta_paquete_detalles', fn (Blueprint $table) => $table->dropColumn('opcion_precio'));
        Schema::table('servicio_turisticos', fn (Blueprint $table) => $table->dropColumn(['lOpcionesPrecio', 'opciones_precio']));
    }
};
