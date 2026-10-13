<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_paquete_detalles', function (Blueprint $table) {
            $table->date('dFechaInicio')->nullable();
            $table->date('dFechaFin')->nullable();
            $table->decimal('dPrecioRegular', 12, 2)->nullable();
            $table->decimal('dDescuentoUnitario', 12, 2)->nullable();
            $table->string('cPromocion', 150)->nullable();
            $table->string('cTipoDescuento', 15)->nullable();
            $table->decimal('dValorDescuento', 12, 2)->nullable();
        });
        // Las ventas anteriores no tienen esta información histórica: no reconstruirla
        // con valores actuales del catálogo, que pudieron cambiar desde la venta.
    }

    public function down(): void
    {
        Schema::table('venta_paquete_detalles', fn (Blueprint $table) => $table->dropColumn([
            'dFechaInicio', 'dFechaFin', 'dPrecioRegular', 'dDescuentoUnitario',
            'cPromocion', 'cTipoDescuento', 'dValorDescuento',
        ]));
    }
};
