<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Collection;

class ServiceTranslationService
{
    /**
     * Get services with area-based pricing and translations.
     *
     * @param Collection $services
     * @param int|null $userAreaId
     * @param string $locale
     * @return Collection
     */
    public function getServicesWithPricingAndTranslations(Collection $services, ?int $userAreaId, string $locale): Collection
    {
        return $services->transform(function ($service) use ($userAreaId, $locale) {
            // Handle area-based pricing
            $this->applyAreaBasedPricing($service, $userAreaId);
            
            // Handle translations
            $this->applyTranslations($service, $locale);
            
            // Clean up relationships
            unset($service->areaPrices, $service->translations);
            
            return $service;
        });
    }

    /**
     * Apply area-based pricing to a service.
     *
     * @param Service $service
     * @param int|null $userAreaId
     * @return void
     */
    private function applyAreaBasedPricing(Service $service, ?int $userAreaId): void
    {
        $areaPrice = $service->areaPrices->first();
        
        if ($userAreaId && $areaPrice) {
            // User has area and area pricing exists - show area-specific price
            $service->price = $areaPrice->price;
            $service->area_name = $areaPrice->area->name;
        } else {
            // User has no area or no area pricing exists - show original price
            $service->price = $service->getOriginal('price');
            // Don't include area_name when showing original price
        }
    }

    /**
     * Apply translations to a service.
     *
     * @param Service $service
     * @param string $locale
     * @return void
     */
    private function applyTranslations(Service $service, string $locale): void
    {
        $translation = $service->translate($locale);
        
        if ($translation) {
            $service->name = $translation->name;
            $service->translation = [
                'locale' => $translation->locale,
                'name' => $translation->name,
            ];
        } else {
            // No translation found, use default name
            $service->name = $service->getOriginal('name');
            $service->translation = null;
        }
    }

    /**
     * Get a single service with pricing and translations.
     *
     * @param Service $service
     * @param int|null $userAreaId
     * @param string $locale
     * @return Service
     */
    public function getServiceWithPricingAndTranslations(Service $service, ?int $userAreaId, string $locale): Service
    {
        // Handle area-based pricing
        $this->applyAreaBasedPricing($service, $userAreaId);
        
        // Handle translations
        $this->applyTranslations($service, $locale);
        
        // Clean up relationships
        unset($service->areaPrices, $service->translations);
        
        return $service;
    }
} 