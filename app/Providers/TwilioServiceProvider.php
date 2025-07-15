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
            
            $client = new Client(
                config('services.twilio.sid'),
                config('services.twilio.auth_token')
            );
            
            return new TwilioService($client);
        });
    }
} 