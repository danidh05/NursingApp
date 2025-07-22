<?php

namespace App\Services;

use App\Models\Popup;
use App\Repositories\Interfaces\IPopupRepository;
use App\Services\FirebaseStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;

class PopupService
{
    public function __construct(
        private IPopupRepository $popupRepository,
        private FirebaseStorageService $firebaseStorage
    ) {}

    public function getAllPopups(): Collection
    {
        return $this->popupRepository->getAll();
    }

    public function getPopup(int $id): Popup
    {
        return $this->popupRepository->findById($id);
    }

    public function getActivePopup(): ?Popup
    {
        return $this->popupRepository->getActive();
    }

    public function createPopup(array $data): Popup
    {
        // Handle image upload if present
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $this->firebaseStorage->uploadFile($data['image'], 'popup-images');
        }

        return $this->popupRepository->create($data);
    }

    public function updatePopup(int $id, array $data): Popup
    {
        // Get existing popup to handle image deletion if needed
        $existingPopup = $this->popupRepository->findById($id);
        $oldImageUrl = $existingPopup->image;

        // Handle image upload if present
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            try {
                $data['image'] = $this->firebaseStorage->uploadFile($data['image'], 'popup-images');
                
                // Delete old image only after successful upload
                if ($oldImageUrl) {
                    $deleteResult = $this->firebaseStorage->deleteFile($oldImageUrl);
                    if (!$deleteResult) {
                        \Log::warning('Failed to delete old popup image', ['url' => $oldImageUrl]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to upload new popup image', ['error' => $e->getMessage()]);
                throw new \Exception('Failed to upload image: ' . $e->getMessage());
            }
        }

        return $this->popupRepository->update($id, $data);
    }

    public function deletePopup(int $id): void
    {
        $popup = $this->popupRepository->findById($id);
        
        // Delete from database first
        $this->popupRepository->delete($id);
        
        // Then attempt to delete image from Firebase (non-blocking)
        if ($popup->image) {
            try {
                $this->firebaseStorage->deleteFile($popup->image);
            } catch (\Exception $e) {
                \Log::error('Failed to delete popup image from Firebase after database deletion', [
                    'popup_id' => $id,
                    'image_url' => $popup->image,
                    'error' => $e->getMessage()
                ]);
                // Don't throw exception here - popup is already deleted from DB
            }
        }
    }
} 