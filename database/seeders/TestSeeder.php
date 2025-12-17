<?php

namespace Database\Seeders;

use App\Models\Test;
use App\Models\TestPackage;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧪 Seeding tests and test packages...');
        
        // Create tests
        $tests = [
            [
                'name' => 'Complete Blood Count',
                'sample_type' => 'Blood',
                'price' => 50.00,
                'translations' => [
                    'en' => [
                        'name' => 'Complete Blood Count',
                        'about_test' => 'Complete blood count test to check for various health conditions including anemia, infections, and blood disorders.',
                        'instructions' => 'Fasting required for 8 hours before the test. Avoid alcohol 24 hours before.',
                    ],
                    'ar' => [
                        'name' => 'تعداد الدم الكامل',
                        'about_test' => 'فحص تعداد الدم الكامل للتحقق من حالات صحية مختلفة بما في ذلك فقر الدم والالتهابات واضطرابات الدم.',
                        'instructions' => 'الصيام مطلوب لمدة 8 ساعات قبل الفحص. تجنب الكحول قبل 24 ساعة.',
                    ],
                ],
            ],
            [
                'name' => 'Urine Analysis',
                'sample_type' => 'Urine',
                'price' => 30.00,
                'translations' => [
                    'en' => [
                        'name' => 'Urine Analysis',
                        'about_test' => 'Urine analysis to detect infections, kidney problems, and diabetes.',
                        'instructions' => 'Collect first morning urine sample in a clean container.',
                    ],
                    'ar' => [
                        'name' => 'تحليل البول',
                        'about_test' => 'تحليل البول للكشف عن الالتهابات ومشاكل الكلى ومرض السكري.',
                        'instructions' => 'اجمع عينة البول الأولى في الصباح في وعاء نظيف.',
                    ],
                ],
            ],
            [
                'name' => 'Saliva Test',
                'sample_type' => 'Saliva',
                'price' => 25.00,
                'translations' => [
                    'en' => [
                        'name' => 'Saliva Test',
                        'about_test' => 'Saliva test for DNA analysis and hormone testing.',
                        'instructions' => 'Do not eat, drink, or brush teeth 30 minutes before sample collection.',
                    ],
                    'ar' => [
                        'name' => 'فحص اللعاب',
                        'about_test' => 'فحص اللعاب لتحليل الحمض النووي وفحص الهرمونات.',
                        'instructions' => 'لا تأكل أو تشرب أو تنظف أسنانك قبل 30 دقيقة من جمع العينة.',
                    ],
                ],
            ],
            [
                'name' => 'Stool Analysis',
                'sample_type' => 'Stool',
                'price' => 40.00,
                'translations' => [
                    'en' => [
                        'name' => 'Stool Analysis',
                        'about_test' => 'Stool analysis to detect parasites, bacteria, and digestive issues.',
                        'instructions' => 'Collect sample in provided container. Avoid contamination with urine.',
                    ],
                    'ar' => [
                        'name' => 'تحليل البراز',
                        'about_test' => 'تحليل البراز للكشف عن الطفيليات والبكتيريا ومشاكل الجهاز الهضمي.',
                        'instructions' => 'اجمع العينة في الحاوية الم provided. تجنب التلوث بالبول.',
                    ],
                ],
            ],
            [
                'name' => 'Swab Test',
                'sample_type' => 'Swab',
                'price' => 35.00,
                'translations' => [
                    'en' => [
                        'name' => 'Swab Test',
                        'about_test' => 'Swab test for bacterial and viral infections.',
                        'instructions' => 'Sample will be collected by healthcare professional.',
                    ],
                    'ar' => [
                        'name' => 'فحص المسحة',
                        'about_test' => 'فحص المسحة للعدوى البكتيرية والفيروسية.',
                        'instructions' => 'سيتم جمع العينة من قبل أخصائي الرعاية الصحية.',
                    ],
                ],
            ],
        ];
        
        $createdTests = [];
        foreach ($tests as $testData) {
            $test = Test::create([
                'name' => $testData['name'],
                'sample_type' => $testData['sample_type'],
                'price' => $testData['price'],
            ]);
            
            // Create translations
            foreach ($testData['translations'] as $locale => $translation) {
                $test->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'about_test' => $translation['about_test'],
                    'instructions' => $translation['instructions'],
                ]);
            }
            
            $createdTests[] = $test;
        }
        
        $this->command->info('   ✅ ' . count($createdTests) . ' tests created');
        
        // Create test packages
        $testPackages = [
            [
                'name' => 'Basic Package',
                'results' => 'within 48 hours',
                'price' => 150.00,
                'show_details' => true,
                'test_ids' => [1, 2], // Blood, Urine
                'translations' => [
                    'en' => [
                        'name' => 'Basic Package',
                        'about_test' => 'Basic health screening package including blood and urine tests.',
                        'instructions' => 'Follow all test instructions carefully. Fasting required for blood test.',
                    ],
                    'ar' => [
                        'name' => 'الباقة الأساسية',
                        'about_test' => 'باقة فحص الصحة الأساسية تشمل فحوصات الدم والبول.',
                        'instructions' => 'اتبع جميع تعليمات الفحص بعناية. الصيام مطلوب لفحص الدم.',
                    ],
                ],
            ],
            [
                'name' => 'Comprehensive Package',
                'results' => 'within 72 hours',
                'price' => 300.00,
                'show_details' => true,
                'test_ids' => [1, 2, 3, 4], // Blood, Urine, Saliva, Stool
                'translations' => [
                    'en' => [
                        'name' => 'Comprehensive Package',
                        'about_test' => 'Complete health assessment package with multiple test types.',
                        'instructions' => 'Comprehensive testing instructions. Follow each test requirement.',
                    ],
                    'ar' => [
                        'name' => 'الباقة الشاملة',
                        'about_test' => 'باقة تقييم الصحة الكاملة مع أنواع متعددة من الفحوصات.',
                        'instructions' => 'تعليمات الفحص الشاملة. اتبع متطلبات كل فحص.',
                    ],
                ],
            ],
            [
                'name' => 'Premium Package',
                'results' => 'within 24 hours',
                'price' => 500.00,
                'show_details' => true,
                'test_ids' => [1, 2, 3, 4, 5], // All tests
                'translations' => [
                    'en' => [
                        'name' => 'Premium Package',
                        'about_test' => 'Premium health screening with all available tests and fastest results.',
                        'instructions' => 'Premium package includes priority processing and fastest results.',
                    ],
                    'ar' => [
                        'name' => 'الباقة المميزة',
                        'about_test' => 'فحص صحة مميز مع جميع الفحوصات المتاحة وأسرع النتائج.',
                        'instructions' => 'الباقة المميزة تشمل المعالجة ذات الأولوية وأسرع النتائج.',
                    ],
                ],
            ],
        ];
        
        foreach ($testPackages as $packageData) {
            $package = TestPackage::create([
                'name' => $packageData['name'],
                'results' => $packageData['results'],
                'price' => $packageData['price'],
                'show_details' => $packageData['show_details'],
            ]);
            
            // Attach tests
            $package->tests()->attach($packageData['test_ids']);
            
            // Create translations
            foreach ($packageData['translations'] as $locale => $translation) {
                $package->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'about_test' => $translation['about_test'],
                    'instructions' => $translation['instructions'],
                ]);
            }
        }
        
        $this->command->info('   ✅ ' . count($testPackages) . ' test packages created');
        $this->command->info('✅ Tests and test packages seeding completed!');
    }
}
