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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Non-translatable base name
            $table->decimal('offer_price', 8, 2); // Base offer price
            $table->decimal('old_price', 8, 2); // Base old price
            $table->string('offer_available_until'); // String date (e.g., "2026-01-31" or "3 Days")
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null'); // Link to category (optional)
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};

