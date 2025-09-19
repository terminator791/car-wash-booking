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
        Schema::create('locations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->text('address')->nullable();
            $t->string('city')->nullable();
            $t->string('postal_code')->nullable();
            $t->string('timezone')->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->string('phone')->nullable();
            $t->jsonb('operating_hours')->nullable();
            $t->boolean('is_active')->default(true);
            $t->integer('capacity')->default(0);
            $t->jsonb('amenities')->nullable();
            $t->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
