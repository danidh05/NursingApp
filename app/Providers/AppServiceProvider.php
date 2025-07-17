<?php

namespace App\Providers;

use App\Repositories\Interfaces\IRequestRepository;
use App\Repositories\RequestRepository;
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