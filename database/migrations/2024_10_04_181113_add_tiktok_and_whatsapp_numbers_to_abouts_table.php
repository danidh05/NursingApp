<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTiktokAndWhatsappNumbersToAboutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('abouts', function (Blueprint $table) {
            // Add the new tiktok_url field
            $table->string('tiktok_url')->nullable()->after('description');

            // Change whatsapp_number to whatsapp_numbers and make it JSON
            $table->json('whatsapp_numbers')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('abouts', function (Blueprint $table) {
            // Drop the tiktok_url field
            $table->dropColumn('tiktok_url');

            // Change whatsapp_numbers back to string or drop the column (depending on your requirement)
            $table->dropColumn('whatsapp_numbers');
        });
    }
}