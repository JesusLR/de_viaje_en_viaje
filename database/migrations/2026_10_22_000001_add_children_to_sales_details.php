<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_paquete_detalles', function (Blueprint $table) {
            $table->unsignedInteger('iCantidadMenores')->default(0);
            $table->decimal('dPrecioMenor', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('venta_paquete_detalles', fn (Blueprint $table) => $table->dropColumn(['iCantidadMenores', 'dPrecioMenor']));
    }
};
