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
            // Category 7: Duties subcategories
            if (!Schema::hasColumn('requests', 'nurse_visit_id')) {
                $table->foreignId('nurse_visit_id')->nullable()->after('physiotherapist_id')->constrained('nurse_visits')->onDelete('cascade');
            }
            if (!Schema::hasColumn('requests', 'duty_id')) {
                $table->foreignId('duty_id')->nullable()->after('nurse_visit_id')->constrained('duties')->onDelete('cascade');
            }
            if (!Schema::hasColumn('requests', 'babysitter_id')) {
                $table->foreignId('babysitter_id')->nullable()->after('duty_id')->constrained('babysitters')->onDelete('cascade');
            }
            // Common fields for Category 7
            if (!Schema::hasColumn('requests', 'visits_per_day')) {
                $table->integer('visits_per_day')->nullable()->after('babysitter_id'); // For Nurse Visits: 1-4
            }
            if (!Schema::hasColumn('requests', 'duration_hours')) {
                $table->integer('duration_hours')->nullable()->after('visits_per_day'); // For Duties/Babysitter: 4, 6, 8, 12, 24, or null for continuous
            }
            if (!Schema::hasColumn('requests', 'is_continuous_care')) {
                $table->boolean('is_continuous_care')->default(false)->after('duration_hours'); // For Duties: continuous care (1 month)
            }
            if (!Schema::hasColumn('requests', 'is_day_shift')) {
                $table->boolean('is_day_shift')->default(true)->after('is_continuous_care'); // Day shift or night shift
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'is_day_shift')) {
                $table->dropColumn('is_day_shift');
            }
            if (Schema::hasColumn('requests', 'is_continuous_care')) {
                $table->dropColumn('is_continuous_care');
            }
            if (Schema::hasColumn('requests', 'duration_hours')) {
                $table->dropColumn('duration_hours');
            }
            if (Schema::hasColumn('requests', 'visits_per_day')) {
                $table->dropColumn('visits_per_day');
            }
            if (Schema::hasColumn('requests', 'babysitter_id')) {
                $table->dropForeign(['babysitter_id']);
                $table->dropColumn('babysitter_id');
            }
            if (Schema::hasColumn('requests', 'duty_id')) {
                $table->dropForeign(['duty_id']);
                $table->dropColumn('duty_id');
            }
            if (Schema::hasColumn('requests', 'nurse_visit_id')) {
                $table->dropForeign(['nurse_visit_id']);
                $table->dropColumn('nurse_visit_id');
            }
        });
    }
};

