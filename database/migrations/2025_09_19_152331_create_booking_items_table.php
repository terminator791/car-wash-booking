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
        Schema::create('booking_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('booking_id');
            $t->uuid('service_id');
            $t->integer('qty')->default(1);
            $t->integer('unit_price_cents');
            $t->integer('duration_min')->default(0);
            $t->integer('total_price_cents');
            $t->timestampTz('created_at')->useCurrent();
            $t->softDeletesTz();
            $t->index(['booking_id']);
            $t->index(['service_id']);
        });

        // Add check constraints
        DB::statement("ALTER TABLE booking_items ADD CONSTRAINT booking_items_qty_chk CHECK (qty > 0)");
        DB::statement("ALTER TABLE booking_items ADD CONSTRAINT booking_items_unit_price_chk CHECK (unit_price_cents >= 0)");
        DB::statement("ALTER TABLE booking_items ADD CONSTRAINT booking_items_total_price_chk CHECK (total_price_cents >= 0)");
        DB::statement("ALTER TABLE booking_items ADD CONSTRAINT booking_items_duration_chk CHECK (duration_min >= 0)");

        // Create recalc trigger function
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION booking_recalc_end()
            RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
            sum_min int;
            BEGIN
            SELECT COALESCE(SUM(duration_min * qty), 0) INTO sum_min
            FROM booking_items WHERE booking_id = NEW.booking_id;

            UPDATE bookings
                SET scheduled_end = scheduled_start + make_interval(mins => sum_min)
            WHERE id = NEW.booking_id AND scheduled_start IS NOT NULL;

            RETURN NEW;
            END $$;
        SQL);

        // Create triggers
        DB::statement("CREATE TRIGGER trg_booking_items_recalc_ins AFTER INSERT ON booking_items FOR EACH ROW EXECUTE FUNCTION booking_recalc_end()");
        DB::statement("CREATE TRIGGER trg_booking_items_recalc_upd AFTER UPDATE OF qty, duration_min ON booking_items FOR EACH ROW EXECUTE FUNCTION booking_recalc_end()");
        DB::statement("CREATE TRIGGER trg_booking_items_recalc_del AFTER DELETE ON booking_items FOR EACH ROW EXECUTE FUNCTION booking_recalc_end()");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers
        DB::statement('DROP TRIGGER IF EXISTS trg_booking_items_recalc_ins ON booking_items');
        DB::statement('DROP TRIGGER IF EXISTS trg_booking_items_recalc_upd ON booking_items');
        DB::statement('DROP TRIGGER IF EXISTS trg_booking_items_recalc_del ON booking_items');

        // Drop function
        DB::statement('DROP FUNCTION IF EXISTS booking_recalc_end');

        // Drop constraints
        DB::statement('ALTER TABLE booking_items DROP CONSTRAINT IF EXISTS booking_items_qty_chk');
        DB::statement('ALTER TABLE booking_items DROP CONSTRAINT IF EXISTS booking_items_unit_price_chk');
        DB::statement('ALTER TABLE booking_items DROP CONSTRAINT IF EXISTS booking_items_total_price_chk');
        DB::statement('ALTER TABLE booking_items DROP CONSTRAINT IF EXISTS booking_items_duration_chk');

        Schema::dropIfExists('booking_items');
    }
};
