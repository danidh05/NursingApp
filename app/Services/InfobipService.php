<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class InfobipService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://peykq3.api.infobip.com/sms/2/text/advanced';
        $this->apiKey = env('INFOBIP_API_KEY'); // Make sure your API key is set in the .env file
    }

    public function sendSms($to, $message)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl, [
                'messages' => [
                    [
                        'destinations' => [['to' => $to]],
                        'from' => '447491163443', // Adjust the sender ID or phone number as needed
                        'text' => $message,
                    ]
                ]
            ]);

            if ($response->successful()) {
                return $response->json(); // Return the response if successful
            } else {
                \Log::error('Unexpected HTTP status: ' . $response->status() . ' ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Infobip SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }
}