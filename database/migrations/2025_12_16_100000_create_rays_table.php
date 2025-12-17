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
        Schema::create('rays', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Base name (non-translatable, for fallback)
            $table->decimal('price', 8, 2); // Same price for all locations
            $table->string('image')->nullable(); // Laravel Storage path
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rays');
    }
};

