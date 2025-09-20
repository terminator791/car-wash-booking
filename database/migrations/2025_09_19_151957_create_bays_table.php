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
        Schema::create('bays', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('location_id');
            $t->string('name');
            $t->string('bay_type', 16);
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();
            $t->index(['location_id', 'is_active']);
        });

        // Add check constraint for bay_type
        DB::statement("ALTER TABLE bays ADD CONSTRAINT bays_type_chk CHECK (bay_type IN ('STANDARD','PREMIUM','TRUCK'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE bays DROP CONSTRAINT IF EXISTS bays_type_chk');
        Schema::dropIfExists('bays');
    }
};
