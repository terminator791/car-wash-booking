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
        Schema::create('reviews', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('booking_id');
            $t->uuid('user_id');
            $t->uuid('location_id');
            $t->integer('rating');
            $t->text('comment')->nullable();
            $t->jsonb('rating_breakdown')->nullable();
            $t->boolean('is_verified')->default(false);
            $t->timestampsTz();
            $t->index(['location_id', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
