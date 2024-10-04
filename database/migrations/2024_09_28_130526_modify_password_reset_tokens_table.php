<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyPasswordResetTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            // Add the phone_number column and make it the new primary key
            // $table->string('phone_number', 15)->primary();  // Adjust length if necessary

            // Modify the email column to be nullable and non-primary
            $table->dropPrimary(['email']);
            $table->string('email')->nullable()->change();

            // Drop the current primary key constraint on the email
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            // Reverse the changes by restoring the email as the primary key
            $table->dropColumn('phone_number');
            $table->string('email')->primary()->change();
        });
    }
}