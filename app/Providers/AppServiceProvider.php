<?php

namespace App\Providers;

use App\Repositories\Interfaces\IRequestRepository;
use App\Repositories\RequestRepository;
use App\Repositories\Interfaces\ISliderRepository;
use App\Repositories\SliderRepository;
use App\Repositories\Interfaces\IPopupRepository;
use App\Repositories\PopupRepository;
use App\Repositories\Interfaces\IFAQRepository;
use App\Repositories\FAQRepository;
use App\Repositories\Interfaces\IAreaRepository;
use App\Repositories\AreaRepository;
use App\Repositories\Interfaces\IContactRepository;
use App\Repositories\ContactRepository;
use App\Services\Interfaces\IRequestService;
use App\Services\RequestService;
use App\Services\TwilioService;
use Illuminate\Support\ServiceProvider;
use Twilio\Rest\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Request Repository
        $this->app->bind(IRequestRepository::class, RequestRepository::class);

        // Register Request Service
        $this->app->bind(IRequestService::class, RequestService::class);

        // Register Slider Repository
        $this->app->bind(ISliderRepository::class, SliderRepository::class);

        // Register Popup Repository
        $this->app->bind(IPopupRepository::class, PopupRepository::class);

        // Register FAQ Repository
        $this->app->bind(IFAQRepository::class, FAQRepository::class);

        // Register Area Repository
        $this->app->bind(IAreaRepository::class, AreaRepository::class);

        // Register Contact Repository
        $this->app->bind(IContactRepository::class, ContactRepository::class);

        // Register TwilioService with proper client based on environment
        $this->app->bind(TwilioService::class, function ($app) {
            if (app()->environment('testing')) {
                // Use null client for testing
                return new TwilioService(null);
            }
            
            // Use actual Twilio client for production/staging
            $accountSid = config('services.twilio.account_sid');
            $authToken = config('services.twilio.auth_token');
            
            if ($accountSid && $authToken) {
                $client = new Client($accountSid, $authToken);
                return new TwilioService($client);
            }
            
            // Fallback to null client if credentials are missing
            return new TwilioService(null);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}