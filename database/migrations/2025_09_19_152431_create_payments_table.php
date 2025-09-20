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
        Schema::create('payments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('booking_id');
            $t->string('transaction_id')->unique();
            $t->string('payment_method', 16);
            $t->string('status', 16);
            $t->integer('amount_cents');
            $t->integer('fee_cents')->default(0);
            $t->string('gateway_provider')->nullable();
            $t->jsonb('gateway_response')->nullable();
            $t->string('idempotency_key')->unique();
            $t->timestampTz('paid_at')->nullable();
            $t->timestampsTz();
            $t->index(['booking_id', 'status']);
        });

        // Add check constraints
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_chk CHECK (payment_method IN ('cash','credit_card','debit_card','e_wallet','bank_transfer'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_chk CHECK (status IN ('pending','processing','completed','failed','refunded'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_amount_chk CHECK (amount_cents > 0)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_fee_chk CHECK (fee_cents >= 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_method_chk');
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_chk');
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_amount_chk');
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_fee_chk');
        Schema::dropIfExists('payments');
    }
};
