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
        Schema::table('user_profiles', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
        Schema::table('bays', function (Blueprint $t) {
            $t->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });
        Schema::table('vehicles', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
        Schema::table('services', function (Blueprint $t) {
            $t->foreign('category_id')->references('id')->on('service_categories')->restrictOnDelete();
        });
        Schema::table('service_pricing', function (Blueprint $t) {
            $t->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
        });
        Schema::table('time_slots', function (Blueprint $t) {
            $t->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });
        Schema::table('slot_instances', function (Blueprint $t) {
            $t->foreign('time_slot_id')->references('id')->on('time_slots')->cascadeOnDelete();
        });
        Schema::table('bookings', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('vehicle_id')->references('id')->on('vehicles')->restrictOnDelete();
            $t->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
            $t->foreign('bay_id')->references('id')->on('bays')->nullOnDelete();
            $t->foreign('slot_instance_id')->references('id')->on('slot_instances')->nullOnDelete();
        });
        Schema::table('booking_items', function (Blueprint $t) {
            $t->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $t->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
        });
        Schema::table('staff', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
        });
        Schema::table('booking_staff', function (Blueprint $t) {
            $t->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $t->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
        });
        Schema::table('payments', function (Blueprint $t) {
            $t->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });
        Schema::table('invoices', function (Blueprint $t) {
            $t->foreign('booking_id')->references('id')->on('bookings')->restrictOnDelete();
            $t->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
        });
        Schema::table('invoice_items', function (Blueprint $t) {
            $t->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });
        Schema::table('promotion_usage', function (Blueprint $t) {
            $t->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            $t->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
        Schema::table('reviews', function (Blueprint $t) {
            $t->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
        });
        Schema::table('notifications', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([
            'user_profiles', 'bays', 'vehicles', 'services', 'service_pricing', 'time_slots', 'slot_instances',
            'bookings', 'booking_items', 'staff', 'booking_staff', 'payments', 'invoices', 'invoice_items',
            'promotion_usage', 'reviews', 'notifications',
        ] as $table) {
            Schema::table($table, function (Blueprint $t) {
                foreach ($t->getColumns() as $col) { /* noop: down is manual */
                }
            });
        }
    }
};
