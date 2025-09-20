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
        Schema::create('service_pricing', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('service_id');
            $t->string('vehicle_type', 16);
            $t->integer('price_cents');
            $t->integer('discount_bp')->default(0); // basis points (0..10000)
            $t->timestampTz('valid_from')->nullable();
            $t->timestampTz('valid_until')->nullable();
            $t->timestampsTz();
            $t->index(['service_id', 'vehicle_type']);
            $t->index(['valid_from']);
            $t->index(['valid_until']);
        });

        // Add check constraints
        DB::statement("ALTER TABLE service_pricing ADD CONSTRAINT service_pricing_price_chk CHECK (price_cents >= 0)");
        DB::statement("ALTER TABLE service_pricing ADD CONSTRAINT service_pricing_discount_chk CHECK (discount_bp >= 0 AND discount_bp <= 10000)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE service_pricing DROP CONSTRAINT IF EXISTS service_pricing_price_chk');
        DB::statement('ALTER TABLE service_pricing DROP CONSTRAINT IF EXISTS service_pricing_discount_chk');
        Schema::dropIfExists('service_pricing');
    }
};
