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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_pricings');
    }
};
