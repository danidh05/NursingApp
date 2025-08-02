<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTranslations
{
    /**
     * Get the translations for the model.
     */
    public function translations(): HasMany
    {
        $translationClass = $this->getTranslationClass();
        return $this->hasMany($translationClass, $this->getTranslationForeignKey());
    }

    /**
     * Get the translation for a specific locale.
     * Falls back to the default locale if translation doesn't exist.
     */
    public function translate($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $defaultLocale = config('app.locale', 'en');
        
        // First try to get the requested locale
        $translation = $this->translations()->where('locale', $locale)->first();
        
        // If not found and it's not the default locale, try default locale
        if (!$translation && $locale !== $defaultLocale) {
            $translation = $this->translations()->where('locale', $defaultLocale)->first();
        }
        
        // If still not found, try any available translation
        if (!$translation) {
            $translation = $this->translations()->first();
        }
        
        return $translation;
    }

    /**
     * Get the translation class name.
     */
    protected function getTranslationClass(): string
    {
        $modelClass = get_class($this);
        $modelName = class_basename($modelClass);
        return "App\\Models\\{$modelName}Translation";
    }

    /**
     * Get the foreign key name for the translation relationship.
     */
    protected function getTranslationForeignKey(): string
    {
        return strtolower(class_basename($this)) . '_id';
    }

    /**
     * Create or update a translation.
     */
    public function setTranslation(string $locale, array $data): void
    {
        $this->translations()->updateOrCreate(
            ['locale' => $locale],
            $data
        );
    }

    /**
     * Get all available locales for this model.
     */
    public function getAvailableLocales(): array
    {
        return $this->translations()->pluck('locale')->toArray();
    }

    /**
     * Check if a translation exists for the given locale.
     */
    public function hasTranslation(string $locale): bool
    {
        return $this->translations()->where('locale', $locale)->exists();
    }
} 