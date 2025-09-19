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
        Schema::create('services', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('category_id');
            $t->string('name');
            $t->text('description')->nullable();
            $t->integer('base_price_cents')->default(0);
            $t->integer('duration_minutes')->default(0);
            $t->boolean('is_active')->default(true);
            $t->jsonb('features')->nullable();
            $t->string('image_url')->nullable();
            $t->timestampsTz();
            $t->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
