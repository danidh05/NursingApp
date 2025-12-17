<?php

namespace Database\Seeders;

use App\Models\Ray;
use Illuminate\Database\Seeder;

class RaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔬 Seeding rays...');
        
        $rays = [
            [
                'name' => 'Chest X-Ray',
                'price' => 100.00,
                'translations' => [
                    'en' => [
                        'name' => 'Chest X-Ray',
                        'about_ray' => 'Chest X-Ray to detect lung conditions, heart problems, and chest injuries.',
                        'instructions' => 'Remove all jewelry and metal objects. Wear a hospital gown. Hold your breath when instructed.',
                        'additional_information' => 'Results available within 24 hours.',
                    ],
                    'ar' => [
                        'name' => 'أشعة الصدر',
                        'about_ray' => 'أشعة الصدر للكشف عن أمراض الرئة ومشاكل القلب وإصابات الصدر.',
                        'instructions' => 'قم بإزالة جميع المجوهرات والأشياء المعدنية. ارتدِ ثوب المستشفى. احبس أنفاسك عند الطلب.',
                        'additional_information' => 'النتائج متاحة خلال 24 ساعة.',
                    ],
                ],
            ],
            [
                'name' => 'Abdominal Ultrasound',
                'price' => 150.00,
                'translations' => [
                    'en' => [
                        'name' => 'Abdominal Ultrasound',
                        'about_ray' => 'Abdominal ultrasound to examine organs in the abdomen including liver, kidneys, and gallbladder.',
                        'instructions' => 'Fast for 6-8 hours before the procedure. Drink water to fill your bladder.',
                        'additional_information' => 'Non-invasive procedure with immediate results.',
                    ],
                    'ar' => [
                        'name' => 'الموجات فوق الصوتية للبطن',
                        'about_ray' => 'الموجات فوق الصوتية للبطن لفحص الأعضاء في البطن بما في ذلك الكبد والكلى والمرارة.',
                        'instructions' => 'الصيام لمدة 6-8 ساعات قبل الإجراء. اشرب الماء لملء المثانة.',
                        'additional_information' => 'إجراء غير جراحي مع نتائج فورية.',
                    ],
                ],
            ],
            [
                'name' => 'MRI Scan',
                'price' => 500.00,
                'translations' => [
                    'en' => [
                        'name' => 'MRI Scan',
                        'about_ray' => 'Magnetic Resonance Imaging for detailed images of internal organs and tissues.',
                        'instructions' => 'Remove all metal objects. Inform staff if you have any implants. Lie still during the scan.',
                        'additional_information' => 'Results available within 48 hours. Claustrophobic patients should inform staff.',
                    ],
                    'ar' => [
                        'name' => 'فحص الرنين المغناطيسي',
                        'about_ray' => 'التصوير بالرنين المغناطيسي للحصول على صور مفصلة للأعضاء والأنسجة الداخلية.',
                        'instructions' => 'قم بإزالة جميع الأشياء المعدنية. أخبر الموظفين إذا كان لديك أي غرسات. استلقِ بلا حراك أثناء الفحص.',
                        'additional_information' => 'النتائج متاحة خلال 48 ساعة. يجب على المرضى الذين يعانون من رهاب الأماكن المغلقة إبلاغ الموظفين.',
                    ],
                ],
            ],
            [
                'name' => 'CT Scan',
                'price' => 400.00,
                'translations' => [
                    'en' => [
                        'name' => 'CT Scan',
                        'about_ray' => 'Computed Tomography scan for detailed cross-sectional images of the body.',
                        'instructions' => 'Fast for 4 hours if contrast is used. Remove metal objects. Inform staff of allergies.',
                        'additional_information' => 'Results available within 24-48 hours.',
                    ],
                    'ar' => [
                        'name' => 'فحص الأشعة المقطعية',
                        'about_ray' => 'فحص التصوير المقطعي المحوسب للحصول على صور مقطعية مفصلة للجسم.',
                        'instructions' => 'الصيام لمدة 4 ساعات إذا تم استخدام التباين. قم بإزالة الأشياء المعدنية. أخبر الموظفين بالحساسية.',
                        'additional_information' => 'النتائج متاحة خلال 24-48 ساعة.',
                    ],
                ],
            ],
            [
                'name' => 'Bone Density Scan',
                'price' => 200.00,
                'translations' => [
                    'en' => [
                        'name' => 'Bone Density Scan',
                        'about_ray' => 'DEXA scan to measure bone mineral density and assess osteoporosis risk.',
                        'instructions' => 'Avoid calcium supplements 24 hours before. Wear comfortable clothing without metal.',
                        'additional_information' => 'Quick and painless procedure. Results available within 1 week.',
                    ],
                    'ar' => [
                        'name' => 'فحص كثافة العظام',
                        'about_ray' => 'فحص DEXA لقياس كثافة المعادن في العظام وتقييم خطر هشاشة العظام.',
                        'instructions' => 'تجنب مكملات الكالسيوم قبل 24 ساعة. ارتدِ ملابس مريحة بدون معدن.',
                        'additional_information' => 'إجراء سريع وغير مؤلم. النتائج متاحة خلال أسبوع واحد.',
                    ],
                ],
            ],
        ];
        
        $createdRays = [];
        foreach ($rays as $rayData) {
            $ray = Ray::create([
                'name' => $rayData['name'],
                'price' => $rayData['price'],
            ]);
            
            // Create translations
            foreach ($rayData['translations'] as $locale => $translation) {
                $ray->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'about_ray' => $translation['about_ray'],
                    'instructions' => $translation['instructions'],
                    'additional_information' => $translation['additional_information'],
                ]);
            }
            
            $createdRays[] = $ray;
        }
        
        $this->command->info('   ✅ ' . count($createdRays) . ' rays created');
        $this->command->info('✅ Rays seeding completed!');
    }
}

