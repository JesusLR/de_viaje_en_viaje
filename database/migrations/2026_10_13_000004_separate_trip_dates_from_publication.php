<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('servicio_turisticos', function (Blueprint $table) {
            $table->date('dFechaInicioViaje')->nullable();
            $table->date('dFechaFinViaje')->nullable();
        });
        // Estas fechas se usaban como viaje antes de separar los dos conceptos.
        DB::table('servicio_turisticos')->where('lUsaVigencia', true)->update([
            'dFechaInicioViaje' => DB::raw('dFechaInicioVigencia'),
            'dFechaFinViaje' => DB::raw('dFechaFinVigencia'),
        ]);
    }

    public function down(): void
    {
        Schema::table('servicio_turisticos', fn (Blueprint $table) => $table->dropColumn(['dFechaInicioViaje', 'dFechaFinViaje']));
    }
};
