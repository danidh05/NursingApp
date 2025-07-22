<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SliderService;
use App\Repositories\Interfaces\ISliderRepository;
use App\Services\FirebaseStorageService;
use App\Models\Slider;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

class SliderServiceTest extends TestCase
{
    private SliderService $sliderService;
    private ISliderRepository $mockRepository;
    private FirebaseStorageService $mockFirebaseStorage;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(ISliderRepository::class);
        $this->mockFirebaseStorage = Mockery::mock(FirebaseStorageService::class);
        
        $this->sliderService = new SliderService(
            $this->mockRepository,
            $this->mockFirebaseStorage
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_sliders_returns_ordered_collection()
    {
        // Arrange
        $expectedSliders = new Collection([
            new Slider(['id' => 1, 'position' => 1]),
            new Slider(['id' => 2, 'position' => 2]),
        ]);
        
        $this->mockRepository
            ->shouldReceive('getAllOrdered')
            ->once()
            ->andReturn($expectedSliders);

        // Act
        $result = $this->sliderService->getAllSliders();

        // Assert
        $this->assertEquals($expectedSliders, $result);
    }

    public function test_get_slider_returns_specific_slider()
    {
        // Arrange
        $sliderId = 1;
        $expectedSlider = new Slider(['id' => $sliderId, 'title' => 'Test Slider']);
        
        $this->mockRepository
            ->shouldReceive('findById')
            ->with($sliderId)
            ->once()
            ->andReturn($expectedSlider);

        // Act
        $result = $this->sliderService->getSlider($sliderId);

        // Assert
        $this->assertEquals($expectedSlider, $result);
    }

    public function test_create_slider_with_image_upload()
    {
        // Arrange
        $mockFile = Mockery::mock(UploadedFile::class);
        $data = [
            'title' => 'New Slider',
            'position' => 1,
            'image' => $mockFile
        ];
        
        $firebaseUrl = 'https://firebase.com/slider.jpg';
        $expectedSlider = new Slider(['id' => 1, 'title' => 'New Slider', 'image' => $firebaseUrl]);
        
        $this->mockFirebaseStorage
            ->shouldReceive('uploadFile')
            ->with($mockFile, 'slider-images')
            ->once()
            ->andReturn($firebaseUrl);
            
        $this->mockRepository
            ->shouldReceive('create')
            ->with(['title' => 'New Slider', 'position' => 1, 'image' => $firebaseUrl])
            ->once()
            ->andReturn($expectedSlider);

        // Act
        $result = $this->sliderService->createSlider($data);

        // Assert
        $this->assertEquals($expectedSlider, $result);
    }

    public function test_create_slider_without_image()
    {
        // Arrange
        $data = [
            'title' => 'New Slider',
            'position' => 1
        ];
        
        $expectedSlider = new Slider(['id' => 1, 'title' => 'New Slider']);
        
        $this->mockRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($expectedSlider);

        // Act
        $result = $this->sliderService->createSlider($data);

        // Assert
        $this->assertEquals($expectedSlider, $result);
    }

    public function test_update_slider_with_new_image_deletes_old_image()
    {
        // Arrange
        $sliderId = 1;
        $mockFile = Mockery::mock(UploadedFile::class);
        $oldImageUrl = 'https://firebase.com/old-slider.jpg';
        $newImageUrl = 'https://firebase.com/new-slider.jpg';
        
        $existingSlider = new Slider(['id' => $sliderId, 'image' => $oldImageUrl]);
        $updatedSlider = new Slider(['id' => $sliderId, 'image' => $newImageUrl]);
        
        $data = [
            'title' => 'Updated Slider',
            'image' => $mockFile
        ];
        
        $this->mockRepository
            ->shouldReceive('findById')
            ->with($sliderId)
            ->once()
            ->andReturn($existingSlider);
            
        $this->mockFirebaseStorage
            ->shouldReceive('deleteFile')
            ->with($oldImageUrl)
            ->once();
            
        $this->mockFirebaseStorage
            ->shouldReceive('uploadFile')
            ->with($mockFile, 'slider-images')
            ->once()
            ->andReturn($newImageUrl);
            
        $this->mockRepository
            ->shouldReceive('update')
            ->with($sliderId, ['title' => 'Updated Slider', 'image' => $newImageUrl])
            ->once()
            ->andReturn($updatedSlider);

        // Act
        $result = $this->sliderService->updateSlider($sliderId, $data);

        // Assert
        $this->assertEquals($updatedSlider, $result);
    }

    public function test_delete_slider_removes_image_from_firebase()
    {
        // Arrange
        $sliderId = 1;
        $imageUrl = 'https://firebase.com/slider.jpg';
        $slider = new Slider(['id' => $sliderId, 'image' => $imageUrl]);
        
        $this->mockRepository
            ->shouldReceive('findById')
            ->with($sliderId)
            ->once()
            ->andReturn($slider);
            
        $this->mockFirebaseStorage
            ->shouldReceive('deleteFile')
            ->with($imageUrl)
            ->once();
            
        $this->mockRepository
            ->shouldReceive('delete')
            ->with($sliderId)
            ->once();

        // Act
        $this->sliderService->deleteSlider($sliderId);

        // Assert - No exceptions thrown
        $this->assertTrue(true);
    }

    public function test_delete_slider_without_image()
    {
        // Arrange
        $sliderId = 1;
        $slider = new Slider(['id' => $sliderId, 'image' => null]);
        
        $this->mockRepository
            ->shouldReceive('findById')
            ->with($sliderId)
            ->once()
            ->andReturn($slider);
            
        $this->mockRepository
            ->shouldReceive('delete')
            ->with($sliderId)
            ->once();

        // Act
        $this->sliderService->deleteSlider($sliderId);

        // Assert - No exceptions thrown
        $this->assertTrue(true);
    }
}