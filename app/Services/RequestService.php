<?php

namespace App\Services;

use App\DTOs\Request\CreateRequestDTO;
use App\DTOs\Request\RequestResponseDTO;
use App\DTOs\Request\UpdateRequestDTO;
use App\Events\UserRequestedService;
use App\Repositories\Interfaces\IRequestRepository;
use App\Services\Interfaces\IRequestService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestService implements IRequestService
{
    public function __construct(
        private readonly IRequestRepository $repository,
        private readonly NotificationService $notificationService
    ) {}

    public function createRequest(CreateRequestDTO $dto, int $userId): RequestResponseDTO
    {
        try {
            DB::beginTransaction();

            $request = $this->repository->create([
                ...$dto->toArray(),
                'user_id' => $userId,
                'status' => 'pending'
            ]);

            // Dispatch event for notifications
            event(new UserRequestedService($request));

            DB::commit();

            return RequestResponseDTO::fromModel($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create request', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateRequest(int $requestId, UpdateRequestDTO $dto): RequestResponseDTO
    {
        try {
            DB::beginTransaction();

            $request = $this->repository->findOrFail($requestId);
            $data = $dto->toArray();

            // Handle time_needed_to_arrive caching if provided
            if (isset($data['time_needed_to_arrive'])) {
                $cacheKey = 'time_needed_to_arrive_' . $requestId;
                $cacheValue = [
                    'time_needed' => $data['time_needed_to_arrive'],
                    'start_time' => now(),
                ];
                Cache::put($cacheKey, $cacheValue, 3600);
                unset($data['time_needed_to_arrive']);
            }

            $request = $this->repository->update($request, $data);

            DB::commit();

            return RequestResponseDTO::fromModel($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update request', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteRequest(int $requestId): bool
    {
        try {
            DB::beginTransaction();

            $request = $this->repository->findOrFail($requestId);
            $result = $this->repository->delete($request);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete request', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getRequest(int $requestId): RequestResponseDTO
    {
        $request = $this->repository->findOrFail($requestId);
        
        // Get cached time_needed_to_arrive if exists
        $cacheKey = 'time_needed_to_arrive_' . $requestId;
        $cachedData = Cache::get($cacheKey);
        
        if ($cachedData) {
            $timeNeededToArrive = $cachedData['time_needed'];
            $startTime = $cachedData['start_time'];
            
            // Calculate the elapsed time in minutes since the cache was created
            $elapsedTime = now()->diffInMinutes($startTime);
            
            // Calculate the remaining time (initial time - elapsed time)
            $remainingTime = max($timeNeededToArrive - $elapsedTime, 0);
            
            // Add to response data
            $request->time_needed_to_arrive = $remainingTime;
        }

        return RequestResponseDTO::fromModel($request);
    }

    public function getAllRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getAll($filters, $perPage)
            ->through(fn($request) => RequestResponseDTO::fromModel($request));
    }

    public function getUserRequests(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getAllByUser($userId, $filters, $perPage)
            ->through(fn($request) => RequestResponseDTO::fromModel($request));
    }
} 