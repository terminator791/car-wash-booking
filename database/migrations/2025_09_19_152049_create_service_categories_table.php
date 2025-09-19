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
        Schema::create('service_categories', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('icon_url')->nullable();
            $t->boolean('is_active')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
