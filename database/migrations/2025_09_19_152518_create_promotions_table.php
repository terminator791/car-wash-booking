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
        Schema::create('promotions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('type', 16); // PERCENT|FIXED|FREE_SERVICE
            $t->integer('value_cents_or_bp')->default(0);
            $t->integer('min_amount_cents')->default(0);
            $t->integer('max_usage')->nullable();
            $t->integer('per_user_limit')->nullable();
            $t->timestampTz('valid_from')->nullable();
            $t->timestampTz('valid_until')->nullable();
            $t->boolean('is_active')->default(true);
            $t->jsonb('conditions')->nullable();
            $t->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
