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
        // Add 24-hour pricing to duties table (separate from day/night)
        Schema::table('duties', function (Blueprint $table) {
            if (!Schema::hasColumn('duties', 'price_24_hours')) {
                $table->decimal('price_24_hours', 8, 2)->nullable()->after('night_shift_price_12_hours');
            }
        });

        // Add 24-hour pricing to duty_area_price table
        Schema::table('duty_area_price', function (Blueprint $table) {
            if (!Schema::hasColumn('duty_area_price', 'price_24_hours')) {
                $table->decimal('price_24_hours', 10, 2)->nullable()->after('night_shift_price_12_hours');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('duty_area_price', function (Blueprint $table) {
            if (Schema::hasColumn('duty_area_price', 'price_24_hours')) {
                $table->dropColumn('price_24_hours');
            }
        });

        Schema::table('duties', function (Blueprint $table) {
            if (Schema::hasColumn('duties', 'price_24_hours')) {
                $table->dropColumn('price_24_hours');
            }
        });
    }
};
