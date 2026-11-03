<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_turisticos', function (Blueprint $table) {
            $table->longText('cDetalles')->nullable();
            $table->longText('cCancelaciones')->nullable();
            $table->longText('cIncluye')->nullable();
            $table->longText('cNoIncluye')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('servicio_turisticos', function (Blueprint $table) {
            $table->dropColumn(['cDetalles', 'cCancelaciones', 'cIncluye', 'cNoIncluye']);
        });
    }
};
