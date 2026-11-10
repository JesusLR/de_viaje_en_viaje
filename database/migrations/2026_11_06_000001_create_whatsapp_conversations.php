<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $t) {
            $t->id();
            $t->uuid('session_id');
            $t->string('chat_id', 80);
            $t->string('nombre')->nullable();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->string('estado')->default('sin_asignar')->index();
            $t->unsignedBigInteger('last_timestamp')->default(0);
            $t->timestamps();
            $t->unique(['session_id', 'chat_id']);
        });
        Schema::create('whatsapp_conversation_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('tipo');
            $t->text('texto');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversation_events');
        Schema::dropIfExists('whatsapp_conversations');
    }
};
