<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('variables_sistema', function (Blueprint $table) {
            $table->string('clave', 100)->primary();
            $table->unsignedInteger('valor');
            $table->foreignId('actualizada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('variables_sistema'); }
};
