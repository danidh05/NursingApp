<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed the roles before running tests
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        //Queue::fake(); // Bypass Redis during tests
        $this->app->bind(\App\Services\FirebaseStorageService::class, function () {
            return new class extends \App\Services\FirebaseStorageService {
                public function __construct() {}
                public function uploadFile(\Illuminate\Http\UploadedFile $uploadedFile, string $folder = 'test-images'): string
                {
                    return 'https://example.test/'.$folder.'/'.uniqid().'.jpg';
                }
                public function deleteFile(string $fileUrl): bool
                {
                    return true;
                }
                public function signV4GetUrl(string $objectName, int $ttlSeconds): string
                {
                    return 'https://signed.example.test/'.$objectName.'?expires='.($ttlSeconds);
                }
                public function signV4PutUrl(string $objectName, string $contentType, int $ttlSeconds): array
                {
                    return [
                        'url' => 'https://signed-put.example.test/'.$objectName.'?expires='.($ttlSeconds),
                        'headers' => ['Content-Type' => $contentType]
                    ];
                }
                public function deleteByPrefix(string $prefix): int
                {
                    return 0;
                }
            };
        });
    }
}