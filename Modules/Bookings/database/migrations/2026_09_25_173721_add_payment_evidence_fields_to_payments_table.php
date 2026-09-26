<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('note');
            }
            if (! Schema::hasColumn('payments', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('recorded_by');
            }
            if (! Schema::hasColumn('payments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('paid_at');
            }
        });

        if (! Schema::hasIndex('payments', 'payments_method_reference_unique')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique(['method', 'reference'], 'payments_method_reference_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (Schema::hasIndex('payments', 'payments_method_reference_unique')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique('payments_method_reference_unique');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            foreach (['receipt_path', 'verified_by', 'verified_at'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
