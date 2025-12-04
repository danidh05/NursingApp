<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates services table for Category 1 structure.
     * - Replace service_pic with image (Laravel Storage path)
     * - Keep existing fields: name, description, price, discount_price, category_id
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Remove old service_pic if exists, add image field
            if (Schema::hasColumn('services', 'service_pic')) {
                $table->dropColumn('service_pic');
            }
            $table->string('image')->nullable()->after('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('image');
            // Restore service_pic if needed
            $table->string('service_pic')->nullable()->after('category_id');
        });
    }
};

