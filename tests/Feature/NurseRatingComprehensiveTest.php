<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Nurse;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class NurseRatingComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private Nurse $nurse;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles for testing
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create users and nurse
        $this->user = User::factory()->create(['role_id' => 2]);
        $this->user->load('role');
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->admin->load('role');
        $this->nurse = Nurse::factory()->create();
    }

    public function test_user_can_rate_nurse(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 5,
            'comment' => 'Excellent service!'
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Rating submitted successfully.'])
                 ->assertJsonStructure(['message', 'rating']);

        // Verify rating was saved in database
        $this->assertDatabaseHas('ratings', [
            'user_id' => $this->user->id,
            'nurse_id' => $this->nurse->id,
            'rating' => 5,
            'comment' => 'Excellent service!'
        ]);
    }

    public function test_user_cannot_rate_nurse_twice(): void
    {
        Sanctum::actingAs($this->user);

        // First rating
        $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 5,
            'comment' => 'First rating'
        ]);

        // Second rating attempt
        $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 4,
            'comment' => 'Second rating'
        ]);

        $response->assertStatus(422)
                 ->assertJson(['message' => 'You have already rated this nurse.']);

        // Verify only one rating exists
        $this->assertDatabaseCount('ratings', 1);
    }

    public function test_rating_validation_works(): void
    {
        Sanctum::actingAs($this->user);

        // Test missing rating
        $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'comment' => 'No rating provided'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['rating']);

        // Test rating too low
        $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 0,
            'comment' => 'Rating too low'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['rating']);

        // Test rating too high
        $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 6,
            'comment' => 'Rating too high'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['rating']);

        // Test non-integer rating
        $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 3.5,
            'comment' => 'Decimal rating'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['rating']);
    }

    public function test_nurse_average_rating_calculation(): void
    {
        // Create multiple users to rate the nurse
        $users = User::factory(5)->create(['role_id' => 2]);
        
        $ratings = [5, 4, 3, 5, 4]; // Average should be 4.2
        
        foreach ($users as $index => $user) {
            Sanctum::actingAs($user);
            
            $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
                'rating' => $ratings[$index],
                'comment' => "Rating {$ratings[$index]}"
            ]);
        }

        // Get nurse with ratings
        $response = $this->getJson("/api/nurses/{$this->nurse->id}");
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'nurse' => [
                         'id', 'name', 'phone_number', 'address', 'profile_picture', 'gender',
                         'ratings' => [
                             '*' => ['id', 'user_id', 'nurse_id', 'rating', 'comment', 'created_at']
                         ]
                     ],
                     'average_rating'
                 ]);

        // Verify average rating calculation
        $averageRating = array_sum($ratings) / count($ratings);
        $response->assertJson(['average_rating' => $averageRating]);
    }

    public function test_rating_accepts_valid_values(): void
    {
        Sanctum::actingAs($this->user);

        $validRatings = [1, 2, 3, 4, 5];

        foreach ($validRatings as $rating) {
            // Create a new nurse for each rating to avoid duplicate rating error
            $nurse = Nurse::factory()->create();
            
            $response = $this->postJson("/api/nurses/{$nurse->id}/rate", [
                'rating' => $rating,
                'comment' => "Rating {$rating}"
            ]);

            $response->assertStatus(201);
        }
    }

    public function test_rating_comment_is_optional(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 5
            // No comment provided
        ]);

        $response->assertStatus(201);

        // Verify rating was saved without comment
        $this->assertDatabaseHas('ratings', [
            'user_id' => $this->user->id,
            'nurse_id' => $this->nurse->id,
            'rating' => 5,
            'comment' => null
        ]);
    }

    public function test_unauthorized_user_cannot_rate_nurse(): void
    {
        $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 5,
            'comment' => 'Unauthorized rating'
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_cannot_rate_nurse(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 5,
            'comment' => 'Admin rating'
        ]);

        // This should fail because the route is protected by 'role:user' middleware
        $response->assertStatus(403);
    }

    public function test_rating_nonexistent_nurse_fails(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/nurses/99999/rate", [
            'rating' => 5,
            'comment' => 'Rating nonexistent nurse'
        ]);

        $response->assertStatus(404);
    }

    public function test_rating_creates_proper_relationships(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 5,
            'comment' => 'Test rating'
        ]);

        // Verify the rating relationship
        $rating = Rating::where('user_id', $this->user->id)
                       ->where('nurse_id', $this->nurse->id)
                       ->first();

        $this->assertNotNull($rating);
        $this->assertEquals($this->user->id, $rating->user_id);
        $this->assertEquals($this->nurse->id, $rating->nurse_id);
        $this->assertEquals(5, $rating->rating);
        $this->assertEquals('Test rating', $rating->comment);
    }

    public function test_multiple_users_can_rate_same_nurse(): void
    {
        $users = User::factory(3)->create(['role_id' => 2]);

        foreach ($users as $user) {
            Sanctum::actingAs($user);
            
            $response = $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
                'rating' => rand(1, 5),
                'comment' => "Rating from user {$user->id}"
            ]);

            $response->assertStatus(201);
        }

        // Verify all ratings were created
        $this->assertDatabaseCount('ratings', 3);
        
        // Verify each user has one rating for this nurse
        foreach ($users as $user) {
            $this->assertDatabaseHas('ratings', [
                'user_id' => $user->id,
                'nurse_id' => $this->nurse->id
            ]);
        }
    }

    public function test_rating_timestamps_are_set(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/nurses/{$this->nurse->id}/rate", [
            'rating' => 5,
            'comment' => 'Test rating'
        ]);

        $rating = Rating::where('user_id', $this->user->id)
                       ->where('nurse_id', $this->nurse->id)
                       ->first();

        $this->assertNotNull($rating->created_at);
        $this->assertNotNull($rating->updated_at);
    }
} 