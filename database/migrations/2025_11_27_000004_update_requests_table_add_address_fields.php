<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds address fields to requests table.
     * Supports both saved address flag and new address details.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Flag to use saved user address
            $table->boolean('use_saved_address')->default(false)->after('location');
            
            // New address fields (when use_saved_address is false)
            $table->string('address_city')->nullable()->after('use_saved_address');
            $table->string('address_street')->nullable()->after('address_city');
            $table->string('address_building')->nullable()->after('address_street');
            $table->text('address_additional_information')->nullable()->after('address_building');
            
            // Location coordinates (if available)
            // Note: 'location' field already exists, but we'll keep it for coordinates
            // If location is coordinates, it should be in format "latitude,longitude"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'use_saved_address',
                'address_city',
                'address_street',
                'address_building',
                'address_additional_information',
            ]);
        });
    }
};

