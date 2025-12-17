<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: image_url column already exists in categories table from create_categories_table migration.
     * This migration is kept for backward compatibility but checks if column exists first.
     */
    public function up(): void
    {
        // Check if column already exists before adding it (it already exists from create_categories_table)
        if (!Schema::hasColumn('categories', 'image_url')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('image_url')->nullable()->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop if column exists and was added by this migration
        // Note: We don't drop it if it was created in create_categories_table
        // This is a safety check to prevent accidental data loss
        if (Schema::hasColumn('categories', 'image_url')) {
            // Check if this is the only reference (would require checking migration history)
            // For safety, we'll leave it as is since it's part of the original table structure
        }
    }
};
