<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        
        // Default locale
        $locale = config('app.locale', 'en');
        
        if ($acceptLanguage) {
            // Parse Accept-Language header (e.g., "ar,en;q=0.9,en-US;q=0.8")
            $languages = $this->parseAcceptLanguage($acceptLanguage);
            
            // Find the first supported language
            foreach ($languages as $lang) {
                $langCode = strtolower(trim($lang));
                
                // Check if it's a supported locale
                if (in_array($langCode, ['en', 'ar'])) {
                    $locale = $langCode;
                    break;
                }
            }
        }
        
        // Set the application locale
        app()->setLocale($locale);
        
        return $next($request);
    }
    
    /**
     * Parse Accept-Language header.
     *
     * @param string $acceptLanguage
     * @return array
     */
    private function parseAcceptLanguage(string $acceptLanguage): array
    {
        $languages = [];
        
        // Split by comma and process each language
        $parts = explode(',', $acceptLanguage);
        
        foreach ($parts as $part) {
            // Remove quality value if present (e.g., "en;q=0.9" -> "en")
            $lang = trim(explode(';', $part)[0]);
            if (!empty($lang)) {
                $languages[] = $lang;
            }
        }
        
        return $languages;
    }
} 