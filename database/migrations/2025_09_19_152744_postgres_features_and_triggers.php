<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            -- Extensions
            CREATE EXTENSION IF NOT EXISTS btree_gist;

            -- ENUM-ish checks (pakai CHECK agar portabel)
            ALTER TABLE users
            ADD CONSTRAINT users_role_chk CHECK (role IN ('customer','staff','admin'));

            ALTER TABLE user_profiles
            ADD CONSTRAINT user_profiles_gender_chk CHECK (gender IS NULL OR gender IN ('male','female','other'));

            ALTER TABLE vehicles
            ADD CONSTRAINT vehicles_type_chk CHECK (vehicle_type IN ('SEDAN','SUV','TRUCK','MOTORBIKE','VAN'));

            ALTER TABLE bays
            ADD CONSTRAINT bays_type_chk CHECK (bay_type IN ('STANDARD','PREMIUM','TRUCK'));

            ALTER TABLE bookings
            ADD CONSTRAINT bookings_status_chk CHECK (status IN ('PENDING_PAYMENT','CONFIRMED','IN_SERVICE','COMPLETED','CANCELED','NO_SHOW')),
            ADD CONSTRAINT bookings_source_chk CHECK (source IN ('WEB','MOBILE','STAFF')),
            ADD CONSTRAINT bookings_time_chk CHECK (scheduled_end > scheduled_start);

            ALTER TABLE staff
            ADD CONSTRAINT staff_position_chk CHECK (position IN ('washer','supervisor','manager'));

            ALTER TABLE booking_staff
            ADD CONSTRAINT booking_staff_role_chk CHECK (role IN ('primary','assistant'));

            ALTER TABLE payments
            ADD CONSTRAINT payments_method_chk CHECK (payment_method IN ('cash','credit_card','debit_card','e_wallet','bank_transfer')),
            ADD CONSTRAINT payments_status_chk CHECK (status IN ('pending','processing','completed','failed','refunded'));

            ALTER TABLE promotions
            ADD CONSTRAINT promotions_type_chk CHECK (type IN ('PERCENT','FIXED','FREE_SERVICE')),
            ADD CONSTRAINT promotions_bp_chk CHECK (value_cents_or_bp >= 0 AND value_cents_or_bp <= 10000);

            ALTER TABLE time_slots
            ADD CONSTRAINT time_slots_dow_chk CHECK (day_of_week IN ('mon','tue','wed','thu','fri','sat','sun'));

            -- Anti-overlap bay scheduling: Exclusion constraint (aktif hanya saat status "aktif")
            -- Catatan: Partial exclusion constraint langsung via WHERE (PG >= 9.6)
            ALTER TABLE bookings ADD CONSTRAINT bookings_no_overlap_per_bay
            EXCLUDE USING gist (
                bay_id WITH =,
                tstzrange(scheduled_start, scheduled_end, '[)') WITH &&
            )
            WHERE (status IN ('CONFIRMED','IN_SERVICE') AND bay_id IS NOT NULL);

            -- ==== State Machine Trigger ====
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

            DROP TRIGGER IF EXISTS trg_booking_state_machine ON bookings;
            CREATE TRIGGER trg_booking_state_machine
            BEFORE INSERT OR UPDATE OF status ON bookings
            FOR EACH ROW EXECUTE FUNCTION booking_enforce_status_transition();

            -- ==== Slot capacity trigger (optional tapi sangat berguna) ====
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

            DROP TRIGGER IF EXISTS trg_booking_slot_capacity_ins ON bookings;
            DROP TRIGGER IF EXISTS trg_booking_slot_capacity_upd ON bookings;
            DROP TRIGGER IF EXISTS trg_booking_slot_capacity_del ON bookings;

            CREATE TRIGGER trg_booking_slot_capacity_ins
            BEFORE INSERT ON bookings
            FOR EACH ROW EXECUTE FUNCTION booking_slot_capacity_guard();

            CREATE TRIGGER trg_booking_slot_capacity_upd
            BEFORE UPDATE OF slot_instance_id, status ON bookings
            FOR EACH ROW EXECUTE FUNCTION booking_slot_capacity_guard();

            CREATE TRIGGER trg_booking_slot_capacity_del
            BEFORE DELETE ON bookings
            FOR EACH ROW EXECUTE FUNCTION booking_slot_capacity_guard();

            -- ==== Recalc scheduled_end (opsional) berdasar jumlah durasi item ====
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

            DROP TRIGGER IF EXISTS trg_booking_items_recalc_ins ON booking_items;
            DROP TRIGGER IF EXISTS trg_booking_items_recalc_upd ON booking_items;
            DROP TRIGGER IF EXISTS trg_booking_items_recalc_del ON booking_items;

            CREATE TRIGGER trg_booking_items_recalc_ins
            AFTER INSERT ON booking_items
            FOR EACH ROW EXECUTE FUNCTION booking_recalc_end();

            CREATE TRIGGER trg_booking_items_recalc_upd
            AFTER UPDATE OF qty, duration_min ON booking_items
            FOR EACH ROW EXECUTE FUNCTION booking_recalc_end();

            CREATE TRIGGER trg_booking_items_recalc_del
            AFTER DELETE ON booking_items
            FOR EACH ROW EXECUTE FUNCTION booking_recalc_end();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS trg_booking_state_machine ON bookings;
            DROP FUNCTION IF EXISTS booking_enforce_status_transition;

            DROP TRIGGER IF EXISTS trg_booking_slot_capacity_ins ON bookings;
            DROP TRIGGER IF EXISTS trg_booking_slot_capacity_upd ON bookings;
            DROP TRIGGER IF EXISTS trg_booking_slot_capacity_del ON bookings;
            DROP FUNCTION IF EXISTS booking_slot_capacity_guard;

            DROP TRIGGER IF EXISTS trg_booking_items_recalc_ins ON booking_items;
            DROP TRIGGER IF EXISTS trg_booking_items_recalc_upd ON booking_items;
            DROP TRIGGER IF EXISTS trg_booking_items_recalc_del ON booking_items;
            DROP FUNCTION IF EXISTS booking_recalc_end;

            ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_no_overlap_per_bay;

            -- Drop CHECKs (opsional saat rollback)
            ALTER TABLE IF EXISTS users DROP CONSTRAINT IF EXISTS users_role_chk;
            ALTER TABLE IF EXISTS user_profiles DROP CONSTRAINT IF EXISTS user_profiles_gender_chk;
            ALTER TABLE IF EXISTS vehicles DROP CONSTRAINT IF EXISTS vehicles_type_chk;
            ALTER TABLE IF EXISTS bays DROP CONSTRAINT IF EXISTS bays_type_chk;
            ALTER TABLE IF EXISTS bookings DROP CONSTRAINT IF EXISTS bookings_status_chk;
            ALTER TABLE IF EXISTS bookings DROP CONSTRAINT IF EXISTS bookings_source_chk;
            ALTER TABLE IF EXISTS bookings DROP CONSTRAINT IF EXISTS bookings_time_chk;
            ALTER TABLE IF EXISTS staff DROP CONSTRAINT IF EXISTS staff_position_chk;
            ALTER TABLE IF EXISTS booking_staff DROP CONSTRAINT IF EXISTS booking_staff_role_chk;
            ALTER TABLE IF EXISTS payments DROP CONSTRAINT IF EXISTS payments_method_chk;
            ALTER TABLE IF EXISTS payments DROP CONSTRAINT IF EXISTS payments_status_chk;
            ALTER TABLE IF EXISTS promotions DROP CONSTRAINT IF EXISTS promotions_type_chk;
            ALTER TABLE IF EXISTS promotions DROP CONSTRAINT IF EXISTS promotions_bp_chk;
            ALTER TABLE IF EXISTS time_slots DROP CONSTRAINT IF EXISTS time_slots_dow_chk;
            SQL);
    }
};
