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
        Schema::create('duties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Duty'); // Static name
            $table->string('image')->nullable();
            // Day shift prices per hour
            $table->decimal('day_shift_price_4_hours', 8, 2); // Price for 4 hours day shift
            $table->decimal('day_shift_price_6_hours', 8, 2); // Price for 6 hours day shift
            $table->decimal('day_shift_price_8_hours', 8, 2); // Price for 8 hours day shift
            $table->decimal('day_shift_price_12_hours', 8, 2); // Price for 12 hours day shift
            // Night shift prices per hour
            $table->decimal('night_shift_price_4_hours', 8, 2); // Price for 4 hours night shift
            $table->decimal('night_shift_price_6_hours', 8, 2); // Price for 6 hours night shift
            $table->decimal('night_shift_price_8_hours', 8, 2); // Price for 8 hours night shift
            $table->decimal('night_shift_price_12_hours', 8, 2); // Price for 12 hours night shift
            // Continuous care (1 month fixed price)
            $table->decimal('continuous_care_price', 8, 2); // Fixed price for continuous care (1 month)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duties');
    }
};

