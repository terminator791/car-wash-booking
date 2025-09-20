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
        Schema::create('users', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('email')->unique();
            $t->string('phone')->nullable()->unique();
            $t->string('password');
            $t->string('full_name')->nullable();
            $t->string('role', 16); // (i prefer spatie/laravel-permission but for simplicity, just a string here)
            $t->boolean('is_verified')->default(false);
            $t->timestampTz('email_verified_at')->nullable();
            $t->timestampsTz();
            $t->softDeletesTz();
        });

        // Add check constraint for role
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_chk CHECK (role IN ('customer','staff','admin'))");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_chk');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
