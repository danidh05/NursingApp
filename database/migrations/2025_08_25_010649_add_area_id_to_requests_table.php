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
            if (!Schema::hasColumn('requests', 'area_id')) {
                $table->foreignId('area_id')->nullable()->after('user_id')->constrained('areas')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'area_id')) {
                $table->dropForeign(['area_id']);
                $table->dropColumn('area_id');
            }
        });
    }
};