<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageStorageService
{
    /**
     * Upload an image file to Laravel Storage (public disk).
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string Path to the stored file (relative to storage/app/public)
     */
    public function uploadImage(UploadedFile $file, string $folder = 'images'): string
    {
        // Validate it's an image
        if (!$file->isValid() || !str_starts_with($file->getMimeType(), 'image/')) {
            throw new \InvalidArgumentException('File must be a valid image');
        }

        // Generate unique filename
        $filename = Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Store in public disk
        $path = $file->storeAs($folder, $filename, 'public');

        return $path;
    }

    /**
     * Get the full URL for a stored image.
     *
     * @param string $path Path relative to storage/app/public
     * @return string Full URL
     */
    public function getImageUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Delete an image from storage.
     *
     * @param string $path Path relative to storage/app/public
     * @return bool
     */
    public function deleteImage(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Update an image (delete old, upload new).
     *
     * @param UploadedFile $file
     * @param string|null $oldPath
     * @param string $folder
     * @return string New path
     */
    public function updateImage(UploadedFile $file, ?string $oldPath = null, string $folder = 'images'): string
    {
        // Delete old image if exists
        if ($oldPath) {
            $this->deleteImage($oldPath);
        }

        // Upload new image
        return $this->uploadImage($file, $folder);
    }
}

