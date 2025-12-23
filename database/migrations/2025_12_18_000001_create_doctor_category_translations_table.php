<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_category_id')->constrained('doctor_categories')->onDelete('cascade');
            $table->string('locale', 5);
            $table->string('name');
            $table->timestamps();
            $table->unique(['doctor_category_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_category_translations');
    }
};

