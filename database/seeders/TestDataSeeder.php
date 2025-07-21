<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\Service;
use App\Models\Nurse;
use App\Models\Request;
use App\Models\About;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting comprehensive test data seeding...');

        // 1. Ensure Roles exist
        $this->seedRoles();
        
        // 2. Create Test Users
        $this->seedTestUsers();
        
        // 3. Create Categories
        $this->seedCategories();
        
        // 4. Create Services
        $this->seedServices();
        
        // 5. Create Nurses
        $this->seedNurses();
        
        // 6. Create Sample Requests with different statuses
        $this->seedSampleRequests();
        
        // 7. Create About information
        $this->seedAboutInfo();

        $this->command->info('✅ Test data seeding completed successfully!');
        $this->printTestCredentials();
    }

    private function seedRoles(): void
    {
        $this->command->info('📝 Seeding roles...');
        
        Role::firstOrCreate(['id' => 1, 'name' => 'admin']);
        Role::firstOrCreate(['id' => 2, 'name' => 'user']);
        
        $this->command->info('   ✅ Roles seeded');
    }

    private function seedTestUsers(): void
    {
        $this->command->info('👥 Seeding test users...');
        
        // Admin User
        User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('admin123'),
                'role_id' => 1,
                'location' => 'Admin Office, NYC',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'phone_number' => '+1234567890',
                'email_verified_at' => now(),
                'is_first_login' => false,
            ]
        );

        // Regular Users
        User::firstOrCreate(
            ['email' => 'user@test.com'],
            [
                'name' => 'Test User',
                'email' => 'user@test.com',
                'password' => Hash::make('user123'),
                'role_id' => 2,
                'location' => 'Brooklyn, NY',
                'latitude' => 40.6782,
                'longitude' => -73.9442,
                'phone_number' => '+1987654321',
                'email_verified_at' => now(),
                'is_first_login' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'john@test.com'],
            [
                'name' => 'John Smith',
                'email' => 'john@test.com',
                'password' => Hash::make('john123'),
                'role_id' => 2,
                'location' => 'Manhattan, NY',
                'latitude' => 40.7831,
                'longitude' => -73.9712,
                'phone_number' => '+1555123456',
                'email_verified_at' => now(),
                'is_first_login' => false,
            ]
        );

        $this->command->info('   ✅ Test users seeded');
    }

    private function seedCategories(): void
    {
        $this->command->info('📁 Seeding categories...');
        
        $categories = [
            ['name' => 'Home Care'],
            ['name' => 'Emergency Care'],
            ['name' => 'Elderly Care'],
            ['name' => 'Post-Surgery Care'],
            ['name' => 'Chronic Disease Management'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }

        $this->command->info('   ✅ Categories seeded');
    }

    private function seedServices(): void
    {
        $this->command->info('🏥 Seeding services...');
        
        $categories = Category::all();
        
        $services = [
            // Home Care Services
            [
                'name' => 'Basic Home Nursing',
                'price' => 75.00,
                'category_id' => $categories->where('name', 'Home Care')->first()?->id,
            ],
            [
                'name' => 'Advanced Home Care',
                'price' => 120.00,
                'category_id' => $categories->where('name', 'Home Care')->first()?->id,
            ],
            
            // Emergency Care Services
            [
                'name' => 'Emergency Response',
                'price' => 200.00,
                'category_id' => $categories->where('name', 'Emergency Care')->first()?->id,
            ],
            [
                'name' => '24/7 Critical Care',
                'price' => 300.00,
                'category_id' => $categories->where('name', 'Emergency Care')->first()?->id,
            ],
            
            // Elderly Care Services
            [
                'name' => 'Senior Companion Care',
                'price' => 60.00,
                'category_id' => $categories->where('name', 'Elderly Care')->first()?->id,
            ],
            [
                'name' => 'Dementia Care',
                'price' => 150.00,
                'category_id' => $categories->where('name', 'Elderly Care')->first()?->id,
            ],
            
            // Post-Surgery Care
            [
                'name' => 'Post-Operative Care',
                'price' => 100.00,
                'category_id' => $categories->where('name', 'Post-Surgery Care')->first()?->id,
            ],
            [
                'name' => 'Rehabilitation Support',
                'price' => 90.00,
                'category_id' => $categories->where('name', 'Post-Surgery Care')->first()?->id,
            ],
            
            // Chronic Disease Management
            [
                'name' => 'Diabetes Management',
                'price' => 80.00,
                'category_id' => $categories->where('name', 'Chronic Disease Management')->first()?->id,
            ],
            [
                'name' => 'Cardiac Care',
                'price' => 110.00,
                'category_id' => $categories->where('name', 'Chronic Disease Management')->first()?->id,
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                ['name' => $service['name']],
                $service
            );
        }

        $this->command->info('   ✅ Services seeded');
    }

    private function seedNurses(): void
    {
        $this->command->info('👩‍⚕️ Seeding nurses...');
        
        $nurses = [
            [
                'name' => 'Sarah Johnson',
                'phone_number' => '+1555001001',
                'address' => '123 Manhattan St, New York, NY 10001',
                'gender' => 'female',
                'profile_picture' => null,
            ],
            [
                'name' => 'Michael Rodriguez',
                'phone_number' => '+1555001002',
                'address' => '456 Brooklyn Ave, Brooklyn, NY 11201',
                'gender' => 'male',
                'profile_picture' => null,
            ],
            [
                'name' => 'Emily Chen',
                'phone_number' => '+1555001003',
                'address' => '789 Queens Blvd, Queens, NY 11372',
                'gender' => 'female',
                'profile_picture' => null,
            ],
            [
                'name' => 'David Thompson',
                'phone_number' => '+1555001004',
                'address' => '321 Bronx St, Bronx, NY 10451',
                'gender' => 'male',
                'profile_picture' => null,
            ],
            [
                'name' => 'Maria Garcia',
                'phone_number' => '+1555001005',
                'address' => '654 Staten Island Way, Staten Island, NY 10301',
                'gender' => 'female',
                'profile_picture' => null,
            ],
        ];

        foreach ($nurses as $nurse) {
            Nurse::firstOrCreate(
                ['phone_number' => $nurse['phone_number']],
                $nurse
            );
        }

        $this->command->info('   ✅ Nurses seeded');
    }

    private function seedSampleRequests(): void
    {
        $this->command->info('📋 Seeding sample requests...');
        
        $users = User::where('role_id', 2)->get();
        $nurses = Nurse::all();
        $services = Service::all();

        // Sample requests with different statuses
        $sampleRequests = [
            [
                'user_id' => $users->first()->id,
                'nurse_id' => $nurses->first()->id,
                'full_name' => 'John Doe Sr.',
                'phone_number' => '+1555100001',
                'name' => 'Emergency Home Care for Father',
                'problem_description' => 'My 75-year-old father needs immediate nursing care after a fall. He has difficulty walking and needs medication management.',
                'location' => '123 Main St, Manhattan, NY',
                'status' => Request::STATUS_SUBMITTED,
                'nurse_gender' => 'female',
                'time_type' => 'full-time',
                'scheduled_time' => now()->addHour(),
            ],
            [
                'user_id' => $users->skip(1)->first()?->id ?? $users->first()->id,
                'nurse_id' => $nurses->skip(1)->first()->id,
                'full_name' => 'Jane Smith',
                'phone_number' => '+1555100002',
                'name' => 'Post-Surgery Recovery Care',
                'problem_description' => 'I had knee surgery last week and need assistance with wound care, medication, and mobility.',
                'location' => '456 Oak Ave, Brooklyn, NY',
                'status' => Request::STATUS_ASSIGNED,
                'nurse_gender' => 'any',
                'time_type' => 'part-time',
                'scheduled_time' => now()->addHours(2),
            ],
            [
                'user_id' => $users->first()->id,
                'nurse_id' => $nurses->skip(2)->first()->id,
                'full_name' => 'Robert Wilson',
                'phone_number' => '+1555100003',
                'name' => 'Diabetes Management Support',
                'problem_description' => 'Need help managing my diabetes medications and blood sugar monitoring. Recently diagnosed and feeling overwhelmed.',
                'location' => '789 Pine St, Queens, NY',
                'status' => Request::STATUS_IN_PROGRESS,
                'nurse_gender' => 'male',
                'time_type' => 'full-time',
                'scheduled_time' => now()->subHour(),
            ],
            [
                'user_id' => $users->skip(1)->first()?->id ?? $users->first()->id,
                'nurse_id' => $nurses->skip(3)->first()->id,
                'full_name' => 'Mary Johnson',
                'phone_number' => '+1555100004',
                'name' => 'Elderly Care for Mother',
                'problem_description' => 'My 82-year-old mother with mild dementia needs daily nursing care and companionship.',
                'location' => '321 Elm St, Bronx, NY',
                'status' => Request::STATUS_COMPLETED,
                'nurse_gender' => 'female',
                'time_type' => 'full-time',
                'scheduled_time' => now()->subDays(2),
                'ending_time' => now()->subDay(),
            ],
        ];

        foreach ($sampleRequests as $requestData) {
            $request = Request::firstOrCreate(
                [
                    'full_name' => $requestData['full_name'],
                    'user_id' => $requestData['user_id']
                ],
                $requestData
            );

            // Attach random services to each request
            if ($request->wasRecentlyCreated) {
                $randomServices = $services->random(rand(1, 3))->pluck('id');
                $request->services()->attach($randomServices);
            }

            // Add time_needed_to_arrive to cache for requests with ASSIGNED status
            if ($request->status === Request::STATUS_ASSIGNED && $request->wasRecentlyCreated) {
                $cacheKey = 'time_needed_to_arrive_' . $request->id;
                \Cache::put($cacheKey, [
                    'time_needed' => 45, // 45 minutes for assigned requests
                    'start_time' => now()
                ], 3600);
                
                $this->command->line("   📍 Cached time_needed_to_arrive for request {$request->id}");
            }
        }

        $this->command->info('   ✅ Sample requests seeded');
    }

    private function seedAboutInfo(): void
    {
        $this->command->info('ℹ️ Seeding about information...');
        
        About::firstOrCreate(
            ['id' => 1],
            [
                'description' => 'Welcome to NurseCare - Your trusted partner in professional home nursing services. We provide compassionate, skilled nursing care in the comfort of your home.',
                'online_shop_url' => 'https://shop.nursecare.com',
                'facebook_url' => 'https://facebook.com/nursecare',
                'instagram_url' => 'https://instagram.com/nursecare',
                'tiktok_url' => 'https://tiktok.com/@nursecare',
                'whatsapp_number' => '+1800NURSECARE',
                'whatsapp_numbers' => json_encode(['+1800NURSECARE', '+1800NURSHELP']),
            ]
        );

        $this->command->info('   ✅ About information seeded');
    }

    private function printTestCredentials(): void
    {
        $this->command->newLine();
        $this->command->info('🎯 TEST CREDENTIALS FOR POSTMAN:');
        $this->command->newLine();
        
        $this->command->info('👑 ADMIN USER:');
        $this->command->line('   Email: admin@test.com');
        $this->command->line('   Password: admin123');
        $this->command->newLine();
        
        $this->command->info('👤 REGULAR USERS:');
        $this->command->line('   Email: user@test.com | Password: user123');
        $this->command->line('   Email: john@test.com | Password: john123');
        $this->command->newLine();
        
        $this->command->info('📊 SEEDED DATA:');
        $this->command->line('   ✅ ' . Category::count() . ' Categories');
        $this->command->line('   ✅ ' . Service::count() . ' Services');
        $this->command->line('   ✅ ' . Nurse::count() . ' Nurses');
        $this->command->line('   ✅ ' . Request::count() . ' Sample Requests');
        $this->command->line('   ✅ ' . User::count() . ' Users');
        $this->command->newLine();
        
        $this->command->info('🚀 READY FOR TESTING! You can now:');
        $this->command->line('   1. Login with any user credentials');
        $this->command->line('   2. Create requests using existing service IDs');
        $this->command->line('   3. Test the full 4-stage status flow');
        $this->command->line('   4. Assign nurses from the pre-created nurse pool');
        $this->command->newLine();
    }
} 