<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Doctor;
use App\Models\DoctorAreaPrice;
use App\Models\DoctorAvailability;
use App\Models\DoctorCategory;
use App\Models\DoctorOperation;
use App\Models\DoctorOperationAreaPrice;
use App\Models\Request;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = Area::all();
        if ($areas->isEmpty()) {
            $this->command->warn('No areas found. Skipping DoctorSeeder.');
            return;
        }

        // Create categories with translations
        $categories = [
            [
                'name_en' => 'Cardiology',
                'name_ar' => 'طب القلب',
            ],
            [
                'name_en' => 'Nephrology',
                'name_ar' => 'أمراض الكلى',
            ],
        ];

        $doctorCategories = [];
        foreach ($categories as $cat) {
            $c = DoctorCategory::create(['image' => null]);
            $c->translations()->create(['locale' => 'en', 'name' => $cat['name_en']]);
            $c->translations()->create(['locale' => 'ar', 'name' => $cat['name_ar']]);
            $doctorCategories[] = $c;
        }

        // Doctors
        $doctorsData = [
            [
                'doctor_category_id' => $doctorCategories[0]->id,
                'name' => 'Dr. Jane Heart',
                'specification' => 'Cardiologist',
                'years_of_experience' => 12,
                'image' => null,
                'price' => 120,
                'job_name' => 'Senior Cardiologist',
                'description' => 'Experienced in cardiac surgery and interventional cardiology.',
                'additional_information' => 'Available for video and clinic appointments.',
                'translations' => [
                    'en' => [
                        'specification' => 'Cardiologist',
                        'job_name' => 'Senior Cardiologist',
                        'description' => 'Experienced in cardiac surgery and interventional cardiology.',
                        'additional_information' => 'Available for video and clinic appointments.',
                    ],
                    'ar' => [
                        'specification' => 'طبيب قلب',
                        'job_name' => 'استشاري قلب',
                        'description' => 'خبرة في جراحة القلب والقسطرة.',
                        'additional_information' => 'متاح لمواعيد الفيديو والعيادة.',
                    ],
                ],
            ],
            [
                'doctor_category_id' => $doctorCategories[1]->id,
                'name' => 'Dr. Omar Kidney',
                'specification' => 'Nephrologist',
                'years_of_experience' => 9,
                'image' => null,
                'price' => 100,
                'job_name' => 'Nephrology Specialist',
                'description' => 'Focus on kidney diseases and dialysis management.',
                'additional_information' => 'Offers home and clinic visits.',
                'translations' => [
                    'en' => [
                        'specification' => 'Nephrologist',
                        'job_name' => 'Nephrology Specialist',
                        'description' => 'Focus on kidney diseases and dialysis management.',
                        'additional_information' => 'Offers home and clinic visits.',
                    ],
                    'ar' => [
                        'specification' => 'أخصائي كلى',
                        'job_name' => 'أخصائي أمراض الكلى',
                        'description' => 'تركيز على أمراض الكلى وإدارة غسيل الكلى.',
                        'additional_information' => 'يقدم زيارات منزلية وعيادية.',
                    ],
                ],
            ],
        ];

        $doctorModels = [];
        foreach ($doctorsData as $docData) {
            $translations = $docData['translations'];
            unset($docData['translations']);
            $doc = Doctor::create($docData);
            foreach ($translations as $locale => $t) {
                $doc->translations()->create(array_merge($t, ['locale' => $locale]));
            }
            // Area prices
            foreach ($areas as $area) {
                $price = $doc->price ?: 100;
                DoctorAreaPrice::create([
                    'doctor_id' => $doc->id,
                    'area_id' => $area->id,
                    'price' => $price + ($area->id - 1) * 10, // slight variation
                ]);
            }
            $doctorModels[] = $doc;
        }

        // Availabilities for doctor 1
        $doctor1 = $doctorModels[0];
        $slots = [];
        for ($i = 1; $i <= 3; $i++) {
            $date = Carbon::today()->addDays($i)->format('Y-m-d');
            $slots[] = DoctorAvailability::create([
                'doctor_id' => $doctor1->id,
                'date' => $date,
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
            ]);
            $slots[] = DoctorAvailability::create([
                'doctor_id' => $doctor1->id,
                'date' => $date,
                'start_time' => '10:30:00',
                'end_time' => '11:30:00',
            ]);
        }

        // Operations for doctor 1
        $op = DoctorOperation::create([
            'doctor_id' => $doctor1->id,
            'name' => 'Heart Check',
            'price' => 200,
            'description' => 'Full cardiac checkup.',
            'additional_information' => 'Includes ECG and lab review.',
            'building_name' => 'Heart Center',
            'location_description' => 'Downtown, Block A',
            'image' => null,
        ]);
        $op->translations()->create([
            'locale' => 'en',
            'name' => 'Heart Check',
            'description' => 'Full cardiac checkup.',
            'additional_information' => 'Includes ECG and lab review.',
        ]);
        $op->translations()->create([
            'locale' => 'ar',
            'name' => 'فحص القلب',
            'description' => 'فحص قلبي شامل.',
            'additional_information' => 'يشمل تخطيط القلب ومراجعة التحاليل.',
        ]);
        foreach ($areas as $area) {
            DoctorOperationAreaPrice::create([
                'doctor_operation_id' => $op->id,
                'area_id' => $area->id,
                'price' => 200 + ($area->id - 1) * 20,
            ]);
        }

        // Seed two sample requests for Category 8 (using first user if exists)
        $userId = 2; // from TestDataSeeder (user@test.com)
        $slotForRequest = $slots[0] ?? null;
        if ($slotForRequest) {
            $req1 = Request::create([
                'user_id' => $userId,
                'category_id' => 8,
                'doctor_id' => $doctor1->id,
                'slot_id' => $slotForRequest->id,
                'appointment_type' => 'check_at_home',
                'area_id' => $areas->first()->id,
                'status' => Request::STATUS_SUBMITTED,
                'full_name' => 'John Doe',
                'phone_number' => '+1234567890',
                'address_city' => 'Beirut',
                'address_street' => 'Main Street',
                'request_details_files' => json_encode([]),
                'total_price' => 120,
                'discounted_price' => 120,
            ]);
            $slotForRequest->update(['is_booked' => true, 'booked_request_id' => $req1->id]);
        }
        if (isset($slots[1])) {
            $req2 = Request::create([
                'user_id' => $userId,
                'category_id' => 8,
                'doctor_id' => $doctor1->id,
                'slot_id' => $slots[1]->id,
                'appointment_type' => 'video_call',
                'area_id' => $areas->first()->id,
                'status' => Request::STATUS_SUBMITTED,
                'full_name' => 'Jane Smith',
                'phone_number' => '+1987654321',
                'address_city' => 'Beirut',
                'address_street' => 'Second Street',
                'request_details_files' => json_encode([]),
                'total_price' => 130,
                'discounted_price' => 130,
            ]);
            $slots[1]->update(['is_booked' => true, 'booked_request_id' => $req2->id]);
        }
    }
}

