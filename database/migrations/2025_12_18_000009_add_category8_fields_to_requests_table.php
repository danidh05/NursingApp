<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'doctor_id')) {
                $table->foreignId('doctor_id')->nullable()->after('physiotherapist_id')->constrained('doctors')->nullOnDelete();
            }
            if (!Schema::hasColumn('requests', 'appointment_type')) {
                $table->string('appointment_type')->nullable()->after('doctor_id');
            }
            if (!Schema::hasColumn('requests', 'slot_id')) {
                $table->foreignId('slot_id')->nullable()->after('appointment_type')->constrained('doctor_availabilities')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'slot_id')) {
                $table->dropForeign(['slot_id']);
                $table->dropColumn('slot_id');
            }
            if (Schema::hasColumn('requests', 'appointment_type')) {
                $table->dropColumn('appointment_type');
            }
            if (Schema::hasColumn('requests', 'doctor_id')) {
                $table->dropForeign(['doctor_id']);
                $table->dropColumn('doctor_id');
            }
        });
    }
};

