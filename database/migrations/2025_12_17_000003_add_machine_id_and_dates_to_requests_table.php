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
            if (!Schema::hasColumn('requests', 'machine_id')) {
                $table->foreignId('machine_id')->nullable()->after('ray_id')->constrained('machines')->onDelete('cascade');
            }
            if (!Schema::hasColumn('requests', 'from_date')) {
                $table->date('from_date')->nullable()->after('machine_id');
            }
            if (!Schema::hasColumn('requests', 'to_date')) {
                $table->date('to_date')->nullable()->after('from_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'to_date')) {
                $table->dropColumn('to_date');
            }
            if (Schema::hasColumn('requests', 'from_date')) {
                $table->dropColumn('from_date');
            }
            if (Schema::hasColumn('requests', 'machine_id')) {
                $table->dropForeign(['machine_id']);
                $table->dropColumn('machine_id');
            }
        });
    }
};

