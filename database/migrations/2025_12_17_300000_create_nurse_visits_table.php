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
        Schema::create('nurse_visits', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Nurse Visit'); // Static name
            $table->string('image')->nullable();
            $table->decimal('price_per_1_visit', 8, 2); // Price for 1 visit per day
            $table->decimal('price_per_2_visits', 8, 2); // Price for 2 visits per day
            $table->decimal('price_per_3_visits', 8, 2); // Price for 3 visits per day
            $table->decimal('price_per_4_visits', 8, 2); // Price for 4 visits per day
            $table->decimal('base_price', 8, 2); // Base price (for area pricing fallback)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nurse_visits');
    }
};

