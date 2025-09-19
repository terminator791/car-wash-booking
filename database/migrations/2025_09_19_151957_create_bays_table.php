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
        Schema::create('bays', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('location_id');
            $t->string('name');
            $t->string('bay_type', 16); // STANDARD|PREMIUM|TRUCK
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();
            $t->index(['location_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bays');
    }
};
