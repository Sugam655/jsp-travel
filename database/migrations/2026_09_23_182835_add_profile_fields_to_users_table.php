<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the customer profile fields to the users table. All profile
     * columns are nullable so existing accounts (including admins) are
     * not forced to supply contact details before the profile flow runs.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 60)->nullable()->after('email');
            $table->string('address', 500)->nullable()->after('phone');
            $table->string('city', 255)->nullable()->after('address');
            $table->string('country', 255)->nullable()->after('city');
            $table->string('emergency_contact', 60)->nullable()->after('country');
            $table->date('date_of_birth')->nullable()->after('emergency_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'address',
                'city',
                'country',
                'emergency_contact',
                'date_of_birth',
            ]);
        });
    }
};
