<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Popup;
use App\Services\BirthdayService;
use App\Services\PopupService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;

class BirthdayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    protected function tearDown(): void
    {
        // Clean up any service container bindings we made
        if ($this->app->resolved(NotificationService::class)) {
            $this->app->forgetInstance(NotificationService::class);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function complete_birthday_flow_works_end_to_end()
    {
        // Arrange: Create user with birthday today
        $birthdayUser = User::factory()->create([
            'name' => 'Birthday User',
            'email' => 'birthday@example.com',
            'birth_date' => now()->subYears(25)->format('Y-m-d'),
            'role_id' => 2
        ]);

        // Mock NotificationService to avoid actual notification sending
        $mockNotificationService = Mockery::mock(NotificationService::class);
        $mockNotificationService->shouldReceive('createNotification')
            ->once()
            ->with(
                Mockery::type(User::class),
                'Happy Birthday! 🎉',
                Mockery::type('string'),
                'birthday'
            );

        $this->app->instance(NotificationService::class, $mockNotificationService);

        // Act: Process birthday celebrations
        $birthdayService = app(BirthdayService::class);
        $birthdayService->processBirthdayCelebrations();

        // Assert: Check that birthday popup was created
        $this->assertDatabaseHas('popups', [
            'title' => '🎉 Happy Birthday!',
            'type' => Popup::TYPE_BIRTHDAY,
            'user_id' => $birthdayUser->id,
            'is_active' => true,
        ]);

        // Assert: User can see their birthday popup via API
        Sanctum::actingAs($birthdayUser);
        $response = $this->getJson('/api/popups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'popup' => [
                    'id',
                    'title',
                    'content',
                    'type',
                    'user_id',
                    'is_active'
                ]
            ])
            ->assertJsonPath('popup.title', '🎉 Happy Birthday!')
            ->assertJsonPath('popup.type', Popup::TYPE_BIRTHDAY)
            ->assertJsonPath('popup.user_id', $birthdayUser->id);
    }

    /** @test */
    public function multiple_users_with_birthdays_get_individual_popups()
    {
        // Arrange: Create multiple users with birthdays today
        $user1 = User::factory()->create([
            'name' => 'User One',
            'birth_date' => now()->subYears(25)->format('Y-m-d'),
            'role_id' => 2
        ]);

        $user2 = User::factory()->create([
            'name' => 'User Two',
            'birth_date' => now()->subYears(30)->format('Y-m-d'),
            'role_id' => 2
        ]);

        // Mock NotificationService
        $mockNotificationService = Mockery::mock(NotificationService::class);
        $mockNotificationService->shouldReceive('createNotification')->twice();
        $this->app->instance(NotificationService::class, $mockNotificationService);

        // Act: Process birthday celebrations
        $birthdayService = app(BirthdayService::class);
        $birthdayService->processBirthdayCelebrations();

        // Assert: Both users have birthday popups
        $this->assertDatabaseHas('popups', [
            'title' => '🎉 Happy Birthday!',
            'type' => Popup::TYPE_BIRTHDAY,
            'user_id' => $user1->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('popups', [
            'title' => '🎉 Happy Birthday!',
            'type' => Popup::TYPE_BIRTHDAY,
            'user_id' => $user2->id,
            'is_active' => true,
        ]);

        // Assert: Each user sees their own popup
        Sanctum::actingAs($user1);
        $response1 = $this->getJson('/api/popups');
        $response1->assertJsonPath('popup.user_id', $user1->id);

        Sanctum::actingAs($user2);
        $response2 = $this->getJson('/api/popups');
        $response2->assertJsonPath('popup.user_id', $user2->id);
    }

    /** @test */
    public function user_without_birthday_today_does_not_get_birthday_popup()
    {
        // Arrange: Create user without birthday today
        $regularUser = User::factory()->create([
            'birth_date' => now()->addDays(30)->format('Y-m-d'),
            'role_id' => 2
        ]);

        // Create global popup
        $globalPopup = Popup::factory()->create([
            'title' => 'Global Announcement',
            'type' => Popup::TYPE_INFO,
            'is_active' => true,
            'user_id' => null,
        ]);

        // Mock NotificationService (should not be called)
        $mockNotificationService = Mockery::mock(NotificationService::class);
        $mockNotificationService->shouldNotReceive('createNotification');
        $this->app->instance(NotificationService::class, $mockNotificationService);

        // Act: Process birthday celebrations
        $birthdayService = app(BirthdayService::class);
        $birthdayService->processBirthdayCelebrations();

        // Assert: No birthday popup created for this user
        $this->assertDatabaseMissing('popups', [
            'type' => Popup::TYPE_BIRTHDAY,
            'user_id' => $regularUser->id,
        ]);

        // Assert: User sees global popup instead
        Sanctum::actingAs($regularUser);
        $response = $this->getJson('/api/popups');
        $response->assertJsonPath('popup.title', 'Global Announcement')
            ->assertJsonPath('popup.user_id', null);
    }

    /** @test */
    public function birthday_popup_expires_correctly()
    {
        // Arrange: Create user and birthday popup from yesterday
        $user = User::factory()->create(['role_id' => 2]);
        
        $expiredBirthdayPopup = Popup::factory()->create([
            'title' => '🎉 Happy Birthday!',
            'type' => Popup::TYPE_BIRTHDAY,
            'user_id' => $user->id,
            'is_active' => true,
            'start_date' => now()->subDay()->startOfDay(),
            'end_date' => now()->subDay()->endOfDay(),
        ]);

        // Act & Assert: User should not see expired birthday popup
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/popups');
        
        $response->assertStatus(200)
            ->assertJson([
                'popup' => null,
                'message' => 'No active popup available'
            ]);
    }

    /** @test */
    public function birthday_service_handles_users_without_birth_date()
    {
        // Arrange: Create user without birth_date
        $userWithoutBirthDate = User::factory()->create([
            'birth_date' => null,
            'role_id' => 2
        ]);

        // Mock NotificationService (should not be called)
        $mockNotificationService = Mockery::mock(NotificationService::class);
        $mockNotificationService->shouldNotReceive('createNotification');
        $this->app->instance(NotificationService::class, $mockNotificationService);

        // Act
        $birthdayService = app(BirthdayService::class);
        $birthdayUsers = $birthdayService->getTodaysBirthdayUsers();

        // Assert: User without birth_date is not included
        $this->assertFalse($birthdayUsers->contains($userWithoutBirthDate));
        
        // Process celebrations should not create popup for this user
        $birthdayService->processBirthdayCelebrations();
        
        $this->assertDatabaseMissing('popups', [
            'type' => Popup::TYPE_BIRTHDAY,
            'user_id' => $userWithoutBirthDate->id,
        ]);
    }


} 