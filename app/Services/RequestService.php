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
        $categoryId = (int)($data['category_id'] ?? 1); // Ensure it's an integer
        
        Log::info('=== REQUEST SERVICE: createRequest called ===');
        Log::info('Category ID: ' . $categoryId . ' (type: ' . gettype($categoryId) . ')');
        Log::info('Data keys received: ' . json_encode(array_keys($data)));
        
        // Get category-specific handler
        $handler = CategoryRequestHandlerFactory::getHandler($categoryId);
        
        // Handle file uploads for Category 2 and Category 3 before mapping to DTO
        // IMPORTANT: Files must be UploadedFile instances, not strings
        if ($categoryId == 2) { // Use == instead of === to handle string "2" vs int 2
            Log::info('=== REQUEST SERVICE: Starting file upload processing ===');
            Log::info('Category 2 detected, checking for files...');
            
            // Debug: Log what we receive BEFORE upload
            if (isset($data['request_details_files'])) {
                Log::info('request_details_files BEFORE upload:', [
                    'is_array' => is_array($data['request_details_files']),
                    'count' => is_array($data['request_details_files']) ? count($data['request_details_files']) : 'not array',
                    'first_type' => is_array($data['request_details_files']) && isset($data['request_details_files'][0]) 
                        ? gettype($data['request_details_files'][0]) 
                        : 'N/A',
                ]);
            }
            if (isset($data['attach_front_face'])) {
                Log::info('attach_front_face BEFORE upload:', [
                    'type' => gettype($data['attach_front_face']),
                    'class' => is_object($data['attach_front_face']) ? get_class($data['attach_front_face']) : 'not object',
                    'is_uploaded_file' => $data['attach_front_face'] instanceof \Illuminate\Http\UploadedFile,
                ]);
            }
            if (isset($data['attach_back_face'])) {
                Log::info('attach_back_face BEFORE upload:', [
                    'type' => gettype($data['attach_back_face']),
                    'class' => is_object($data['attach_back_face']) ? get_class($data['attach_back_face']) : 'not object',
                    'is_uploaded_file' => $data['attach_back_face'] instanceof \Illuminate\Http\UploadedFile,
                ]);
            }
            
            $data = $this->handleCategory2FileUploads($data);
            
            // Debug: Log what we have AFTER upload
            Log::info('=== REQUEST SERVICE: After file upload processing ===');
            if (isset($data['request_details_files'])) {
                Log::info('request_details_files AFTER upload:', [
                    'is_array' => is_array($data['request_details_files']),
                    'count' => is_array($data['request_details_files']) ? count($data['request_details_files']) : 'not array',
                    'values' => is_array($data['request_details_files']) ? $data['request_details_files'] : $data['request_details_files'],
                ]);
            }
            if (isset($data['attach_front_face'])) {
                Log::info('attach_front_face AFTER upload: ' . $data['attach_front_face']);
            }
            if (isset($data['attach_back_face'])) {
                Log::info('attach_back_face AFTER upload: ' . $data['attach_back_face']);
            }
        } elseif ($categoryId == 3) { // Category 3: Rays
            Log::info('=== REQUEST SERVICE: Starting Category 3 file upload processing ===');
            $data = $this->handleCategory3FileUploads($data);
        } elseif ($categoryId == 5) { // Category 5: Physiotherapists
            Log::info('=== REQUEST SERVICE: Starting Category 5 file upload processing ===');
            $data = $this->handleCategory5FileUploads($data);
        } elseif ($categoryId == 7) { // Category 7: Duties
            Log::info('=== REQUEST SERVICE: Starting Category 7 file upload processing ===');
            $data = $this->handleCategory7FileUploads($data);
        } elseif ($categoryId == 8) { // Category 8: Doctors
            Log::info('=== REQUEST SERVICE: Starting Category 8 file upload processing ===');
            $data = $this->handleCategory8FileUploads($data);
        }
        
        // Map data to DTO using category-specific handler
        $dto = $handler->mapToDTO($data);
        
        // Extract total_price from data for Category 5, 7, 8 (frontend-calculated)
        $totalPrice = null;
        if (($categoryId === 5 || $categoryId === 7 || $categoryId === 8) && isset($data['total_price'])) {
            $totalPrice = (float)$data['total_price'];
        }
        
        // Create the request
        $request = $this->requestRepository->create($dto, $user, $totalPrice);
        
        // Calculate and set total price (for Category 1: Service Request, Category 3: Rays, and Category 4: Machines)
        if ($categoryId === 1 && $dto->service_id) {
            $totalPrice = $this->calculateRequestTotalPrice($request);
            $request->update([
                'total_price' => $totalPrice,
                'discounted_price' => $totalPrice // Initially same as total price
            ]);
        } elseif ($categoryId === 3 && $dto->ray_id) {
            $totalPrice = $this->calculateCategory3RequestTotalPrice($request);
            $request->update([
                'total_price' => $totalPrice,
                'discounted_price' => $totalPrice // Initially same as total price
            ]);
        } elseif ($categoryId === 4 && $dto->machine_id) {
            $totalPrice = $this->calculateCategory4RequestTotalPrice($request);
            $request->update([
                'total_price' => $totalPrice,
                'discounted_price' => $totalPrice // Initially same as total price
            ]);
        } elseif ($categoryId === 5 && $totalPrice !== null) {
            // Category 5: Use total_price from frontend (already set in create method)
            // No additional update needed
        } elseif ($categoryId === 7) {
            // Category 7: Calculate price if not provided by frontend, otherwise use frontend price
            if ($totalPrice === null) {
                $totalPrice = $this->calculateCategory7RequestTotalPrice($request);
                $request->update([
                    'total_price' => $totalPrice,
                    'discounted_price' => $totalPrice
                ]);
            }
            // If total_price was provided by frontend, it's already set in create method
        } elseif ($categoryId === 8) {
            // Category 8: Calculate if not provided by frontend
            if ($totalPrice === null) {
                $totalPrice = $this->calculateCategory8RequestTotalPrice($request);
                $request->update([
                    'total_price' => $totalPrice,
                    'discounted_price' => $totalPrice
                ]);
            }
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
        Log::info('=== HANDLE FILE UPLOADS: Starting ===');
        $imageStorageService = app(\App\Services\ImageStorageService::class);
        
        // Handle request_details_files (multiple files or single file)
        if (isset($data['request_details_files'])) {
            Log::info('Processing request_details_files, is_array: ' . (is_array($data['request_details_files']) ? 'YES' : 'NO'));
            Log::info('Type: ' . gettype($data['request_details_files']));
            
            $filePaths = [];
            
            // Handle both array and single file cases
            $files = is_array($data['request_details_files']) 
                ? $data['request_details_files'] 
                : [$data['request_details_files']];
            
            foreach ($files as $index => $file) {
                Log::info("Processing request_details_files[$index]");
                Log::info("  - Type: " . gettype($file));
                Log::info("  - Is UploadedFile: " . ($file instanceof \Illuminate\Http\UploadedFile ? 'YES' : 'NO'));
                
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    try {
                        Log::info("  - Original name: " . $file->getClientOriginalName());
                        Log::info("  - Size: " . $file->getSize() . " bytes");
                        $uploadedPath = $imageStorageService->uploadImage($file, 'request-details');
                        Log::info("  - Uploaded to: " . $uploadedPath);
                        $filePaths[] = $uploadedPath;
                    } catch (\Exception $e) {
                        Log::error("  - Failed to upload request_details_files[$index]: " . $e->getMessage());
                        Log::error("  - Exception trace: " . $e->getTraceAsString());
                        // Continue with other files
                    }
                } elseif (is_string($file)) {
                    // Already a path string (shouldn't happen, but handle it)
                    Log::info("  - Already a path string: " . substr($file, 0, 100));
                    $filePaths[] = $file;
                } else {
                    Log::warning("  - request_details_files[$index] is unexpected type: " . gettype($file));
                }
            }
            
            if (!empty($filePaths)) {
                $data['request_details_files'] = $filePaths;
                Log::info('Successfully uploaded ' . count($filePaths) . ' files for request_details_files');
            } else {
                Log::warning('No files were successfully uploaded for request_details_files');
                $data['request_details_files'] = null;
            }
        } else {
            Log::info('request_details_files not set in data');
        }
        
        // Handle attach_front_face
        if (isset($data['attach_front_face'])) {
            Log::info('Processing attach_front_face');
            Log::info('  - Type: ' . gettype($data['attach_front_face']));
            Log::info('  - Is UploadedFile: ' . ($data['attach_front_face'] instanceof \Illuminate\Http\UploadedFile ? 'YES' : 'NO'));
            
            if ($data['attach_front_face'] instanceof \Illuminate\Http\UploadedFile) {
                try {
                    Log::info('  - Original name: ' . $data['attach_front_face']->getClientOriginalName());
                    Log::info('  - Size: ' . $data['attach_front_face']->getSize() . ' bytes');
                    $uploadedPath = $imageStorageService->uploadImage($data['attach_front_face'], 'insurance-cards');
                    Log::info('  - Uploaded to: ' . $uploadedPath);
                    $data['attach_front_face'] = $uploadedPath;
                } catch (\Exception $e) {
                    Log::error('  - Failed to upload attach_front_face: ' . $e->getMessage());
                    Log::error('  - Exception trace: ' . $e->getTraceAsString());
                    $data['attach_front_face'] = null;
                }
            } elseif (is_string($data['attach_front_face'])) {
                Log::warning('  - attach_front_face is a string (temp path?): ' . substr($data['attach_front_face'], 0, 100));
                $data['attach_front_face'] = null;
            } else {
                Log::warning('  - attach_front_face is unexpected type: ' . gettype($data['attach_front_face']));
                $data['attach_front_face'] = null;
            }
        } else {
            Log::info('attach_front_face not set in data');
        }
        
        // Handle attach_back_face
        if (isset($data['attach_back_face'])) {
            Log::info('Processing attach_back_face');
            Log::info('  - Type: ' . gettype($data['attach_back_face']));
            Log::info('  - Is UploadedFile: ' . ($data['attach_back_face'] instanceof \Illuminate\Http\UploadedFile ? 'YES' : 'NO'));
            
            if ($data['attach_back_face'] instanceof \Illuminate\Http\UploadedFile) {
                try {
                    Log::info('  - Original name: ' . $data['attach_back_face']->getClientOriginalName());
                    Log::info('  - Size: ' . $data['attach_back_face']->getSize() . ' bytes');
                    $uploadedPath = $imageStorageService->uploadImage($data['attach_back_face'], 'insurance-cards');
                    Log::info('  - Uploaded to: ' . $uploadedPath);
                    $data['attach_back_face'] = $uploadedPath;
                } catch (\Exception $e) {
                    Log::error('  - Failed to upload attach_back_face: ' . $e->getMessage());
                    Log::error('  - Exception trace: ' . $e->getTraceAsString());
                    $data['attach_back_face'] = null;
                }
            } elseif (is_string($data['attach_back_face'])) {
                Log::warning('  - attach_back_face is a string (temp path?): ' . substr($data['attach_back_face'], 0, 100));
                $data['attach_back_face'] = null;
            } else {
                Log::warning('  - attach_back_face is unexpected type: ' . gettype($data['attach_back_face']));
                $data['attach_back_face'] = null;
            }
        } else {
            Log::info('attach_back_face not set in data');
        }
        
        Log::info('=== HANDLE FILE UPLOADS: Finished ===');
        return $data;
    }

    /**
     * Handle file uploads for Category 3: Rays
     * Only handles request_details_files (PDF files)
     */
    private function handleCategory3FileUploads(array $data): array
    {
        Log::info('=== HANDLE CATEGORY 3 FILE UPLOADS: Starting ===');
        $imageStorageService = app(\App\Services\ImageStorageService::class);
        
        // Handle request_details_files (PDF files)
        if (isset($data['request_details_files'])) {
            Log::info('Processing request_details_files for Category 3, is_array: ' . (is_array($data['request_details_files']) ? 'YES' : 'NO'));
            Log::info('Type: ' . gettype($data['request_details_files']));
            
            $filePaths = [];
            
            // Handle both array and single file cases
            $files = is_array($data['request_details_files']) 
                ? $data['request_details_files'] 
                : [$data['request_details_files']];
            
            foreach ($files as $index => $file) {
                Log::info("Processing request_details_files[$index]");
                Log::info("  - Type: " . gettype($file));
                Log::info("  - Is UploadedFile: " . ($file instanceof \Illuminate\Http\UploadedFile ? 'YES' : 'NO'));
                
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    try {
                        Log::info("  - Original name: " . $file->getClientOriginalName());
                        Log::info("  - Size: " . $file->getSize() . " bytes");
                        $uploadedPath = $imageStorageService->uploadImage($file, 'rays');
                        Log::info("  - Uploaded to: " . $uploadedPath);
                        $filePaths[] = $uploadedPath;
                    } catch (\Exception $e) {
                        Log::error("  - Failed to upload request_details_files[$index]: " . $e->getMessage());
                        Log::error("  - Exception trace: " . $e->getTraceAsString());
                        // Continue with other files
                    }
                } elseif (is_string($file)) {
                    // Already a path string (shouldn't happen, but handle it)
                    Log::info("  - Already a path string: " . substr($file, 0, 100));
                    $filePaths[] = $file;
                } else {
                    Log::warning("  - request_details_files[$index] is unexpected type: " . gettype($file));
                }
            }
            
            if (!empty($filePaths)) {
                $data['request_details_files'] = $filePaths;
                Log::info('Successfully uploaded ' . count($filePaths) . ' files for request_details_files');
            } else {
                Log::warning('No files were successfully uploaded for request_details_files');
                $data['request_details_files'] = null;
            }
        } else {
            Log::info('request_details_files not set in data for Category 3');
        }
        
        Log::info('=== HANDLE CATEGORY 3 FILE UPLOADS: Finished ===');
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

    /**
     * Calculate the total price for a Category 3 (Rays) request based on ray and area.
     * Falls back to ray's base price if no area-specific pricing is found.
     *
     * @param Request $request
     * @return float
     * @throws \Exception
     */
    private function calculateCategory3RequestTotalPrice(Request $request): float
    {
        $ray = $request->ray;
        
        if (!$ray) {
            throw new \Exception('No ray attached to request');
        }
        
        // Use request's area_id if available, otherwise fall back to user's registered area
        $areaId = $request->area_id ?? $request->user->area_id;
        
        // If no area is specified, use base price
        if (!$areaId) {
            return $ray->price;
        }
        
        // Try to find area-specific pricing
        $rayAreaPrice = \App\Models\RayAreaPrice::where('ray_id', $ray->id)
                                               ->where('area_id', $areaId)
                                               ->first();

        // If no area-specific pricing found, fallback to base price
        if (!$rayAreaPrice) {
            return $ray->price;
        }

        return $rayAreaPrice->price;
    }

    /**
     * Calculate the total price for a Category 4 (Machines) request based on machine and area.
     * Falls back to machine's base price if no area-specific pricing is found.
     *
     * @param Request $request
     * @return float
     * @throws \Exception
     */
    private function calculateCategory4RequestTotalPrice(Request $request): float
    {
        $machine = $request->machine;
        
        if (!$machine) {
            throw new \Exception('No machine attached to request');
        }
        
        // Use request's area_id if available, otherwise fall back to user's registered area
        $areaId = $request->area_id ?? $request->user->area_id;
        
        // If no area is specified, use base price
        if (!$areaId) {
            return $machine->price;
        }
        
        // Try to find area-specific pricing
        $machineAreaPrice = \App\Models\MachineAreaPrice::where('machine_id', $machine->id)
                                               ->where('area_id', $areaId)
                                               ->first();

        // If no area-specific pricing found, fallback to base price
        if (!$machineAreaPrice) {
            return $machine->price;
        }

        return $machineAreaPrice->price;
    }

    /**
     * Handle file uploads for Category 5 (Physiotherapists).
     * Uploads request_details (PDF file).
     *
     * @param array $data
     * @return array
     */
    private function handleCategory5FileUploads(array $data): array
    {
        Log::info('=== HANDLE CATEGORY 5 FILE UPLOADS: Starting ===');
        $imageStorageService = app(\App\Services\ImageStorageService::class);
        
        // Handle request_details (single PDF file)
        if (isset($data['request_details']) && $data['request_details'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['request_details'];
            
            Log::info('Processing request_details for Category 5');
            Log::info('  - Type: ' . gettype($file));
            Log::info('  - Is UploadedFile: ' . ($file instanceof \Illuminate\Http\UploadedFile ? 'YES' : 'NO'));
            Log::info('  - MIME Type: ' . $file->getMimeType());
            Log::info('  - Original Name: ' . $file->getClientOriginalName());
            
            // Validate it's a PDF
            if ($file->getMimeType() !== 'application/pdf') {
                throw new \Exception('request_details must be a PDF file.');
            }
            
            // Upload the file using ImageStorageService (handles PDFs too)
            $filePath = $imageStorageService->uploadImage($file, 'physiotherapists/requests');
            Log::info('  - Uploaded to: ' . $filePath);
            
            // Replace UploadedFile with file path (as array for consistency with request_details_files)
            $data['request_details_files'] = [$filePath];
            unset($data['request_details']);
            
            Log::info('=== HANDLE CATEGORY 5 FILE UPLOADS: Completed ===');
        } else {
            Log::info('No request_details file found for Category 5');
        }
        
        return $data;
    }

    /**
     * Handle file uploads for Category 7: Duties
     * Uploads request_details (single PDF) and request_details_files (multiple PDFs)
     */
    private function handleCategory7FileUploads(array $data): array
    {
        Log::info('=== HANDLE CATEGORY 7 FILE UPLOADS: Starting ===');
        $imageStorageService = app(\App\Services\ImageStorageService::class);
        
        $storedPaths = [];

        // Handle request_details (single PDF file)
        if (isset($data['request_details']) && $data['request_details'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['request_details'];

            Log::info('Processing request_details for Category 7 (single)');
            if ($file->getMimeType() !== 'application/pdf') {
                throw new \Exception('request_details must be a PDF file.');
            }

            $filePath = $imageStorageService->uploadImage($file, 'duties/requests');
            Log::info('  - Uploaded single request_details to: ' . $filePath);
            $storedPaths[] = $filePath;
            unset($data['request_details']);
        }

        // Handle request_details_files (multiple PDFs)
        if (isset($data['request_details_files'])) {
            $files = $data['request_details_files'];
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $idx => $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile)) {
                    Log::warning("request_details_files[$idx] is not UploadedFile, type: " . gettype($file));
                    continue;
                }
                if ($file->getMimeType() !== 'application/pdf') {
                    throw new \Exception('request_details_files items must be PDF files.');
                }
                $filePath = $imageStorageService->uploadImage($file, 'duties/requests');
                Log::info("  - Uploaded request_details_files[$idx] to: " . $filePath);
                $storedPaths[] = $filePath;
            }
        }

        if (!empty($storedPaths)) {
            $data['request_details_files'] = array_values($storedPaths);
        }

        Log::info('=== HANDLE CATEGORY 7 FILE UPLOADS: Completed ===');
        
        return $data;
    }

    /**
     * Handle file uploads for Category 8: Doctors (request_details_files)
     */
    private function handleCategory8FileUploads(array $data): array
    {
        Log::info('=== HANDLE CATEGORY 8 FILE UPLOADS: Starting ===');
        $imageStorageService = app(\App\Services\ImageStorageService::class);

        $storedPaths = [];

        if (isset($data['request_details_files'])) {
            $files = $data['request_details_files'];
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $idx => $file) {
                if (!($file instanceof \Illuminate\Http\UploadedFile)) {
                    Log::warning("Category8 request_details_files[$idx] is not UploadedFile, type: " . gettype($file));
                    continue;
                }
                $filePath = $imageStorageService->uploadImage($file, 'doctors/requests');
                Log::info("  - Uploaded request_details_files[$idx] to: " . $filePath);
                $storedPaths[] = $filePath;
            }
        }

        if (!empty($storedPaths)) {
            $data['request_details_files'] = array_values($storedPaths);
        }

        Log::info('=== HANDLE CATEGORY 8 FILE UPLOADS: Completed ===');
        return $data;
    }

    /**
     * Calculate total price for Category 8 based on doctor area price or fallback.
     */
    private function calculateCategory8RequestTotalPrice(Request $request): float
    {
        $doctor = \App\Models\Doctor::with('areaPrices')->find($request->doctor_id);
        if (!$doctor) {
            throw new \Exception("Doctor not found for request {$request->id}");
        }

        $areaId = $request->area_id;
        $price = $doctor->price;
        if ($areaId) {
            $areaPrice = $doctor->areaPrices()->where('area_id', $areaId)->first();
            if ($areaPrice) {
                $price = $areaPrice->price;
            }
        }

        return (float) $price;
    }

    /**
     * Calculate total price for Category 7: Duties requests
     * This is optional - frontend can send total_price directly
     */
    private function calculateCategory7RequestTotalPrice(Request $request): float
    {
        $areaId = $request->area_id;
        
        // Nurse Visits subcategory
        if ($request->nurse_visit_id) {
            $nurseVisit = \App\Models\NurseVisit::find($request->nurse_visit_id);
            if (!$nurseVisit) {
                throw new \Exception("Nurse visit not found: {$request->nurse_visit_id}");
            }
            
            $pricePerDay = $nurseVisit->getPriceForVisits($request->visits_per_day ?? 1, $areaId);
            $fromDate = \Carbon\Carbon::parse($request->from_date);
            $toDate = \Carbon\Carbon::parse($request->to_date);
            $numberOfDays = $fromDate->diffInDays($toDate) + 1;
            
            return $pricePerDay * $numberOfDays;
        }
        
        // Duties subcategory
        if ($request->duty_id) {
            $duty = \App\Models\Duty::find($request->duty_id);
            if (!$duty) {
                throw new \Exception("Duty not found: {$request->duty_id}");
            }
            
            // Continuous care (1 month fixed price)
            if ($request->is_continuous_care) {
                return $duty->getContinuousCarePrice($areaId);
            }
            
            // Duration-based pricing
            $durationHours = $request->duration_hours;
            if (!$durationHours) {
                throw new \Exception("duration_hours is required for duty requests (unless is_continuous_care is true)");
            }
            
            $pricePerDay = $duty->getPriceForDuration($durationHours, $request->is_day_shift ?? true, $areaId);
            $fromDate = \Carbon\Carbon::parse($request->from_date);
            $toDate = \Carbon\Carbon::parse($request->to_date);
            $numberOfDays = $fromDate->diffInDays($toDate) + 1;
            
            return $pricePerDay * $numberOfDays;
        }
        
        // Babysitter subcategory
        if ($request->babysitter_id) {
            $babysitter = \App\Models\Babysitter::find($request->babysitter_id);
            if (!$babysitter) {
                throw new \Exception("Babysitter not found: {$request->babysitter_id}");
            }
            
            $durationHours = $request->duration_hours;
            if (!$durationHours || !in_array($durationHours, [12, 24])) {
                throw new \Exception("duration_hours must be 12 or 24 for babysitter requests");
            }
            
            $pricePerDay = $babysitter->getPriceForDuration($durationHours, $request->is_day_shift ?? true, $areaId);
            $fromDate = \Carbon\Carbon::parse($request->from_date);
            $toDate = \Carbon\Carbon::parse($request->to_date);
            $numberOfDays = $fromDate->diffInDays($toDate) + 1;
            
            return $pricePerDay * $numberOfDays;
        }
        
        throw new \Exception("Category 7 request must have one of: nurse_visit_id, duty_id, or babysitter_id");
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