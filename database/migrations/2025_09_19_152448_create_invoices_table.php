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
        Schema::create('invoices', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('booking_id');
            $t->uuid('location_id');
            $t->string('number')->unique(); // unique per tenant; bisa tambah prefix per lokasi di app
            $t->integer('subtotal_cents')->default(0);
            $t->integer('discount_cents')->default(0);
            $t->integer('tax_cents')->default(0);
            $t->integer('total_cents')->default(0);
            $t->timestampTz('issued_at')->nullable();
            $t->timestampsTz();
            $t->index(['location_id', 'issued_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
