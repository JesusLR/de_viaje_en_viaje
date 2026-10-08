<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cPrimerApellido', 100)->nullable()->after('name');
            $table->string('cSegundoApellido', 100)->nullable()->after('cPrimerApellido');
            $table->string('cTelefono', 20)->nullable()->after('cSegundoApellido');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cPrimerApellido', 'cSegundoApellido', 'cTelefono']);
        });
    }
};
