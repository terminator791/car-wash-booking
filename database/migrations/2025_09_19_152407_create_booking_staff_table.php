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
        Schema::create('booking_staff', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('booking_id');
            $t->uuid('staff_id');
            $t->string('role', 16)->default('primary'); // primary|assistant
            $t->timestampTz('assigned_at')->nullable();
            $t->timestampTz('started_at')->nullable();
            $t->timestampTz('completed_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestampsTz();
            $t->unique(['booking_id', 'staff_id', 'role']);
        });

        // Add check constraint
        DB::statement("ALTER TABLE booking_staff ADD CONSTRAINT booking_staff_role_chk CHECK (role IN ('primary','assistant'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE booking_staff DROP CONSTRAINT IF EXISTS booking_staff_role_chk');
        Schema::dropIfExists('booking_staff');
    }
};
