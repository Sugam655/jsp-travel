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
        Schema::create('home_why_choose_us', function (Blueprint $table) {
            $table->id();
            $table->string('small_title')->nullable();
            $table->string('title')->nullable();
            $table->text('left_paragraph_1')->nullable();
            $table->text('left_paragraph_2')->nullable();
            $table->text('right_paragraph_1')->nullable();
            $table->text('right_paragraph_2')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_why_choose_us');
    }
};
