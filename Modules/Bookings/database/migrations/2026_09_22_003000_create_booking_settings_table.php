<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configurable booking business rules (cancellation policy, taxes, limits).
     */
    public function up(): void
    {
        Schema::create('booking_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->text('value')->nullable();
            $table->string('group', 40)->default('general')->index();
            $table->timestamps();
        });

        $defaults = [
            ['currency', 'NPR', 'general'],
            ['tax_rate', '13', 'pricing'],
            ['service_charge_rate', '0', 'pricing'],
            ['booking_approval_required', '1', 'workflow'],
            ['payment_deadline_hours', '48', 'workflow'],
            ['max_booking_horizon', '365', 'validation'],
            ['max_hotel_stay', '30', 'validation'],
            ['max_rental_days', '30', 'rental'],
            ['rental_price_unit_limit_days', '14', 'rental'],
            ['cancellation_policy', json_encode([
                ['days' => 30, 'refund' => 100],
                ['days' => 15, 'refund' => 75],
                ['days' => 7, 'refund' => 50],
                ['days' => 2, 'refund' => 25],
                ['days' => 0, 'refund' => 0],
            ]), 'cancellation'],
            ['payment_methods', json_encode([
                ['method' => 'bank_transfer', 'label' => 'Bank Transfer', 'details' => 'Bank: NMB Bank, A/C: Jay Shiv Parvati Travel & Tour, A/C No: 1234567890123, Branch: Dhangadhi'],
                ['method' => 'esewa', 'label' => 'eSewa', 'details' => 'Pay to phone: 9822773259'],
                ['method' => 'khalti', 'label' => 'Khalti', 'details' => 'Pay to phone: 9868442393'],
                ['method' => 'fonepay', 'label' => 'Fonepay', 'details' => 'Complete payment in Fonepay, then submit the transaction ID for verification.'],
                ['method' => 'cash', 'label' => 'Cash at Office', 'details' => 'Attariya-01, Kailali, Dhangadhi Road'],
            ]), 'payments'],
        ];

        foreach ($defaults as [$key, $value, $group]) {
            DB::table('booking_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_settings');
    }
};
