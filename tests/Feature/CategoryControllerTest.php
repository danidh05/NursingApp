<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles for testing
        $this->seed(RoleSeeder::class);
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
                 ->assertJsonStructure(['categories' => [['id', 'name']]]);
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
                 ->assertJsonStructure(['categories' => [['id', 'name']]]);
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
                 ->assertJson(['message' => 'Category created successfully.']);

        $this->assertDatabaseHas('categories', ['name' => 'New Category']);
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
}