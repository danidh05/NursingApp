<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TranslationService
{
    /**
     * Set translations for a model.
     */
    public function setTranslations(Model $model, array $translations): void
    {
        foreach ($translations as $locale => $data) {
            $model->setTranslation($locale, $data);
        }
    }

    /**
     * Get translations for a model in all available locales.
     */
    public function getTranslations(Model $model): Collection
    {
        return $model->translations;
    }

    /**
     * Get a specific translation for a model.
     */
    public function getTranslation(Model $model, string $locale)
    {
        return $model->translate($locale);
    }

    /**
     * Check if a model has translation for a specific locale.
     */
    public function hasTranslation(Model $model, string $locale): bool
    {
        return $model->hasTranslation($locale);
    }

    /**
     * Get all available locales for a model.
     */
    public function getAvailableLocales(Model $model): array
    {
        return $model->getAvailableLocales();
    }

    /**
     * Delete a specific translation for a model.
     */
    public function deleteTranslation(Model $model, string $locale): bool
    {
        $translation = $model->translations()->where('locale', $locale)->first();
        
        if ($translation) {
            return $translation->delete();
        }
        
        return false;
    }

    /**
     * Get translated data for API responses.
     */
    public function getTranslatedData(Model $model, string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $translation = $model->translate($locale);
        
        if (!$translation) {
            return [];
        }
        
        // Get the translation data as an array
        $translatedData = $translation->toArray();
        
        // Remove the model-specific fields and keep only the translated fields
        unset($translatedData['id'], $translatedData['created_at'], $translatedData['updated_at']);
        
        // Remove the foreign key field (we'll determine it dynamically)
        $foreignKey = strtolower(class_basename($model)) . '_id';
        unset($translatedData[$foreignKey]);
        
        return $translatedData;
    }

    /**
     * Get all translations for a model in a structured format.
     */
    public function getAllTranslations(Model $model): array
    {
        $translations = [];
        
        foreach ($model->translations as $translation) {
            $locale = $translation->locale;
            $translatedData = $translation->toArray();
            
            // Remove the model-specific fields
            unset($translatedData['id'], $translatedData['created_at'], $translatedData['updated_at']);
            
            // Remove the foreign key field (we'll determine it dynamically)
            $foreignKey = strtolower(class_basename($model)) . '_id';
            unset($translatedData[$foreignKey]);
            
            $translations[$locale] = $translatedData;
        }
        
        return $translations;
    }

    /**
     * Validate translation data.
     */
    public function validateTranslationData(array $data, array $requiredFields): array
    {
        $errors = [];
        
        foreach ($data as $locale => $translationData) {
            if (!is_string($locale) || strlen($locale) !== 2) {
                $errors[] = "Invalid locale format: {$locale}";
                continue;
            }
            
            foreach ($requiredFields as $field) {
                if (!isset($translationData[$field]) || empty($translationData[$field])) {
                    $errors[] = "Missing required field '{$field}' for locale '{$locale}'";
                }
            }
        }
        
        return $errors;
    }
} 