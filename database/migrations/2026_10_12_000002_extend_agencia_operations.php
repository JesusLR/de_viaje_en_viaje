<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas_paquetes', function (Blueprint $table) {
            $table->string('cNombre', 150)->nullable();
            $table->string('cTipoOperacion', 15)->default('APARTADO');
            $table->timestamp('finalizada_at')->nullable()->index();
        });
        Schema::table('pagos_ventas', function (Blueprint $table) {
            $table->date('dFechaPago')->nullable()->index();
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('iIDUsuarioAnula')->nullable()->constrained('users')->nullOnDelete();
        });
        DB::table('pagos_ventas')->update(['dFechaPago' => DB::raw('DATE(created_at)')]);
        // Las operaciones anteriores ya pagadas se conservan como ventas liquidadas.
        DB::table('ventas_paquetes')->whereNull('cancelada_at')
            ->whereRaw('dTotal = (select coalesce(sum(dImporte), 0) from pagos_ventas where pagos_ventas.iIDVenta = ventas_paquetes.iID)')
            ->update(['finalizada_at' => DB::raw('updated_at')]);
        Schema::create('venta_paquete_detalles', function (Blueprint $table) {
            $table->id('iID');
            $table->foreignId('iIDVenta')->constrained('ventas_paquetes', 'iID')->restrictOnDelete();
            $table->foreignId('iIDServicio')->constrained('servicio_turisticos', 'iID')->restrictOnDelete();
            $table->string('cPaquete');
            $table->unsignedInteger('iCantidad');
            $table->decimal('dPrecioUnitario', 12, 2);
            $table->decimal('dSubtotal', 12, 2);
            $table->timestamps();
        });
        DB::table('ventas_paquetes')->orderBy('iID')->chunkById(100, function ($ventas) {
            foreach ($ventas as $venta) {
                DB::table('venta_paquete_detalles')->insert([
                    'iIDVenta' => $venta->iID, 'iIDServicio' => $venta->iIDServicio,
                    'cPaquete' => $venta->cPaquete, 'iCantidad' => $venta->iCantidad,
                    'dPrecioUnitario' => $venta->dPrecioUnitario, 'dSubtotal' => $venta->dTotal,
                    'created_at' => $venta->created_at, 'updated_at' => $venta->updated_at,
                ]);
            }
        }, 'iID');
        Schema::create('movimientos_financieros', function (Blueprint $table) {
            $table->id('iID');
            $table->string('cTipo', 10);
            $table->string('cConcepto', 150);
            $table->decimal('dImporte', 12, 2);
            $table->string('cMoneda', 10);
            $table->string('cMetodo', 30);
            $table->date('dFecha')->index();
            $table->text('cObservaciones')->nullable();
            $table->foreignId('iIDUsuario')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('iIDUsuarioAnula')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_financieros');
        Schema::dropIfExists('venta_paquete_detalles');
        Schema::table('pagos_ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('iIDUsuarioAnula');
            $table->dropColumn(['dFechaPago', 'anulado_at']);
        });
        Schema::table('ventas_paquetes', fn (Blueprint $table) => $table->dropColumn(['cNombre', 'cTipoOperacion', 'finalizada_at']));
    }
};
