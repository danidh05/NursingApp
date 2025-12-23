<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_category_id')->constrained('doctor_categories')->onDelete('cascade');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('name');
            $table->string('specification')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2)->nullable(); // base price fallback
            $table->string('job_name')->nullable();
            $table->text('description')->nullable();
            $table->text('additional_information')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};

