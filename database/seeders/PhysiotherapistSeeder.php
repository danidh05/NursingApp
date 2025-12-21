<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Physiotherapist;
use App\Models\PhysiotherapistAreaPrice;
use App\Models\Area;
use App\Models\PhysioMachine;

class PhysiotherapistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Physio Machines first
        $physioMachines = [
            [
                'name' => 'TENS Machine',
                'price' => 50.00,
            ],
            [
                'name' => 'Ultrasound Therapy Machine',
                'price' => 75.00,
            ],
            [
                'name' => 'Heat Therapy Pad',
                'price' => 30.00,
            ],
            [
                'name' => 'Cold Therapy Pack',
                'price' => 25.00,
            ],
            [
                'name' => 'Massage Therapy Chair',
                'price' => 100.00,
            ],
        ];

        foreach ($physioMachines as $machineData) {
            PhysioMachine::create($machineData);
        }

        // Seed Physiotherapists
        $physiotherapists = [
            [
                'name' => 'Dr. John Smith',
                'price' => 200.00,
                'job_name' => 'Senior Physiotherapist',
                'job_specification' => 'Musculoskeletal Specialist',
                'specialization' => 'Sports Medicine',
                'years_of_experience' => 10,
                'translations' => [
                    'en' => [
                        'name' => 'Dr. John Smith',
                        'description' => 'Experienced physiotherapist specializing in sports medicine and musculoskeletal rehabilitation. With over 10 years of experience, Dr. Smith has helped numerous athletes and patients recover from injuries and improve their physical performance.',
                    ],
                    'ar' => [
                        'name' => 'د. جون سميث',
                        'description' => 'أخصائي علاج طبيعي ذو خبرة متخصص في الطب الرياضي وإعادة تأهيل الجهاز العضلي الهيكلي. مع أكثر من 10 سنوات من الخبرة، ساعد د. سميث العديد من الرياضيين والمرضى على التعافي من الإصابات وتحسين أدائهم البدني.',
                    ],
                ],
            ],
            [
                'name' => 'Dr. Sarah Johnson',
                'price' => 180.00,
                'job_name' => 'Physiotherapist',
                'job_specification' => 'Neurological Rehabilitation',
                'specialization' => 'Neurology',
                'years_of_experience' => 8,
                'translations' => [
                    'en' => [
                        'name' => 'Dr. Sarah Johnson',
                        'description' => 'Specialized in neurological rehabilitation, helping patients with stroke, spinal cord injuries, and neurological disorders regain their mobility and independence.',
                    ],
                    'ar' => [
                        'name' => 'د. سارة جونسون',
                        'description' => 'متخصصة في إعادة التأهيل العصبي، تساعد المرضى الذين يعانون من السكتة الدماغية وإصابات الحبل الشوكي والاضطرابات العصبية على استعادة حركتهم واستقلاليتهم.',
                    ],
                ],
            ],
            [
                'name' => 'Dr. Michael Brown',
                'price' => 220.00,
                'job_name' => 'Senior Physiotherapist',
                'job_specification' => 'Orthopedic Rehabilitation',
                'specialization' => 'Orthopedics',
                'years_of_experience' => 12,
                'translations' => [
                    'en' => [
                        'name' => 'Dr. Michael Brown',
                        'description' => 'Expert in orthopedic rehabilitation, specializing in post-surgical recovery, joint replacements, and chronic pain management.',
                    ],
                    'ar' => [
                        'name' => 'د. مايكل براون',
                        'description' => 'خبير في إعادة التأهيل العظمي، متخصص في التعافي بعد الجراحة، استبدال المفاصل، وإدارة الألم المزمن.',
                    ],
                ],
            ],
            [
                'name' => 'Dr. Emily Davis',
                'price' => 190.00,
                'job_name' => 'Physiotherapist',
                'job_specification' => 'Pediatric Rehabilitation',
                'specialization' => 'Pediatrics',
                'years_of_experience' => 7,
                'translations' => [
                    'en' => [
                        'name' => 'Dr. Emily Davis',
                        'description' => 'Dedicated to pediatric physiotherapy, helping children with developmental delays, cerebral palsy, and other pediatric conditions reach their full potential.',
                    ],
                    'ar' => [
                        'name' => 'د. إيميلي ديفيس',
                        'description' => 'ملتزمة بالعلاج الطبيعي للأطفال، تساعد الأطفال الذين يعانون من التأخر في النمو والشلل الدماغي وحالات الأطفال الأخرى على الوصول إلى إمكاناتهم الكاملة.',
                    ],
                ],
            ],
            [
                'name' => 'Dr. Robert Wilson',
                'price' => 210.00,
                'job_name' => 'Senior Physiotherapist',
                'job_specification' => 'Geriatric Rehabilitation',
                'specialization' => 'Geriatrics',
                'years_of_experience' => 15,
                'translations' => [
                    'en' => [
                        'name' => 'Dr. Robert Wilson',
                        'description' => 'Specialized in geriatric physiotherapy, focusing on improving mobility, balance, and quality of life for elderly patients.',
                    ],
                    'ar' => [
                        'name' => 'د. روبرت ويلسون',
                        'description' => 'متخصص في العلاج الطبيعي للمسنين، يركز على تحسين الحركة والتوازن وجودة الحياة للمرضى المسنين.',
                    ],
                ],
            ],
        ];

        // Get all areas for area pricing
        $areas = Area::all();

        foreach ($physiotherapists as $physioData) {
            $translations = $physioData['translations'];
            unset($physioData['translations']);

            // Create physiotherapist
            $physiotherapist = Physiotherapist::create($physioData);

            // Create translations
            foreach ($translations as $locale => $translationData) {
                $physiotherapist->translations()->create([
                    'locale' => $locale,
                    'name' => $translationData['name'],
                    'description' => $translationData['description'],
                ]);
            }

            // Create area prices for all areas (using base price initially)
            foreach ($areas as $area) {
                PhysiotherapistAreaPrice::create([
                    'physiotherapist_id' => $physiotherapist->id,
                    'area_id' => $area->id,
                    'price' => $physioData['price'], // Use base price for all areas initially
                ]);
            }
        }
    }
}

