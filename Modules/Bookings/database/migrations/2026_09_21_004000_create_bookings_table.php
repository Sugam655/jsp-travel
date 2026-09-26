<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_type')->index();
            $table->unsignedBigInteger('service_id')->nullable()->index();
            $table->string('service_title');
            $table->string('name');
            $table->string('email');
            $table->string('phone', 60)->nullable();
            $table->string('address', 500)->nullable();
            $table->unsignedInteger('travelers')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('admin_note')->nullable();
            $table->string('source')->default('web');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['booking_type', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
