<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TwilioService;
use Twilio\Rest\Client;

class TwilioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TwilioService::class, function ($app) {
            if (app()->environment('testing')) {
                return new TwilioService(null);
            }
            
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
} 