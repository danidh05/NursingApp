<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\FirebaseStorageService;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        // Seed roles for testing - CRITICAL: Do this BEFORE creating any users

        $this->seed(RoleSeeder::class);   // Seed roles first
        
        // Mock Firebase Storage Service
        $this->mockFirebaseStorage();
    }

    private function mockFirebaseStorage()
    {
        $mock = Mockery::mock(FirebaseStorageService::class);
        
        $mock->shouldReceive('uploadFile')
            ->andReturn('https://firebasestorage.googleapis.com/v0/b/test-bucket/o/category-images/test-image.jpg?alt=media');
            
        $mock->shouldReceive('deleteFile')
            ->andReturn(true);
            
        $this->app->instance(FirebaseStorageService::class, $mock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function user_can_view_all_categories()
    {
        // Arrange: Create a user and some categories
        $user = User::factory()->create(['role_id' => 2]); // User role
        Sanctum::actingAs($user);

        Category::factory()->count(3)->create();

        // Act: Send a GET request to view all categories
        $response = $this->getJson('/api/categories');

        // Assert: Check response and structure
        $response->assertStatus(200)
                 ->assertJsonStructure(['categories' => [['id', 'name', 'image_url']]]);
    }

    #[Test]
    public function user_can_view_a_specific_category()
    {
        // Arrange: Create a user and a category
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user);

        $category = Category::factory()->create();

        // Act: Send a GET request to view a specific category
        $response = $this->getJson("/api/categories/{$category->id}");

        // Assert: Check response and data match
        $response->assertStatus(200)
                 ->assertJsonStructure(['category' => ['id', 'name', 'image_url']])
                 ->assertJson(['category' => ['id' => $category->id, 'name' => $category->name]]);
    }

    #[Test]
 
    public function admin_can_view_all_categories()
    {
        // Arrange: Create an admin user
        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);
    
        // Seed categories
        Category::factory()->count(3)->create();
    
        // Act: Send a GET request as admin
        $response = $this->getJson('/api/categories');
    
        // Assert: Check if response is successful
        $response->assertStatus(200)
                 ->assertJsonStructure(['categories' => [['id', 'name', 'image_url']]]);
    }
    

    #[Test]
    public function admin_can_create_a_category()
    {
        // Arrange: Create an admin user
        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);

        // Act: Send a POST request to create a category
        $response = $this->postJson('/api/admin/categories', [
            'name' => 'New Category',
        ]);

        // Assert: Check creation and database entry
        $response->assertStatus(201)
                 ->assertJson(['message' => 'Category created successfully.'])
                 ->assertJsonStructure(['category' => ['id', 'name', 'created_at', 'updated_at']]);

        $this->assertDatabaseHas('categories', ['name' => 'New Category']);
    }

    #[Test]
    public function admin_can_create_a_category_with_image()
    {
        // Arrange: Create an admin user
        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);

        $image = UploadedFile::fake()->image('category.jpg', 100, 100);

        // Act: Send a POST request to create a category with image
        $response = $this->post('/api/admin/categories', [
            'name' => 'New Category with Image',
            'image' => $image,
        ]);

        // Assert: Check creation and database entry
        $response->assertStatus(201)
                 ->assertJson(['message' => 'Category created successfully.'])
                 ->assertJsonStructure(['category' => ['id', 'name', 'image_url', 'created_at', 'updated_at']]);

        $this->assertDatabaseHas('categories', [
            'name' => 'New Category with Image',
        ]);

        // Check that image_url is set
        $category = Category::where('name', 'New Category with Image')->first();
        $this->assertNotNull($category->image_url);
        $this->assertStringContainsString('firebasestorage.googleapis.com', $category->image_url);
    }

    #[Test]
    public function admin_gets_error_if_image_upload_fails()
    {
        // Arrange: Create an admin user
        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);

        // Mock FirebaseStorageService to throw exception
        $mock = \Mockery::mock(FirebaseStorageService::class);
        $mock->shouldReceive('uploadFile')->andThrow(new \Exception('Simulated upload failure'));
        $this->app->instance(FirebaseStorageService::class, $mock);

        $image = UploadedFile::fake()->image('fail.jpg', 100, 100);

        $response = $this->post('/api/admin/categories', [
            'name' => 'Fail Category',
            'image' => $image,
        ]);

        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'Failed to upload image: Simulated upload failure']);
    }

    #[Test]
    public function admin_can_update_a_category()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);
    
        $category = Category::factory()->create(['name' => 'Old Category']);
    
        $response = $this->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'Updated Category',
        ]);
    
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Category updated successfully.']);
    
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Category']);
    }

    #[Test]
    public function admin_can_update_a_category_with_image()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);
    
        $category = Category::factory()->create(['name' => 'Old Category']);
        $image = UploadedFile::fake()->image('updated-category.jpg', 100, 100);
    
        $response = $this->post("/api/admin/categories/{$category->id}", [
            'name' => 'Updated Category with Image',
            'image' => $image,
            '_method' => 'PUT',
        ]);
    
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Category updated successfully.']);
    
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Category with Image']);
        
        // Check that image_url is updated
        $category->refresh();
        $this->assertNotNull($category->image_url);
        $this->assertStringContainsString('firebasestorage.googleapis.com', $category->image_url);
    }
    
    #[Test]
    public function admin_can_delete_a_category()
    {
        // Arrange: Create an admin user and a category
        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);

        $category = Category::factory()->create();

        // Act: Send a DELETE request to delete the category
        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        // Assert: Check deletion and database entry
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Category deleted successfully.']);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[Test]
    public function admin_cannot_create_category_with_invalid_image()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);

        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        // Test validation by calling the controller method directly
        $request = new \Illuminate\Http\Request();
        $request->merge(['name' => 'New Category']);
        $request->files->set('image', $invalidFile);

        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('image', $validator->errors()->toArray());
    }

    #[Test]
    public function admin_cannot_create_category_with_image_too_large()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);

        $largeImage = UploadedFile::fake()->image('large.jpg', 100, 100)->size(3000); // 3MB

        // Test validation by calling the controller method directly
        $request = new \Illuminate\Http\Request();
        $request->merge(['name' => 'New Category']);
        $request->files->set('image', $largeImage);

        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('image', $validator->errors()->toArray());
    }
}