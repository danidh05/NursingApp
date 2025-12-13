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
            // Add first_name and last_name fields
            $table->string('first_name')->nullable()->after('user_id');
            $table->string('last_name')->nullable()->after('first_name');
            
            // Make full_name nullable (can be built from first_name + last_name)
            $table->string('full_name')->nullable()->change();
            
            // Make phone_number, problem_description, nurse_gender nullable (optional for all categories)
            $table->string('phone_number')->nullable()->change();
            $table->text('problem_description')->nullable()->change();
            $table->string('nurse_gender')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
            // Note: We don't revert nullable changes as they might have been nullable before
        });
    }
};
