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
        Schema::create('physiotherapists', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Non-translatable base name
            $table->string('image')->nullable();
            $table->string('job_name')->nullable(); // Non-translatable
            $table->string('job_specification')->nullable(); // Non-translatable
            $table->string('specialization')->nullable(); // Non-translatable
            $table->integer('years_of_experience')->nullable();
            $table->decimal('price', 8, 2); // Base price
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physiotherapists');
    }
};

