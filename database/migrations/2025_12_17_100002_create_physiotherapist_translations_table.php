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
        Schema::create('physiotherapist_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physiotherapist_id')->constrained('physiotherapists')->onDelete('cascade');
            $table->string('locale', 5);
            $table->string('name'); // Translatable name
            $table->text('description')->nullable(); // about
            $table->timestamps();

            $table->unique(['physiotherapist_id', 'locale']);
            $table->index(['locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physiotherapist_translations');
    }
};

