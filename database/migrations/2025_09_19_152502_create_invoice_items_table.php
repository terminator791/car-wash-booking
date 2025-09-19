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
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('invoice_id');
            $t->text('description');
            $t->integer('qty')->default(1);
            $t->integer('unit_price_cents');
            $t->integer('total_price_cents');
            $t->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
