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
        Schema::create('staff', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('user_id');
            $t->uuid('location_id');
            $t->string('employee_id')->unique();
            $t->string('position', 16); // washer|supervisor|manager
            $t->integer('hourly_rate_cents')->default(0);
            $t->jsonb('skills')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestampTz('hired_at')->nullable();
            $t->timestampsTz();
            $t->index(['location_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
