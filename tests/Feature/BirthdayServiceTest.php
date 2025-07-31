<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\BirthdayService;
use App\Services\PopupService;
use App\Services\NotificationService;
use App\Services\OneSignalService;
use App\Models\User;
use App\Models\Popup;
use App\Events\UserBirthday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Carbon\Carbon;

class BirthdayServiceTest extends TestCase
{
    use RefreshDatabase;

    private BirthdayService $birthdayService;
    private $mockPopupService;
    private $mockNotificationService;
    private $mockOneSignalService;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure queued listeners run synchronously in tests
        $this->app['config']->set('queue.default', 'sync');

        // Mock the services
        $this->mockPopupService = Mockery::mock(PopupService::class);
        $this->mockOneSignalService = Mockery::mock(OneSignalService::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);

        // Create real NotificationService with mocked OneSignalService
        $notificationService = new NotificationService($this->mockOneSignalService);

        // Bind the mocked NotificationService to the container so listeners can use it
        $this->app->instance(NotificationService::class, $notificationService);

        $this->birthdayService = new BirthdayService(
            $this->mockPopupService,
            $notificationService
        );

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
    }

    /** @test */
    public function it_can_find_users_with_birthdays_today()
    {
        // Arrange: Create users with different birth dates
        $today = now();
        
        // User with birthday today (different year)
        $birthdayUserToday = User::factory()->create([
            'birth_date' => $today->copy()->subYears(25)->format('Y-m-d'),
            'role_id' => 2
        ]);

        // User with birthday tomorrow
        $birthdayUserTomorrow = User::factory()->create([
            'birth_date' => $today->copy()->addDay()->subYears(30)->format('Y-m-d'),
            'role_id' => 2
        ]);

        // User with birthday yesterday
        $birthdayUserYesterday = User::factory()->create([
            'birth_date' => $today->copy()->subDay()->subYears(22)->format('Y-m-d'),
            'role_id' => 2
        ]);

        // User with no birthday set
        $userNoBirthday = User::factory()->create([
            'birth_date' => null,
            'role_id' => 2
        ]);

        // Act
        $birthdayUsers = $this->birthdayService->getTodaysBirthdayUsers();

        // Assert
        $this->assertCount(1, $birthdayUsers);
        $this->assertTrue($birthdayUsers->contains($birthdayUserToday));
        $this->assertFalse($birthdayUsers->contains($birthdayUserTomorrow));
        $this->assertFalse($birthdayUsers->contains($birthdayUserYesterday));
        $this->assertFalse($birthdayUsers->contains($userNoBirthday));
    }

    /** @test */
    public function it_returns_empty_collection_when_no_birthdays_today()
    {
        // Arrange: Create users with birthdays not today
        User::factory()->count(3)->create([
            'birth_date' => now()->addDays(5)->format('Y-m-d'),
            'role_id' => 2
        ]);

        // Act
        $birthdayUsers = $this->birthdayService->getTodaysBirthdayUsers();

        // Assert
        $this->assertCount(0, $birthdayUsers);
    }

    /** @test */
    public function it_finds_birthday_users_across_different_years()
    {
        // Arrange: Create users born in different years but same month/day as today
        $today = now();
        
        $user1990 = User::factory()->create([
            'birth_date' => Carbon::create(1990, $today->month, $today->day)->format('Y-m-d'),
            'role_id' => 2
        ]);

        $user2000 = User::factory()->create([
            'birth_date' => Carbon::create(2000, $today->month, $today->day)->format('Y-m-d'),
            'role_id' => 2
        ]);

        $user2010 = User::factory()->create([
            'birth_date' => Carbon::create(2010, $today->month, $today->day)->format('Y-m-d'),
            'role_id' => 2
        ]);

        // Act
        $birthdayUsers = $this->birthdayService->getTodaysBirthdayUsers();

        // Assert
        $this->assertCount(3, $birthdayUsers);
        $this->assertTrue($birthdayUsers->contains($user1990));
        $this->assertTrue($birthdayUsers->contains($user2000));
        $this->assertTrue($birthdayUsers->contains($user2010));
    }

    /** @test */
    public function it_processes_birthday_celebrations_for_users()
    {
        // Arrange
        $birthdayUser = User::factory()->create([
            'birth_date' => now()->format('Y-m-d'),
            'role_id' => 2
        ]);

        $popup = new Popup([
            'id' => 1,
            'title' => '🎉 Happy Birthday!',
            'content' => "Happy Birthday {$birthdayUser->name}! 🎂",
            'type' => Popup::TYPE_BIRTHDAY,
            'is_active' => true,
        ]);

        // Mock expectations
        $this->mockPopupService
            ->shouldReceive('createPopup')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($popup);

        // Mock OneSignal service
        $this->mockOneSignalService
            ->shouldReceive('sendToUser')
            ->once()
            ->andReturn(true);

        // Act
        $this->birthdayService->processBirthdayCelebrations();

        // Assert - Mockery will verify the expectations
        $this->assertTrue(true); // If we get here, the mocks were called correctly
    }

    /** @test */
    public function it_handles_multiple_birthday_users_on_same_day()
    {
        // Arrange
        $user1 = User::factory()->create([
            'birth_date' => now()->format('Y-m-d'),
            'role_id' => 2
        ]);

        $user2 = User::factory()->create([
            'birth_date' => now()->format('Y-m-d'),
            'role_id' => 2
        ]);

        $popup = new Popup([
            'id' => 1,
            'title' => '🎉 Happy Birthday!',
            'type' => Popup::TYPE_BIRTHDAY,
        ]);

        // Mock expectations - should be called twice (once per user)
        $this->mockPopupService
            ->shouldReceive('createPopup')
            ->twice()
            ->andReturn($popup);

        // Mock OneSignal service - should be called twice (once per user)
        $this->mockOneSignalService
            ->shouldReceive('sendToUser')
            ->twice()
            ->andReturn(true);

        // Act
        $this->birthdayService->processBirthdayCelebrations();

        // Assert - Mockery will verify the expectations
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_leap_year_birthdays_correctly()
    {
        // Set current date to Feb 29 on a leap year
        Carbon::setTestNow(Carbon::create(2024, 2, 29)); // 2024 is a leap year

        // Arrange: User born on Feb 29 in a previous leap year
        $leapYearUser = User::factory()->create([
            'birth_date' => Carbon::create(2020, 2, 29)->format('Y-m-d'), // 2020 was a leap year
            'role_id' => 2
        ]);

        // Act
        $birthdayUsers = $this->birthdayService->getTodaysBirthdayUsers();

        // Assert
        $this->assertCount(1, $birthdayUsers);
        $this->assertTrue($birthdayUsers->contains($leapYearUser));

        // Clean up
        Carbon::setTestNow();
    }

    /** @test */
    public function it_dispatches_birthday_event()
    {
        // Arrange
        $birthdayUser = User::factory()->create([
            'birth_date' => now()->format('Y-m-d'),
            'role_id' => 2
        ]);

        $popup = new Popup([
            'id' => 1,
            'title' => '🎉 Happy Birthday!',
            'content' => "Happy Birthday {$birthdayUser->name}! 🎂",
            'type' => Popup::TYPE_BIRTHDAY,
            'is_active' => true,
        ]);

        // Mock PopupService
        $this->mockPopupService
            ->shouldReceive('createPopup')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($popup);

        // Mock OneSignal service
        $this->mockOneSignalService
            ->shouldReceive('sendToUser')
            ->once()
            ->andReturn(true);

        // Act
        $this->birthdayService->processBirthdayCelebrations();

        // Assert - Check that the notification was created in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $birthdayUser->id,
            'title' => '🎉 Happy Birthday!',
            'message' => 'Wishing you a wonderful birthday filled with joy and happiness!',
            'type' => 'birthday',
        ]);
    }
} 