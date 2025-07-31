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
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->nullable()->after('name');
            }

            if (!Schema::hasColumn('requests', 'total_price')) {
                $table->decimal('total_price', 10, 2)->nullable()->after('discount_percentage');
            }

            if (!Schema::hasColumn('requests', 'discounted_price')) {
                $table->decimal('discounted_price', 10, 2)->nullable()->after('total_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'discount_percentage')) {
                $table->dropColumn('discount_percentage');
            }

            if (Schema::hasColumn('requests', 'total_price')) {
                $table->dropColumn('total_price');
            }

            if (Schema::hasColumn('requests', 'discounted_price')) {
                $table->dropColumn('discounted_price');
            }
        });
    }
};