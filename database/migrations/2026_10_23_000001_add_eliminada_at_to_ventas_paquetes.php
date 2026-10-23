<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ventas_paquetes', function (Blueprint $table) {
            $table->timestamp('eliminada_at')->nullable()->index();
            $table->unsignedBigInteger('iIDUsuarioElimina')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ventas_paquetes', function (Blueprint $table) {
            $table->dropColumn(['eliminada_at', 'iIDUsuarioElimina']);
        });
    }
};
