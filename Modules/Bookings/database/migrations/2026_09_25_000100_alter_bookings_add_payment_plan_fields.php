<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot the payment plan on the booking row: the advance amount the
     * customer must pay first and the date the remaining balance is due.
     * These are stored (not derived) so history survives later rule changes.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'advance_amount')) {
                $table->decimal('advance_amount', 12, 2)->nullable()->after('total_amount');
            }
            if (! Schema::hasColumn('bookings', 'payment_due_date')) {
                $table->date('payment_due_date')->nullable()->after('advance_amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            foreach (['advance_amount', 'payment_due_date'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
