<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PopupService;
use App\Repositories\Interfaces\IPopupRepository;
use App\Services\FirebaseStorageService;
use App\Models\Popup;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

class PopupServiceTest extends TestCase
{
    private PopupService $popupService;
    private IPopupRepository $mockRepository;
    private FirebaseStorageService $mockFirebaseStorage;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(IPopupRepository::class);
        $this->mockFirebaseStorage = Mockery::mock(FirebaseStorageService::class);
        
        $this->popupService = new PopupService(
            $this->mockRepository,
            $this->mockFirebaseStorage
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_popups_returns_collection()
    {
        // Arrange
        $expectedPopups = new Collection([
            new Popup(['id' => 1, 'title' => 'Test Popup 1']),
            new Popup(['id' => 2, 'title' => 'Test Popup 2']),
        ]);
        
        $this->mockRepository
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($expectedPopups);

        // Act
        $result = $this->popupService->getAllPopups();

        // Assert
        $this->assertEquals($expectedPopups, $result);
    }

    public function test_get_popup_returns_specific_popup()
    {
        // Arrange
        $popupId = 1;
        $expectedPopup = new Popup(['id' => $popupId, 'title' => 'Test Popup']);
        
        $this->mockRepository
            ->shouldReceive('findById')
            ->with($popupId)
            ->once()
            ->andReturn($expectedPopup);

        // Act
        $result = $this->popupService->getPopup($popupId);

        // Assert
        $this->assertEquals($expectedPopup, $result);
    }

    public function test_get_active_popup_returns_active_popup()
    {
        // Arrange
        $activePopup = new Popup([
            'id' => 1, 
            'title' => 'Active Popup',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay()
        ]);
        
        $this->mockRepository
            ->shouldReceive('getActiveForUser')
            ->once()
            ->with(null)
            ->andReturn($activePopup);

        // Act
        $result = $this->popupService->getActivePopup();

        // Assert
        $this->assertEquals($activePopup, $result);
    }

    public function test_get_active_popup_returns_null_when_no_active_popup()
    {
        // Arrange
        $this->mockRepository
            ->shouldReceive('getActiveForUser')
            ->once()
            ->with(null)
            ->andReturn(null);

        // Act
        $result = $this->popupService->getActivePopup();

        // Assert
        $this->assertNull($result);
    }

    public function test_create_popup_with_image_upload()
    {
        // Arrange
        $mockFile = Mockery::mock(UploadedFile::class);
        $data = [
            'title' => 'New Popup',
            'content' => 'Popup content',
            'type' => 'info',
            'image' => $mockFile
        ];
        
        $firebaseUrl = 'https://firebase.com/popup.jpg';
        $expectedPopup = new Popup([
            'id' => 1, 
            'title' => 'New Popup', 
            'image' => $firebaseUrl
        ]);
        
        $this->mockFirebaseStorage
            ->shouldReceive('uploadFile')
            ->with($mockFile, 'popup-images')
            ->once()
            ->andReturn($firebaseUrl);
            
        $this->mockRepository
            ->shouldReceive('create')
            ->with([
                'title' => 'New Popup',
                'content' => 'Popup content',
                'type' => 'info',
                'image' => $firebaseUrl
            ])
            ->once()
            ->andReturn($expectedPopup);

        // Act
        $result = $this->popupService->createPopup($data);

        // Assert
        $this->assertEquals($expectedPopup, $result);
    }

    public function test_create_popup_without_image()
    {
        // Arrange
        $data = [
            'title' => 'New Popup',
            'content' => 'Popup content',
            'type' => 'warning'
        ];
        
        $expectedPopup = new Popup(['id' => 1, 'title' => 'New Popup']);
        
        $this->mockRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($expectedPopup);

        // Act
        $result = $this->popupService->createPopup($data);

        // Assert
        $this->assertEquals($expectedPopup, $result);
    }

    public function test_update_popup_with_new_image_deletes_old_image()
    {
        // Arrange
        $popupId = 1;
        $mockFile = Mockery::mock(UploadedFile::class);
        $oldImageUrl = 'https://firebase.com/old-popup.jpg';
        $newImageUrl = 'https://firebase.com/new-popup.jpg';
        
        $existingPopup = new Popup(['id' => $popupId, 'image' => $oldImageUrl]);
        $updatedPopup = new Popup(['id' => $popupId, 'image' => $newImageUrl]);
        
        $data = [
            'title' => 'Updated Popup',
            'image' => $mockFile
        ];
        
        $this->mockRepository
            ->shouldReceive('findById')
            ->with($popupId)
            ->once()
            ->andReturn($existingPopup);
            
        $this->mockFirebaseStorage
            ->shouldReceive('deleteFile')
            ->with($oldImageUrl)
            ->once();
            
        $this->mockFirebaseStorage
            ->shouldReceive('uploadFile')
            ->with($mockFile, 'popup-images')
            ->once()
            ->andReturn($newImageUrl);
            
        $this->mockRepository
            ->shouldReceive('update')
            ->with($popupId, ['title' => 'Updated Popup', 'image' => $newImageUrl])
            ->once()
            ->andReturn($updatedPopup);

        // Act
        $result = $this->popupService->updatePopup($popupId, $data);

        // Assert
        $this->assertEquals($updatedPopup, $result);
    }

    public function test_update_popup_without_image_change()
    {
        // Arrange
        $popupId = 1;
        $existingPopup = new Popup(['id' => $popupId, 'title' => 'Original Title']);
        $updatedPopup = new Popup(['id' => $popupId, 'title' => 'Updated Title']);
        
        $data = ['title' => 'Updated Title'];
        
        $this->mockRepository
            ->shouldReceive('findById')
            ->with($popupId)
            ->once()
            ->andReturn($existingPopup);
            
        $this->mockRepository
            ->shouldReceive('update')
            ->with($popupId, $data)
            ->once()
            ->andReturn($updatedPopup);

        // Act
        $result = $this->popupService->updatePopup($popupId, $data);

        // Assert
        $this->assertEquals($updatedPopup, $result);
    }

    public function test_delete_popup_removes_image_from_firebase()
    {
        // Arrange
        $popupId = 1;
        $imageUrl = 'https://firebase.com/popup.jpg';
        $popup = new Popup(['id' => $popupId, 'image' => $imageUrl]);
        
        $this->mockRepository
            ->shouldReceive('findById')
            ->with($popupId)
            ->once()
            ->andReturn($popup);
            
        $this->mockFirebaseStorage
            ->shouldReceive('deleteFile')
            ->with($imageUrl)
            ->once();
            
        $this->mockRepository
            ->shouldReceive('delete')
            ->with($popupId)
            ->once();

        // Act
        $this->popupService->deletePopup($popupId);

        // Assert - No exceptions thrown
        $this->assertTrue(true);
    }

    public function test_delete_popup_without_image()
    {
        // Arrange
        $popupId = 1;
        $popup = new Popup(['id' => $popupId, 'image' => null]);
        
        $this->mockRepository
            ->shouldReceive('findById')
            ->with($popupId)
            ->once()
            ->andReturn($popup);
            
        $this->mockRepository
            ->shouldReceive('delete')
            ->with($popupId)
            ->once();

        // Act
        $this->popupService->deletePopup($popupId);

        // Assert - No exceptions thrown
        $this->assertTrue(true);
    }
}
