<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_turisticos', function (Blueprint $table) {
            $table->id('iID');
            $table->string('cCodigo', 30)->unique();
            $table->string('cNombre', 255);
            $table->string('cDescripcionCorta', 500)->nullable();
            $table->longText('cDescripcionCompleta')->nullable();
            
            // Relaciones con Catálogos Independientes
            $table->foreignId('iIDCategoria')->constrained('categoria_turisticas', 'iID')->restrictOnDelete();
            $table->foreignId('iIDDestino')->constrained('destino_turisticos', 'iID')->restrictOnDelete();

            // Imagen Principal
            $table->string('cImagenPrincipal', 255)->nullable();

            // Información Comercial (Precios en DECIMAL 12,2)
            $table->decimal('dPrecioCompra', 12, 2)->default(0.00);
            $table->decimal('dPrecioVenta', 12, 2)->default(0.00);
            $table->string('cMoneda', 10)->default('MXN');

            // Promociones y Descuentos
            $table->boolean('lTienePromocion')->default(false);
            $table->string('cNombrePromocion', 150)->nullable();
            $table->text('cDescripcionPromocion')->nullable();
            $table->enum('cTipoDescuento', ['PORCENTAJE', 'IMPORTE'])->nullable();
            $table->decimal('dValorDescuento', 12, 2)->default(0.00);
            $table->dateTime('dFechaInicioPromocion')->nullable();
            $table->dateTime('dFechaFinPromocion')->nullable();
            $table->boolean('lPromocionActiva')->default(true);

            // Vigencia del Servicio
            $table->date('dFechaInicioVigencia');
            $table->date('dFechaFinVigencia');

            // Estatus Administrativo y Auditoría
            $table->boolean('lActivo')->default(true);
            $table->foreignId('iIDUsuario')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Índices para optimización de filtros de consulta
            $table->index(['lActivo', 'dFechaInicioVigencia', 'dFechaFinVigencia'], 'idx_servicios_vigencia');
            $table->index('cCodigo', 'idx_servicios_codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_turisticos');
    }
};
