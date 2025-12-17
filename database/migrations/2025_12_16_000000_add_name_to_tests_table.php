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
        Schema::table('tests', function (Blueprint $table) {
            // Add name field if it doesn't exist
            if (!Schema::hasColumn('tests', 'name')) {
                $table->string('name')->after('id');
            }
        });

        Schema::table('test_translations', function (Blueprint $table) {
            // Add name field to translations if it doesn't exist
            if (!Schema::hasColumn('test_translations', 'name')) {
                $table->string('name')->nullable()->after('locale');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            if (Schema::hasColumn('tests', 'name')) {
                $table->dropColumn('name');
            }
        });

        Schema::table('test_translations', function (Blueprint $table) {
            if (Schema::hasColumn('test_translations', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};

