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
        Schema::create('slot_instances', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('time_slot_id');
            $t->date('service_date');
            $t->timestampTz('start_at');
            $t->timestampTz('end_at');
            $t->integer('capacity');
            $t->integer('used_count')->default(0);
            $t->timestampsTz();
            $t->unique(['time_slot_id', 'service_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slot_instances');
    }
};
