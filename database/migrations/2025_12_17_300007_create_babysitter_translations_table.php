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
        Schema::create('babysitter_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('babysitter_id')->constrained('babysitters')->onDelete('cascade');
            $table->string('locale', 5);
            $table->text('about')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('additional_instructions')->nullable();
            $table->text('service_include')->nullable();
            $table->text('description')->nullable();
            $table->text('additional_information')->nullable();
            $table->timestamps();

            $table->unique(['babysitter_id', 'locale']);
            $table->index(['locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('babysitter_translations');
    }
};

