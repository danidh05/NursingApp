<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\MostRequestedService;
use App\Models\Service;
use App\Models\SuggestedDoctor;
use App\Models\TrustedImage;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📊 Seeding dashboard data...');

        // Seed Most Requested Services
        $this->seedMostRequestedServices();

        // Seed Suggested Doctors
        $this->seedSuggestedDoctors();

        // Seed Trusted Images
        $this->seedTrustedImages();

        $this->command->info('✅ Dashboard data seeding completed!');
    }

    private function seedMostRequestedServices(): void
    {
        $this->command->info('   📋 Seeding most requested services...');

        $services = Service::where('category_id', 1)->limit(5)->get();

        if ($services->isEmpty()) {
            $this->command->warn('      ⚠️  No services found. Skipping most requested services.');
            return;
        }

        $order = 0;
        foreach ($services as $service) {
            MostRequestedService::firstOrCreate(
                ['service_id' => $service->id],
                [
                    'service_id' => $service->id,
                    'order' => $order++,
                ]
            );
        }

        $this->command->info("      ✅ Added {$services->count()} services to most requested");
    }

    private function seedSuggestedDoctors(): void
    {
        $this->command->info('   👨‍⚕️ Seeding suggested doctors...');

        $doctors = Doctor::limit(3)->get();

        if ($doctors->isEmpty()) {
            $this->command->warn('      ⚠️  No doctors found. Skipping suggested doctors.');
            return;
        }

        $order = 0;
        foreach ($doctors as $doctor) {
            SuggestedDoctor::firstOrCreate(
                ['doctor_id' => $doctor->id],
                [
                    'doctor_id' => $doctor->id,
                    'order' => $order++,
                ]
            );
        }

        $this->command->info("      ✅ Added {$doctors->count()} doctors to suggested");
    }

    private function seedTrustedImages(): void
    {
        $this->command->info('   🖼️  Seeding trusted images...');

        // Note: We're creating placeholder entries
        // Actual images should be uploaded via admin panel
        // For seeding, we'll create entries with null images or placeholder paths
        $trustedImages = [
            [
                'image' => null, // Admin should upload actual images
                'order' => 0,
            ],
            [
                'image' => null,
                'order' => 1,
            ],
            [
                'image' => null,
                'order' => 2,
            ],
        ];

        foreach ($trustedImages as $imageData) {
            TrustedImage::firstOrCreate(
                ['order' => $imageData['order']],
                $imageData
            );
        }

        $this->command->info('      ✅ Created 3 trusted image placeholders');
        $this->command->warn('      ⚠️  Note: Actual images should be uploaded via admin panel');
    }
}
