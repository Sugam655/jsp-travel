<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the customer link, payment purpose and gateway payload fields to
     * the payment ledger. Existing rows are backfilled so the customer
     * payment history works for historical records too.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('booking_id');
            }
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type', 20)->default('partial')->after('method');
            }
            if (! Schema::hasColumn('payments', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('gateway');
            }
        });

        if (! Schema::hasIndex('payments', 'payments_user_id_index')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('user_id');
            });
        }

        $orphaned = DB::table('payments as p')
            ->leftJoin('bookings as b', 'b.id', '=', 'p.booking_id')
            ->whereNull('p.user_id')
            ->whereNotNull('b.user_id')
            ->select(['p.id', 'b.user_id'])
            ->get();

        foreach ($orphaned as $row) {
            DB::table('payments')->where('id', $row->id)->update(['user_id' => (int) $row->user_id]);
        }

        DB::table('payments as p')
            ->join('bookings as b', 'b.id', '=', 'p.booking_id')
            ->where('p.payment_type', 'partial')
            ->whereNotNull('b.total_amount')
            ->whereRaw('p.amount >= b.total_amount')
            ->update(['p.payment_type' => 'full']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            foreach (['user_id', 'payment_type', 'gateway_response'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
