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
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('invoice_id');
            $t->text('description');
            $t->integer('qty')->default(1);
            $t->integer('unit_price_cents');
            $t->integer('total_price_cents');
            $t->softDeletesTz();
        });

        // Add check constraints
        DB::statement("ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_qty_chk CHECK (qty > 0)");
        DB::statement("ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_unit_price_chk CHECK (unit_price_cents >= 0)");
        DB::statement("ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_total_price_chk CHECK (total_price_cents >= 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE invoice_items DROP CONSTRAINT IF EXISTS invoice_items_qty_chk');
        DB::statement('ALTER TABLE invoice_items DROP CONSTRAINT IF EXISTS invoice_items_unit_price_chk');
        DB::statement('ALTER TABLE invoice_items DROP CONSTRAINT IF EXISTS invoice_items_total_price_chk');
        Schema::dropIfExists('invoice_items');
    }
};
