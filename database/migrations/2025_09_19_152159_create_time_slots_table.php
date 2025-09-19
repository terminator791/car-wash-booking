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
        Schema::create('time_slots', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('location_id');
            $t->time('start_time');
            $t->time('end_time');
            $t->string('day_of_week', 3); // mon..sun
            $t->integer('max_bookings')->default(1);
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();
            $t->unique(['location_id', 'day_of_week', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
