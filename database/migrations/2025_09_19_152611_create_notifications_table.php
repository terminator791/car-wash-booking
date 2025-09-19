<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('user_id');
            $t->string('title');
            $t->text('message');
            $t->string('type', 32);
            $t->string('channel', 16);
            $t->boolean('is_read')->default(false);
            $t->jsonb('metadata')->nullable();
            $t->timestampTz('scheduled_at')->nullable();
            $t->timestampTz('sent_at')->nullable();
            $t->timestampTz('created_at')->useCurrent();
            $t->index(['user_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
