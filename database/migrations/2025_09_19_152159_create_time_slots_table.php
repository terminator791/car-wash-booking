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
        Schema::create('time_slots', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('location_id');
            $t->time('start_time');
            $t->time('end_time');
            $t->enum('day_of_week', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']);
            $t->integer('max_bookings')->default(1);
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();
            $t->unique(['location_id', 'day_of_week', 'start_time', 'end_time']);
        });

        // Add check constraint
        DB::statement("ALTER TABLE time_slots ADD CONSTRAINT time_slots_dow_chk CHECK (day_of_week IN ('mon','tue','wed','thu','fri','sat','sun'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE time_slots DROP CONSTRAINT IF EXISTS time_slots_dow_chk');
        Schema::dropIfExists('time_slots');
    }
};
