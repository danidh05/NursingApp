<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveServiceIdFromRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requests', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['service_id']); // Drop the foreign key constraint
            $table->dropColumn('service_id');    // Then drop the column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable(); // Re-add the column
            
            // Optionally, re-add the foreign key if necessary
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }
}