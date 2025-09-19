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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
