<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_area_price', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamps();
            $table->unique(['doctor_id', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_area_price');
    }
};

