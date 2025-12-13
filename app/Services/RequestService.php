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
use App\Services\CategoryHandlers\CategoryRequestHandlerFactory;
use App\Models\ServiceAreaPrice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class RequestService implements IRequestService
{
    public function __construct(
        private IRequestRepository $requestRepository
    ) {}

    public function createRequest(array $data, User $user): RequestResponseDTO
    {
        // Get category_id (defaults to 1: Service Request)
        $categoryId = $data['category_id'] ?? 1;
        
        // Get category-specific handler
        $handler = CategoryRequestHandlerFactory::getHandler($categoryId);
        
        // Map data to DTO using category-specific handler
        $dto = $handler->mapToDTO($data);
        
        // Create the request
        $request = $this->requestRepository->create($dto, $user);
        
        // Calculate and set total price (only for categories that use services)
        if ($categoryId === 1 && !empty($dto->service_ids)) {
            $totalPrice = $this->calculateRequestTotalPrice($request);
            $request->update([
                'total_price' => $totalPrice,
                'discounted_price' => $totalPrice // Initially same as total price
            ]);
        }
        
        // Category-specific post-processing
        $handler->afterCreate($request, $user);
        
        // Dispatch event
        Event::dispatch(new UserRequestedService($request, $user));
        
        // Clear cache after creation
        Cache::forget("user_requests_{$user->id}");
        
        // Reload request with services
        $request = $request->fresh(['services', 'user', 'nurse']);
        
        return RequestResponseDTO::fromModel($request);
    }

    /**
     * Calculate the total price for a request based on its services and request's area.
     */
    private function calculateRequestTotalPrice(Request $request): float
    {
        $serviceIds = $request->services->pluck('id')->toArray();
        
        // Use request's area_id if available, otherwise fall back to user's registered area
        $areaId = $request->area_id ?? $request->user->area_id;
        
        if (!$areaId) {
            throw new \Exception('No area specified for pricing calculation');
        }
        
        $serviceAreaPrices = ServiceAreaPrice::whereIn('service_id', $serviceIds)
                                           ->where('area_id', $areaId)
                                           ->get();

        $totalPrice = 0;
        foreach ($serviceIds as $serviceId) {
            $price = $serviceAreaPrices->where('service_id', $serviceId)->first();
            if ($price) {
                $totalPrice += $price->price;
            }
        }

        return $totalPrice;
    }

    public function updateRequest(int $id, array $data, User $user): RequestResponseDTO
    {
        $dto = UpdateRequestDTO::fromArray($data);
        // Fetch the request and capture original status BEFORE update
        $request = $this->requestRepository->findById($id, $user);
        $originalStatus = $request->status;

        $request = $this->requestRepository->update($id, $dto, $user);

        // Auto-update status to 'in_progress' if nurse arrived (time_needed_to_arrive = 0) and status is 'assigned'
        if (isset($data['time_needed_to_arrive']) && $data['time_needed_to_arrive'] === 0) {
            if ($request->status === Request::STATUS_ASSIGNED) {
                $request->update(['status' => Request::STATUS_IN_PROGRESS]);
                \Log::info("Auto-updated request {$id} status to 'in_progress' - nurse arrived");
            }
        }

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