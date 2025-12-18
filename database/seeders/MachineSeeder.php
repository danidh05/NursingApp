<?php

namespace Database\Seeders;

use App\Models\Machine;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $machines = [
            [
                'name' => 'Ventilator Machine',
                'price' => 500.00,
                'translations' => [
                    [
                        'locale' => 'en',
                        'name' => 'Ventilator Machine',
                        'description' => 'Advanced ventilator machine for respiratory support. Suitable for home care and hospital use. Features include adjustable pressure settings, oxygen concentration control, and alarm systems.',
                        'additional_information' => 'Requires professional setup and monitoring. Available for daily, weekly, or monthly rental. Includes delivery and pickup service.',
                    ],
                    [
                        'locale' => 'ar',
                        'name' => 'جهاز التنفس الصناعي',
                        'description' => 'جهاز تنفس صناعي متقدم لدعم الجهاز التنفسي. مناسب للرعاية المنزلية والاستخدام في المستشفى. يتضمن ميزات مثل إعدادات الضغط القابلة للتعديل، التحكم في تركيز الأكسجين، وأنظمة الإنذار.',
                        'additional_information' => 'يتطلب إعداد ومراقبة مهنية. متاح للإيجار اليومي أو الأسبوعي أو الشهري. يشمل خدمة التوصيل والاستلام.',
                    ],
                ],
            ],
            [
                'name' => 'Oxygen Concentrator',
                'price' => 300.00,
                'translations' => [
                    [
                        'locale' => 'en',
                        'name' => 'Oxygen Concentrator',
                        'description' => 'Portable oxygen concentrator for continuous oxygen therapy. Lightweight and easy to use. Ideal for patients requiring supplemental oxygen at home.',
                        'additional_information' => 'Includes oxygen tubing, nasal cannula, and user manual. Regular maintenance included. 24/7 technical support available.',
                    ],
                    [
                        'locale' => 'ar',
                        'name' => 'مكثف الأكسجين',
                        'description' => 'مكثف أكسجين محمول للعلاج بالأكسجين المستمر. خفيف الوزن وسهل الاستخدام. مثالي للمرضى الذين يحتاجون إلى أكسجين إضافي في المنزل.',
                        'additional_information' => 'يشمل أنابيب الأكسجين، القنية الأنفية، ودليل المستخدم. الصيانة الدورية مشمولة. الدعم الفني متاح على مدار الساعة.',
                    ],
                ],
            ],
            [
                'name' => 'Hospital Bed',
                'price' => 400.00,
                'translations' => [
                    [
                        'locale' => 'en',
                        'name' => 'Hospital Bed',
                        'description' => 'Electric adjustable hospital bed with side rails and mattress. Features include height adjustment, backrest elevation, and leg elevation controls.',
                        'additional_information' => 'Includes mattress, bed linens, and remote control. Professional setup and delivery service. Suitable for long-term home care.',
                    ],
                    [
                        'locale' => 'ar',
                        'name' => 'سرير مستشفى',
                        'description' => 'سرير مستشفى كهربائي قابل للتعديل مع قضبان جانبية ومرتبة. يتضمن ميزات مثل تعديل الارتفاع، رفع مسند الظهر، وضوابط رفع الساق.',
                        'additional_information' => 'يشمل المرتبة، أغطية السرير، والتحكم عن بعد. خدمة الإعداد المهني والتوصيل. مناسب للرعاية المنزلية طويلة الأمد.',
                    ],
                ],
            ],
            [
                'name' => 'Wheelchair',
                'price' => 150.00,
                'translations' => [
                    [
                        'locale' => 'en',
                        'name' => 'Wheelchair',
                        'description' => 'Lightweight, foldable wheelchair for mobility assistance. Easy to transport and store. Suitable for temporary or long-term use.',
                        'additional_information' => 'Available in standard and heavy-duty models. Includes cushion and footrests. Delivery and pickup service available.',
                    ],
                    [
                        'locale' => 'ar',
                        'name' => 'كرسي متحرك',
                        'description' => 'كرسي متحرك خفيف الوزن وقابل للطي للمساعدة في التنقل. سهل النقل والتخزين. مناسب للاستخدام المؤقت أو طويل الأمد.',
                        'additional_information' => 'متوفر في نماذج قياسية وثقيلة. يشمل الوسادة ومساند القدمين. خدمة التوصيل والاستلام متاحة.',
                    ],
                ],
            ],
            [
                'name' => 'Nebulizer Machine',
                'price' => 200.00,
                'translations' => [
                    [
                        'locale' => 'en',
                        'name' => 'Nebulizer Machine',
                        'description' => 'Medical nebulizer for respiratory medication delivery. Compact and easy to use. Ideal for asthma, COPD, and other respiratory conditions.',
                        'additional_information' => 'Includes nebulizer kit, mask, and mouthpiece. Cleaning supplies and instructions included. Suitable for home use.',
                    ],
                    [
                        'locale' => 'ar',
                        'name' => 'جهاز البخاخ',
                        'description' => 'بخاخ طبي لتوصيل الأدوية التنفسية. مضغوط وسهل الاستخدام. مثالي للربو ومرض الانسداد الرئوي المزمن وحالات الجهاز التنفسي الأخرى.',
                        'additional_information' => 'يشمل مجموعة البخاخ، القناع، وقطعة الفم. مستلزمات التنظيف والتعليمات مشمولة. مناسب للاستخدام المنزلي.',
                    ],
                ],
            ],
        ];

        foreach ($machines as $machineData) {
            // Create machine
            $machine = Machine::create([
                'name' => $machineData['name'],
                'price' => $machineData['price'],
                'image' => null, // Images can be uploaded via admin panel
            ]);

            // Create translations
            foreach ($machineData['translations'] as $translation) {
                $machine->translations()->create([
                    'locale' => $translation['locale'],
                    'name' => $translation['name'],
                    'description' => $translation['description'],
                    'additional_information' => $translation['additional_information'],
                ]);
            }

            // Automatically create area prices for all existing areas using the base price
            $areas = \App\Models\Area::all();
            foreach ($areas as $area) {
                \App\Models\MachineAreaPrice::create([
                    'machine_id' => $machine->id,
                    'area_id' => $area->id,
                    'price' => $machine->price, // Use the same base price for all areas initially
                ]);
            }
        }
    }
}

