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
        Schema::create('trusted_images', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable()->comment('Image path stored in storage');
            $table->integer('order')->default(0)->comment('Display order for sorting');
            $table->timestamps();
            
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trusted_images');
    }
};
