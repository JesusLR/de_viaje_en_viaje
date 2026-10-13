<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_turisticos', function (Blueprint $table) {
            $table->unsignedBigInteger('iIDCategoria')->nullable()->change();
            $table->unsignedBigInteger('iIDDestino')->nullable()->change();
            $table->boolean('lUsaVigencia')->default(true);
            $table->date('dFechaInicioVigencia')->nullable()->change();
            $table->date('dFechaFinVigencia')->nullable()->change();
        });
        Schema::table('ventas_paquetes', fn (Blueprint $table) => $table->date('dFechaViaje')->nullable()->change());
        Schema::table('venta_paquete_detalles', fn (Blueprint $table) => $table->boolean('lUsaVigencia')->default(true));
    }

    public function down(): void
    {
        // Mantener la nulabilidad: existen servicios válidos sin catálogos o fechas.
        Schema::table('servicio_turisticos', fn (Blueprint $table) => $table->dropColumn('lUsaVigencia'));
        Schema::table('venta_paquete_detalles', fn (Blueprint $table) => $table->dropColumn('lUsaVigencia'));
    }
};
