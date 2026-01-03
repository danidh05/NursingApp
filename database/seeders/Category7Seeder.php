<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Babysitter;
use App\Models\BabysitterAreaPrice;
use App\Models\Duty;
use App\Models\DutyAreaPrice;
use App\Models\NurseVisit;
use App\Models\NurseVisitAreaPrice;
use Illuminate\Database\Seeder;

class Category7Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = Area::all();
        
        // Seed Nurse Visits
        $nurseVisits = [
            [
                'name' => 'Nurse Visit',
                'price_per_1_visit' => 50.00,
                'price_per_2_visits' => 90.00,
                'price_per_3_visits' => 130.00,
                'price_per_4_visits' => 170.00,
                'translations' => [
                    'en' => [
                        'about' => 'Professional nursing visits for home care services.',
                        'terms_and_conditions' => 'Visits must be scheduled in advance. Cancellation requires 24-hour notice.',
                        'additional_instructions' => 'Please ensure the patient is ready for the visit. Have all necessary documents available.',
                        'service_include' => 'Medical assessment, medication administration, wound care, vital signs monitoring.',
                        'description' => 'Our experienced nurses provide comprehensive home care services.',
                        'additional_information' => 'Available 24/7 for emergency visits.',
                    ],
                    'ar' => [
                        'about' => 'زيارات تمريضية مهنية لخدمات الرعاية المنزلية.',
                        'terms_and_conditions' => 'يجب جدولة الزيارات مسبقاً. يتطلب الإلغاء إشعاراً قبل 24 ساعة.',
                        'additional_instructions' => 'يرجى التأكد من استعداد المريض للزيارة. احتفظ بجميع المستندات اللازمة.',
                        'service_include' => 'التقييم الطبي، إعطاء الأدوية، العناية بالجروح، مراقبة العلامات الحيوية.',
                        'description' => 'ممرضاتنا ذوات الخبرة يقدمن خدمات رعاية منزلية شاملة.',
                        'additional_information' => 'متاح على مدار الساعة للزيارات الطارئة.',
                    ],
                ],
            ],
        ];

        foreach ($nurseVisits as $nurseVisitData) {
            $translations = $nurseVisitData['translations'];
            unset($nurseVisitData['translations']);
            
            $nurseVisit = NurseVisit::create($nurseVisitData);
            
            // Create translations
            foreach ($translations as $locale => $translationData) {
                $nurseVisit->translations()->create(array_merge($translationData, ['locale' => $locale]));
            }
            
            // Create area prices
            foreach ($areas as $area) {
                NurseVisitAreaPrice::create([
                    'nurse_visit_id' => $nurseVisit->id,
                    'area_id' => $area->id,
                    'price_per_1_visit' => $nurseVisitData['price_per_1_visit'],
                    'price_per_2_visits' => $nurseVisitData['price_per_2_visits'],
                    'price_per_3_visits' => $nurseVisitData['price_per_3_visits'],
                    'price_per_4_visits' => $nurseVisitData['price_per_4_visits'],
                ]);
            }
        }

        // Seed Duties
        $duties = [
            [
                'name' => 'Duty',
                'day_shift_price_4_hours' => 80.00,
                'day_shift_price_6_hours' => 110.00,
                'day_shift_price_8_hours' => 140.00,
                'day_shift_price_12_hours' => 200.00,
                'night_shift_price_4_hours' => 100.00,
                'night_shift_price_6_hours' => 140.00,
                'night_shift_price_8_hours' => 180.00,
                'night_shift_price_12_hours' => 250.00,
                'price_24_hours' => 400.00, // 24-hour shift price (not day/night specific)
                'continuous_care_price' => 5000.00,
                'translations' => [
                    'en' => [
                        'about' => 'Professional nursing duty services for extended care.',
                        'terms_and_conditions' => 'Duty shifts require advance booking. Minimum 4 hours per shift.',
                        'additional_instructions' => 'Nurses will provide continuous monitoring and care during duty hours.',
                        'service_include' => '24/7 monitoring, medication management, personal care, meal assistance.',
                        'description' => 'Comprehensive nursing duty services for patients requiring extended care.',
                        'additional_information' => 'Available for day and night shifts. Continuous care options available.',
                    ],
                    'ar' => [
                        'about' => 'خدمات واجبات تمريضية مهنية للرعاية الممتدة.',
                        'terms_and_conditions' => 'تتطلب نوبات الواجب الحجز مسبقاً. الحد الأدنى 4 ساعات لكل نوبة.',
                        'additional_instructions' => 'ستوفر الممرضات المراقبة والرعاية المستمرة خلال ساعات الواجب.',
                        'service_include' => 'المراقبة على مدار الساعة، إدارة الأدوية، الرعاية الشخصية، المساعدة في الوجبات.',
                        'description' => 'خدمات واجبات تمريضية شاملة للمرضى الذين يحتاجون رعاية ممتدة.',
                        'additional_information' => 'متاح لنوبات النهار والليل. خيارات الرعاية المستمرة متاحة.',
                    ],
                ],
            ],
        ];

        foreach ($duties as $dutyData) {
            $translations = $dutyData['translations'];
            unset($dutyData['translations']);
            
            $duty = Duty::create($dutyData);
            
            // Create translations
            foreach ($translations as $locale => $translationData) {
                $duty->translations()->create(array_merge($translationData, ['locale' => $locale]));
            }
            
            // Create area prices
            foreach ($areas as $area) {
                DutyAreaPrice::create([
                    'duty_id' => $duty->id,
                    'area_id' => $area->id,
                    'day_shift_price_4_hours' => $dutyData['day_shift_price_4_hours'],
                    'day_shift_price_6_hours' => $dutyData['day_shift_price_6_hours'],
                    'day_shift_price_8_hours' => $dutyData['day_shift_price_8_hours'],
                    'day_shift_price_12_hours' => $dutyData['day_shift_price_12_hours'],
                    'night_shift_price_4_hours' => $dutyData['night_shift_price_4_hours'],
                    'night_shift_price_6_hours' => $dutyData['night_shift_price_6_hours'],
                    'night_shift_price_8_hours' => $dutyData['night_shift_price_8_hours'],
                    'night_shift_price_12_hours' => $dutyData['night_shift_price_12_hours'],
                    'price_24_hours' => $dutyData['price_24_hours'],
                    'continuous_care_price' => $dutyData['continuous_care_price'],
                ]);
            }
        }

        // Seed Babysitters
        $babysitters = [
            [
                'name' => 'Baby Sitter',
                'day_shift_price_12_hours' => 150.00,
                'day_shift_price_24_hours' => 280.00, // Deprecated: kept for backward compatibility
                'night_shift_price_12_hours' => 180.00,
                'night_shift_price_24_hours' => 340.00, // Deprecated: kept for backward compatibility
                'price_24_hours' => 310.00, // 24-hour shift price (not day/night specific) - USE THIS
                'translations' => [
                    'en' => [
                        'about' => 'Professional babysitting services for your children.',
                        'terms_and_conditions' => 'Babysitters are certified and experienced. Advance booking required.',
                        'additional_instructions' => 'Please provide all necessary information about your child\'s needs and routines.',
                        'service_include' => 'Child supervision, meal preparation, play activities, bedtime routines.',
                        'description' => 'Experienced and caring babysitters for your peace of mind.',
                        'additional_information' => 'Available for day and night shifts. Emergency services available.',
                    ],
                    'ar' => [
                        'about' => 'خدمات جليسة أطفال مهنية لأطفالك.',
                        'terms_and_conditions' => 'الجليسات معتمدات وذوات خبرة. الحجز مسبقاً مطلوب.',
                        'additional_instructions' => 'يرجى تقديم جميع المعلومات اللازمة حول احتياجات طفلك وروتينه.',
                        'service_include' => 'إشراف على الأطفال، تحضير الوجبات، أنشطة اللعب، روتين وقت النوم.',
                        'description' => 'جليسات أطفال ذوات خبرة ورعاية لراحة بالك.',
                        'additional_information' => 'متاح لنوبات النهار والليل. الخدمات الطارئة متاحة.',
                    ],
                ],
            ],
        ];

        foreach ($babysitters as $babysitterData) {
            $translations = $babysitterData['translations'];
            unset($babysitterData['translations']);
            
            $babysitter = Babysitter::create($babysitterData);
            
            // Create translations
            foreach ($translations as $locale => $translationData) {
                $babysitter->translations()->create(array_merge($translationData, ['locale' => $locale]));
            }
            
            // Create area prices
            foreach ($areas as $area) {
                BabysitterAreaPrice::create([
                    'babysitter_id' => $babysitter->id,
                    'area_id' => $area->id,
                    'day_shift_price_12_hours' => $babysitterData['day_shift_price_12_hours'],
                    'day_shift_price_24_hours' => $babysitterData['day_shift_price_24_hours'], // Deprecated
                    'night_shift_price_12_hours' => $babysitterData['night_shift_price_12_hours'],
                    'night_shift_price_24_hours' => $babysitterData['night_shift_price_24_hours'], // Deprecated
                    'price_24_hours' => $babysitterData['price_24_hours'],
                ]);
            }
        }
    }
}

