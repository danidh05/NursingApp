<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_operation_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_operation_id')->constrained('doctor_operations')->onDelete('cascade');
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->text('additional_information')->nullable();
            $table->timestamps();
            $table->unique(['doctor_operation_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_operation_translations');
    }
};

