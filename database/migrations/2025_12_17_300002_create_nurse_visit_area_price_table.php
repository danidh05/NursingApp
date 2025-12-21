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
        Schema::create('nurse_visit_area_price', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurse_visit_id')->constrained('nurse_visits')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->decimal('price_per_1_visit', 10, 2);
            $table->decimal('price_per_2_visits', 10, 2);
            $table->decimal('price_per_3_visits', 10, 2);
            $table->decimal('price_per_4_visits', 10, 2);
            $table->timestamps();
            
            $table->unique(['nurse_visit_id', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nurse_visit_area_price');
    }
};

