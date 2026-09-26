<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend the bookings table with the professional workflow fields.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'booking_reference')) {
                $table->string('booking_reference', 30)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('bookings', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index()->after('booking_reference');
            }
            if (! Schema::hasColumn('bookings', 'currency')) {
                $table->string('currency', 5)->default('NPR')->after('amount');
            }
            if (! Schema::hasColumn('bookings', 'base_price')) {
                $table->decimal('base_price', 12, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('bookings', 'quantity')) {
                $table->unsignedInteger('quantity')->nullable()->after('base_price');
            }
            if (! Schema::hasColumn('bookings', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('quantity');
            }
            if (! Schema::hasColumn('bookings', 'service_charge_rate')) {
                $table->decimal('service_charge_rate', 5, 2)->default(0)->after('tax_rate');
            }
            if (! Schema::hasColumn('bookings', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('service_charge_rate');
            }
            if (! Schema::hasColumn('bookings', 'service_charge')) {
                $table->decimal('service_charge', 12, 2)->default(0)->after('tax_amount');
            }
            if (! Schema::hasColumn('bookings', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('service_charge');
            }
            if (! Schema::hasColumn('bookings', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->nullable()->after('discount');
            }
            if (! Schema::hasColumn('bookings', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount');
            }
            if (! Schema::hasColumn('bookings', 'policy_accepted_at')) {
                $table->timestamp('policy_accepted_at')->nullable()->after('paid_amount');
            }
            if (! Schema::hasColumn('bookings', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('policy_accepted_at');
            }
            if (! Schema::hasColumn('bookings', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->index()->after('expires_at');
            }
            if (! Schema::hasColumn('bookings', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('bookings', 'cancelled_by')) {
                $table->string('cancelled_by', 20)->nullable()->after('reviewed_at');
            }
            if (! Schema::hasColumn('bookings', 'cancelled_reason')) {
                $table->text('cancelled_reason')->nullable()->after('cancelled_by');
            }
            if (! Schema::hasColumn('bookings', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_reason');
            }
            if (! Schema::hasColumn('bookings', 'cancellation_fee')) {
                $table->decimal('cancellation_fee', 12, 2)->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('bookings', 'refund_amount')) {
                $table->decimal('refund_amount', 12, 2)->nullable()->after('cancellation_fee');
            }
            if (! Schema::hasColumn('bookings', 'policy_snapshot')) {
                $table->json('policy_snapshot')->nullable()->after('refund_amount');
            }
        });

        DB::table('bookings')
            ->whereNull('booking_reference')
            ->orderBy('id')
            ->eachById(function ($booking) {
                $reference = 'BK-'.substr((string) $booking->created_at, 0, 4).'-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
                DB::table('bookings')->where('id', $booking->id)->update(['booking_reference' => $reference]);
            }, 100);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'booking_reference', 'user_id', 'currency', 'base_price', 'quantity',
                'tax_rate', 'service_charge_rate', 'tax_amount', 'service_charge',
                'discount', 'total_amount', 'paid_amount', 'policy_accepted_at',
                'expires_at', 'reviewed_by', 'reviewed_at', 'cancelled_by',
                'cancelled_reason', 'cancelled_at', 'cancellation_fee', 'refund_amount',
                'policy_snapshot',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
