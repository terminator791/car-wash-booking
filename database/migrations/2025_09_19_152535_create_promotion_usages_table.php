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
        Schema::create('promotion_usage', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('promotion_id');
            $t->uuid('booking_id');
            $t->uuid('user_id');
            $t->integer('discount_cents')->default(0);
            $t->timestampTz('used_at')->nullable();
            $t->timestampTz('created_at')->useCurrent();
            $t->unique(['promotion_id', 'booking_id']);
            $t->index(['promotion_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_usages');
    }
};
