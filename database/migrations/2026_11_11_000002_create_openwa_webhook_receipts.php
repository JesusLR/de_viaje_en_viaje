<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openwa_webhook_sessions', function (Blueprint $t) {
            $t->uuid('session_id')->primary();
            $t->timestamp('registered_at');
            $t->timestamp('last_event_at')->nullable();
        });
        Schema::create('openwa_webhook_receipts', function (Blueprint $t) {
            $t->string('event_key', 64)->primary();
            $t->uuid('session_id')->index();
            $t->string('event', 60);
            $t->timestamp('received_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openwa_webhook_receipts');
        Schema::dropIfExists('openwa_webhook_sessions');
    }
};
