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
            // Category 2: Tests specific fields
            $table->foreignId('test_package_id')->nullable()->after('category_id')->constrained('test_packages')->onDelete('cascade');
            $table->json('request_details_files')->nullable()->after('test_package_id'); // Array of file paths
            $table->text('notes')->nullable()->after('request_details_files');
            $table->boolean('request_with_insurance')->default(false)->after('notes');
            $table->string('attach_front_face')->nullable()->after('request_with_insurance'); // File path
            $table->string('attach_back_face')->nullable()->after('attach_front_face'); // File path
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['test_package_id']);
            $table->dropColumn([
                'test_package_id',
                'request_details_files',
                'notes',
                'request_with_insurance',
                'attach_front_face',
                'attach_back_face',
            ]);
        });
    }
};
