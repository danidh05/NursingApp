<?php

namespace App\Services;

use App\Models\FAQ;
use Illuminate\Support\Collection;

class FAQTranslationService
{
    /**
     * Get FAQs with translations.
     *
     * @param Collection $faqs
     * @param string $locale
     * @return Collection
     */
    public function getFAQsWithTranslations(Collection $faqs, string $locale): Collection
    {
        return $faqs->transform(function ($faq) use ($locale) {
            $this->applyTranslations($faq, $locale);
            
            // Clean up relationships
            unset($faq->translations);
            
            return $faq;
        });
    }

    /**
     * Get a single FAQ with translations.
     *
     * @param FAQ $faq
     * @param string $locale
     * @return FAQ
     */
    public function getFAQWithTranslations(FAQ $faq, string $locale): FAQ
    {
        $this->applyTranslations($faq, $locale);
        
        // Clean up relationships
        unset($faq->translations);
        
        return $faq;
    }

    /**
     * Apply translations to a FAQ.
     *
     * @param FAQ $faq
     * @param string $locale
     * @return void
     */
    private function applyTranslations(FAQ $faq, string $locale): void
    {
        // Get the specific translation for the requested locale
        $translation = $faq->translations()->where('locale', $locale)->first();
        
        if ($translation) {
            // Translation exists for the requested locale
            $faq->question = $translation->question;
            $faq->answer = $translation->answer;
            $faq->translation = [
                'locale' => $translation->locale,
                'question' => $translation->question,
                'answer' => $translation->answer,
            ];
        } else {
            // No translation found for the requested locale, use original content
            $faq->question = $faq->getOriginal('question');
            $faq->answer = $faq->getOriginal('answer');
            $faq->translation = null;
        }
    }
} 