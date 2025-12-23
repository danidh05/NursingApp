<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_operation_area_price', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_operation_id')->constrained('doctor_operations')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamps();
            $table->unique(['doctor_operation_id', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_operation_area_price');
    }
};

