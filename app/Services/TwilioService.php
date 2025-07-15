<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client;
    }

    public function sendVerificationCode(string $phoneNumber): bool
    {
        if (!$this->client) {
            return true; // For testing purposes
        }

        try {
            $this->client->verify->v2
                ->services(config('services.twilio.verify_service_sid'))
                ->verifications
                ->create($phoneNumber, 'whatsapp');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send verification code: ' . $e->getMessage());
            return false;
        }
    }

    public function verifyCode(string $phoneNumber, string $code): bool
    {
        if (!$this->client) {
            return true; // For testing purposes
        }

        try {
            $verification = $this->client->verify->v2
                ->services(config('services.twilio.verify_service_sid'))
                ->verificationChecks
                ->create([
                    'to' => $phoneNumber,
                    'code' => $code
                ]);

            return $verification->status === 'approved';
        } catch (\Exception $e) {
            Log::error('Failed to verify code: ' . $e->getMessage());
            return false;
        }
    }
} 