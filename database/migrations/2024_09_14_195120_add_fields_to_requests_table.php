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
            $table->text('problem_description')->nullable()->after('location'); // Add problem_description field
            $table->string('full_name')->nullable()->after('user_id');          // Add full_name field
            $table->string('phone_number')->nullable()->after('full_name');     // Add phone_number field
              // Add optional request name/title
            $table->timestamp('ending_time')->nullable()->after('scheduled_time'); // Add ending_time field
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('problem_description');
            $table->dropColumn('full_name');
            $table->dropColumn('phone_number');
            $table->dropColumn('name');
            $table->dropColumn('ending_time');
        });
    }
};