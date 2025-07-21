<?php

namespace App\Services;

use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class FirebaseStorageService
{
    protected $storage;

    public function __construct()
    {
        $this->storage = Firebase::storage();
    }

    public function uploadFile(UploadedFile $uploadedFile, string $folder = 'category-images'): string
    {
        try {
            $bucket = $this->storage->getBucket();
            
            // Debug logging
            Log::info('Firebase upload attempt', [
                'bucket_name' => $bucket->name(),
                'folder' => $folder,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'file_size' => $uploadedFile->getSize(),
            ]);

            $filename = $folder . '/' . uniqid() . '_' . time() . '.' . $uploadedFile->getClientOriginalExtension();

            $object = $bucket->upload(
                file_get_contents($uploadedFile->getRealPath()),
                ['name' => $filename]
            );

            // Make the object publicly accessible
            $object->update(['acl' => [['entity' => 'allUsers', 'role' => 'READER']]]);

            $publicUrl = 'https://storage.googleapis.com/' . $bucket->name() . '/' . $filename;
            
            Log::info('Firebase upload successful', [
                'filename' => $filename,
                'public_url' => $publicUrl,
            ]);

            return $publicUrl;
            
        } catch (\Exception $e) {
            Log::error('Firebase upload failed', [
                'error' => $e->getMessage(),
                'bucket_name' => $bucket->name() ?? 'unknown',
                'file_name' => $uploadedFile->getClientOriginalName(),
            ]);
            
            throw new \Exception('Failed to upload file to Firebase: ' . $e->getMessage());
        }
    }
    
    public function deleteFile(string $fileUrl): bool
    {
        try {
            $bucket = $this->storage->getBucket();
            
            // Extract filename from URL
            $urlParts = parse_url($fileUrl);
            $path = $urlParts['path'] ?? '';
            $filename = str_replace('/' . $bucket->name() . '/', '', $path);
            
            if ($bucket->object($filename)->exists()) {
                $bucket->object($filename)->delete();
                Log::info('Firebase file deleted', ['filename' => $filename]);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete file from Firebase: ' . $e->getMessage());
            return false;
        }
    }
} 