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
        Schema::create('transport_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('vehicle_type', 30)->default('car');
            $table->foreignId('destination_id')
                ->nullable()
                ->constrained('home_destinations')
                ->nullOnDelete();
            $table->string('location', 255)->nullable();
            $table->unsignedTinyInteger('seating_capacity')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('price_unit', 20)->default('per_day');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('transmission', 50)->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->text('features')->nullable();
            $table->string('image')->nullable();
            $table->boolean('availability')->default(true);
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('vehicle_type');
            $table->index('is_active');
            $table->index('featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_vehicles');
    }
};
