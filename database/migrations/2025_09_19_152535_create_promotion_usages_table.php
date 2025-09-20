<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $t->softDeletesTz();
            $t->unique(['promotion_id', 'booking_id']);
            $t->index(['promotion_id', 'user_id']);
        });

        // Add check constraint
        DB::statement("ALTER TABLE promotion_usage ADD CONSTRAINT promotion_usage_discount_chk CHECK (discount_cents >= 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE promotion_usage DROP CONSTRAINT IF EXISTS promotion_usage_discount_chk');
        Schema::dropIfExists('promotion_usage');
    }
};
