<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Popup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class BirthdayPopupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }



    /** @test */
    public function user_sees_global_popup_when_no_user_specific_popup_exists()
    {
        // Arrange
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user);

        // Create a global popup
        $globalPopup = Popup::factory()->create([
            'title' => 'Global Announcement',
            'content' => 'Welcome to our app!',
            'type' => Popup::TYPE_INFO,
            'is_active' => true,
            'user_id' => null,
            'start_date' => null,
            'end_date' => null,
        ]);

        // Act
        $response = $this->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => [
                    'id' => $globalPopup->id,
                    'title' => 'Global Announcement',
                    'content' => 'Welcome to our app!',
                    'type' => Popup::TYPE_INFO,
                    'user_id' => null,
                ]
            ]);
    }

    /** @test */
    public function user_sees_user_specific_popup_when_available()
    {
        // Arrange
        $user = User::factory()->create(['role_id' => 2]);
        $otherUser = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user);

        // Create a global popup
        $globalPopup = Popup::factory()->create([
            'title' => 'Global Announcement',
            'type' => Popup::TYPE_INFO,
            'is_active' => true,
            'user_id' => null,
        ]);

        // Create user-specific popup for the authenticated user
        $userPopup = Popup::factory()->create([
            'title' => '🎉 Happy Birthday!',
            'content' => "Happy Birthday {$user->name}!",
            'type' => Popup::TYPE_BIRTHDAY,
            'is_active' => true,
            'user_id' => $user->id,
            'start_date' => now()->startOfDay(),
            'end_date' => now()->endOfDay(),
        ]);

        // Create user-specific popup for another user
        $otherUserPopup = Popup::factory()->create([
            'title' => 'Other User Birthday',
            'type' => Popup::TYPE_BIRTHDAY,
            'is_active' => true,
            'user_id' => $otherUser->id,
        ]);

        // Act
        $response = $this->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => [
                    'id' => $userPopup->id,
                    'title' => '🎉 Happy Birthday!',
                    'type' => Popup::TYPE_BIRTHDAY,
                    'user_id' => $user->id,
                ]
            ]);
    }

    /** @test */
    public function birthday_popup_is_only_shown_on_correct_date()
    {
        // Arrange
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user);

        // Create birthday popup that should be active today
        $activeBirthdayPopup = Popup::factory()->create([
            'title' => '🎉 Happy Birthday!',
            'type' => Popup::TYPE_BIRTHDAY,
            'is_active' => true,
            'user_id' => $user->id,
            'start_date' => now()->startOfDay(),
            'end_date' => now()->endOfDay(),
        ]);

        // Create birthday popup from yesterday (should be expired)
        $expiredBirthdayPopup = Popup::factory()->create([
            'title' => 'Yesterday Birthday',
            'type' => Popup::TYPE_BIRTHDAY,
            'is_active' => true,
            'user_id' => $user->id,
            'start_date' => now()->subDay()->startOfDay(),
            'end_date' => now()->subDay()->endOfDay(),
        ]);

        // Act
        $response = $this->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => [
                    'id' => $activeBirthdayPopup->id,
                    'title' => '🎉 Happy Birthday!',
                ]
            ]);
    }

    /** @test */
    public function inactive_birthday_popup_is_not_shown()
    {
        // Arrange
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user);

        // Create inactive birthday popup
        $inactivePopup = Popup::factory()->create([
            'title' => 'Inactive Birthday',
            'type' => Popup::TYPE_BIRTHDAY,
            'is_active' => false,
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => null,
                'message' => 'No active popup available'
            ]);
    }

    /** @test */
    public function user_without_authentication_cannot_access_popups()
    {
        // Act
        $response = $this->getJson('/api/popups');

        // Assert
        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function popup_scheduling_works_correctly()
    {
        // Arrange
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user);

        // Create popup scheduled for tomorrow
        $futurePopup = Popup::factory()->create([
            'title' => 'Future Popup',
            'type' => Popup::TYPE_INFO,
            'is_active' => true,
            'user_id' => $user->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
        ]);

        // Act
        $response = $this->getJson('/api/popups');

        // Assert - Should not show future popup
        $response->assertStatus(200)
            ->assertJson([
                'popup' => null,
                'message' => 'No active popup available'
            ]);
    }

    /** @test */
    public function expired_popup_is_not_shown()
    {
        // Arrange
        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user);

        // Create expired popup
        $expiredPopup = Popup::factory()->create([
            'title' => 'Expired Popup',
            'type' => Popup::TYPE_INFO,
            'is_active' => true,
            'user_id' => $user->id,
            'start_date' => now()->subDays(2),
            'end_date' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => null,
                'message' => 'No active popup available'
            ]);
    }
} 