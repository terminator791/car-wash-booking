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
        Schema::create('user_profiles', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('user_id');
            $t->text('address')->nullable();
            $t->string('city')->nullable();
            $t->string('postal_code')->nullable();
            $t->date('birth_date')->nullable();
            $t->string('gender', 16)->nullable(); // check constraint later
            $t->string('avatar_url')->nullable();
            $t->jsonb('preferences')->nullable();
            $t->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
