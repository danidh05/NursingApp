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
use Illuminate\Support\Facades\Log;

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
        
        // Handle file uploads for Category 2 before mapping to DTO
        if ($categoryId === 2) {
            $data = $this->handleCategory2FileUploads($data);
        }
        
        // Map data to DTO using category-specific handler
        $dto = $handler->mapToDTO($data);
        
        // Create the request
        $request = $this->requestRepository->create($dto, $user);
        
        // Calculate and set total price (only for Category 1: Service Request)
        if ($categoryId === 1 && $dto->service_id) {
            $totalPrice = $this->calculateRequestTotalPrice($request);
            $request->update([
                'total_price' => $totalPrice,
                'discounted_price' => $totalPrice // Initially same as total price
            ]);
        } elseif ($categoryId === 2) {
            // Category 2: Set price from test package or individual test (no area pricing)
            if ($dto->test_package_id) {
                $testPackage = \App\Models\TestPackage::find($dto->test_package_id);
                if ($testPackage) {
                    $request->update([
                        'total_price' => $testPackage->price,
                        'discounted_price' => $testPackage->price,
                    ]);
                }
            } elseif ($dto->test_id) {
                $test = \App\Models\Test::find($dto->test_id);
                if ($test) {
                    $request->update([
                        'total_price' => $test->price,
                        'discounted_price' => $test->price,
                    ]);
                }
            }
        }
        
        // Category-specific post-processing
        $handler->afterCreate($request, $user);
        
        // Dispatch event
        Event::dispatch(new UserRequestedService($request, $user));
        
        // Clear cache after creation
        Cache::forget("user_requests_{$user->id}");
        
        // Reload request with services
        $request = $request->fresh(['services', 'user', 'nurse', 'testPackage', 'test']);
        
        return RequestResponseDTO::fromModel($request);
    }

    /**
     * Handle file uploads for Category 2 requests.
     */
    private function handleCategory2FileUploads(array $data): array
    {
        $imageStorageService = app(\App\Services\ImageStorageService::class);
        
        // Handle request_details_files (multiple files)
        if (isset($data['request_details_files']) && is_array($data['request_details_files'])) {
            $filePaths = [];
            foreach ($data['request_details_files'] as $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $filePaths[] = $imageStorageService->uploadImage($file, 'request-details');
                }
            }
            $data['request_details_files'] = $filePaths;
        }
        
        // Handle attach_front_face
        if (isset($data['attach_front_face']) && $data['attach_front_face'] instanceof \Illuminate\Http\UploadedFile) {
            $data['attach_front_face'] = $imageStorageService->uploadImage($data['attach_front_face'], 'insurance-cards');
        }
        
        // Handle attach_back_face
        if (isset($data['attach_back_face']) && $data['attach_back_face'] instanceof \Illuminate\Http\UploadedFile) {
            $data['attach_back_face'] = $imageStorageService->uploadImage($data['attach_back_face'], 'insurance-cards');
        }
        
        return $data;
    }

    /**
     * Calculate the total price for a request based on its service and request's area.
     * Category 1 only supports a single service per request.
     */
    private function calculateRequestTotalPrice(Request $request): float
    {
        $service = $request->services->first();
        
        if (!$service) {
            throw new \Exception('No service attached to request');
        }
        
        // Use request's area_id if available, otherwise fall back to user's registered area
        $areaId = $request->area_id ?? $request->user->area_id;
        
        if (!$areaId) {
            throw new \Exception('No area specified for pricing calculation');
        }
        
        $serviceAreaPrice = ServiceAreaPrice::where('service_id', $service->id)
                                           ->where('area_id', $areaId)
                                           ->first();

        if (!$serviceAreaPrice) {
            throw new \Exception("No pricing found for service {$service->id} in area {$areaId}");
        }

        return $serviceAreaPrice->price;
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
                Log::info("Auto-updated request {$id} status to 'in_progress' - nurse arrived");
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