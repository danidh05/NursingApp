<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('additional_information')->nullable();
            $table->string('building_name')->nullable();
            $table->string('location_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_operations');
    }
};

