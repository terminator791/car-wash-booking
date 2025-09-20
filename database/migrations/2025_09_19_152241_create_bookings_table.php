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
        // Create extension for exclusion constraints
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::create('bookings', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('code')->unique();
            $t->uuid('user_id');
            $t->uuid('vehicle_id');
            $t->uuid('location_id');
            $t->uuid('bay_id')->nullable();
            $t->uuid('slot_instance_id')->nullable();
            $t->timestampTz('scheduled_start');
            $t->timestampTz('scheduled_end');
            $t->string('status', 20); // check + trigger later
            $t->string('source', 10)->default('WEB'); // WEB|MOBILE|STAFF
            $t->text('notes')->nullable();
            $t->jsonb('metadata')->nullable();
            $t->timestampTz('status_changed_at')->nullable();
            $t->timestampsTz();
            $t->index(['location_id', 'scheduled_start']);
            $t->index(['user_id', 'scheduled_start']);
            $t->index(['bay_id', 'scheduled_start']);
        });

        // Add check constraints
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_status_chk CHECK (status IN ('PENDING_PAYMENT','CONFIRMED','IN_SERVICE','COMPLETED','CANCELED','NO_SHOW'))");
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_source_chk CHECK (source IN ('WEB','MOBILE','STAFF'))");
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_time_chk CHECK (scheduled_end > scheduled_start)");

        // Add exclusion constraint for bay scheduling
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_no_overlap_per_bay EXCLUDE USING gist (bay_id WITH =, tstzrange(scheduled_start, scheduled_end, '[)') WITH &&) WHERE (status IN ('CONFIRMED','IN_SERVICE') AND bay_id IS NOT NULL)");

        // Create state machine trigger function
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION booking_enforce_status_transition()
            RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
            old_status text := COALESCE(OLD.status,'');
            new_status text := NEW.status;
            ok boolean := false;
            BEGIN
            IF TG_OP = 'INSERT' THEN
                -- Allow only initial statuses:
                IF new_status IN ('PENDING_PAYMENT','CONFIRMED') THEN
                NEW.status_changed_at := COALESCE(NEW.status_changed_at, NOW());
                RETURN NEW;
                ELSE
                RAISE EXCEPTION 'Invalid initial booking status: %', new_status;
                END IF;
            END IF;

            -- UPDATE
            IF old_status = new_status THEN
                RETURN NEW; -- no transition
            END IF;

            -- Allowed transitions
            IF old_status = 'PENDING_PAYMENT' AND new_status IN ('CONFIRMED','CANCELED') THEN ok := true; END IF;
            IF old_status = 'CONFIRMED'       AND new_status IN ('IN_SERVICE','CANCELED','NO_SHOW') THEN ok := true; END IF;
            IF old_status = 'IN_SERVICE'      AND new_status IN ('COMPLETED','CANCELED') THEN ok := true; END IF;

            IF NOT ok THEN
                RAISE EXCEPTION 'Illegal booking status transition % -> %', old_status, new_status;
            END IF;

            NEW.status_changed_at := NOW();

            -- Require bay_id on entering CONFIRMED (hard rule)
            IF new_status = 'CONFIRMED' AND NEW.bay_id IS NULL THEN
                RAISE EXCEPTION 'bay_id must be set when confirming a booking';
            END IF;

            RETURN NEW;
            END $$;
        SQL);

        // Create trigger
        DB::statement("CREATE TRIGGER trg_booking_state_machine BEFORE INSERT OR UPDATE OF status ON bookings FOR EACH ROW EXECUTE FUNCTION booking_enforce_status_transition()");

        // Create slot capacity trigger function
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION booking_slot_capacity_guard()
            RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
            old_counts int := 0;
            new_counts int := 0;
            BEGIN
            -- operate only if slot_instance_id present
            IF (TG_OP = 'INSERT') THEN
                IF NEW.slot_instance_id IS NOT NULL AND NEW.status IN ('CONFIRMED','IN_SERVICE') THEN
                PERFORM 1 FROM slot_instances WHERE id = NEW.slot_instance_id FOR UPDATE;
                UPDATE slot_instances
                    SET used_count = used_count + 1
                WHERE id = NEW.slot_instance_id
                    AND used_count < capacity;
                IF NOT FOUND THEN
                    RAISE EXCEPTION 'Slot capacity exceeded for slot_instance %', NEW.slot_instance_id;
                END IF;
                END IF;
                RETURN NEW;
            ELSIF (TG_OP = 'UPDATE') THEN
                -- if slot/status changed, decrement old if counted, increment new if counted
                IF OLD.slot_instance_id IS NOT NULL AND OLD.status IN ('CONFIRMED','IN_SERVICE')
                AND (NEW.slot_instance_id IS DISTINCT FROM OLD.slot_instance_id OR NEW.status NOT IN ('CONFIRMED','IN_SERVICE')) THEN
                PERFORM 1 FROM slot_instances WHERE id = OLD.slot_instance_id FOR UPDATE;
                UPDATE slot_instances SET used_count = GREATEST(used_count - 1, 0) WHERE id = OLD.slot_instance_id;
                END IF;

                IF NEW.slot_instance_id IS NOT NULL AND NEW.status IN ('CONFIRMED','IN_SERVICE')
                AND (NEW.slot_instance_id IS DISTINCT FROM OLD.slot_instance_id OR OLD.status NOT IN ('CONFIRMED','IN_SERVICE')) THEN
                PERFORM 1 FROM slot_instances WHERE id = NEW.slot_instance_id FOR UPDATE;
                UPDATE slot_instances
                    SET used_count = used_count + 1
                WHERE id = NEW.slot_instance_id
                    AND used_count < capacity;
                IF NOT FOUND THEN
                    RAISE EXCEPTION 'Slot capacity exceeded for slot_instance %', NEW.slot_instance_id;
                END IF;
                END IF;
                RETURN NEW;
            ELSE -- DELETE
                IF OLD.slot_instance_id IS NOT NULL AND OLD.status IN ('CONFIRMED','IN_SERVICE') THEN
                PERFORM 1 FROM slot_instances WHERE id = OLD.slot_instance_id FOR UPDATE;
                UPDATE slot_instances SET used_count = GREATEST(used_count - 1, 0) WHERE id = OLD.slot_instance_id;
                END IF;
                RETURN OLD;
            END IF;
            END $$;
        SQL);

        // Create slot capacity triggers
        DB::statement("CREATE TRIGGER trg_booking_slot_capacity_ins BEFORE INSERT ON bookings FOR EACH ROW EXECUTE FUNCTION booking_slot_capacity_guard()");
        DB::statement("CREATE TRIGGER trg_booking_slot_capacity_upd BEFORE UPDATE OF slot_instance_id, status ON bookings FOR EACH ROW EXECUTE FUNCTION booking_slot_capacity_guard()");
        DB::statement("CREATE TRIGGER trg_booking_slot_capacity_del BEFORE DELETE ON bookings FOR EACH ROW EXECUTE FUNCTION booking_slot_capacity_guard()");
    }
    public function down(): void
    {
        // Drop triggers
        DB::statement('DROP TRIGGER IF EXISTS trg_booking_slot_capacity_ins ON bookings');
        DB::statement('DROP TRIGGER IF EXISTS trg_booking_slot_capacity_upd ON bookings');
        DB::statement('DROP TRIGGER IF EXISTS trg_booking_slot_capacity_del ON bookings');
        DB::statement('DROP TRIGGER IF EXISTS trg_booking_state_machine ON bookings');

        // Drop functions
        DB::statement('DROP FUNCTION IF EXISTS booking_slot_capacity_guard');
        DB::statement('DROP FUNCTION IF EXISTS booking_enforce_status_transition');

        // Drop constraints
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_no_overlap_per_bay');
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_time_chk');
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_source_chk');
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_status_chk');

        Schema::dropIfExists('bookings');
    }
};
