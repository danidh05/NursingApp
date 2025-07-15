<?php

namespace Tests\Feature;

use App\DTOs\Request\CreateRequestDTO;
use App\DTOs\Request\UpdateRequestDTO;
use App\Events\UserRequestedService;
use App\Models\Request;
use App\Models\Service;
use App\Models\User;
use App\Repositories\RequestRepository;
use App\Services\NotificationService;
use App\Services\RequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private RequestService $service;
    private User $user;
    private array $services;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role_id' => 2]);
        $this->services = Service::factory(2)->create()->pluck('id')->toArray();
        
        $this->service = new RequestService(
            new RequestRepository(),
            $this->app->make(NotificationService::class)
        );
    }

    public function test_can_create_request(): void
    {
        Event::fake();

        $dto = new CreateRequestDTO(
            full_name: 'John Doe',
            phone_number: '1234567890',
            location: 'Test Location',
            time_type: Request::TIME_TYPE_FULL,
            nurse_gender: 'female',
            service_ids: $this->services,
            problem_description: 'Test problem',
            scheduled_time: now()->addDay(),
            ending_time: now()->addDays(2)
        );

        $response = $this->service->createRequest($dto, $this->user->id);

        $this->assertDatabaseHas('requests', [
            'full_name' => 'John Doe',
            'user_id' => $this->user->id,
            'status' => Request::STATUS_PENDING
        ]);

        Event::assertDispatched(UserRequestedService::class);
    }

    public function test_can_update_request(): void
    {
        $request = Request::factory()
            ->for($this->user)
            ->create(['status' => Request::STATUS_PENDING]);

        $dto = new UpdateRequestDTO(
            full_name: 'Updated Name',
            problem_description: 'Updated Description'
        );

        $response = $this->service->updateRequest($request->id, $dto);

        $this->assertEquals('Updated Name', $response->full_name);
        $this->assertEquals('Updated Description', $response->problem_description);
    }

    public function test_can_update_request_with_time_needed(): void
    {
        $request = Request::factory()->create(['status' => Request::STATUS_PENDING]);

        $dto = new UpdateRequestDTO(
            status: Request::STATUS_APPROVED,
            time_needed_to_arrive: 30
        );

        $response = $this->service->updateRequest($request->id, $dto);

        $this->assertEquals(Request::STATUS_APPROVED, $response->status);

        // Verify cache
        $cacheKey = 'time_needed_to_arrive_' . $request->id;
        $cachedData = Cache::get($cacheKey);
        $this->assertNotNull($cachedData);
        $this->assertEquals(30, $cachedData['time_needed']);
    }

    public function test_can_get_request_with_time_needed(): void
    {
        $request = Request::factory()->create();
        
        // Set cache data
        $cacheKey = 'time_needed_to_arrive_' . $request->id;
        Cache::put($cacheKey, [
            'time_needed' => 30,
            'start_time' => now()->subMinutes(10)
        ], 3600);

        $response = $this->service->getRequest($request->id);

        $this->assertEquals(20, $response->time_needed_to_arrive);
    }

    public function test_can_get_all_requests(): void
    {
        Request::factory(3)->create();

        $response = $this->service->getAllRequests();

        $this->assertEquals(3, $response->total());
    }

    public function test_can_get_filtered_requests(): void
    {
        Request::factory(2)->create(['status' => Request::STATUS_PENDING]);
        Request::factory()->create(['status' => Request::STATUS_APPROVED]);

        $response = $this->service->getAllRequests(['status' => Request::STATUS_PENDING]);

        $this->assertEquals(2, $response->total());
    }

    public function test_can_get_user_requests(): void
    {
        Request::factory(2)->for($this->user)->create();
        Request::factory()->create(); // Another user's request

        $response = $this->service->getUserRequests($this->user->id);

        $this->assertEquals(2, $response->total());
    }

    public function test_can_soft_delete_request(): void
    {
        $request = Request::factory()->create();

        $result = $this->service->deleteRequest($request->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('requests', ['id' => $request->id]);
    }

    public function test_user_can_view_soft_deleted_own_request(): void
    {
        $request = Request::factory()
            ->for($this->user)
            ->create();

        // Soft delete the request
        $this->service->deleteRequest($request->id);

        // User should still be able to get it
        $response = $this->service->getUserRequests($this->user->id);

        $this->assertEquals(1, $response->total());
    }
} 