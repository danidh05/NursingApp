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
        // Add 24-hour pricing to babysitters table (separate from day/night)
        Schema::table('babysitters', function (Blueprint $table) {
            if (!Schema::hasColumn('babysitters', 'price_24_hours')) {
                $table->decimal('price_24_hours', 8, 2)->nullable()->after('night_shift_price_24_hours');
            }
        });

        // Add 24-hour pricing to babysitter_area_price table
        Schema::table('babysitter_area_price', function (Blueprint $table) {
            if (!Schema::hasColumn('babysitter_area_price', 'price_24_hours')) {
                $table->decimal('price_24_hours', 10, 2)->nullable()->after('night_shift_price_24_hours');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('babysitter_area_price', function (Blueprint $table) {
            if (Schema::hasColumn('babysitter_area_price', 'price_24_hours')) {
                $table->dropColumn('price_24_hours');
            }
        });

        Schema::table('babysitters', function (Blueprint $table) {
            if (Schema::hasColumn('babysitters', 'price_24_hours')) {
                $table->dropColumn('price_24_hours');
            }
        });
    }
};
