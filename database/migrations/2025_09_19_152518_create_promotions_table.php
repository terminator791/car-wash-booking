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
        Schema::create('promotions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('type', 16); // PERCENT|FIXED|FREE_SERVICE
            $t->integer('value_cents_or_bp')->default(0);
            $t->integer('min_amount_cents')->default(0);
            $t->integer('max_usage')->nullable();
            $t->integer('per_user_limit')->nullable();
            $t->timestampTz('valid_from')->nullable();
            $t->timestampTz('valid_until')->nullable();
            $t->boolean('is_active')->default(true);
            $t->jsonb('conditions')->nullable();
            $t->timestampsTz();
        });

        // Add check constraints
        DB::statement("ALTER TABLE promotions ADD CONSTRAINT promotions_type_chk CHECK (type IN ('PERCENT','FIXED','FREE_SERVICE'))");
        DB::statement("ALTER TABLE promotions ADD CONSTRAINT promotions_bp_chk CHECK (value_cents_or_bp >= 0 AND value_cents_or_bp <= 10000)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE promotions DROP CONSTRAINT IF EXISTS promotions_type_chk');
        DB::statement('ALTER TABLE promotions DROP CONSTRAINT IF EXISTS promotions_bp_chk');
        Schema::dropIfExists('promotions');
    }
};
