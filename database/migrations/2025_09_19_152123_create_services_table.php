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
        Schema::create('services', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('category_id');
            $t->string('name');
            $t->text('description')->nullable();
            $t->integer('base_price_cents')->default(0);
            $t->integer('duration_minutes')->default(0);
            $t->boolean('is_active')->default(true);
            $t->jsonb('features')->nullable();
            $t->string('image_url')->nullable();
            $t->timestampsTz();
            $t->index(['category_id', 'is_active']);
        });

        // Add check constraints
        DB::statement("ALTER TABLE services ADD CONSTRAINT services_base_price_chk CHECK (base_price_cents >= 0)");
        DB::statement("ALTER TABLE services ADD CONSTRAINT services_duration_chk CHECK (duration_minutes >= 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS services_base_price_chk');
        DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS services_duration_chk');
        Schema::dropIfExists('services');
    }
};
