<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offer;
use App\Models\OfferAreaPrice;
use App\Models\Area;
use App\Models\Category;

class OfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories for linking offers
        $category1 = Category::find(1); // Service Request
        
        // Get all areas for area pricing
        $areas = Area::all();

        $offers = [
            [
                'name' => 'Special Service Offer',
                'offer_price' => 10.00,
                'old_price' => 20.00,
                'offer_available_until' => '3 Days',
                'category_id' => $category1?->id,
                'translations' => [
                    'en' => [
                        'name' => 'Special Service Offer',
                        'description' => 'Get 50% off on selected nursing services. Limited time offer!',
                    ],
                    'ar' => [
                        'name' => 'عرض خدمة خاص',
                        'description' => 'احصل على خصم 50% على خدمات التمريض المختارة. عرض لفترة محدودة!',
                    ],
                ],
            ],
            [
                'name' => 'Home Care Package Deal',
                'offer_price' => 150.00,
                'old_price' => 200.00,
                'offer_available_until' => '7 Days',
                'category_id' => $category1?->id,
                'translations' => [
                    'en' => [
                        'name' => 'Home Care Package Deal',
                        'description' => 'Complete home care package at a discounted price. Perfect for long-term care needs.',
                    ],
                    'ar' => [
                        'name' => 'عرض باقة الرعاية المنزلية',
                        'description' => 'باقة الرعاية المنزلية الكاملة بسعر مخفض. مثالي لاحتياجات الرعاية طويلة الأمد.',
                    ],
                ],
            ],
            [
                'name' => 'Emergency Care Discount',
                'offer_price' => 80.00,
                'old_price' => 120.00,
                'offer_available_until' => '5 Days',
                'category_id' => $category1?->id,
                'translations' => [
                    'en' => [
                        'name' => 'Emergency Care Discount',
                        'description' => 'Special discount on emergency nursing care services. Available 24/7.',
                    ],
                    'ar' => [
                        'name' => 'خصم الرعاية الطارئة',
                        'description' => 'خصم خاص على خدمات الرعاية التمريضية الطارئة. متاح على مدار الساعة.',
                    ],
                ],
            ],
            [
                'name' => 'Senior Care Special',
                'offer_price' => 180.00,
                'old_price' => 250.00,
                'offer_available_until' => '10 Days',
                'category_id' => $category1?->id,
                'translations' => [
                    'en' => [
                        'name' => 'Senior Care Special',
                        'description' => 'Comprehensive senior care services at a special price. Includes regular check-ups and monitoring.',
                    ],
                    'ar' => [
                        'name' => 'عرض رعاية المسنين الخاص',
                        'description' => 'خدمات رعاية المسنين الشاملة بسعر خاص. يشمل الفحوصات والمراقبة المنتظمة.',
                    ],
                ],
            ],
            [
                'name' => 'Post-Surgery Care Package',
                'offer_price' => 200.00,
                'old_price' => 300.00,
                'offer_available_until' => '14 Days',
                'category_id' => $category1?->id,
                'translations' => [
                    'en' => [
                        'name' => 'Post-Surgery Care Package',
                        'description' => 'Complete post-surgery care package with professional nursing support and monitoring.',
                    ],
                    'ar' => [
                        'name' => 'باقة الرعاية بعد الجراحة',
                        'description' => 'باقة الرعاية الكاملة بعد الجراحة مع الدعم التمريضي المهني والمراقبة.',
                    ],
                ],
            ],
        ];

        foreach ($offers as $offerData) {
            $translations = $offerData['translations'];
            unset($offerData['translations']);

            // Create offer
            $offer = Offer::create($offerData);

            // Create translations
            foreach ($translations as $locale => $translationData) {
                $offer->translations()->create([
                    'locale' => $locale,
                    'name' => $translationData['name'],
                    'description' => $translationData['description'],
                ]);
            }

            // Create area prices for all areas (using base prices initially)
            foreach ($areas as $area) {
                OfferAreaPrice::create([
                    'offer_id' => $offer->id,
                    'area_id' => $area->id,
                    'offer_price' => $offerData['offer_price'], // Use base offer price for all areas initially
                    'old_price' => $offerData['old_price'], // Use base old price for all areas initially
                ]);
            }
        }
    }
}

