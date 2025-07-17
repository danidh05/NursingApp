<?php

namespace App\Services;

use App\DTOs\Request\CreateRequestDTO;
use App\DTOs\Request\UpdateRequestDTO;
use App\DTOs\Request\RequestResponseDTO;
use App\Events\UserRequestedService;
use App\Events\AdminUpdatedRequest;
use App\Models\Request;
use App\Models\User;
use App\Repositories\Interfaces\IRequestRepository;
use App\Services\Interfaces\IRequestService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class RequestService implements IRequestService
{
    public function __construct(
        private IRequestRepository $requestRepository
    ) {}

    public function createRequest(array $data, User $user): RequestResponseDTO
    {
        // Remove any manual transaction management - let Laravel handle it
        $dto = CreateRequestDTO::fromArray($data);
        
        $request = $this->requestRepository->create($dto, $user);
        
        // Dispatch event
        Event::dispatch(new UserRequestedService($request, $user));
        
        // Clear cache after creation
        Cache::forget("user_requests_{$user->id}");
        
        return RequestResponseDTO::fromModel($request);
    }

    public function updateRequest(int $id, array $data, User $user): RequestResponseDTO
    {
        $dto = UpdateRequestDTO::fromArray($data);
        // Fetch the request and capture original status BEFORE update
        $request = $this->requestRepository->findById($id, $user);
        $originalStatus = $request->status;

        $request = $this->requestRepository->update($id, $dto, $user);

        // Dispatch event if status changed
        if (isset($data['status']) && $data['status'] !== $originalStatus) {
            Event::dispatch(new AdminUpdatedRequest($request, $user, $data['status']));
        }
        // Cache time_needed_to_arrive if provided
        if (isset($data['time_needed_to_arrive'])) {
            $cacheKey = 'time_needed_to_arrive_' . $request->id;
            Cache::put($cacheKey, [
                'time_needed' => $data['time_needed_to_arrive'],
                'start_time' => now()
            ], 3600);
        }
        // Clear cache after update
        Cache::forget("user_requests_{$user->id}");
        return RequestResponseDTO::fromModel($request);
    }

    public function getRequest(int $id, User $user): RequestResponseDTO
    {
        $request = $this->requestRepository->findById($id, $user);
        return RequestResponseDTO::fromModel($request);
    }

    public function getAllRequests(User $user): array
    {
        $cacheKey = "user_requests_{$user->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user) {
            $requests = $this->requestRepository->getAll($user);
            return $requests->map(fn($request) => RequestResponseDTO::fromModel($request))->toArray();
        });
    }

    public function softDeleteRequest(int $id, User $user): void
    {
        // Remove any manual transaction management
        $this->requestRepository->softDelete($id, $user);
        
        // Clear cache after deletion
        Cache::forget("user_requests_{$user->id}");
    }
} 