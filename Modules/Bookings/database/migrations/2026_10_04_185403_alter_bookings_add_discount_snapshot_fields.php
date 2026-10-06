<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `discount` already stores the money taken off, but a booking that
     * changed the global discount later would show a bare amount with no
     * explanation. These columns snapshot how the discount was configured at
     * the moment it was applied.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('discount_type', 20)->nullable()->after('discount');
            $table->decimal('discount_value', 12, 2)->nullable()->after('discount_type');
            $table->string('discount_label', 60)->nullable()->after('discount_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_label']);
        });
    }
};
