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
        Schema::create('bookings', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('code')->unique();
            $t->uuid('user_id');
            $t->uuid('vehicle_id');
            $t->uuid('location_id');
            $t->uuid('bay_id')->nullable();
            $t->uuid('slot_instance_id')->nullable();
            $t->timestampTz('scheduled_start');
            $t->timestampTz('scheduled_end');
            $t->string('status', 20); // check + trigger later
            $t->string('source', 10)->default('WEB'); // WEB|MOBILE|STAFF
            $t->text('notes')->nullable();
            $t->jsonb('metadata')->nullable();
            $t->timestampTz('status_changed_at')->nullable();
            $t->timestampsTz();
            $t->index(['location_id', 'scheduled_start']);
            $t->index(['user_id', 'scheduled_start']);
            $t->index(['bay_id', 'scheduled_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
