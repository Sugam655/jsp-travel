<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the numeric fields needed for professional tour booking validation.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tours')) {
            return;
        }

        Schema::table('tours', function (Blueprint $table) {
            if (! Schema::hasColumn('tours', 'duration_days')) {
                $table->unsignedInteger('duration_days')->nullable()->after('duration');
            }
            if (! Schema::hasColumn('tours', 'capacity')) {
                $table->unsignedInteger('capacity')->nullable()->after('price');
            }
        });

        DB::table('tours')
            ->whereNull('duration_days')
            ->orderBy('id')
            ->eachById(function ($tour) {
                $days = null;
                if (preg_match('/(\d+)\s+Days?/i', (string) $tour->duration, $matches)) {
                    $days = (int) $matches[1];
                }

                if ($days !== null) {
                    DB::table('tours')->where('id', $tour->id)->update(['duration_days' => $days]);
                }
            }, 100);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tours')) {
            return;
        }

        Schema::table('tours', function (Blueprint $table) {
            if (Schema::hasColumn('tours', 'duration_days')) {
                $table->dropColumn('duration_days');
            }
            if (Schema::hasColumn('tours', 'capacity')) {
                $table->dropColumn('capacity');
            }
        });
    }
};
