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
            if (!Schema::hasColumn('requests', 'physiotherapist_id')) {
                $table->foreignId('physiotherapist_id')->nullable()->after('machine_id')->constrained('physiotherapists')->onDelete('cascade');
            }
            if (!Schema::hasColumn('requests', 'sessions_per_month')) {
                $table->integer('sessions_per_month')->nullable()->after('physiotherapist_id');
            }
            if (!Schema::hasColumn('requests', 'machines_included')) {
                $table->boolean('machines_included')->default(false)->after('sessions_per_month');
            }
            if (!Schema::hasColumn('requests', 'physio_machines')) {
                $table->json('physio_machines')->nullable()->after('machines_included'); // Array of physio machine IDs with their data
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'physio_machines')) {
                $table->dropColumn('physio_machines');
            }
            if (Schema::hasColumn('requests', 'machines_included')) {
                $table->dropColumn('machines_included');
            }
            if (Schema::hasColumn('requests', 'sessions_per_month')) {
                $table->dropColumn('sessions_per_month');
            }
            if (Schema::hasColumn('requests', 'physiotherapist_id')) {
                $table->dropForeign(['physiotherapist_id']);
                $table->dropColumn('physiotherapist_id');
            }
        });
    }
};

