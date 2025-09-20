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
        Schema::create('user_profiles', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('user_id');
            $t->text('address')->nullable();
            $t->string('city')->nullable();
            $t->string('postal_code')->nullable();
            $t->date('birth_date')->nullable();
            $t->enum('gender', ['male', 'female', 'other'])->nullable();
            $t->string('avatar_url')->nullable();
            $t->jsonb('preferences')->nullable();
            $t->timestampsTz();
        });

        // Add check constraint for gender
        DB::statement("ALTER TABLE user_profiles ADD CONSTRAINT user_profiles_gender_chk CHECK (gender IS NULL OR gender IN ('male','female','other'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE user_profiles DROP CONSTRAINT IF EXISTS user_profiles_gender_chk');
        Schema::dropIfExists('user_profiles');
    }
};
