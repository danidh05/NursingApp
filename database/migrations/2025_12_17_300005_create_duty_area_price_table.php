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
        Schema::create('duty_area_price', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duty_id')->constrained('duties')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            // Day shift prices per hour
            $table->decimal('day_shift_price_4_hours', 10, 2);
            $table->decimal('day_shift_price_6_hours', 10, 2);
            $table->decimal('day_shift_price_8_hours', 10, 2);
            $table->decimal('day_shift_price_12_hours', 10, 2);
            // Night shift prices per hour
            $table->decimal('night_shift_price_4_hours', 10, 2);
            $table->decimal('night_shift_price_6_hours', 10, 2);
            $table->decimal('night_shift_price_8_hours', 10, 2);
            $table->decimal('night_shift_price_12_hours', 10, 2);
            // Continuous care (1 month fixed price)
            $table->decimal('continuous_care_price', 10, 2);
            $table->timestamps();
            
            $table->unique(['duty_id', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duty_area_price');
    }
};

