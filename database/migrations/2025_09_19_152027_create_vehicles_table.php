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
        Schema::create('vehicles', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('user_id');
            $t->string('plate_number')->unique();
            $t->string('model')->nullable();
            $t->string('color')->nullable();
            $t->string('vehicle_type', 16); // check constraint later
            $t->integer('manufacture_year')->nullable();
            $t->boolean('is_default')->default(false);
            $t->timestampsTz();
            $t->softDeletesTz();
            $t->index(['user_id', 'vehicle_type']);
        });

        // Add check constraint for vehicle_type
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT vehicles_type_chk CHECK (vehicle_type IN ('SEDAN','SUV','TRUCK','MOTORBIKE','VAN'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE vehicles DROP CONSTRAINT IF EXISTS vehicles_type_chk');
        Schema::dropIfExists('vehicles');
    }
};
