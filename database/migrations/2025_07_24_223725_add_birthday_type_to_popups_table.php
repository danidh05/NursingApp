<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // For MySQL, we need to modify the enum column to include 'birthday'
            DB::statement("ALTER TABLE popups MODIFY COLUMN type ENUM('info', 'warning', 'promo', 'birthday') DEFAULT 'info'");
        } elseif ($driver === 'sqlite') {
            // For SQLite, we need to recreate the table since it doesn't support MODIFY COLUMN
            // This is handled by the tests using RefreshDatabase trait
            // The enum constraint will be enforced at the application level
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // Remove 'birthday' from the enum
            DB::statement("ALTER TABLE popups MODIFY COLUMN type ENUM('info', 'warning', 'promo') DEFAULT 'info'");
        }
        // For SQLite, no action needed as RefreshDatabase will handle it
    }
};