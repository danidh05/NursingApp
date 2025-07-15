<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Nurse;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class NurseRatingTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();
        
        // Seed roles before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    #[Test]
    public function user_can_rate_a_nurse()
    {
        $user = User::factory()->create();
        $nurse = Nurse::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/nurses/' . $nurse->id . '/rate', [
            'rating' => 5,
            'comment' => 'Great service!',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Rating submitted successfully.',
                 ]);

        $this->assertDatabaseHas('ratings', [
            'nurse_id' => $nurse->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);
    }

    #[Test]
    public function user_cannot_rate_a_nurse_twice()
    {
        $user = User::factory()->create();
        $nurse = Nurse::factory()->create();

        // First rating
        Rating::factory()->create(['nurse_id' => $nurse->id, 'user_id' => $user->id, 'rating' => 5]);

        Sanctum::actingAs($user);

        // Attempt to rate again
        $response = $this->postJson('/api/nurses/' . $nurse->id . '/rate', [
            'rating' => 4,
            'comment' => 'Trying to rate again',
        ]);

        $response->assertStatus(422) // Changed from 403 to 422 to reflect validation error for duplicate rating
                 ->assertJson([
                     'message' => 'You have already rated this nurse.',
                 ]);
    }
}