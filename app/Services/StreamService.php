<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class StreamService
{
    private string $apiKey;
    private string $apiSecret;
    private string $appId;
    private string $apiBase;
    private Client $httpClient;

    public function __construct()
    {
        $this->apiKey = config('services.stream.api_key');
        $this->apiSecret = config('services.stream.api_secret');
        $this->appId = config('services.stream.app_id');
        $this->apiBase = config('services.stream.api_base');
        $this->httpClient = new Client([
            'base_uri' => $this->apiBase,
            'timeout' => 30,
        ]);
    }

    /**
     * Generate a JWT token for Stream.io authentication
     *
     * @param string $userId The user ID to generate token for
     * @param int $expirationTime Token expiration time in seconds (default: 1 hour)
     * @return string The JWT token
     */
    public function generateToken(string $userId, int $expirationTime = 3600): string
    {
        $now = time();
        
        $payload = [
            'user_id' => $userId,
            'iat' => $now,
            'exp' => $now + $expirationTime,
        ];

        return JWT::encode($payload, $this->apiSecret, 'HS256');
    }

    /**
     * Create a new user in Stream.io
     *
     * @param string $userId
     * @param array $userData Additional user data (name, email, etc.)
     * @return array|false
     */
    public function createUser(string $userId, array $userData = [])
    {
        try {
            $response = $this->httpClient->post("/v1/users", [
                'headers' => [
                    'Authorization' => $this->getAuthHeader(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'id' => $userId,
                    'name' => $userData['name'] ?? $userId,
                    'email' => $userData['email'] ?? null,
                    'image' => $userData['image'] ?? null,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Failed to create Stream user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a user in Stream.io
     *
     * @param string $userId
     * @param array $userData
     * @return array|false
     */
    public function updateUser(string $userId, array $userData)
    {
        try {
            $response = $this->httpClient->put("/v1/users/{$userId}", [
                'headers' => [
                    'Authorization' => $this->getAuthHeader(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $userData,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Failed to update Stream user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a channel in Stream.io
     *
     * @param string $channelType
     * @param string $channelId
     * @param array $members Array of user IDs to add to the channel
     * @param array $channelData Additional channel data
     * @return array|false
     */
    public function createChannel(string $channelType, string $channelId, array $members = [], array $channelData = [])
    {
        try {
            $response = $this->httpClient->post("/v1/channels/{$channelType}/{$channelId}", [
                'headers' => [
                    'Authorization' => $this->getAuthHeader(),
                    'Content-Type' => 'application/json',
                ],
                'json' => array_merge([
                    'members' => $members,
                ], $channelData),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Failed to create Stream channel: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add members to a channel
     *
     * @param string $channelType
     * @param string $channelId
     * @param array $members Array of user IDs to add
     * @return array|false
     */
    public function addChannelMembers(string $channelType, string $channelId, array $members)
    {
        try {
            $response = $this->httpClient->post("/v1/channels/{$channelType}/{$channelId}", [
                'headers' => [
                    'Authorization' => $this->getAuthHeader(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'add_members' => $members,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Failed to add channel members: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a message to a channel
     *
     * @param string $channelType
     * @param string $channelId
     * @param string $message
     * @param string $userId
     * @param array $additionalData
     * @return array|false
     */
    public function sendMessage(string $channelType, string $channelId, string $message, string $userId, array $additionalData = [])
    {
        try {
            $response = $this->httpClient->post("/v1/channels/{$channelType}/{$channelId}/message", [
                'headers' => [
                    'Authorization' => $this->getAuthHeader(),
                    'Content-Type' => 'application/json',
                ],
                'json' => array_merge([
                    'text' => $message,
                    'user_id' => $userId,
                ], $additionalData),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Failed to send message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the API key for frontend use
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the app ID for frontend use
     *
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * Generate authorization header for API requests
     *
     * @return string
     */
    private function getAuthHeader(): string
    {
        return 'Bearer ' . $this->apiSecret;
    }
}