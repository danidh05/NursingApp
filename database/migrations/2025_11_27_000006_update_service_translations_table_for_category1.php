<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates service_translations table to add new translatable fields:
     * - description (already exists in services table, but we need it translatable)
     * - details
     * - instructions
     * - service_includes
     */
    public function up(): void
    {
        Schema::table('service_translations', function (Blueprint $table) {
            // Add new translatable fields
            $table->text('description')->nullable()->after('name');
            $table->text('details')->nullable()->after('description');
            $table->text('instructions')->nullable()->after('details');
            $table->text('service_includes')->nullable()->after('instructions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_translations', function (Blueprint $table) {
            $table->dropColumn(['description', 'details', 'instructions', 'service_includes']);
        });
    }
};

