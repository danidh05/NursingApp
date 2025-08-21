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
        $this->storage = Firebase::storage(); // exposes Google\Cloud\Storage\Bucket underneath
    }

    // ------------------------
    // EXISTING PUBLIC UPLOADS
    // ------------------------
    public function uploadFile(UploadedFile $uploadedFile, string $folder = 'category-images'): string
    {
        $bucket = $this->storage->getBucket();

        $filename = $folder.'/'.uniqid().'_'.time().'.'.$uploadedFile->getClientOriginalExtension();

        $object = $bucket->upload(
            file_get_contents($uploadedFile->getRealPath()),
            ['name' => $filename]
        );

        // Keep legacy behavior: public
        $object->update(['acl' => [['entity' => 'allUsers', 'role' => 'READER']]]);

        return 'https://storage.googleapis.com/'.$bucket->name().'/'.$filename;
    }

    public function deleteFile(string $fileUrl): bool
    {
        $bucket = $this->storage->getBucket();
        $objectName = $this->objectNameFromUrl($fileUrl, $bucket->name());

        if ($objectName && $bucket->object($objectName)->exists()) {
            $bucket->object($objectName)->delete();
            return true;
        }
        return false;
    }

    // ------------------------
    // NEW: CHAT-SPECIFIC APIS
    // ------------------------

    /**
     * Return a signed V4 GET URL for a private object.
     */
    public function signV4GetUrl(string $objectName, int $ttlSeconds): string
    {
        $bucket = $this->storage->getBucket();
        $object = $bucket->object($objectName);
        return $object->signedUrl(now()->addSeconds($ttlSeconds), [
            'version' => 'v4',
            'method'  => 'GET',
        ]);
        // Note: do NOT make object public for chat.
    }

    /**
     * Optionally: signed PUT if your client uploads directly to GCS/Firebase.
     */
    public function signV4PutUrl(string $objectName, string $contentType, int $ttlSeconds): array
    {
        $bucket = $this->storage->getBucket();
        $object = $bucket->object($objectName);
        $url = $object->signedUrl(now()->addSeconds($ttlSeconds), [
            'version'     => 'v4',
            'method'      => 'PUT',
            'contentType' => $contentType,
        ]);
        return ['url' => $url, 'headers' => ['Content-Type' => $contentType]];
    }

    /**
     * Delete all objects with a given prefix (e.g., "chats/{threadId}/").
     */
    public function deleteByPrefix(string $prefix): int
    {
        $bucket = $this->storage->getBucket();
        $deleted = 0;
        foreach ($bucket->objects(['prefix' => $prefix]) as $object) {
            $object->delete();
            $deleted++;
        }
        return $deleted;
    }

    /**
     * Convert a public URL to an object name (path inside bucket).
     * Safe for legacy rows where URL was stored instead of object name.
     */
    public function objectNameFromUrl(string $url, ?string $bucketName = null): ?string
    {
        $parts = parse_url($url);
        if (!isset($parts['path'])) return null;

        $path = ltrim($parts['path'], '/');
        if ($bucketName && str_starts_with($path, $bucketName.'/')) {
            return substr($path, strlen($bucketName) + 1); // strip "bucket/"
        }
        // If URL was already like storage.googleapis.com/<bucket>/<object>
        // or if a CDN is used, you may need additional normalization here.
        return $path;
    }

    /**
     * Build a public URL (used only for legacy/public assets, not for chat).
     */
    public function publicUrlFromObjectName(string $objectName): string
    {
        $bucket = $this->storage->getBucket();
        return 'https://storage.googleapis.com/'.$bucket->name().'/'.$objectName;
    }
}