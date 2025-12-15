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
        Schema::create('test_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Will be translatable
            $table->string('results'); // e.g., "within 48 hours"
            $table->decimal('price', 8, 2); // Same price for all areas
            $table->string('image')->nullable(); // Laravel Storage path
            $table->boolean('show_details')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_packages');
    }
};
