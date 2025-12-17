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
        Schema::create('ray_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ray_id')->constrained('rays')->onDelete('cascade');
            $table->string('locale', 5); // 'en', 'ar', etc.
            $table->string('name');
            $table->text('about_ray')->nullable();
            $table->text('instructions')->nullable();
            $table->text('additional_information')->nullable();
            $table->timestamps();
            
            // Ensure unique combination of ray_id and locale
            $table->unique(['ray_id', 'locale']);
            $table->index(['locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ray_translations');
    }
};

