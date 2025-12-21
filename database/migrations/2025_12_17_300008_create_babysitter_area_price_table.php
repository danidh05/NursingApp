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
        Schema::create('babysitter_area_price', function (Blueprint $table) {
            $table->id();
            $table->foreignId('babysitter_id')->constrained('babysitters')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            // Day shift prices
            $table->decimal('day_shift_price_12_hours', 10, 2);
            $table->decimal('day_shift_price_24_hours', 10, 2);
            // Night shift prices
            $table->decimal('night_shift_price_12_hours', 10, 2);
            $table->decimal('night_shift_price_24_hours', 10, 2);
            $table->timestamps();
            
            $table->unique(['babysitter_id', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('babysitter_area_price');
    }
};

