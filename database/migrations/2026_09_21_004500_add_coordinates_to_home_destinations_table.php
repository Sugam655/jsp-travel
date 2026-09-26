<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('home_destinations') || Schema::hasColumn('home_destinations', 'latitude')) {
            return;
        }

        Schema::table('home_destinations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        $coordinates = [
            'khaptad' => [29.2755, 81.1440],
            'badimalika' => [29.3436, 81.4228],
            'badi malika' => [29.3436, 81.4228],
            'api' => [29.8497, 80.5331],
            'ramaroshan' => [28.8737, 81.5049],
            'rama rosn' => [28.8737, 81.5049],
            'darchula' => [29.8497, 80.5331],
            'achham' => [28.8737, 81.5049],
        ];

        foreach ($coordinates as $name => [$lat, $lng]) {
            DB::table('home_destinations')
                ->where('latitude', null)
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$name}%")
                    ->orWhere('location', 'like', "%{$name}%"))
                ->update(['latitude' => $lat, 'longitude' => $lng]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_destinations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
