<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_paquetes', function (Blueprint $table) {
            $table->id('iID');
            $table->foreignId('iIDCliente')->constrained('clientes', 'iID')->restrictOnDelete();
            $table->foreignId('iIDServicio')->constrained('servicio_turisticos', 'iID')->restrictOnDelete();
            $table->foreignId('iIDUsuario')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cPaquete');
            $table->string('cMoneda', 10);
            $table->unsignedInteger('iCantidad');
            $table->decimal('dPrecioUnitario', 12, 2);
            $table->decimal('dTotal', 12, 2);
            $table->date('dFechaViaje');
            $table->text('cObservaciones')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->timestamps();
        });
        Schema::create('pagos_ventas', function (Blueprint $table) {
            $table->id('iID');
            $table->foreignId('iIDVenta')->constrained('ventas_paquetes', 'iID')->restrictOnDelete();
            $table->foreignId('iIDUsuario')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('dImporte', 12, 2);
            $table->string('cMetodo', 30);
            $table->string('cReferencia', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_ventas');
        Schema::dropIfExists('ventas_paquetes');
    }
};
