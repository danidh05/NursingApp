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
        Schema::create('babysitters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Baby Sitter'); // Static name
            $table->string('image')->nullable();
            // Day shift prices
            $table->decimal('day_shift_price_12_hours', 8, 2); // Price for 12 hours day shift
            $table->decimal('day_shift_price_24_hours', 8, 2); // Price for 24 hours day shift
            // Night shift prices
            $table->decimal('night_shift_price_12_hours', 8, 2); // Price for 12 hours night shift
            $table->decimal('night_shift_price_24_hours', 8, 2); // Price for 24 hours night shift
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('babysitters');
    }
};

