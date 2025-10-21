<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $client;

    public function __construct()
    {
        $sid = config('services.twilio.account_sid');
        $token = config('services.twilio.auth_token');

        if (!$sid || !$token) {
            Log::error('Twilio credentials missing: SID or TOKEN is null');
            $this->client = null;
            return;
        }

        $this->client = new Client($sid, $token);
    }

    public function sendVerificationCode(string $phoneNumber): bool
    {
        if (!$this->client) {
            Log::error('Twilio client not initialized');
            return false;
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
            Log::error('Twilio client not initialized');
            return false;
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