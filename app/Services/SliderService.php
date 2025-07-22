<?php

namespace App\Services;

use App\Models\Slider;
use App\Repositories\Interfaces\ISliderRepository;
use App\Services\FirebaseStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;

class SliderService
{
    public function __construct(
        private ISliderRepository $sliderRepository,
        private FirebaseStorageService $firebaseStorage
    ) {}

    public function getAllSliders(): Collection
    {
        return $this->sliderRepository->getAllOrdered();
    }

    public function getSlider(int $id): Slider
    {
        return $this->sliderRepository->findById($id);
    }

    public function createSlider(array $data): Slider
    {
        // Handle image upload if present
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $this->firebaseStorage->uploadFile($data['image'], 'slider-images');
        }

        return $this->sliderRepository->create($data);
    }

    public function updateSlider(int $id, array $data): Slider
    {
        // Get existing slider to handle image deletion if needed
        $existingSlider = $this->sliderRepository->findById($id);
        $oldImageUrl = $existingSlider->image;

        // Handle image upload if present
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            try {
                $data['image'] = $this->firebaseStorage->uploadFile($data['image'], 'slider-images');
                
                // Delete old image only after successful upload
                if ($oldImageUrl) {
                    $deleteResult = $this->firebaseStorage->deleteFile($oldImageUrl);
                    if (!$deleteResult) {
                        \Log::warning('Failed to delete old slider image', ['url' => $oldImageUrl]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to upload new slider image', ['error' => $e->getMessage()]);
                throw new \Exception('Failed to upload image: ' . $e->getMessage());
            }
        }

        return $this->sliderRepository->update($id, $data);
    }

    public function deleteSlider(int $id): void
    {
        $slider = $this->sliderRepository->findById($id);
        
        // Delete from database first
        $this->sliderRepository->delete($id);
        
        // Then attempt to delete image from Firebase (non-blocking)
        if ($slider->image) {
            try {
                $this->firebaseStorage->deleteFile($slider->image);
            } catch (\Exception $e) {
                \Log::error('Failed to delete slider image from Firebase after database deletion', [
                    'slider_id' => $id,
                    'image_url' => $slider->image,
                    'error' => $e->getMessage()
                ]);
                // Don't throw exception here - slider is already deleted from DB
            }
        }
    }
} 