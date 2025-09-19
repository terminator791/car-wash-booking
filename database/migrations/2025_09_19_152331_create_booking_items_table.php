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
        Schema::create('booking_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('booking_id');
            $t->uuid('service_id');
            $t->integer('qty')->default(1);
            $t->integer('unit_price_cents');
            $t->integer('duration_min')->default(0);
            $t->integer('total_price_cents');
            $t->timestampTz('created_at')->useCurrent();
            $t->index(['booking_id']);
            $t->index(['service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
