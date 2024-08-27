<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
    //  * Run the migrations.
    //  */
    public function up(): void
    {
        // Create the users table with all necessary columns
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone_number')->unique();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable(); // Add latitude column
            $table->decimal('longitude', 11, 8)->nullable(); // Add longitude column
            $table->string('password');
            $table->string('confirmation_code')->nullable(); // Add confirmation code column
            $table->timestamp('confirmation_code_expires_at')->nullable(); // Add confirmation code expiration column
            $table->rememberToken();
            $table->timestamps();
        });

        // Create the password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Create the sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};