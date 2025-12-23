<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the comprehensive test data seeder
        $this->call([
            RoleSeeder::class,
            AreaSeeder::class,
            TestDataSeeder::class,
            TestSeeder::class, // Category 2: Tests and Test Packages
            RaySeeder::class, // Category 3: Rays
            MachineSeeder::class, // Category 4: Machines
            PhysiotherapistSeeder::class, // Category 5: Physiotherapists
            OfferSeeder::class, // Category 6: Offers
            Category7Seeder::class, // Category 7: Duties (Nurse Visits, Duties, Babysitters)
            DoctorSeeder::class, // Category 8: Doctors
        ]);
    }
}