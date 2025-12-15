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
        Schema::create('test_package_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_package_id')->constrained('test_packages')->onDelete('cascade');
            $table->string('locale', 5); // 'en', 'ar', etc.
            $table->string('name');
            $table->text('about_test')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();
            
            // Ensure unique combination of test_package_id and locale
            $table->unique(['test_package_id', 'locale']);
            $table->index(['locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_package_translations');
    }
};
