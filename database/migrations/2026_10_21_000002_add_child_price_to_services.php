<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_turisticos', function (Blueprint $table) {
            $table->boolean('lPrecioMenorActivo')->default(false);
            $table->decimal('dPrecioMenor', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('servicio_turisticos', function (Blueprint $table) {
            $table->dropColumn(['lPrecioMenorActivo', 'dPrecioMenor']);
        });
    }
};
