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
        Schema::create('system_settings', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->string('type', 16)->default('string');
            $t->text('description')->nullable();
            $t->boolean('is_public')->default(false);
            $t->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
