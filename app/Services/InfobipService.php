<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfobipService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.infobip.base_url');
        $this->apiKey = config('services.infobip.api_key');
    }

    public function sendSMS(string $to, string $message): bool
    {
        try {
            // Remove any manual transaction management
            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/sms/2/text/advanced", [
                'messages' => [
                    [
                        'from' => config('services.infobip.from'),
                        'destinations' => [
                            ['to' => $to]
                        ],
                        'text' => $message
                    ]
                ]
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Infobip SMS failed: ' . $e->getMessage());
            return false;
        }
    }
}