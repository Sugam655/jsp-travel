<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('booking_settings')->where('key', 'payment_methods')->first();
        $methods = $setting !== null ? json_decode($setting->value, true) : [];
        $methods = is_array($methods) ? $methods : [];

        if (! collect($methods)->contains('method', 'fonepay')) {
            $methods[] = [
                'method' => 'fonepay',
                'label' => 'Fonepay',
                'details' => 'Complete payment in Fonepay, then submit the transaction ID for verification.',
            ];
        }

        DB::table('booking_settings')->updateOrInsert(
            ['key' => 'payment_methods'],
            [
                'value' => json_encode($methods, JSON_THROW_ON_ERROR),
                'group' => 'payments',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void {}
};
